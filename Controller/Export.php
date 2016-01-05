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
use Thelia\Model\AddressQuery;
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
    // FONCTION POUR LE FICHIER D'EXPORT BY Maitre eroudeix@openstudio.fr
    // extended by bperche9@gmail.com
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
        if (is_readable(ExportExaprint::getJSONpath())) {
            $admici = json_decode(file_get_contents(ExportExaprint::getJSONpath()), true);
            $keys = array("name", "addr", "zipcode", "city", "tel", "mobile", "mail", "expcode");
            $valid = true;
            foreach ($keys as $key) {
                $valid &= isset($admici[$key]) && ($key === "assur" ? true : !empty($admici[$key]));
            }
            if (!$valid) {
                return Response::create(
                    Translator::getInstance()->trans(
                        "The file DpdPickup/Config/exportdat.json is not valid. Please correct it.",
                        [],
                        DpdPickup::DOMAIN
                    ),
                    500
                );
            }
        } else {
            return Response::create(
                Translator::getInstance()->trans(
                    "Can't read DpdPickup/Config/exportdat.json. Did you save the export information ?",
                    [],
                    DpdPickup::DOMAIN
                ),
                500
            );
        }
        $exp_name = $admici['name'];
        $exp_address1 = $admici['addr'];
        $exp_address2 = isset($admici['addr2']) ? $admici['addr2'] : "";
        $exp_zipcode = $admici['zipcode'];
        $exp_city = $admici['city'];
        $exp_phone = $admici['tel'];
        $exp_cellphone = $admici['mobile'];
        $exp_email = $admici['mail'];
        $exp_code = $admici['expcode'];
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
        foreach ($orders as $order) {

            $orderRef = str_replace(".", "-", $order->getRef());

            if ($vform->get($orderRef)->getData()) {

                // Get if the package is assured, how many packages there are & their weight
                $assur_package = $vform->get($orderRef . "-assur")->getData();
                $pkgNumber = $vform->get($orderRef . '-pkgNumber')->getData();
                $pkgWeight = $vform->get($orderRef . '-pkgWeight')->getData();

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
                //Get OrderAddress object - customer's address
                $address = OrderAddressQuery::create()
                    ->findPK($order->getInvoiceOrderAddressId());

                //Get Customer object
                $customer = CustomerQuery::create()
                    ->findPK($order->getCustomerId());

                //Get OrderAddressDpdPickup object
                $icirelais_code = OrderAddressIcirelaisQuery::create()
                    ->findPK($order->getDeliveryOrderAddressId());
                if ($icirelais_code !== null) {

                    // Get Customer's cellphone
                    $cellphone = AddressQuery::create()
                        ->filterByCustomerId($order->getCustomerId())
                        ->filterByIsDefault("1")
                        ->findOne()
                        ->getCellphone();

                    //Weigth & price calc
                    $price = 0;
                    $price = $order->getTotalAmount($price, false); // tax = 0 && include postage = flase

                    $pkgWeight = floor($pkgWeight * 100);

                    $assur_price = ($assur_package == 'true') ? $price : 0;
                    $date_format = date("d/m/y", $order->getUpdatedAt()->getTimestamp());

                    $res .= self::harmonise($order->getRef(), 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 2);
                    $res .= self::harmonise($pkgWeight, 'numeric', 8);
                    $res .= self::harmonise("", 'alphanumeric', 15);
                    $res .= self::harmonise($customer->getLastname(), 'alphanumeric', 35);
                    $res .= self::harmonise($customer->getFirstname(), 'alphanumeric', 35);
                    $res .= self::harmonise($address->getAddress2(), 'alphanumeric', 35);
                    $res .= self::harmonise($address->getAddress3(), 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 35);
                    $res .= self::harmonise($address->getZipcode(), 'alphanumeric', 10);
                    $res .= self::harmonise($address->getCity(), 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 10);
                    $res .= self::harmonise($address->getAddress1(), 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 10);
                    $res .= self::harmonise(
                        "F",
                        'alphanumeric',
                        3
                    );                                // CODE PAYS DESTINATAIRE PAR DEFAUT F
                    $res .= self::harmonise($address->getPhone(), 'alphanumeric', 30);
                    $res .= self::harmonise("", 'alphanumeric', 15);
                    $res .= self::harmonise($exp_name, 'alphanumeric', 35);                        // DEBUT EXPEDITEUR
                    $res .= self::harmonise($exp_address2, 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 140);
                    $res .= self::harmonise($exp_zipcode, 'alphanumeric', 10);
                    $res .= self::harmonise($exp_city, 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 10);
                    $res .= self::harmonise($exp_address1, 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 10);
                    $res .= self::harmonise(
                        "F",
                        'alphanumeric',
                        3
                    );                                // CODE PAYS EXPEDITEUR PAR DEFAUT F
                    $res .= self::harmonise($exp_phone, 'alphanumeric', 30);
                    $res .= self::harmonise(
                        "",
                        'alphanumeric',
                        35
                    );                                // COMMENTAIRE 1 DE LA COMMANDE
                    $res .= self::harmonise(
                        "",
                        'alphanumeric',
                        35
                    );                                // COMMENTAIRE 2 DE LA COMMANDE
                    $res .= self::harmonise(
                        "",
                        'alphanumeric',
                        35
                    );                                // COMMENTAIRE 3 DE LA COMMANDE
                    $res .= self::harmonise(
                        "",
                        'alphanumeric',
                        35
                    );                                // COMMENTAIRE 3 DE LA COMMANDE
                    $res .= self::harmonise($date_format, 'alphanumeric', 10);
                    $res .= self::harmonise(
                        $exp_code,
                        'numeric',
                        8
                    );                                        // N° COMPTE CHARGEUR ICIRELAIS ?
                    $res .= self::harmonise("", 'alphanumeric', 35);                                // CODE BARRE
                    $res .= self::harmonise($customer->getRef(), 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 29);
                    $res .= self::harmonise(
                        $assur_price,
                        'float',
                        9
                    );                                // MONTANT DE LA VALEUR MARCHANDE A ASSURER EX: 20 euros -> 000020.00
                    $res .= self::harmonise("", 'alphanumeric', 8);
                    $res .= self::harmonise($customer->getId(), 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 46);
                    $res .= self::harmonise($exp_email, 'alphanumeric', 80);
                    $res .= self::harmonise($exp_cellphone, 'alphanumeric', 35);
                    $res .= self::harmonise($customer->getEmail(), 'alphanumeric', 80);
                    $res .= self::harmonise($cellphone, 'alphanumeric', 35);
                    $res .= self::harmonise("", 'alphanumeric', 96);
                    $res .= self::harmonise(
                        $icirelais_code->getCode(),
                        'alphanumeric',
                        8
                    );        // IDENTIFIANT ESPACE ICIRELAIS

                    $res .= "\r\n";
                }
            }
        }

        $response = new Response(
            utf8_decode(mb_strtoupper($res)),
            200,
            array(
                'Content-Type' => 'application/csv-tab-delimited-table;charset=iso-8859-1',
                'Content-disposition' => 'filename=export.dat'
            )
        );

        return $response;
    }
}
