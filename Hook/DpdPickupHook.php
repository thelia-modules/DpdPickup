<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace DpdPickup\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\OrderQuery;

/**
 * Class DpdPickupHook
 * @package DpdPickup\Hook
 * @author Manuel Raynaud <manu@thelia.net>
 */
class DpdPickupHook extends BaseHook
{
    public function renderDpdPickupChoice(HookRenderEvent $event)
    {
        $event->add($this->render('pickup.html', ['dpd_id' => $event->getArgument('module')]));
    }

    public function renderDeliveryAddress(HookRenderEvent $event)
    {
        $event->add($this->render("delivery-address.html"));
    }
    public function orderDeliveryStylesheet(HookRenderEvent $event)
    {
        $content = $this->addCSS('assets/css/style.css');
        $event->add($content);
    }
    public function onModuleConfig(HookRenderEvent $event)
    {
        $event->add($this->render('module_configuration.html'));
    }

    public function onModuleConfigJs(HookRenderEvent $event)
    {
        $event->add($this->render('module-config-js.html'));
    }

    public function onOrderModuleTab(HookRenderEvent $event)
    {
        $event->add($this->render('order-edit.html'));
    }
    public function onMenuItems(HookRenderEvent $event)
    {
        $event->add($this->render('hook/dpdpickup-menu-item.html'));
    }

    /**
     * @param HookRenderEvent $event
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function onOrderBillTop(HookRenderEvent $event)
    {
        $moduleCode = OrderQuery::create()->findOneById($event->getArgument("order_id"))->getModuleRelatedByDeliveryModuleId()->getCode();

        if("DpdPickup" === $moduleCode){
            $event->add($this->render('hook/dpdpickup-order-edit-label.html'));
        }
    }
}
