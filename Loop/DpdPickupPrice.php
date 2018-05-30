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

use DpdPickup\Model\Base\DpdpickupPrice as DpdpickupPriceModel;
use DpdPickup\Model\DpdpickupPriceQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;

/**
 * Class DpdPickupPrice
 * @package DpdPickup\Loop
 * @author Thelia <info@thelia.net>
 * @original_author Etienne Roudeix <eroudeix@openstudio.fr>
 */
class DpdPickupPrice extends BaseLoop implements PropelSearchLoopInterface
{
    /* set countable to false since we need to preserve keys */
    protected $countable = false;

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('area', null, true)
        );
    }

    public function buildModelCriteria()
    {
        $area = $this->getArea();

        $areaPrices = DpdpickupPriceQuery::create()
            ->filterByAreaId($area)
            ->orderByWeight();

        return $areaPrices;
    }

    public function parseResults(LoopResult $loopResult)
    {
        /** @var DpdpickupPriceModel $price */
        foreach ($loopResult->getResultDataCollection() as $price) {
            $loopResultRow = new LoopResultRow();
            $loopResultRow
                ->set("SLICE_ID", $price->getId())
                ->set("AREA_ID", $price->getAreaId())
                ->set("MAX_WEIGHT", $price->getWeight())
                ->set("PRICE", $price->getPrice());

            $loopResult->addRow($loopResultRow);
        }
        return $loopResult;
    }
}
