<?php

namespace App\Services;

class ClaimPricing
{
    public function claimUnitPrice(int $retailPrice, int $discountPercent): int
    {
        return (int) round($retailPrice * (100 - $discountPercent) / 100);
    }
}
