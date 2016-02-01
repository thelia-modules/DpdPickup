<?php

namespace DpdPickup\Form;

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
            );
    }
}