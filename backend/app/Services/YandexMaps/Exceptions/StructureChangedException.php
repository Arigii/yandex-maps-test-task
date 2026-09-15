<?php

namespace App\Services\YandexMaps\Exceptions;

use Exception;

class StructureChangedException extends Exception
{
    public function __construct(string $message, public readonly array $debugContext = [])
    {
        parent::__construct($message);
    }
}
