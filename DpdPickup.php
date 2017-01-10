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
use DpdPickup\Model\IcirelaisFreeshippingQuery;
use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Exception\OrderException;
use Thelia\Install\Database;
use Thelia\Model\Country;
use Thelia\Module\AbstractDeliveryModule;
use Thelia\Module\Exception\DeliveryException;

class DpdPickup extends AbstractDeliveryModule
{
    const DOMAIN = 'dpdpickup';
    const DELIVERY_REF_COLUMN = 17;
    const ORDER_REF_COLUMN = 18;

    const STATUS_PAID = 2;
    const STATUS_PROCESSING = 3;
    const STATUS_SENT = 4;

    const NO_CHANGE = 'nochange';
    const PROCESS = 'processing';
    const SEND = 'sent';

    const CONF_EXA_NAME = 'conf_exa_name';
    const CONF_EXA_ADDR = 'conf_exa_addr';
    const CONF_EXA_ADDR2 = 'conf_exa_addr2';
    const CONF_EXA_ZIPCODE = 'conf_exa_zipcode';
    const CONF_EXA_CITY = 'conf_exa_city';
    const CONF_EXA_TEL = 'conf_exa_tel';
    const CONF_EXA_MOBILE = 'conf_exa_mobile';
    const CONF_EXA_MAIL = 'conf_exa_mail';
    const CONF_EXA_EXPCODE = 'conf_exa_expcode';

    protected $request;
    protected $dispatcher;

    private static $prices = null;

    const JSON_PRICE_RESOURCE = "/Config/prices.json";

    public function postActivation(ConnectionInterface $con = null)
    {
        $database = new Database($con->getWrappedConnection());

        $database->insertSql(null, array(__DIR__ . '/Config/thelia.sql'));
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
            if (is_readable(sprintf('%s/%s', __DIR__, self::JSON_PRICE_RESOURCE))) {
                self::$prices = json_decode(
                    file_get_contents(sprintf('%s/%s', __DIR__, self::JSON_PRICE_RESOURCE)),
                    true
                );
            } else {
                self::$prices = null;
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
            if ($freeShippingAmount > 0 && $freeShippingAmount <= $cartAmount) {
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
}
