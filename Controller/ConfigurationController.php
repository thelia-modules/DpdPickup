<?php

namespace DpdPickup\Controller;

use DpdPickup\DpdPickup;
use DpdPickup\Form\ConfigurationForm;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConfigurationController
 * @package DpdPickup\Controller
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */


/**
 * @Route("/admin/module/dpdpickup/config", name="dpdpickup_config")
 */
class ConfigurationController extends BaseAdminController
{

    /**
     * @Route("", name="_save", methods="POST")
     */
    public function configureAction()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['DpdPickup'], [AccessManager::CREATE, AccessManager::UPDATE])) {
            return $response;
        }

        $baseForm = $this->createForm(ConfigurationForm::getName());

        $errorMessage = null;

        try {
            $form = $this->validateForm($baseForm);
            $data = $form->getData();

            // Save data
            DpdPickup::setConfigValue('default_status', $data["default_status"]);
            DpdPickup::setConfigGoogleMapKey($data["google_map_key"]);
            DpdPickup::setConfigExcludeZipCode($data["exclude_zip_code"]);

        } catch (FormValidationException $ex) {
            $errorMessage = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            $errorMessage = $this->getTranslator()->trans('Sorry, an error occurred: %err', ['%err' => $ex->getMessage()], DpdPickup::DOMAIN);
        }

        if ($errorMessage !== null) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error while updating status",
                    [],
                    DpdPickup::DOMAIN
                ),
                $errorMessage,
                $baseForm
            );
        }

        return $this->generateRedirectFromRoute(
            "admin.module.configure",
            [],
            [
                'module_code' => "DpdPickup",
                'current_tab' => "config",
                '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureAction'
            ]
        );
    }
}