<?php


namespace DpdPickup\Listener;


use DpdPickup\DpdPickup;
use OpenApi\Model\Api\DeliveryModuleOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Delivery\PickupLocationEvent;
use Thelia\Core\Event\TheliaEvents;
use OpenApi\Events\DeliveryModuleOptionEvent;
use OpenApi\Events\OpenApiEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;
use Thelia\Model\CountryArea;
use Thelia\Model\ModuleQuery;
use Thelia\Model\PickupLocation;
use Thelia\Model\PickupLocationAddress;
use Thelia\Module\Exception\DeliveryException;

class APIListener implements EventSubscriberInterface
{
    /** @var ContainerInterface  */
    protected $container;

    /**
     * APIListener constructor.
     * @param ContainerInterface $container We need the container because we use a service from another module
     * which is not mandatory, and using its service without it being installed will crash
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Calls the Chronopost API and returns a response containing the informations of the relay points found
     *
     * @param PickupLocationEvent $pickupLocationEvent
     * @return mixed
     * @throws \ErrorException
     */
    protected function callWebService(PickupLocationEvent $pickupLocationEvent)
    {
        $excludeZipCodes = DpdPickup::getConfigExcludeZipCode();

        $address = $pickupLocationEvent->getAddress();
        $zipCode = $pickupLocationEvent->getZipCode();
        $city = $pickupLocationEvent->getCity();
        $date = date('d/m/Y');

        try {
            $getPudoSoap = new \SoapClient(__DIR__ . "/../Config/exapaq.wsdl", array('soap_version' => SOAP_1_2));

            if (in_array($zipCode, $excludeZipCodes)) {
                return null;
            }

            $responses = $getPudoSoap->GetPudoList(
                array(
                    "address"    => str_replace(" ", "%", $address),
                    "zipCode"    => $zipCode,
                    "city"       => str_replace(" ", "%", $city),
                    "request_id" => "1234",
                    "date_from"  => $date
                )
            );

        } catch (\SoapFault $e) {
            Tlog::getInstance()->error(
                sprintf(
                    "[%s %s - SOAP Error %d]: %s",
                    $date,
                    date("H:i:s"),
                    (int) $e->getCode(),
                    (string) $e->getMessage()
                )
            );

            return null;
        }

        $xml = new \SimpleXMLElement($responses->GetPudoListResult->any);
        if (isset($xml->ERROR)) {
            throw new \ErrorException("Error while choosing pick-up & go store: " . $xml->ERROR);
        }

        return $xml->PUDO_ITEMS;
    }

    /**
     * Creates and returns a new location address
     *
     * @param $response
     * @return PickupLocationAddress
     */
    protected function createPickupLocationAddressFromResponse($response)
    {
        /** We create the new location address */
        $pickupLocationAddress = new PickupLocationAddress();

        /** We set the differents properties of the location address */
        $pickupLocationAddress
            ->setId((string)$response->PUDO_ID)
            ->setTitle((string)$response->NAME)
            ->setAddress1((string)$response->ADDRESS1)
            ->setAddress2((string)$response->ADDRESS2)
            ->setAddress3((string)$response->ADDRESS3)
            ->setCity((string)$response->CITY)
            ->setZipCode((string)$response->ZIPCODE)
            ->setPhoneNumber('')
            ->setCellphoneNumber('')
            ->setCompany('')
            ->setCountryCode('FR') /** DPD Pickup only delivers in France as of 23/06/2020 */
            ->setFirstName('')
            ->setLastName('')
            ->setIsDefault(0)
            ->setLabel('')
            ->setAdditionalData([])
        ;

        return $pickupLocationAddress;
    }

    /**
     * Creates then returns a location from a response of the WebService
     *
     * @param $response
     * @return PickupLocation
     * @throws \Exception
     */
    protected function createPickupLocationFromResponse($response)
    {
        /** We create the new location */
        $pickupLocation = new PickupLocation();

        /** We set the differents properties of the location */
        $pickupLocation
            ->setId((string)$response->PUDO_ID)
            ->setTitle((string)$response->NAME)
            ->setAddress($this->createPickupLocationAddressFromResponse($response))
            ->setLatitude(str_replace(',', '.', (string)$response->LATITUDE))
            ->setLongitude(str_replace(',', '.', (string)$response->LONGITUDE))
            ->setModuleId(DpdPickup::getModuleId())
        ;

        /** We set the opening hours separately since we got them as an array */
        foreach ($response->OPENING_HOURS_ITEMS->OPENING_HOURS_ITEM as $horaire) {
            $openedHours = $pickupLocation->getOpeningHours()[($horaire->DAY_ID - 1)];
            $openedHours .= $openedHours === null ? $horaire->START_TM . '-' . $horaire->END_TM : ' ' . $horaire->START_TM . '-' . $horaire->END_TM;
            $pickupLocation->setOpeningHours(($horaire->DAY_ID - 1), $openedHours);
        }

        return $pickupLocation;
    }

