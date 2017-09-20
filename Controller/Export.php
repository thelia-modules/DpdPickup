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

use DpdPickup\Form\ExportExaprintSelection;
use DpdPickup\DpdPickup;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use DpdPickup\Model\OrderAddressIcirelaisQuery;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Log\Tlog;
use Thelia\Model\Order;
use Thelia\Model\OrderAddressQuery;
use Thelia\Model\OrderQuery;
use Thelia\Model\CustomerQuery;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\AccessManager;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

/**
 * Class Export
 * @package DpdPickup\Controller
 * @author Thelia <info@thelia.net>
 * @original_author etienne roudeix <eroudeix@openstudio.fr>
 * @contributor Etienne Perriere <eperriere@openstudio.fr>
 */
class Export extends BaseAdminController
{
    // L'arrivée de Maitre Guigit détrône les anciens maitres pour corriger le soucis de json qui se supprime à chaque composer install
    // Esclaves : Ex Maitre Roudeix @ Espeche
    public static function harmonise($value, $type, $len)
    {
        switch ($type) {
            case 'numeric':
                $value = (string)$value;
                if (mb_strlen($value, 'utf8') > $len) {
                    $value = substr($value, 0, $len);
                }
                for ($i = mb_strlen($value, 'utf8'); $i < $len; $i++) {
                    $value = '0' . $value;
                }
                break;
            case 'alphanumeric':
                $value = (string)$value;
                if (mb_strlen($value, 'utf8') > $len) {
                    $value = substr($value, 0, $len);
                }
                for ($i = mb_strlen($value, 'utf8'); $i < $len; $i++) {
                    $value .= ' ';
                }
                break;
            case 'float':
                if (!preg_match("#\d{1,6}\.\d{1,}#", $value)) {
                    $value = str_repeat("0", $len - 3) . ".00";
                } else {
                    $value = explode(".", $value);
                    $int = self::harmonise($value[0], 'numeric', $len - 3);
                    $dec = substr($value[1], 0, 2) . "." . substr($value[1], 2, strlen($value[1]));
                    $dec = (string)ceil(floatval($dec));
                    $dec = str_repeat("0", 2 - strlen($dec)) . $dec;
                    $value = $int . "." . $dec;
                }
                break;
        }

        return $value;
    }

    public function exportfile()
    {
        if (null !== $response = $this->checkAuth(
                array(AdminResources::MODULE),
                array('DpdPickup'),
                AccessManager::UPDATE
            )) {
            return $response;
        }

        $return_type = DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_TYPE, DpdPickup::RETURN_NONE);

        // Check required infos

        $keys = array(
            DpdPickup::KEY_EXPEDITOR_NAME,
            DpdPickup::KEY_EXPEDITOR_ADDR,
            DpdPickup::KEY_EXPEDITOR_ZIPCODE,
            DpdPickup::KEY_EXPEDITOR_CITY,
            DpdPickup::KEY_EXPEDITOR_TEL,
            DpdPickup::KEY_EXPEDITOR_MOBILE,
            DpdPickup::KEY_EXPEDITOR_MAIL,
            DpdPickup::KEY_EXPEDITOR_DPDCODE
        );

        if ($return_type != DpdPickup::RETURN_NONE) {
            $keys[] = DpdPickup::KEY_RETURN_NAME;
            $keys[] = DpdPickup::KEY_RETURN_ADDR;
            $keys[] = DpdPickup::KEY_RETURN_ZIPCODE;
            $keys[] = DpdPickup::KEY_RETURN_CITY;
            $keys[] = DpdPickup::KEY_RETURN_TEL;
        }

        $valid = true;
        foreach ($keys as $key) {
            if (null === DpdPickup::getConfigValue($key)) {
                $valid = false;
                break;
            }
        }

        if (!$valid) {
            return Response::create(
                Translator::getInstance()->trans(
                    "The EXAPRINT configuration is missing. Please correct it.",
                    [],
                    DpdPickup::DOMAIN
                ),
                500
            );
        }

