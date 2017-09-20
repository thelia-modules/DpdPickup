<?php

namespace DpdPickup\Form;

use DpdPickup\DataTransformer\ZipCodeListTransformer;
use DpdPickup\DpdPickup;
use Thelia\Form\BaseForm;

/**
 * Class ConfigurationForm
 * @package DpdPickup\Form
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
class ConfigurationForm extends BaseForm
{
    public function getName()
    {
        return "dpdpickup-config-form";
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
                'choice',
                [
                    'label' => $this->translator->trans('Change order status to', [], DpdPickup::DOMAIN),
                    'choices' => [
                        DpdPickup::NO_CHANGE => $this->translator->trans("Do not change", [], DpdPickup::DOMAIN),
                        DpdPickup::PROCESS => $this->translator->trans("Set orders status as processing", [], DpdPickup::DOMAIN),
                        DpdPickup::SEND => $this->translator->trans("Set orders status as sent", [], DpdPickup::DOMAIN)
                    ],
                    'required' => true,
                    'expanded' => true,
                    'multiple' => false,
                    'data' => $data
                ]
            )
            ->add(
                'google_map_key',
                'text',
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
                'textarea',
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