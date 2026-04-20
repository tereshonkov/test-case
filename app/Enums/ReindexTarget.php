<?php

namespace App\Enums;

enum ReindexTarget: string
{
    case Products = 'products';
    case Orders   = 'orders';
}
