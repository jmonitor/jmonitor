<?php

declare(strict_types=1);

namespace App\Order;

use App\Repository\OrderRepository;
use DateTime;

/**
 * Generates a unique order number in the format
 * YYYYMM[rand 0001-9999]
 */
class OrderNumberGenerator
{
    private readonly OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function generate(): int
    {
        do {
            $number = new DateTime()->format('Ym') . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $found = $this->orderRepository->findOneBy(['number' => $number]);
        } while ($found);

        return (int) $number;
    }
}
