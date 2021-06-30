<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
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
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace DpdPickup\Controller;

use DpdPickup\Event\DpdPickupEvents;
use DpdPickup\Event\DpdPickupPriceEvent;
use DpdPickup\Model\DpdpickupPrice;
use DpdPickup\Model\DpdpickupPriceQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Model\AreaQuery;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\AccessManager;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/dpdpickup/prices", name="dpdpickup_prices")
 * Class EditPrices
 * @package DpdPickup\Controller
 * @author Thelia <info@thelia.net>
 */
class EditPrices extends BaseAdminController
{
    /**
     * @Route("", name="_save", methods="POST")
     */
    public function editprices(RequestStack $requestStack, EventDispatcherInterface $eventDispatcher)
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), array('DpdPickup'), AccessManager::UPDATE)) {
            return $response;
        }
        // Get data & treat
        $post = $requestStack->getCurrentRequest();
        $operation = $post->get('operation');
        $area = $post->get('area');
        $weight = $post->get('weight');
        $price = $post->get('price');
        if (preg_match("#^add|delete$#", $operation) &&
            preg_match("#^\d+$#", $area) &&
            preg_match("#^\d+\.?\d*$#", $weight)
        ) {
            // check if area exists in db
            $exists = AreaQuery::create()
                ->findPK($area);
            if ($exists !== null) {

                if ((float) $weight <= 0) {
                    throw new \Exception("Weight must be superior to 0");
                }

                if (null === $dpdPickupPrice = DpdpickupPriceQuery::create()->filterByAreaId($area)->filterByWeightMax($weight)->findOne()) {
                    $dpdPickupPrice = new DpdpickupPrice();
                    $dpdPickupPrice
                        ->setAreaId($area)
                        ->setWeightMax($weight)
                    ;
                }
                $dpdPickupPrice->setPrice($price);
                $dpdPickupPriceEvent = new DpdPickupPriceEvent($dpdPickupPrice);

                if ($operation === 'delete') {
                    $eventDispatcher->dispatch($dpdPickupPriceEvent, DpdPickupEvents::DPDPICKUP_PRICE_DELETE);
                } else {
                    $eventDispatcher->dispatch($dpdPickupPriceEvent, DpdPickupEvents::DPDPICKUP_PRICE_UPDATE);
                }
            } else {
                throw new \Exception("Area not found");
            }
        } else {
            throw new \ErrorException("Arguments are missing or invalid");
        }

        return $this->generateRedirectFromRoute("admin.module.configure", array(),
            array( 'module_code'=>"DpdPickup",
                'current_tab'=>"price_slices_tab",
                '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureAction'
            )
        );
    }
}
