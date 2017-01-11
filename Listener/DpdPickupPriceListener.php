<?php
/**
 * Created by PhpStorm.
 * User: guigit
 * Date: 11/01/2017
 * Time: 09:22
 */

namespace DpdPickup\Listener;


use DpdPickup\Event\DpdPickupEvents;
use DpdPickup\Event\DpdPickupPriceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DpdPickupPriceListener implements EventSubscriberInterface
{
    public function createOrUpdate(DpdPickupPriceEvent $event)
    {
        $event->getDpdPickupPrice()->save();
    }

    public function delete(DpdPickupPriceEvent $event)
    {
        $event->getDpdPickupPrice()->delete();
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            DpdPickupEvents::DPDPICKUP_PRICE_CREATE => ['createOrUpdate', 128],
            DpdPickupEvents::DPDPICKUP_PRICE_UPDATE => ['createOrUpdate', 128],
            DpdPickupEvents::DPDPICKUP_PRICE_DELETE => ['delete', 128]
        ];
    }
}
