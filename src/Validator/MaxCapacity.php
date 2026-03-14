<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class MaxCapacity extends Constraint
{
    public string $message = 'There are only {{ available }} seats left at the selected time.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
