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

use DpdPickup\DpdPickup;
use DpdPickup\Model\IcirelaisFreeshipping;
use Symfony\Component\HttpFoundation\JsonResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\AccessManager;
use Thelia\Tools\URL;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/dpdpickup", name="dpdpickup")
 */
class FreeShipping extends BaseAdminController
{
    /**
     * @Route("/freeshipping", name="_freeshipping", methods="POST")
     */
    public function set()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), array('DpdPickup'), AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(\DpdPickup\Form\FreeShipping::getName());
        $response=null;

        try {
            $vform = $this->validateForm($form);
            $data = $vform->get('freeshipping')->getData();

            $save = new IcirelaisFreeshipping();
            $save->setActive(!empty($data))->save();
            $response = Response::create('');
        } catch (\Exception $e) {
            $response = JsonResponse::create(array("error"=>$e->getMessage()), 500);
        }

        return $response;
    }

    /**
     * @Route("/freeshipping_amount", name="_freeshipping_amount", methods="POST")
     */
    public function amountAction()
    {
        $form = $this->createForm(\DpdPickup\Form\FreeShippingAmount::getName());

        try {
            $vform = $this->validateForm($form);
            $data = $vform->get('amount')->getData();

            DpdPickup::setFreeShippingAmount($data);
        } catch (\Exception $e) {
            $form->setErrorMessage($e->getMessage());

            $this->getParserContext()->addForm($form);

            return $this->render(
                'module-configure',
                [
                    'module_code' => DpdPickup::getModuleCode(),
                    'current_tab' => "prices_slices_tab"
                ]
            );
        }

        return $this->generateRedirect(
            URL::getInstance()->absoluteUrl('/admin/module/DpdPickup', ['current_tab' => 'prices_slices_tab'])
        );
    }
}
