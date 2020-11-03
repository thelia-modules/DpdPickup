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

namespace DpdPickup;

use DpdPickup\DataTransformer\ZipCodeListTransformer;
use DpdPickup\Model\DpdpickupPriceQuery;
use DpdPickup\Model\IcirelaisFreeshippingQuery;
use DpdPickup\Model\Map\DpdpickupPriceTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\Finder\Finder;
use Thelia\Exception\OrderException;
use Thelia\Install\Database;
use Thelia\Model\Country;
use Thelia\Model\Map\AreaTableMap;
use Thelia\Module\AbstractDeliveryModule;
use Thelia\Module\Exception\DeliveryException;

class DpdPickup extends AbstractDeliveryModule
{
    const DOMAIN = 'dpdpickup';
    const DOMAIN_ADMIN = "dpdpickup.ai";

    /** @var string */
    const UPDATE_PATH = __DIR__ . DS . 'Config' . DS . 'update';

    const DELIVERY_REF_COLUMN = 17;
    const ORDER_REF_COLUMN = 18;

    const STATUS_PAID = 2;
    const STATUS_PROCESSING = 3;
    const STATUS_SENT = 4;

    const NO_CHANGE = 'nochange';
    const PROCESS = 'processing';
    const SEND = 'sent';

    const KEY_EXPEDITOR_NAME = 'conf_exa_name';
    const KEY_EXPEDITOR_ADDR = 'conf_exa_addr';
    const KEY_EXPEDITOR_ADDR2 = 'conf_exa_addr2';
    const KEY_EXPEDITOR_ZIPCODE = 'conf_exa_zipcode';
    const KEY_EXPEDITOR_CITY = 'conf_exa_city';
    const KEY_EXPEDITOR_TEL = 'conf_exa_tel';
    const KEY_EXPEDITOR_MOBILE = 'conf_exa_mobile';
    const KEY_EXPEDITOR_MAIL = 'conf_exa_mail';
    const KEY_EXPEDITOR_DPDCODE = 'conf_exa_expcode';

    const KEY_RETURN_NAME = 'return_name';
    const KEY_RETURN_ADDR = 'return_addr';
    const KEY_RETURN_ADDR2 = 'return_addr2';
    const KEY_RETURN_ZIPCODE = 'return_zipcode';
    const KEY_RETURN_CITY = 'return_city';
    const KEY_RETURN_TEL = 'return_tel';

    const KEY_RETURN_TYPE = 'return_type';

    const RETURN_NONE = 0;
    const RETURN_ON_DEMAND = 3;
    const RETURN_PREPARED = 4;

    const API_USER_ID = "dpdpickup_userid";
    const API_PASSWORD = "dpdpickup_password";
    const API_CENTER_NUMBER = "dpdpickup_center_number";
    const API_CUSTOMER_NUMBER = "dpdpickup_customer_number";
    const API_IS_TEST = "dpdpickup_is_test";

    const API_SHIPPER_NAME = "dpdpickup_shipper_name";
    const API_SHIPPER_ADDRESS1 = "dpdpickup_shipper_address1";
    const API_SHIPPER_ADDRESS2 = "dpdpickup_shipper_address2";
    const API_SHIPPER_COUNTRY = "dpdpickup_shipper_country";
    const API_SHIPPER_CITY = "dpdpickup_shipper_city";
    const API_SHIPPER_ZIP = "dpdpickup_shipper_zip_code";
    const API_SHIPPER_CIV = "dpdpickup_shipper_civ";
    const API_SHIPPER_CONTACT = "dpdpickup_shipper_contact";
    const API_SHIPPER_PHONE = "dpdpickup_shipper_phone";
    const API_SHIPPER_FAX = "dpdpickup_shipper_fax";
    const API_SHIPPER_MAIL = "dpdpickup_shipper_mail";

