<?php

namespace DpdPickup\Controller;

use DpdPickup\DpdPickup;
use DpdPickup\Model\DpdpickupPrice;
use DpdPickup\Model\DpdpickupPriceQuery;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;

class SliceController extends BaseAdminController
{
    public function deleteSliceAction()
    {
        if (null !== $response =
                $this->checkAuth(
                    [],
                    ['DpdPickup'],
                    [AccessManager::DELETE]
                )
        ) {
            return $response;
        }
        $message = [];

        try {
            if (0 !== $id = intval($this->getRequest()->request->get('id'))) {
                $slice = DpdpickupPriceQuery::create()->findPk($id);
                if (null !== $slice) {
                    $slice->delete();
                }
            } else {
                $message[] = $this->getTranslator()->trans(
                    'The slice has not been deleted',
                    [],
                    dpdpickup::DOMAIN
                );
            }
        } catch (\Exception $e) {
            $message[] = $e->getMessage();
        }

        return $this->generateRedirectFromRoute(
            "admin.module.configure",
            [],
            [
                'module_code'=>"DpdPickup",
                'current_tab'=>"price_slices_tab",
                '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureAction'
            ]
        );
    }

    public function saveSliceAction()
    {
        if (null !== $response =
                $this->checkAuth(
                    [],
                    ['DpdClassic'],
                    [AccessManager::UPDATE]
                )
        ) {
            return $response;
        }
        $message = [];

        try {
            $requestData = $this->getRequest()->request;

            if ((0 !== $id = intval($requestData->get('id', 0)))) {
                $slice = DpdpickupPriceQuery::create()->findPk($id);
            } else {
                $slice = new DpdpickupPrice();
            }

            if (0 !== $areaId = intval($requestData->get('area', 0))) {
                $slice->setAreaId($areaId);
            } else {
                $message[] = $this->getTranslator()->trans(
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
                    $message[] = $this->getTranslator()->trans(
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
                $message[] = $this->getTranslator()->trans(
                    'The price value is not valid',
                    [],
                    dpdpickup::DOMAIN
                );
            }

            if (0 === count($message)) {
                $slice->save();
                $message[] = $this->getTranslator()->trans(
                    'Your slice has been saved',
                    [],
                    dpdpickup::DOMAIN
                );
            }
        } catch (\Exception $e) {
            $messages[] = $e->getMessage();
        }

        return $this->generateRedirectFromRoute(
            "admin.module.configure",
            [],
            [
                'module_code'=>"DpdPickup",
                'current_tab'=>"price_slices_tab",
                '_controller' => 'Thelia\\Controller\\Admin\\ModuleController::configureAction'
            ]
        );
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
