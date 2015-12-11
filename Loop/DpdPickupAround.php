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

namespace DpdPickup\Loop;

use Thelia\Log\Tlog;
use Thelia\Model\AddressQuery;
use Thelia\Core\Template\Loop\Address;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;

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
        if (!empty($zipcode) and !empty($city)) {
            $this->zipcode = $zipcode;
            $this->city = $city;
            $this->addressflag =  false;
        } else {
            $search = AddressQuery::create();

            $customer=$this->securityContext->getCustomerUser();
            if ($customer !== null) {
                $search->filterByCustomerId($customer->getId());
                $search->filterByIsDefault("1");
            } else {
                throw new \ErrorException("Customer not connected.");
            }

            return $search;
        }
    }

    public function parseResults(LoopResult $loopResult)
    {
        $date = date('d/m/Y');
        try {
            $getPudoSoap = new \SoapClient(__DIR__ . "/../Config/exapaq.wsdl", array('soap_version' => SOAP_1_2));

            if ($this->addressflag) {
                foreach ($loopResult->getResultDataCollection() as $address) {
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

            // Then define all the variables
            $loopResultRow->set("NAME", htmlspecialchars($item->NAME))
                ->set("LONGITUDE", str_replace(",", ".", $item->LONGITUDE))
                ->set("LATITUDE", str_replace(",", ".", $item->LATITUDE))
                ->set("CODE", $item->PUDO_ID)
                ->set("ADDRESS", htmlspecialchars($item->ADDRESS1))
                ->set("ZIPCODE", $item->ZIPCODE)
                ->set("CITY", htmlspecialchars($item->CITY))
                ->set("DISTANCE", $distance);
            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