    const DPD_WSDL_TEST = "http://92.103.148.116/exa-eprintwebservice/eprintwebservice.asmx?WSDL";
    const DPD_WSDL = "https://e-station.cargonet.software/dpd-eprintwebservice/eprintwebservice.asmx?WSDL";

    const DPD_LABEL_DIR = THELIA_LOCAL_DIR . "DpdPickupLabel";

    protected $request;
    protected $dispatcher;

    private static $prices = null;

    public function postActivation(ConnectionInterface $con = null)
    {
        $database = new Database($con->getWrappedConnection());

        if ("1" !== self::getConfigValue("is_initialized")){
            $database->insertSql(null, array(__DIR__ . '/Config/thelia.sql'));

            self::setConfigValue("is_initialized", 1);
        }
    }

    /**
     * @inheritdoc
     *
     * @param string $currentVersion
     * @param string $newVersion
     * @param ConnectionInterface|null $con
     */
    public function update($currentVersion, $newVersion, ConnectionInterface $con = null)
    {
        $finder = (new Finder())->files()->name('#.*?\.sql#')->sortByName()->in(self::UPDATE_PATH);

        if ($finder->count() === 0) {
            return;
        }

        $database = new Database($con);

        /** @var \Symfony\Component\Finder\SplFileInfo $updateSQLFile */
        foreach ($finder as $updateSQLFile) {
            if (version_compare($currentVersion, str_replace('.sql', '', $updateSQLFile->getFilename()), '<')) {
                $database->insertSql(null, [$updateSQLFile->getPathname()]);
            }
        }
    }

    public static function getFreeShippingAmount()
    {
        if (!null !== $amount = self::getConfigValue('free_shipping_amount')) {
            return (float) $amount;
        }

        return 0;
    }

    public static function setFreeShippingAmount($amount)
    {
        self::setConfigValue('free_shipping_amount', $amount);
    }

    public static function getPrices()
    {
        if (null === self::$prices) {
            self::$prices = [];

            $areaJoin = new Join(DpdpickupPriceTableMap::AREA_ID, AreaTableMap::ID, Criteria::INNER_JOIN);
            $dpdPickupPrices = DpdpickupPriceQuery::create()
                ->addJoinObject($areaJoin)
                ->withColumn(AreaTableMap::NAME, 'NAME')
                ->orderByAreaId()
                ->orderByWeightMax()
                ->find()
            ;

            /** @var \DpdPickup\Model\DpdpickupPrice $dpdPickupPrice */
            foreach ($dpdPickupPrices as $dpdPickupPrice) {

                if (!array_key_exists($dpdPickupPrice->getAreaId(), self::$prices)) {
                    self::$prices[$dpdPickupPrice->getAreaId()] = [
                        '_info' => 'area ' . $dpdPickupPrice->getAreaId() . ' : ' . $dpdPickupPrice->getVirtualColumn('NAME'),
                        'slices' => []
                    ];
                }

                self::$prices[$dpdPickupPrice->getAreaId()]['slices'][(string)$dpdPickupPrice->getWeightMax()] = $dpdPickupPrice->getPrice();
            }
        }

        return self::$prices;
    }

    /**
     * This method is called by the Delivery  loop, to check if the current module has to be displayed to the customer.
     * Override it to implements your delivery rules/
     *
     * If you return true, the delivery method will de displayed to the customer
     * If you return false, the delivery method will not be displayed
     *
     * @param Country $country the country to deliver to.
     *
     * @return boolean
     */
    public function isValidDelivery(Country $country)
    {
        $cartWeight = $this->getRequest()->getSession()->getSessionCart($this->getDispatcher())->getWeight();

        $areaId = $country->getAreaId();

        $prices = self::getPrices();

        /* check if Ici Relais delivers the asked area */
        if (isset($prices[$areaId]) && isset($prices[$areaId]["slices"])) {
            $areaPrices = $prices[$areaId]["slices"];
            ksort($areaPrices);

            /* check this weight is not too much */
            end($areaPrices);

            $maxWeight = key($areaPrices);
            if ($cartWeight <= $maxWeight) {
                return true;
            }
        }

        return false;
    }

