<?php

namespace App\Services\Exceptions;

use RuntimeException;

class AddressNotFoundException extends RuntimeException
{
    public static function for(string $address): self
    {
        return new self("Endereço não encontrado: {$address}");
    }
}
