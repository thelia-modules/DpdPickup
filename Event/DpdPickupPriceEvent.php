<?php

namespace DpdPickup\Event;

use DpdPickup\Model\DpdpickupPrice;
use Thelia\Core\Event\ActionEvent;

/**
 * Created by PhpStorm.
 * User: guigit
 * Date: 11/01/2017
 * Time: 09:20
 */
class DpdPickupPriceEvent extends ActionEvent
{
    /** @var  DpdpickupPrice */
    protected $dpdPickupPrice;

    /**
     * DpdPickupPriceEvent constructor.
     * @param DpdpickupPrice $dpdPickupPrice
     */
    public function __construct(DpdpickupPrice $dpdPickupPrice)
    {
        $this->dpdPickupPrice = $dpdPickupPrice;
    }

    /**
     * @return DpdpickupPrice
     */
    public function getDpdPickupPrice()
    {
        return $this->dpdPickupPrice;
    }

    /**
     * @param DpdpickupPrice $dpdPickupPrice
     *
     * @return $this
     */
    public function setDpdPickupPrice($dpdPickupPrice)
    {
        $this->dpdPickupPrice = $dpdPickupPrice;

        return $this;
    }
}
