<?php

namespace DpdPickup\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ZipCodeListTransformer implements DataTransformerInterface
{
    /**
     * @param  string[] $zipCode
     * @return string
     */
    public function transform($zipCode)
    {
        if ($zipCode === null) {
            return '';
        }

        if (!is_array($zipCode)) {
            throw new \InvalidArgumentException('The argument zipCode is not an array');
        }

        return implode(',', $zipCode);
    }

    /**
     * @param  string $zipCodes
     * @return string[]
     */
    public function reverseTransform($zipCodes)
    {
        $return = [];

        if (!empty($zipCodes)) {
            $zipCodes = explode(',', $zipCodes);

            foreach ($zipCodes as $zipCode) {
                $zipCode = rtrim($zipCode);

                if (!empty($zipCode)) {
                    $return[] = $zipCode;
                }
            }
        }

        return $return;
    }
}
