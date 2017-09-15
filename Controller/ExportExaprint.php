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
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.     */
/*                                                                                   */
/*************************************************************************************/

namespace DpdPickup\Controller;

use DpdPickup\Form\ExportExaprintForm;
use DpdPickup\DpdPickup;
use DpdPickup\Form\ExportExaprintFormReturn;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Translation\Translator;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\AccessManager;
use Thelia\Model\Exception\InvalidArgumentException;

/**
 * Class ExportExaprint
 * @package DpdPickup\Controller
 * @author Thelia <info@thelia.net>
 */
class ExportExaprint extends BaseAdminController
{
    public function export()
    {
        if (null !== $response = $this->checkAuth(
                array(AdminResources::MODULE),
                array('DpdPickup'),
                AccessManager::UPDATE
            )) {
            return $response;
        }

        $form = new ExportExaprintForm($this->getRequest());
        $error_message = null;
        try {
            $vform = $this->validateForm($form);

            $return_type = $vform->get('return_type')->getData();

            if (!$this->isCorrectReturnType($return_type)) {
                throw new InvalidArgumentException("Incorrect return code.");
            }

            if ($return_type == DpdPickup::RETURN_ON_DEMAND || $return_type == DpdPickup::RETURN_PREPARED) {
                $this->checkFormReturnFields($vform);
                DpdPickup::setConfigValue(DpdPickup::KEY_RETURN_NAME, $vform->get('return_name')->getData());
                DpdPickup::setConfigValue(DpdPickup::KEY_RETURN_ADDR, $vform->get('return_addr')->getData());
                DpdPickup::setConfigValue(DpdPickup::KEY_RETURN_ADDR2, $vform->get('return_addr2')->getData());
                DpdPickup::setConfigValue(DpdPickup::KEY_RETURN_ZIPCODE, $vform->get('return_zipcode')->getData());
                DpdPickup::setConfigValue(DpdPickup::KEY_RETURN_CITY, $vform->get('return_city')->getData());
                DpdPickup::setConfigValue(DpdPickup::KEY_RETURN_TEL, $vform->get('return_tel')->getData());
            }

            DpdPickup::setConfigValue(DpdPickup::KEY_RETURN_TYPE, $return_type);

            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_NAME, $vform->get('exp_name')->getData());
            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_ADDR, $vform->get('exp_addr')->getData());
            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_ADDR2, $vform->get('exp_addr2')->getData());
            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_ZIPCODE, $vform->get('exp_zipcode')->getData());
            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_CITY, $vform->get('exp_city')->getData());
            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_TEL, $vform->get('exp_tel')->getData());
            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_MOBILE, $vform->get('exp_mobile')->getData());
            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_MAIL, $vform->get('exp_mail')->getData());
            DpdPickup::setConfigValue(DpdPickup::KEY_EXPEDITOR_DPDCODE, $vform->get('exp_dpdcode')->getData());


            return $this->generateRedirectFromRoute(
                "admin.module.configure",
                [],
                [
                    'module_code' => "DpdPickup",
                    'current_tab' => "configure_export_exaprint",
                    '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureAction'
                ]
            );
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
        }

        $this->setupFormErrorContext(
            'erreur export fichier exaprint',
            $error_message,
            $form
        );

        return $this->render(
            'module-configure',
            [
                'module_code' => DpdPickup::getModuleCode(),
                'current_tab' => "configure_export_exaprint"
            ]
        );
    }

    public function isCorrectReturnType($returnType)
    {
        return $returnType == DpdPickup::RETURN_NONE
            || $returnType == DpdPickup::RETURN_ON_DEMAND
            || $returnType == DpdPickup::RETURN_PREPARED;
    }

    /**
     * @param $vform \Symfony\Component\Form\Form
     */
    public function checkFormReturnFields($vform)
    {
        $requiredKeys = ['return_name', 'return_addr', 'return_zipcode', 'return_city', 'return_tel'];

        foreach ($requiredKeys as $key) {
            $field = $vform->get($key);
            if (empty($field->getData())) {
                $translatedFieldName = $this->getTranslator()->trans($field->getName(), [], DpdPickup::DOMAIN_ADMIN);
                throw new InvalidArgumentException($this->getTranslator()->trans("Empty value not allowed", [], DpdPickup::DOMAIN)." : ".$translatedFieldName);
            }
        }
    }
}
