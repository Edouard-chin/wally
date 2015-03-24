<?php

namespace SocialWallBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ArrayToStringTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if (!$value || !is_array($value)) {
            return;
        }

        return implode("\n", $value);
    }

    public function reverseTransform($value)
    {
        if (!$value) {
            return;
        }

        return explode("\n", $value);
    }
}
