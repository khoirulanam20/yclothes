<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $productName)
    {
        parent::__construct("Stok {$productName} tidak mencukupi.");
    }
}
