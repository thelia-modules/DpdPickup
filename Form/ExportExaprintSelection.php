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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
    public static function getName()
    {
        return "dpdpickup_selection";
    }

    protected function buildForm()
    {
        if (null === $data = DpdPickup::getConfigValue('default_status')){
            $data = DpdPickup::NO_CHANGE;
        }

        $this->formBuilder
            ->add(
                'new_status_id',
                ChoiceType::class,
                array(
                    'label' => Translator::getInstance()->trans('Change order status to', [], DpdPickup::DOMAIN),
                    'choices' => array(
                        Translator::getInstance()->trans("Do not change", [], DpdPickup::DOMAIN) => DpdPickup::NO_CHANGE,
                        Translator::getInstance()->trans("Set orders status as processing", [], DpdPickup::DOMAIN) => DpdPickup::PROCESS,
                        Translator::getInstance()->trans("Set orders status as sent", [], DpdPickup::DOMAIN) => DpdPickup::SEND
                    ),
                    'required' => true,
                    'expanded' => true,
                    'multiple' => false,
                    'data' => $data
                )
            )

            // Collections

            ->add(
                'order_ref_check',
                CollectionType::class,
                array(
                    'entry_type'   => CheckboxType::class,
                    'allow_add'    => true,
                    'allow_delete' => true,
                )
            )
            ->add(
                'order_ref',
                CollectionType::class,
                array(
                    'entry_type'   => TextType::class,
                    'allow_add'    => true,
                    'allow_delete' => true,
                )
            )
            ->add(
                'assur',
                CollectionType::class,
                array(
                    'entry_type'   => CheckboxType::class,
                    'allow_add'    => true,
                    'allow_delete' => true,
                )
            )
            ->add(
                'pkgNumber',
                CollectionType::class,
                array(
                    'entry_type'   => NumberType::class,
                    'allow_add'    => true,
                    'allow_delete' => true,
                )
            )
            ->add(
                'pkgWeight',
                CollectionType::class,
                array(
                    'entry_type'   => NumberType::class,
                    'allow_add'    => true,
                    'allow_delete' => true,
                )
            )
        ;
    }
}
