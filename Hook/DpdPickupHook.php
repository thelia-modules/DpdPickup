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
}