        // Get configuration

        $exp_name = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_NAME);
        $exp_address1 = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_ADDR);
        $exp_address2 = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_ADDR2, '');
        $exp_zipcode = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_ZIPCODE);
        $exp_city = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_CITY);
        $exp_phone = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_TEL);
        $exp_cellphone = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_MOBILE);
        $exp_email = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_MAIL);
        $exp_code = DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_DPDCODE);


        if ($return_type != DpdPickup::RETURN_NONE) {
            $return_name = DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_NAME);
            $return_address1 = DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_ADDR);
            $return_address2 = DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_ADDR2, '');
            $return_zipcode = DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_ZIPCODE);
            $return_city = DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_CITY);
            $return_phone = DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_TEL);
        }
        
        $res = self::harmonise('$' . "VERSION=110", 'alphanumeric', 12) . "\r\n";

        $orders = OrderQuery::create()
            ->filterByDeliveryModuleId(DpdPickup::getModuleId())
            ->find();

        // FORM VALIDATION
        $form = new ExportExaprintSelection($this->getRequest());
        $status_id = null;
        try {
            $vform = $this->validateForm($form);
            $status_id = $vform->get("new_status_id")->getData();
            if (!preg_match("#^nochange|processing|sent$#", $status_id)) {
                throw new \Exception("Invalid status ID. Expecting nochange or processing or sent");
            }
        } catch (\Exception $e) {
            Tlog::getInstance()->error("Form dpdpickup.selection sent with bad infos. ");

            return Response::create(
                Translator::getInstance()->trans(
                    "Got invalid data : %err",
                    ['%err' => $e->getMessage()],
                    DpdPickup::DOMAIN
                ),
                500
            );
        }

        // For each selected order
        /** @var Order $order */
        foreach ($orders as $order) {
            $orderRef = str_replace(".", "-", $order->getRef());

            $collectionKey = array_search($orderRef, $vform->getData()['order_ref']);
            if (false !== $collectionKey
                && array_key_exists($collectionKey, $vform->getData()['order_ref_check'])
                && $vform->getData()['order_ref_check'][$collectionKey]) {

                // Get if the package is assured, how many packages there are & their weight
                $assur_package = array_key_exists($collectionKey, $vform->getData()['assur']) ? $vform->getData()['assur'][$collectionKey] : false;
                // $pkgNumber = array_key_exists($collectionKey, $vform->getData()['pkgNumber']) ? $vform->getData()['pkgNumber'][$collectionKey] : null;
                $pkgWeight = array_key_exists($collectionKey, $vform->getData()['pkgWeight']) ? $vform->getData()['pkgWeight'][$collectionKey] : null;

                // Check if status has to be changed
                if ($status_id == "processing") {
                    $event = new OrderEvent($order);
                    $status = OrderStatusQuery::create()
                        ->findOneByCode(OrderStatus::CODE_PROCESSING);
                    $event->setStatus($status->getId());
                    $this->getDispatcher()->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
                } elseif ($status_id == "sent") {
                    $event = new OrderEvent($order);
                    $status = OrderStatusQuery::create()
                        ->findOneByCode(OrderStatus::CODE_SENT);
                    $event->setStatus($status->getId());
                    $this->getDispatcher()->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
                }

                //Get invoice address
                $address = OrderAddressQuery::create()
                    ->findPk($order->getInvoiceOrderAddressId());

                //Get Customer object
                $customer = CustomerQuery::create()
                    ->findPk($order->getCustomerId());

                //Get OrderAddressDpdPickup object
                $icirelais_code = OrderAddressIcirelaisQuery::create()
                    ->findPk($order->getDeliveryOrderAddressId());

                if ($icirelais_code !== null) {
                    // Get Customer's cellphone
                    if (null == $cellphone = $address->getCellphone()) {
                        $cellphone = $address->getPhone();
                    }

                    //Weight & price calc
                    $price = 0;
                    $price = $order->getTotalAmount($price, false); // tax = 0 && include postage = false

                    $pkgWeight = floor($pkgWeight * 100);

                    $assur_price = ($assur_package == 'true') ? $price : 0;
                    $date_format = date("d/m/Y", $order->getUpdatedAt()->getTimestamp());


                    // Delivery address

                    $res .= self::harmonise($order->getRef(), 'alphanumeric', 35);              // 1. Customer ref #1 = Order ref | MANDATORY
                    $res .= self::harmonise("", 'alphanumeric', 2);                             // 2. Filler
                    $res .= self::harmonise($pkgWeight, 'numeric', 8);                          // 3. Package weight
                    $res .= self::harmonise("", 'alphanumeric', 15);                            // 4. Filler
                    $res .= self::harmonise($address->getLastname(), 'alphanumeric', 35);       // 5. Delivery name | MANDATORY
                    $res .= self::harmonise($address->getFirstname(), 'alphanumeric', 35);      // 6. Delivery firstname
                    $res .= self::harmonise($address->getAddress2(), 'alphanumeric', 35);       // 7. Delivery address 2
                    $res .= self::harmonise($address->getAddress3(), 'alphanumeric', 35);       // 8. Delivery address 3
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 9. Delivery address 4 | SKIPPED
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 10. Delivery address 5 | SKIPPED
                    $res .= self::harmonise($address->getZipcode(), 'alphanumeric', 10);        // 11. Delivery zipcode | MANDATORY
                    $res .= self::harmonise($address->getCity(), 'alphanumeric', 35);           // 12. Delivery city | MANDATORY
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 13. Filler
                    $res .= self::harmonise($address->getAddress1(), 'alphanumeric', 35);       // 14. Delivery street | MANDATORY
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 15. Filler
                    $res .= self::harmonise("F", 'alphanumeric', 3);                            // 16. Delivery country code | MANDATORY
                    $res .= self::harmonise($address->getPhone(), 'alphanumeric', 20);          // 17. Delivery phone


                    // Expeditor address

                    $res .= self::harmonise("", 'alphanumeric', 25);                            // 18. Filler
                    $res .= self::harmonise($exp_name, 'alphanumeric', 35);                     // 19. Expeditor name
                    $res .= self::harmonise($exp_address2, 'alphanumeric', 35);                 // 20. Expeditor address
                    $res .= self::harmonise("", 'alphanumeric', 140);                           // 21-24. Filler
                    $res .= self::harmonise($exp_zipcode, 'alphanumeric', 10);                  // 25. Expeditor zipcode
                    $res .= self::harmonise($exp_city, 'alphanumeric', 35);                     // 26. Expeditor city
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 27. Filler
                    $res .= self::harmonise($exp_address1, 'alphanumeric', 35);                 // 28. Expeditor street
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 29. Filler
                    $res .= self::harmonise("F", 'alphanumeric', 3);                            // 30. Expeditor country code
                    $res .= self::harmonise($exp_phone, 'alphanumeric', 20);                    // 31. Expeditor phone
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 32. Filler
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 33. Order comment 1
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 34. Order comment 2
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 35. Order comment 3
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 36. Order comment 4
                    $res .= self::harmonise($date_format, 'alphanumeric', 10);                  // 37. Expedition date
                    $res .= self::harmonise($exp_code, 'numeric', 8);                           // 38. Expeditor DPD code
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 39. Bar code
                    $res .= self::harmonise($customer->getRef(), 'alphanumeric', 35);           // 40. Customer ref #2
                    $res .= self::harmonise("", 'alphanumeric', 29);                            // 41. Filler
                    $res .= self::harmonise($assur_price, 'float', 9);                          // 42. Insured value
                    $res .= self::harmonise("", 'alphanumeric', 8);                             // 43. Filler
                    $res .= self::harmonise($customer->getId(), 'alphanumeric', 35);            // 44. Customer ref #3
                    $res .= self::harmonise("", 'alphanumeric', 1);                             // 45. Filler
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 46. Consolidation number | SKIPPED
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 47. Filler
                    $res .= self::harmonise($exp_email, 'alphanumeric', 80);                    // 48. Expeditor email
                    $res .= self::harmonise($exp_cellphone, 'alphanumeric', 35);                // 49. Expeditor cellphone
                    $res .= self::harmonise($customer->getEmail(), 'alphanumeric', 80);         // 50. Customer email
                    $res .= self::harmonise($cellphone, 'alphanumeric', 35);                    // 51. Customer cellphone
                    $res .= self::harmonise("", 'alphanumeric', 96);                            // 52. Filler
                    $res .= self::harmonise($icirelais_code->getCode(), 'alphanumeric', 8);     // 53. DPD relay ID
                    $res .= self::harmonise("", 'alphanumeric', 113);                           // 54. Filler
                    $res .= self::harmonise("", 'alphanumeric', 2);                             // 55. Consolidation type | SKIPPED
                    $res .= self::harmonise("", 'alphanumeric', 2);                             // 56. Consolidation attribute | SKIPPED
                    $res .= self::harmonise("", 'alphanumeric', 1);                             // 57. Filler
                    $res .= self::harmonise("", 'numeric', 1);                                  // 58. Predict | SKIPPED
                    $res .= self::harmonise("", 'alphanumeric', 35);                            // 59. Contact name | SKIPPED
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 60. Digicode1 | SKIPPED
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 61. Digicode2 | SKIPPED
                    $res .= self::harmonise("", 'alphanumeric', 10);                            // 62. Intercom | SKIPPED


                    // Return address

                    if ($return_type != DpdPickup::RETURN_NONE) {
                        $res .= self::harmonise("", 'alphanumeric', 200);                           // 63. Filler
                        $res .= self::harmonise($return_type, 'numeric', 1);                        // 64. Return type
                        $res .= self::harmonise("", 'alphanumeric', 15);                            // 65. Filler
                        $res .= self::harmonise($return_name, 'alphanumeric', 35);                  // 66. Return name
                        $res .= self::harmonise($return_address2, 'alphanumeric', 35);              // 67. Return address 1
                        $res .= self::harmonise("", 'alphanumeric', 35);                            // 68. Return address 2 | SKIPPED
                        $res .= self::harmonise("", 'alphanumeric', 35);                            // 69. Return address 3 | SKIPPED
                        $res .= self::harmonise("", 'alphanumeric', 35);                            // 70. Return address 4 | SKIPPED
                        $res .= self::harmonise("", 'alphanumeric', 35);                            // 71. Return address 5 | SKIPPED
                        $res .= self::harmonise($return_zipcode, 'alphanumeric', 10);               // 72. Return zipcode
                        $res .= self::harmonise($return_city, 'alphanumeric', 35);                  // 73. Return city
                        $res .= self::harmonise("", 'alphanumeric', 10);                            // 74. Filler
                        $res .= self::harmonise($return_address1, 'alphanumeric', 35);              // 75. Return street
                        $res .= self::harmonise("", 'alphanumeric', 10);                            // 76. Filler
                        $res .= self::harmonise("F", 'alphanumeric', 3);                            // 77. Return country code
                        $res .= self::harmonise($return_phone, 'alphanumeric', 30);                 // 78. Return phone
                        $res .= self::harmonise("", 'alphanumeric', 18);                            // 79. CargoID | SKIPPED
                        $res .= self::harmonise("", 'alphanumeric', 35);                            // 80. Customer ref #4 | SKIPPED
                    }

                    $res .= "\r\n";
                }
            }
        }

        $response = new Response(
            utf8_decode($res),
            200,
            array(
                'Content-Type' => 'application/csv-tab-delimited-table;charset=iso-8859-1',
                'Content-disposition' => 'filename=export.dat'
            )
        );

        return $response;
    }
}
