<?php

namespace DpdPickup\Controller;

use Propel\Runtime\Map\TableMap;
use DpdPickup\Model\DpdpickupPrice;
use DpdPickup\Model\DpdpickupPriceQuery;
use DpdPickup\DpdPickup;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;

class SliceController extends BaseAdminController
{
    public function deleteSliceAction()
    {
        $response = $this->checkAuth([], ['DpdPickup'], AccessManager::DELETE);

        if (null !== $response) {
            return $response;
        }

        $this->checkXmlHttpRequest();

        $responseData = [
            "success" => false,
            "message" => '',
            "slice" => null
        ];
        $response = null;

        try {
            $requestData = $this->getRequest()->request;

            if (0 !== $id = intval($requestData->get('id'))) {
                $slice = DpdpickupPriceQuery::create()->findPk($id);
                $slice->delete();
                $responseData['success'] = true;
            } else {
                $responseData['message'] = $this->getTranslator()->trans(
                    'The slice has not been deleted',
                    [],
                    dpdpickup::DOMAIN
                );
            }
        } catch (\Exception $e) {
            $responseData['message'] = $e->getMessage();
        }
        return $this->generateRedirectFromRoute(
            "admin.module.configure",
            [],
            [
                'module_code'=>"DpdPickup",
                'current_tab'=>"price_slices_tab",
                '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureAction'
            ]);
    }

    public function saveSliceAction()
    {
        $response = $this->checkAuth([], ['DpdPickup'], AccessManager::UPDATE);

        if (null !== $response) {
            return $response;
        }

        $this->checkXmlHttpRequest();

        $responseData = [
            "success" => false,
            "message" => '',
            "slice" => null
        ];

        $messages = [];
        $response = null;

        try {
            $requestData = $this->getRequest()->request;
            if (0 !== $id = intval($requestData->get('id', 0)))
            {
                $slice = DpdpickupPriceQuery::create()->findPk($id);
            }
            else
            {
                $slice = new DpdpickupPrice();
            }

            if (0 !== $areaId = intval($requestData->get('area', 0)))
            {
                $slice->setAreaId($areaId);
            }
            else
            {
                $messages[] = $this->getTranslator()->trans(
                    'The area is not valid',
                    [],
                    dpdpickup::DOMAIN
                );
            }

            $requestWeight= $requestData->get('weight', null);

            if (!empty($requestWeight)) {
                $weight= $this->getFloatVal($requestWeight);
                if ((0 < $weight) || ( $requestWeight != $weight)) {
                    $slice->setWeight($weight);
                } else {
                    $messages[] = $this->getTranslator()->trans(
                        'The weight value is not valid',
                        [],
                        dpdpickup::DOMAIN
                    );
                }
            } else {
                $slice->setWeight(null);
            }

            $price = $this->getFloatVal($requestData->get('price', 0));
            if (0 <= $price) {
                $slice->setPrice($price);
            } else {
                $messages[] = $this->getTranslator()->trans(
                    'The price value is not valid',
                    [],
                    dpdpickup::DOMAIN
                );
            }

            if (0 === count($messages)) {
                $slice->save();
                $messages[] = $this->getTranslator()->trans(
                    'Your slice has been saved',
                    [],
                    dpdpickup::DOMAIN
                );

                $responseData['success'] = true;
                $responseData['slice'] = $slice->toArray(TableMap::TYPE_STUDLYPHPNAME);
            }
        } catch (\Exception $e) {
            $messages[] = $e->getMessage();
        }

        $responseData['message'] = $messages;


        return $this->generateRedirectFromRoute(
            "admin.module.configure",
            [],
            [
                'module_code'=>"DpdPickup",
                'current_tab'=>"price_slices_tab",
                '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureAction'
            ]);
    }
    protected function getFloatVal($val, $default = -1)
    {
        if (preg_match("#^([0-9\.,]+)$#", $val, $match)) {
            $val = $match[0];
            if (strstr($val, ",")) {
                $val = str_replace(".", "", $val);
                $val = str_replace(",", ".", $val);
            }
            $val = floatval($val);

            return $val;
        }

        return $default;
    }
}
