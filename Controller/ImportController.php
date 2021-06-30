<?php

namespace DpdPickup\Controller;

use DpdPickup\DpdPickup;
use DpdPickup\Form\ImportExaprintForm;
use Propel\Runtime\Propel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Model\Map\OrderTableMap;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/dpdpickup/import", name="dpdpickup_import")
 * Class ImportController
 * @package DpdPickup\Controller
 * @author Etienne Perriere - OpenStudio <eperriere@openstudio.fr>
 */
class ImportController extends BaseAdminController
{
    /**
     * This function supposes that delivery ref is always in the 17th column
     * @Route("", name="_import", methods="POST")
     */
    public function importFile(EventDispatcherInterface $eventDispatcher)
    {
        $i = 0;

        $con = Propel::getWriteConnection(OrderTableMap::DATABASE_NAME);
        $con->beginTransaction();

        $form = $this->createForm(ImportExaprintForm::getName());

        try {
            $vForm = $this->validateForm($form);

            // Get file
            $importedFile = $vForm->getData()['import_file'];

            // Check extension
            if (!in_array(strtolower($importedFile->getClientOriginalExtension()), ['csv', 'txt'])) {
                throw new FormValidationException(
                    Translator::getInstance()->trans('Bad file format. Plain text or CSV expected.',
                    [],
                    DpdPickup::DOMAIN)
                );
            }

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
                        $this->importDeliveryRef($deliveryRef, $orderRef, $i, $eventDispatcher);
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
            return $this->generateRedirect(URL::getInstance()->absoluteUrl($form->getSuccessUrl(), ['current_tab' => 'import_exaprint']));
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
     * @param string    $deliveryRef
     * @param string    $orderRef
     * @param int       $i
     */
    public function importDeliveryRef($deliveryRef, $orderRef, &$i, EventDispatcherInterface $eventDispatcher)
    {
        // Get order
        $order = OrderQuery::create()
            ->findOneByRef($orderRef);

        // Check if the order exists
        if ($order !== null) {
            $event = new OrderEvent($order);

            // Check if delivery refs are different
            if ($order->getDeliveryRef() != $deliveryRef) {
                $event->setDeliveryRef($deliveryRef);
                $eventDispatcher->dispatch($event, TheliaEvents::ORDER_UPDATE_DELIVERY_REF);

                // Set 'sent' order status if not already sent
                if ($order->getStatusId() != DpdPickup::STATUS_SENT) {
                    $event->setStatus(DpdPickup::STATUS_SENT);
                    $eventDispatcher->dispatch($event, TheliaEvents::ORDER_UPDATE_STATUS);
                }

                $i++;
            }
        }
    }
}
