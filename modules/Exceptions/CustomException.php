<?php

declare(strict_types=1);

namespace Modules\Exceptions;

use Exception;

class CustomException extends Exception
{
    public function __construct(string $format, mixed ...$values)
    {
        parent::__construct(sprintf($format, ...$values));
    }
}
