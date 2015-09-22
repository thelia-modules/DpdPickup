<?php

namespace DpdPickup\Controller;

use DpdPickup\DpdPickup;
use Propel\Runtime\Propel;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Model\Map\OrderTableMap;
use Thelia\Model\OrderQuery;

class ImportController extends BaseAdminController
{
    /**
     * This function supposes that delivery ref is always in the 17th column
     */
    public function importFile()
    {
        $i = 0;

        $con = Propel::getWriteConnection(OrderTableMap::DATABASE_NAME);
        $con->beginTransaction();

        $form = $this->createForm('dpdpickup.import');

        try {
            $vForm = $this->validateForm($form);

            // Get file and parse it
            $importedFile = $vForm->getData()['import_file'];
            $csvData = file_get_contents($importedFile);
            $lines = explode(PHP_EOL, $csvData);

            // For each line, parse columns
            foreach ($lines as $line) {

                $parsedLine = str_getcsv($line, "\t");

                // Check if there are enough columns to include order ref
                if (count($parsedLine) > DpdPickup::ORDER_REF_COLUMN) {

                    // Get delivery and order ref
                    $deliveryRef = $parsedLine[DpdPickup::DELIVERY_REF_COLUMN];
                    $orderRef = $parsedLine[DpdPickup::ORDER_REF_COLUMN];

                    // Save delivery ref if there is one
                    if (!empty($deliveryRef)) {
                        $i = $this->importDeliveryRef($deliveryRef, $orderRef, $i);
                    }
                }
            }

            $con->commit();

            // Get number of affected rows to display
            $this->getSession()->getFlashBag()->add(
                'update-orders-result',
                Translator::getInstance()->trans(
                    'Operation successful. %i orders affected.',
                    ['%i' => $i],
                    DpdPickup::DOMAIN
                )
            );

            // Redirect
            return new RedirectResponse($form->getSuccessUrl());

        } catch (FormValidationException $e) {
            $con->rollback();

            $this->setupFormErrorContext(
                null,
                $e->getMessage(),
                $form
            );

            return $this->render(
                'module-configure',
                [
                    'module_code' => DpdPickup::getModuleCode(),
                    'current_tab' => 'import_exaprint'
                ]
            );
        }
    }

    /**
     * Update order's delivery ref
     *
     * @param $deliveryRef
     * @param $orderRef
     * @param $i
     * @return mixed
     */
    public function importDeliveryRef($deliveryRef, $orderRef, $i)
    {
        // Get order
        $order = OrderQuery::create()
            ->findOneByRef($orderRef);

        // Check if the order exists and delivery refs are different
        if ($order !== NULL) {
            if ($order->getDeliveryRef() != $deliveryRef) {
                $event = new OrderEvent($order);
                $event->setDeliveryRef($deliveryRef);
                $this->getDispatcher()->dispatch(TheliaEvents::ORDER_UPDATE_DELIVERY_REF, $event);

                $i++;
            }
        }

        return $i;
    }
}