    public static function getPostageAmount($areaId, $weight, $cartAmount = 0)
    {
        $freeshipping = IcirelaisFreeshippingQuery::create()->getLast();
        $postage=0;
        if (!$freeshipping) {
            $freeShippingAmount = (float) self::getFreeShippingAmount();

            //If a min price for freeShipping is define and the amount of cart reach this montant return 0
            //Be carefull ! Thelia cartAmount is a decimal with 6 in precision ! That's why we must round cart amount
            if ($freeShippingAmount > 0 && $freeShippingAmount <= round($cartAmount, 2)) {
                return 0;
            }

            $prices = self::getPrices();

            /* check if DpdPickup delivers the asked area */
            if (!isset($prices[$areaId]) || !isset($prices[$areaId]["slices"])) {
                throw new DeliveryException(
                    "Ici Relais delivery unavailable for the chosen delivery country",
                    OrderException::DELIVERY_MODULE_UNAVAILABLE
                );
            }

            $areaPrices = $prices[$areaId]["slices"];
            ksort($areaPrices);

            /* check this weight is not too much */
            end($areaPrices);
            $maxWeight = key($areaPrices);
            if ($weight > $maxWeight) {
                throw new DeliveryException(
                    sprintf("Ici Relais delivery unavailable for this cart weight (%s kg)", $weight),
                    OrderException::DELIVERY_MODULE_UNAVAILABLE
                );
            }

            $postage = current($areaPrices);

            while (prev($areaPrices)) {
                if ($weight > key($areaPrices)) {
                    break;
                }

                $postage = current($areaPrices);
            }
        }

        return $postage;
    }

    public function getPostage(Country $country)
    {
        $request = $this->getRequest();

        $cartWeight = $this->getRequest()->getSession()->getSessionCart($this->getDispatcher())->getWeight();
        $cartAmount = $request->getSession()->getSessionCart($this->getDispatcher())->getTaxedAmount($country);

        $postage = self::getPostageAmount(
            $country->getAreaId(),
            $cartWeight,
            $cartAmount
        );

        return $postage;
    }

    public static function getConfigExcludeZipCode()
    {
        return (new ZipCodeListTransformer())->reverseTransform(self::getConfigValue('exclude_zip_code'));
    }

    public static function setConfigExcludeZipCode($value)
    {
        self::setConfigValue('exclude_zip_code', (new ZipCodeListTransformer())->transform($value));
    }

    public static function getConfigGoogleMapKey()
    {
        return self::getConfigValue('google_map_key');
    }

    public static function setConfigGoogleMapKey($value)
    {
        self::setConfigValue('google_map_key', $value);
    }

    public function getDeliveryMode()
    {
        return "pickup";
    }

    public static function getApiConfig()
    {
        $data = [];
        $data['userId'] = self::getConfigValue(self::API_USER_ID);
        $data['password'] = self::getConfigValue(self::API_PASSWORD);
        $data['center_number'] = self::getConfigValue(self::API_CENTER_NUMBER);
        $data['customer_number'] = self::getConfigValue(self::API_CUSTOMER_NUMBER);
        $data['isTest'] = (int)self::getConfigValue(self::API_IS_TEST);
        $data['shipperName'] = self::getConfigValue(self::API_SHIPPER_NAME);
        $data['shipperAddress1'] = self::getConfigValue(self::API_SHIPPER_ADDRESS1);
        $data['shipperCountry'] = self::getConfigValue(self::API_SHIPPER_COUNTRY);
        $data['shipperCity'] = self::getConfigValue(self::API_SHIPPER_CITY);
        $data['shipperZipCode'] = self::getConfigValue(self::API_SHIPPER_ZIP);
        $data['shipperPhone'] = self::getConfigValue(self::API_SHIPPER_PHONE);
        $data['shipperFax'] = self::getConfigValue(self::API_SHIPPER_FAX);

        return $data;
    }
}
