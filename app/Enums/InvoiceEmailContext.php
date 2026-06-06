<?php

namespace App\Enums;

enum InvoiceEmailContext: string
{
    case Created = 'created';
    case Paid = 'paid';
}
