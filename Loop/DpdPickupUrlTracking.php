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

use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\OrderQuery;

/**
 * Class DpdPickupUrlTracking
 * @package DpdPickup\Loop
 * @author Thelia <info@thelia.net>
 * @method string getRef
 *
 * @deprecated This loop is became useless, you can use the order loop
 */
class DpdPickupUrlTracking extends BaseLoop implements ArraySearchLoopInterface
{
    /**
     * @return ArgumentCollection
     */
    const BASE_URL = "http://www.dpd.fr/traces_info_%s";

    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createAnyTypeArgument('ref', null, true)
        );
    }

    public function buildArray()
    {
        if (null !== $order = OrderQuery::create()->findOneByRef($this->getRef())) {
            return [$order->getRef() => $order->getDeliveryRef()];
        }

        return [];
    }

    public function parseResults(LoopResult $loopResult)
    {
        foreach ($loopResult->getResultDataCollection() as $ref => $code) {
            $loopResultRow = new LoopResultRow();
            $loopResultRow->set("URL", sprintf(self::BASE_URL, $code));

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
