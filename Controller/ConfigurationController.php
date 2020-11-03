<?php

namespace DpdPickup\Controller;

use DpdPickup\DpdPickup;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;

/**
 * Class ConfigurationController
 * @package DpdPickup\Controller
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
class ConfigurationController extends BaseAdminController
{
    public function configureAction()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['DpdPickup'], [AccessManager::CREATE, AccessManager::UPDATE])) {
            return $response;
        }

        $baseForm = $this->createForm("dpdpickup.config.form");

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

    public function configureApiAction(){
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['DpdPickup'], [AccessManager::CREATE, AccessManager::UPDATE])) {
            return $response;
        }

        $baseForm = $this->createForm("dpdpickup.api.configuration.form");

        $errorMessage = null;

        try {
            $form = $this->validateForm($baseForm);
            $data = $form->getData();

            DpdPickup::setConfigValue(DpdPickup::API_USER_ID, $data["user_id"]);
            DpdPickup::setConfigValue(DpdPickup::API_PASSWORD, $data["password"]);
            DpdPickup::setConfigValue(DpdPickup::API_CENTER_NUMBER, $data["center_number"]);
            DpdPickup::setConfigValue(DpdPickup::API_CUSTOMER_NUMBER, $data["customer_number"]);
            DpdPickup::setConfigValue(DpdPickup::API_IS_TEST, $data["isTest"]);
            DpdPickup::setConfigValue(DpdPickup::API_SHIPPER_NAME, $data["shipper_name"]);
            DpdPickup::setConfigValue(DpdPickup::API_SHIPPER_ADDRESS1, $data["shipper_address1"]);
            DpdPickup::setConfigValue(DpdPickup::API_SHIPPER_COUNTRY, $data["shipper_country"]);
            DpdPickup::setConfigValue(DpdPickup::API_SHIPPER_CITY, $data["shipper_city"]);
            DpdPickup::setConfigValue(DpdPickup::API_SHIPPER_ZIP, $data["shipper_zip_code"]);
            DpdPickup::setConfigValue(DpdPickup::API_SHIPPER_PHONE, $data["shipper_phone"]);
            DpdPickup::setConfigValue(DpdPickup::API_SHIPPER_FAX, $data["shipper_fax"]);

        } catch (FormValidationException $ex) {
            $errorMessage = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            $errorMessage = $this->getTranslator()->trans('Sorry, an error occurred: %err', ['%err' => $ex->getMessage()], DpdPickup::DOMAIN);
        }


        if ($errorMessage !== null) {

            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error while updating api configurations",
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
                'current_tab' => "api_config",
                '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureApiAction'
            ]
        );
    }
}