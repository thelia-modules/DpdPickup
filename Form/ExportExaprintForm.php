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
use Symfony\Component\Validator\Constraints\Regex;
use Thelia\Form\BaseForm;
use Thelia\Core\Translation\Translator;
use Symfony\Component\Validator\Constraints\NotBlank;
use DpdPickup\Controller\ExportExaprint;

/**
 * Class ExportExaprintForm
 * @package DpdPickup\Form
 * @author Thelia <info@thelia.net>
 */
class ExportExaprintForm extends BaseForm
{
    public function getName()
    {
        return "exportexaprintform";
    }

    protected function buildForm()
    {
        // Add value(s) if Config/exportdat.json exists

        if (is_readable(ExportExaprint::getJSONpath())) {
            $values = json_decode(file_get_contents(ExportExaprint::getJSONpath()), true);
        }

        $this->formBuilder
            ->add(
                'name',
                'text',
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s name', [], DpdPickup::DOMAIN),
                    'data' => (isset($values['name']) ? $values['name'] : ""),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'name'
                    )
                )
            )
            ->add(
                'addr',
                'text',
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s address1', [], DpdPickup::DOMAIN),
                    'data' => (isset($values['addr']) ? $values['addr'] : ""),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'addr'
                    )
                )
            )
            ->add(
                'addr2',
                'text',
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s address2', [], DpdPickup::DOMAIN),
                    'data' => (isset($values['addr2']) ? $values['addr2'] : ""),
                    'label_attr' => array(
                        'for' => 'addr2'
                    )
                )
            )
            ->add(
                'zipcode',
                'text',
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s zipcode', [], DpdPickup::DOMAIN),
                    'data' => (isset($values['zipcode']) ? $values['zipcode'] : ""),
                    'constraints' => array(new NotBlank(), new Regex(['pattern' => "/^(2[A-B])|([0-9]{2})\d{3}$/"])),
                    'label_attr' => array(
                        'for' => 'zipcode'
                    )
                )
            )
            ->add(
                'city',
                'text',
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s city', [], DpdPickup::DOMAIN),
                    'data' => (isset($values['city']) ? $values['city'] : ""),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'city'
                    )
                )
            )
            ->add(
                'tel',
                'text',
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s phone', [], DpdPickup::DOMAIN),
                    'data' => (isset($values['tel']) ? $values['tel'] : ""),
                    'constraints' => array(new NotBlank(), new Regex(['pattern' => "/^0[1-9]\d{8}$/"])),
                    'label_attr' => array(
                        'for' => 'tel'
                    )
                )
            )
            ->add(
                'mobile',
                'text',
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s mobile phone', [], DpdPickup::DOMAIN),
                    'data' => (isset($values['mobile']) ? $values['mobile'] : ""),
                    'constraints' => array(new NotBlank(), new Regex(['pattern' => "#^0[6-7]{1}\d{8}$#"])),
                    'label_attr' => array(
                        'for' => 'mobile'
                    )
                )
            )
            ->add(
                'mail',
                'email',
                array(
                    'label' => Translator::getInstance()->trans('Sender\'s email', [], DpdPickup::DOMAIN),
                    'data' => (isset($values['mail']) ? $values['mail'] : ""),
                    'constraints' => array(new NotBlank()),
                    'label_attr' => array(
                        'for' => 'mail'
                    )
                )
            )
            ->add(
                'expcode',
                'text',
                array(
                    'label' => Translator::getInstance()->trans('DpdPickup Sender\'s code', [], DpdPickup::DOMAIN),
                    'constraints' => array(new NotBlank(), new Regex(['pattern' => "#^\d{8}$#"])),
                    'data' => (isset($values['expcode']) ? $values['expcode'] : ""),
                    'label_attr' => array(
                        'for' => 'expcode'
                    )
                )
            );
    }
}
