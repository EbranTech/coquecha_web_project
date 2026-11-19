<?php

namespace App\Exceptions;

use Exception;

class ShiftConflictException extends Exception
{
    /** @param array<int, int> $resourceIds */
    public function __construct(public readonly array $resourceIds)
    {
        parent::__construct('Uno o más recursos ya están reservados en el intervalo solicitado.');
    }
}
