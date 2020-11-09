<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 03/11/2020
 * Time: 16:42
 */

namespace DpdPickup\Form;


use DpdPickup\DpdPickup;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class LabelGenerationForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'weight',
                TextType::class,
                [
                    "required" => true,
                    "label" => Translator::getInstance()->trans("Weight (kg)", [], DpdPickup::DOMAIN),
                    "label_attr" => [
                        "for" => "weight",
                    ],
                ]
            );
    }

    public function getName()
    {
        return "dpdpickup-label-generation-form";
    }

}