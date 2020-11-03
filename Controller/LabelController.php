<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 02/11/2020
 * Time: 13:10
 */

namespace DpdPickup\Controller;


use DpdPickup\DpdPickup;
use DpdPickup\Model\DpdpickupLabels;
use DpdPickup\Model\DpdpickupLabelsQuery;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Model\ConfigQuery;
use Thelia\Model\CountryQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderAddressQuery;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;

class LabelController extends BaseAdminController
{
    public function showAction()
    {
        $err = $this->getRequest()->get("err");
        return $this->render('dpdpickup-labels',[
            "err" => $err
        ]);
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function saveAction()
    {
        $orderId = $this->getRequest()->get("orderId");

        $labelDir = DpdPickup::DPD_LABEL_DIR;

        $fileSystem = new Filesystem();

        if (! $fileSystem->exists($labelDir)){
            $fileSystem->mkdir($labelDir, 0777);
        }
        $order = OrderQuery::create()->filterById($orderId)->findOne();

        $labelName = $labelDir.DS.$order->getRef().".pdf";

        $err = null;

        if (!$label = DpdpickupLabelsQuery::create()->filterByOrderId($order->getId())->findOne()) {
            $err = $this->createLabel($order, $labelName);


            if ($err) {
                return $this->generateRedirect(URL::getInstance()->absoluteUrl("admin/module/DpdPickup/labels", [
                    "err" => $err
                ]));
            }

            $params = ['file' => base64_encode($labelName)];

            return $this->generateRedirect(URL::getInstance()->absoluteUrl('admin/module/DpdPickup/labels', $params));

        }

        return $this->downloadAction(base64_encode($labelName));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function generateLabelAction()
    {
        $orderId = $this->getRequest()->get("orderId");

        $order = OrderQuery::create()->filterById($orderId)->findOne();

        $labelName = DpdPickup::DPD_LABEL_DIR . DS . $order->getRef() . ".pdf";

        $err = $this->createLabel($order, $labelName);

        if($err){
            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/order/update/'.$orderId, [
                "err" => $err
            ]));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/order/update/'.$orderId));
    }


    public function downloadAction($base64EncodedFilename)
    {
        $fileName = base64_decode($base64EncodedFilename);

        if (file_exists($fileName)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileName));
            readfile($fileName);
        } else {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/DpdPickup/labels"));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/DpdPickup/labels"));
    }

    public function getLabelAction($orderRef)
    {
        if (null !== $response = $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return $response;
        }

        $labelDir = DpdPickup::DPD_LABEL_DIR;

        $file = $labelDir . DS . $orderRef.".pdf";

        $response = new BinaryFileResponse($file);

        return $response;
    }

    /**
     * @return mixed|\Symfony\Component\HttpFoundation\Response
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function deleteLabelAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return $response;
        }

        $orderId = $this->getRequest()->get("orderId");

        $labelDir = DpdPickup::DPD_LABEL_DIR;

        $label = DpdpickupLabelsQuery::create()->filterByOrderId($orderId)->findOne();

        $fs = new Filesystem();

        $fs->remove($labelDir.DS.$label->getOrder().".pdf");

        $label->delete();

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($this->getRequest()->get("redirect_url")));

    }

    /**
     * @param Order $order
     * @param $labelName
     * @return null|string
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function createLabel(Order $order, $labelName)
    {
        $data = $this->writeData($order);

        $DpdWSD = DpdPickup::DPD_WSDL;

        if(1 === (int)DpdPickup::getConfigValue(DpdPickup::API_IS_TEST)){
            $DpdWSD = DpdPickup::DPD_WSDL_TEST;
        }

        $client = new \SoapClient($DpdWSD, array("trace" => 1, "exception" => 1, 'encoding'=>'ISO-8859-1'));

        try{
            $header = new \SoapHeader('http://www.cargonet.software', 'UserCredentials', $data["Header"]);
            $client->__setSoapHeaders($header);
            $response = $client->CreateShipmentWithLabels(["request" => $data["Body"]]);
        }catch (\Exception $e){
            return $e->getMessage();
        }

        $shipments = $response->CreateShipmentWithLabelsResult->shipments->Shipment;
        $labels = $response->CreateShipmentWithLabelsResult->labels->Label;

        if (false === @file_put_contents($labelName, $labels[0]->label)) {
            return Translator::getInstance()->trans("L'étiquette n'a pas pu être sauvegardée dans $labelName", DpdPickup::DOMAIN);
        }

        $label = new DpdpickupLabels();
        $label
            ->setOrderId($order->getId())
            ->setLabelNumber($shipments->parcelnumber)
            ->save();

        return null;
    }

    /**
     * @param Order $order
     * @return mixed
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function writeData(Order $order)
    {

        $data = DpdPickup::getApiConfig();

        $shopCountry = CountryQuery::create()->filterById(ConfigQuery::create()->filterByName("store_country")->findOne()->getValue())->findOne();

        $ApiData["Header"] = [
            "userid" => $data['userId'],
            "password" => $data['password']
        ];

        $deliveryAddress = OrderAddressQuery::create()->filterById($order->getDeliveryOrderAddressId())->findOne();

        $receiveraddress = [
            'name' => $deliveryAddress->getFirstname().' '.$deliveryAddress->getLastname(),
            'countryPrefix' => $deliveryAddress->getCountry()->getIsoalpha2(),
            'city' => $deliveryAddress->getCity(),
            'zipCode' => $deliveryAddress->getZipcode(),
            'street' => $deliveryAddress->getAddress1(),
            'phoneNumber' => $deliveryAddress->getPhone() ?: "x",
            'faxNumber' => '',
            'geoX' => '',
            'geoY' => ''
        ];

        $shipperaddress = [
            'name' => $data['shipperName'],
            'countryPrefix' => $data['shipperCountry'],
            'city' => $data['shipperCity'],
            'zipCode' => $data['shipperZipCode'],
            'street' => $data['shipperAddress1'],
            'phoneNumber' => $data['shipperPhone'],
            'faxNumber' => $data['shipperFax'],
            'geoX' => '',
            'geoY' => ''
        ];

        $label = array(
            'type'=>'PDF',
        );

        $ApiData["Body"] = [
            "customer_countrycode" => (int)$shopCountry->getIsocode(),
            "customer_centernumber" => (int)$data['center_number'],
            "customer_number" => (int)$data['customer_number'],
            "receiveraddress" => $receiveraddress,
            "shipperaddress" => $shipperaddress,
            "weight" => $order->getWeight(),
            "referencenumber" => $order->getRef(),
            "labelType" => $label
        ];

        return $ApiData;
    }
}