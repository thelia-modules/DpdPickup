<?php

namespace DpdPickup\Form;

use DpdPickup\DpdPickup;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

/**
 * Class ImportExaprintForm
 * @package DpdPickup\Form
 * @author Etienne Perriere - OpenStudio <eperriere@openstudio.fr>
 */
class ImportExaprintForm extends BaseForm
{
    public static function getName()
    {
        return 'dpdpickup_import';
    }

    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'import_file', FileType::class,
                [
                    'label' => Translator::getInstance()->trans('Select file to import', [], DpdPickup::DOMAIN),
                    'constraints' => [
                        new Constraints\NotBlank(),
                        new Constraints\File(['mimeTypes' => ['text/csv', 'text/plain']])
                    ],
                    'label_attr' => ['for' => 'import_file']
                ]
            );
    }
}
