<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 28/09/2020
 * Time: 11:48
 */

namespace DpdPickup\Smarty\Plugins;


use DpdClassic\Model\DpdclassicLabelsQuery;
use DpdPickup\Model\DpdpickupLabelsQuery;
use TheliaSmarty\Template\AbstractSmartyPlugin;
use TheliaSmarty\Template\SmartyPluginDescriptor;

class DpdPickupLabelNumber extends AbstractSmartyPlugin
{
    public function getPluginDescriptors()
    {
        return [
            new SmartyPluginDescriptor('function', 'DpdPickupLabelNumber', $this, 'dpdPickupLabelNumber'),
        ];
    }

    /**
     * @param $params
     * @param $smarty
     */
    public function dpdPickupLabelNumber($params, $smarty)
    {
        $orderId = $params["order_id"];

        $labelNumber = DpdpickupLabelsQuery::create()->filterByOrderId($orderId)->findOne();

        $smarty->assign('labelNbr', $labelNumber ? $labelNumber->getLabelNumber() : null);
    }
}