<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia                                                                       */
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
/*      along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace DpdPickup\Loop;

use DpdPickup\DpdPickup;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;
use Thelia\Model\AddressQuery;

/**
 * Class DpdPickupAround
 * @package DpdPickup\Loop
 * @author Thelia <info@thelia.net>
 */
class DpdPickupAround extends BaseLoop implements PropelSearchLoopInterface
{
    private $addressflag=true;
    private $zipcode="";
    private $city="";
    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createAnyTypeArgument("zipcode", ""),
            Argument::createAnyTypeArgument("city", "")
        );
    }

    public function buildModelCriteria()
    {
        $zipcode = $this->getZipcode();
        $city = $this->getCity();
        if (!empty($zipcode) && !empty($city)) {
            $this->zipcode = $zipcode;
            $this->city = $city;
            $this->addressflag = false;
        } else {
            if (null !== $customer = $this->securityContext->getCustomerUser()) {
                $search = AddressQuery::create();

                $search
                    ->filterByCustomerId($customer->getId())
                    ->filterByIsDefault(true);
            } else {
                throw new \ErrorException("Customer not connected.");
            }

            return $search;
        }
    }

    public function parseResults(LoopResult $loopResult)
    {
        $excludeZipCodes = DpdPickup::getConfigExcludeZipCode();

        $date = date('d/m/Y');
        try {
            $getPudoSoap = new \SoapClient(__DIR__ . "/../Config/exapaq.wsdl", array('soap_version' => SOAP_1_2));

            if ($this->addressflag) {
                foreach ($loopResult->getResultDataCollection() as $address) {
                    if (in_array($address->getZipcode(), $excludeZipCodes)) {
                        return $loopResult;
                    }

                    $response = $getPudoSoap->GetPudoList(
                        array(
                            "address"    => str_replace(" ", "%", $address->getAddress1()),
                            "zipCode"    => $address->getZipcode(),
                            "city"       => str_replace(" ", "%", $address->getCity()),
                            "request_id" => "1234",
                            "date_from"  => $date
                        )
                    );
                }
            } else {
                if (in_array($this->zipcode, $excludeZipCodes)) {
                    return $loopResult;
                }

                $response = $getPudoSoap->GetPudoList(
                    array(
                        "zipCode"    => $this->zipcode,
                        "city"       => str_replace(" ", "%", $this->city),
                        "request_id" => "1234",
                        "date_from"  => $date
                    )
                );
            }
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

            return array();
        }

        $xml = new \SimpleXMLElement($response->GetPudoListResult->any);
        if (isset($xml->ERROR)) {
            throw new \ErrorException("Error while choosing pick-up & go store: " . $xml->ERROR);
        }
        foreach ($xml->PUDO_ITEMS->PUDO_ITEM as $item) {
            $loopResultRow = new LoopResultRow();
            // Write distance in m / km
            $distance = $item->DISTANCE;
            if (strlen($distance) < 4) {
                $distance .= " m";
            } else {
                $distance = (string) floatval($distance) / 1000;
                while (substr($distance, strlen($distance) - 1, 1) == "0") {
                    $distance = substr($distance, 0, strlen($distance) - 1);
                }
                $distance = str_replace(".", ",", $distance) . " km";
            }

            $hours = [];

            foreach ($item->OPENING_HOURS_ITEMS->OPENING_HOURS_ITEM as $openingHoursItem) {
                $day = Translator::getInstance()->trans(
                    'day_' . (string)$openingHoursItem->DAY_ID,
                    [],
                    DpdPickup::DOMAIN
                );
                if (!isset($hours[$day])) {
                    $hours[$day] = [];
                }
                $hours[$day][] = [
                    'START_TM' => (string)$openingHoursItem->START_TM,
                    'END_TM' => (string)$openingHoursItem->END_TM
                ];
            }

            // Then define all the variables
            $loopResultRow
                ->set("NAME", self::cleanString($item->NAME))
                ->set("LONGITUDE", str_replace(",", ".", $item->LONGITUDE))
                ->set("LATITUDE", str_replace(",", ".", $item->LATITUDE))
                ->set("CODE", $item->PUDO_ID)
                ->set("ADDRESS", self::cleanString($item->ADDRESS1))
                ->set("ZIPCODE", self::cleanString($item->ZIPCODE))
                ->set("CITY", self::cleanString($item->CITY))
                ->set("DISTANCE", $distance)
                ->set("HOURS", $hours)
            ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }

    public static function cleanString($string)
    {
        return str_replace(
            array('"'),
            array('\''),
            $string
        );
    }
}
