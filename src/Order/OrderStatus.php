<?php

namespace App\Order;

enum OrderStatus: string
{
    case TO_PAID = 'topaid';
    case PAID = 'paid';
}