    /**
     * Get the list of locations (relay points)
     *
     * @param PickupLocationEvent $pickupLocationEvent
     * @throws \Exception
     */
    public function getPickupLocations(PickupLocationEvent $pickupLocationEvent)
    {
        if (null !== $moduleIds = $pickupLocationEvent->getModuleIds()) {
            if (!in_array(DpdPickup::getModuleId(), $moduleIds)) {
                return ;
            }
        }

        $responses = $this->callWebService($pickupLocationEvent);

        if (null === $responses) {
            return ;
        }

        foreach ($responses->PUDO_ITEM as $response) {
            $pickupLocationEvent->appendLocation($this->createPickupLocationFromResponse($response));
        }
    }

    public function getDeliveryModuleOptions(DeliveryModuleOptionEvent $deliveryModuleOptionEvent)
    {
        if ($deliveryModuleOptionEvent->getModule()->getId() !== DpdPickup::getModuleId()) {
            return ;
        }

        $isValid = true;
        $postage = null;
        $postageTax = null;

        try {
            $module = new DpdPickup();
            $country = $deliveryModuleOptionEvent->getCountry();

            if (empty($module->getAreaForCountry($country))) {
                throw new DeliveryException(Translator::getInstance()->trans("Your delivery country is not covered by DpdClassic"));
            }

            $countryAreas = $country->getCountryAreas();
            $areasArray = [];

            /** @var CountryArea $countryArea */
            foreach ($countryAreas as $countryArea) {
                $areasArray[] = $countryArea->getAreaId();
            }

            if (empty($countryAreas->getFirst())) {
                throw new DeliveryException(Translator::getInstance()->trans("Your delivery country is not covered by DpdClassic"));
            }

            $postage = $module->getPostageAmount(
                $countryAreas->getFirst()->getAreaId(),
                $deliveryModuleOptionEvent->getCart()->getWeight(),
                $deliveryModuleOptionEvent->getCart()->getTaxedAmount($country)
            );

            $postageTax = 0; //TODO
        } catch (\Exception $exception) {
            $isValid = false;
            var_dump($exception->getMessage());
            var_dump($postage); die;
        }

        $minimumDeliveryDate = ''; // TODO (calculate delivery date from day of order)
        $maximumDeliveryDate = ''; // TODO (calculate delivery date from day of order

        $propelModule = ModuleQuery::create()
            ->filterById(DpdPickup::getModuleId())
            ->findOne();

        /** @var DeliveryModuleOption $deliveryModuleOption */
        $deliveryModuleOption = ($this->container->get('open_api.model.factory'))->buildModel('DeliveryModuleOption');
        $deliveryModuleOption
            ->setCode(DpdPickup::getModuleCode())
            ->setValid($isValid)
            ->setTitle($propelModule->getTitle())
            ->setImage('')
            ->setMinimumDeliveryDate($minimumDeliveryDate)
            ->setMaximumDeliveryDate($maximumDeliveryDate)
            ->setPostage($postage)
            ->setPostageTax($postageTax)
            ->setPostageUntaxed($postage - $postageTax)
        ;

        $deliveryModuleOptionEvent->appendDeliveryModuleOptions($deliveryModuleOption);
    }

    public static function getSubscribedEvents()
    {
        $listenedEvents = [];

        /** Check for old versions of Thelia where the events used by the API didn't exists */
        if (class_exists(PickupLocation::class)) {
            $listenedEvents[TheliaEvents::MODULE_DELIVERY_GET_PICKUP_LOCATIONS] = array("getPickupLocations", 131);
        }

        /** Check for old versions of Thelia where the events used by the API didn't exists */
        if (class_exists(DeliveryModuleOptionEvent::class)) {
            $listenedEvents[OpenApiEvents::MODULE_DELIVERY_GET_OPTIONS] = array("getDeliveryModuleOptions", 129);
        }

        return $listenedEvents;
    }
}
