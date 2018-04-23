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

use DpdPickup\Model\AddressIcirelaisQuery;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Address;

/**
 * Class DpdPickupDelivery
 * @package DpdPickup\Loop
 * @author Thelia <info@thelia.net>
 */
class DpdPickupAddress extends Address
{
    protected $exists = false;
    protected $timestampable = false;

    protected function setExists($id)
    {
        $this->exists = AddressIcirelaisQuery::create()->findPK($id) !== null;
    }
    public function buildModelCriteria()
    {
        $id = $this->getId();
        $this->setExists($id[0]);

        return $this->exists ?
                AddressIcirelaisQuery::create()->filterById($id[0]) :
                parent::buildModelCriteria();
    }
    public function parseResults(LoopResult $loopResult)
    {
        if (!$this->exists) {
            return parent::parseResults($loopResult);
        } else {
            foreach ($loopResult->getResultDataCollection() as $address) {
                $loopResultRow = new LoopResultRow();
                $loopResultRow->set("TITLE", $address->getTitleId())
                    ->set("COMPANY", $address->getCompany())
                    ->set("FIRSTNAME", $address->getFirstname())
                    ->set("LASTNAME", $address->getLastname())
                    ->set("ADDRESS1", $address->getAddress1())
                    ->set("ADDRESS2", $address->getAddress2())
                    ->set("ADDRESS3", $address->getAddress3())
                    ->set("ZIPCODE", $address->getZipcode())
                    ->set("CITY", $address->getCity())
                    ->set("COUNTRY", $address->getCountryId())
                ;
                $loopResult->addRow($loopResultRow);
            }

            return $loopResult;
        }
    }
}
