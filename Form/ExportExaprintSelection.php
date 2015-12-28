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

namespace DpdPickup\Form;

use DpdPickup\DpdPickup;
use Thelia\Form\BaseForm;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;

/**
 * Class ExportExaprintSelection
 * @package DpdPickup\Form
 * @author Thelia <info@thelia.net>
 */
class ExportExaprintSelection extends BaseForm
{
    public function getName()
    {
        return "exportexaprintselection";
    }

    protected function buildForm()
    {
        $entries = OrderQuery::create()
            ->filterByDeliveryModuleId(DpdPickup::getModuleId())
            ->find();

        $this->formBuilder
            ->add(
                'new_status_id',
                'choice',
                array(
                    'label' => Translator::getInstance()->trans('Change order status to', [], DpdPickup::DOMAIN),
                    'choices' => array(
                        "nochange" => Translator::getInstance()->trans("Do not change", [], DpdPickup::DOMAIN),
                        "processing" => Translator::getInstance()->trans("Set orders status as processing", [], DpdPickup::DOMAIN),
                        "sent" => Translator::getInstance()->trans("Set orders status as sent", [], DpdPickup::DOMAIN)
                    ),
                    'required' => true,
                    'expanded' => true,
                    'multiple' => false,
                    'data' => 'nochange'
                )
            );

        foreach ($entries as $order) {
            $orderRef = str_replace(".", "-", $order->getRef());

            $this->formBuilder
                ->add(
                    $orderRef,
                    'checkbox',
                    array(
                        'label' => $orderRef,
                        'label_attr' => array(
                            'for' => $orderRef
                        )
                    )
                )
                ->add(
                    $orderRef . "-assur",
                    'checkbox'
                )
                ->add(
                    $orderRef . "-pkgNumber",
                    'number'
                )
                ->add(
                    $orderRef . "-pkgWeight",
                    'number'
                );
        }
    }
}
