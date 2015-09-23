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
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Translation\Translator;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\AccessManager;

/**
 * Class ExportExaprint
 * @package DpdPickup\Controller
 * @author Thelia <info@thelia.net>
 */
class ExportExaprint extends BaseAdminController
{
    public static function getJSONpath()
    {
        return __DIR__ . "/../Config/exportdat.json";
    }

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

            $file_path = self::getJSONpath();

            if ((file_exists($file_path) ? is_writable($file_path) : is_writable(__DIR__ . "/../Config/"))) {
                $file = fopen(self::getJSONpath(), 'w');
                fwrite(
                    $file,
                    json_encode(
                        array(
                            "name" => $vform->get('name')->getData(),
                            "addr" => $vform->get('addr')->getData(),
                            "addr2" => $vform->get('addr2')->getData(),
                            "zipcode" => $vform->get('zipcode')->getData(),
                            "city" => $vform->get('city')->getData(),
                            "tel" => $vform->get('tel')->getData(),
                            "mobile" => $vform->get('mobile')->getData(),
                            "mail" => $vform->get('mail')->getData(),
                            "expcode" => ($vform->get('expcode')->getData())
                        )
                    )
                );

                fclose($file);

                return $this->generateRedirectFromRoute(
                    "admin.module.configure",
                    [],
                    [
                        'module_code' => "DpdPickup",
                        'current_tab' => "configure_export_exaprint",
                        '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureAction'
                    ]
                );
            } else {
                throw new \Exception(
                    Translator::getInstance()->trans(
                        "Can't write DpdPickup/Config/exportdat.json. Please change the rights on the file and/or the directory.",
                        [],
                        DpdPickup::DOMAIN
                    )
                );
            }
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
}
