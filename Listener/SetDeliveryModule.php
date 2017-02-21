<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace DpdPickup\Listener;

use DpdPickup\DpdPickup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use DpdPickup\Model\OrderAddressIcirelais;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderAddressQuery;
use DpdPickup\Model\AddressIcirelais;
use DpdPickup\Model\AddressIcirelaisQuery;
use Thelia\Model\AddressQuery;

/**
 * Class SetDeliveryModule
 * @package DpdPickup\Listener
 * @author Thelia <info@thelia.net>
 */

class SetDeliveryModule implements EventSubscriberInterface
{
    protected $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return \Thelia\Core\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    protected function check_module($id)
    {
        return $id == DpdPickup::getModuleId();
    }

    public function isModuleDpdPickup(OrderEvent $event)
    {
        $address = AddressIcirelaisQuery::create()
            ->findPk($event->getDeliveryAddress());

        if ($this->check_module($event->getDeliveryModule())) {
            //tmp solution
            $request = $this->getRequest();
            $pr_code = $request->request->get('pr_code');
            if (!empty($pr_code)) {
                // Get details w/ SOAP
                $con = new \SoapClient(__DIR__."/../Config/exapaq.wsdl", array('soap_version'=>SOAP_1_2));
                $response = $con->GetPudoDetails(array("pudo_id"=>$pr_code));
                $xml = new \SimpleXMLElement($response->GetPudoDetailsResult->any);
                if (isset($xml->ERROR)) {
                    throw new \ErrorException("Error while choosing pick-up & go store: ".$xml->ERROR);
                }

                $customer_name = AddressQuery::create()
                    ->findPk($event->getDeliveryAddress());


                $request->getSession()->set('DpdPickupDeliveryId', $event->getDeliveryAddress());
                if ($address === null) {
                    $address = new AddressIcirelais();
                    $address->setId($event->getDeliveryAddress());
                }

                // France Métropolitaine
                $address->setCode($pr_code)
                    ->setCompany((string) $xml->PUDO_ITEMS->PUDO_ITEM->NAME)
                    ->setAddress1((string) $xml->PUDO_ITEMS->PUDO_ITEM->ADDRESS1)
                    ->setAddress2((string) $xml->PUDO_ITEMS->PUDO_ITEM->ADDRESS2)
                    ->setAddress3((string) $xml->PUDO_ITEMS->PUDO_ITEM->ADDRESS3)
                    ->setZipcode((string) $xml->PUDO_ITEMS->PUDO_ITEM->ZIPCODE)
                    ->setCity((string) $xml->PUDO_ITEMS->PUDO_ITEM->CITY)
                    ->setFirstname($customer_name->getFirstname())
                    ->setLastname($customer_name->getLastname())
                    ->setTitleId($customer_name->getTitleId())
                    ->setCountryId($customer_name->getCountryId())
                    ->save();
            } else {
                throw new \ErrorException(Translator::getInstance()->trans("No pick-up & go store chosen for DpdPickup delivery module", [], DpdPickup::DOMAIN));
            }
        } elseif (null !== $address) {
            $address->delete();
        }
    }

    public function updateDeliveryAddress(OrderEvent $event)
    {
        if ($this->check_module($event->getOrder()->getDeliveryModuleId())) {
            $request = $this->getRequest();
            $tmp_address = AddressIcirelaisQuery::create()
                ->findPk($request->getSession()->get('DpdPickupDeliveryId'));

            if ($tmp_address === null) {
                throw new \ErrorException(Translator::getInstance()->trans("Got an error with DpdPickup module. Please try again to checkout.", [], DpdPickup::DOMAIN));
            }

            $savecode = new OrderAddressIcirelais();
            $savecode->setId($event->getOrder()->getDeliveryOrderAddressId())
                ->setCode($tmp_address->getCode())
                ->save();

            $update = OrderAddressQuery::create()
                ->findPK($event->getOrder()->getDeliveryOrderAddressId())
                ->setCompany($tmp_address->getCompany())
                ->setAddress1($tmp_address->getAddress1())
                ->setAddress2($tmp_address->getAddress2())
                ->setAddress3($tmp_address->getAddress3())
                ->setZipcode($tmp_address->getZipcode())
                ->setCity($tmp_address->getCity())
                ->save();
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::ORDER_SET_DELIVERY_MODULE => array('isModuleDpdPickup', 64),
            TheliaEvents::ORDER_BEFORE_PAYMENT => array('updateDeliveryAddress', 256)
        );
    }
}
