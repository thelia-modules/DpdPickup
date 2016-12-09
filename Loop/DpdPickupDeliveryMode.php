<?php

namespace DpdPickup\Loop;

use DpdPickup\DpdPickup;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

class DpdPickupDeliveryMode extends BaseLoop implements ArraySearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection();
    }

    public function buildArray()
    {
        return [
            [
                'free_shipping_amount' => DpdPickup::getFreeShippingAmount()
            ]
        ];
    }

    public function parseResults(LoopResult $loopResult)
    {
        foreach ($loopResult->getResultDataCollection() as $mode) {
            $loopResultRow = new LoopResultRow();
            $loopResultRow->set("FREESHIPPING_FROM", $mode['free_shipping_amount']);
            $loopResult->addRow($loopResultRow);
        }
        return $loopResult;
    }
}
