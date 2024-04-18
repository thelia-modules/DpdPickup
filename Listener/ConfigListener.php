<?php

namespace DpdPickup\Listener;

use DpdPickup\DpdPickup;
use DpdPickup\Model\DpdpickupPriceQuery;
use DpdPickup\Model\IcirelaisFreeshippingQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Thelia\Model\AreaDeliveryModuleQuery;
use Thelia\Model\ModuleConfigQuery;

class ConfigListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'module.config' => ['onModuleConfig', 128]
        ];
    }

    public function onModuleConfig(GenericEvent $event)
    {
        $subject = $event->getSubject();

        if ($subject !== "HealthStatus") {
            throw new \RuntimeException('Event subject does not match expected value');
        }

        $shippingZoneConfig = AreaDeliveryModuleQuery::create()
            ->filterByDeliveryModuleId(DpdPickup::getModuleId())
            ->find();

        $freeShippingAmount = ModuleConfigQuery::create()
            ->filterByModuleId(DpdPickup::getModuleId())
            ->filterByName(['free_shipping_amount'])
            ->findOne();

        $freeShippingAmount = $freeShippingAmount?->getValue();

        $freeShipping = IcirelaisFreeshippingQuery::create()
            ->orderByCreatedAt('desc')
            ->findOne();

        $freeShipping = $freeShipping?->getActive();

        $slicesConfig = DpdpickupPriceQuery::create()
            ->find();

        $moduleConfig = [];
        $moduleConfig['module'] = DpdPickup::getModuleCode();
        $configsCompleted = true;

        $defaultTaxRule = ModuleConfigQuery::create()
            ->filterByModuleId(DpdPickup::getModuleId())
            ->filterByName('dpd_pickup_point_tax_rule_id')
            ->findOne();

        $defaultTaxRule = $defaultTaxRule?->getValue();

        if ($defaultTaxRule === null) {
            $configsCompleted = false;
        }

        if ($freeShippingAmount === null && $freeShipping === false && $slicesConfig->count() === 0) {
            $configsCompleted = false;
        }

        if ($shippingZoneConfig->count() === 0) {
            $configsCompleted = false;
        }

        $moduleConfig['completed'] = $configsCompleted;

        $event->setArgument('dpd.pickup.config', $moduleConfig);
    }





}