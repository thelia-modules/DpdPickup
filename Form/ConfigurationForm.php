<?php

namespace DpdPickup\Form;

use DpdPickup\DataTransformer\ZipCodeListTransformer;
use DpdPickup\DpdPickup;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;

/**
 * Class ConfigurationForm
 * @package DpdPickup\Form
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
class ConfigurationForm extends BaseForm
{
    public static function getName()
    {
        return "dpdpickup_config_form";
    }

    /**
     * @return null
     */
    protected function buildForm()
    {
        if (null === $data = DpdPickup::getConfigValue('default_status')){
            $data = DpdPickup::NO_CHANGE;
        }

        $this->formBuilder
            ->add(
                'default_status',
                ChoiceType::class,
                [
                    'label' => $this->translator->trans('Change order status to', [], DpdPickup::DOMAIN),
                    'choices' => [
                        $this->translator->trans("Do not change", [], DpdPickup::DOMAIN) => DpdPickup::NO_CHANGE,
                        $this->translator->trans("Set orders status as processing", [], DpdPickup::DOMAIN) => DpdPickup::PROCESS,
                        $this->translator->trans("Set orders status as sent", [], DpdPickup::DOMAIN) => DpdPickup::SEND
                    ],
                    'required' => true,
                    'expanded' => true,
                    'multiple' => false,
                    'data' => $data
                ]
            )
            ->add(
                'google_map_key',
                TextType::class,
                [
                    'required' => false,
                    'constraints' => [],
                    'data'        => DpdPickup::getConfigGoogleMapKey(),
                    'label'       => $this->translator->trans("Google map API key", [], DpdPickup::DOMAIN),
                    'label_attr'  => ['for' => 'google_map_key']
                ]
            )
            ->add(
                'exclude_zip_code',
                TextareaType::class,
                [
                    'required' => false,
                    'constraints' => [],
                    'data'        => DpdPickup::getConfigExcludeZipCode(),
                    'label'       => $this->translator->trans("Exclude ZipCode", [], DpdPickup::DOMAIN),
                    'label_attr'  => ['for' => 'exclude_zip_code', 'help' => $this->translator->trans('List of zip code separated by commas.', [], DpdPickup::DOMAIN)]
                ]
            );

        $this->formBuilder->get('exclude_zip_code')
            ->addModelTransformer(new ZipCodeListTransformer());
    }
}