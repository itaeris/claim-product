<?php

namespace Tests\Unit;

use App\Services\ClaimPricing;
use PHPUnit\Framework\TestCase;

class ClaimPricingTest extends TestCase
{
    public function test_employee_discount_matches_the_catalog_prices(): void
    {
        $pricing = new ClaimPricing;

        $this->assertSame(48300, $pricing->claimUnitPrice(69000, 30));
        $this->assertSame(349300, $pricing->claimUnitPrice(499000, 30));
    }
}
