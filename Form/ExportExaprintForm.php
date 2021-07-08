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
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Thelia\Form\BaseForm;
use Thelia\Core\Translation\Translator;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ExportExaprintForm
 * @package DpdPickup\Form
 * @author Thelia <info@thelia.net>
 */
class ExportExaprintForm extends BaseForm
{
    public static function getName()
    {
        return "dpdpickup_export";
    }

    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'exp_name',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s name', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_NAME),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'exp_name'
                    )
                )
            )
            ->add(
                'exp_addr',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s address1', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_ADDR),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'exp_addr'
                    )
                )
            )
            ->add(
                'exp_addr2',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s address2', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_ADDR2),
                    'label_attr' => array(
                        'for' => 'exp_addr2'
                    )
                )
            )
            ->add(
                'exp_zipcode',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s zipcode', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_ZIPCODE),
                    'constraints' => array(new NotBlank(), new Regex(['pattern' => "/^(2[A-B])|([0-9]{2})\d{3}$/"])),
                    'label_attr' => array(
                        'for' => 'exp_zipcode'
                    )
                )
            )
            ->add(
                'exp_city',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s city', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_CITY),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'exp_city'
                    )
                )
            )
            ->add(
                'exp_tel',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s phone', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_TEL),
                    'constraints' => array(new NotBlank(), new Regex(['pattern' => "/^0[1-9]\d{8}$/"])),
                    'label_attr' => array(
                        'for' => 'exp_tel'
                    )
                )
            )
            ->add(
                'exp_mobile',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s mobile phone', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_MOBILE),
                    'constraints' => array(new NotBlank(), new Regex(['pattern' => "#^0[6-7]{1}\d{8}$#"])),
                    'label_attr' => array(
                        'for' => 'exp_mobile'
                    )
                )
            )
            ->add(
                'exp_mail',
                EmailType::class,
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s email', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_MAIL),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'exp_mail'
                    )
                )
            )
            ->add(
                'exp_dpdcode',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('DpdPickup Sender\'s code', [], DpdPickup::DOMAIN),
                    'constraints' => array(new NotBlank(), new Length(['min' => 8, 'max' => 8])),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_EXPEDITOR_DPDCODE),
                    'label_attr' => array(
                        'for' => 'exp_dpdcode'
                    )
                )
            )->add(
                'return_type',
                IntegerType::class,
                array(
                    'label' => Translator::getInstance()->trans('Choose a return service', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_TYPE, DpdPickup::RETURN_NONE),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'return_type'
                    )
                )
            )->add(
                'return_name',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Return name', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_NAME),
                    'label_attr' => array(
                        'for' => 'return_name'
                    )
                )
            )
            ->add(
                'return_addr',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Return address1', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_ADDR),
                    'label_attr' => array(
                        'for' => 'return_addr'
                    )
                )
            )
            ->add(
                'return_addr2',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Return address2', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_ADDR2),
                    'label_attr' => array(
                        'for' => 'return_addr2'
                    )
                )
            )
            ->add(
                'return_zipcode',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Return zipcode', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_ZIPCODE),
                    'label_attr' => array(
                        'for' => 'return_zipcode'
                    )
                )
            )
            ->add(
                'return_city',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Return city', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_CITY),
                    'label_attr' => array(
                        'for' => 'return_city'
                    )
                )
            )
            ->add(
                'return_tel',
                TextType::class,
                array(
                    'label' => Translator::getInstance()->trans('Return phone', [], DpdPickup::DOMAIN),
                    'data' => DpdPickup::getConfigValue(DpdPickup::KEY_RETURN_TEL),
                    'label_attr' => array(
                        'for' => 'return_tel'
                    )
                )
            );
    }
}
