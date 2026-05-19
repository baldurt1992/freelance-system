<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Money;

use App\Support\Money\MoneyMath;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneyMathTest extends TestCase
{
    #[Test]
    public function it_computes_net_first_without_tax_when_tax_disabled(): void
    {
        $result = MoneyMath::computeLineTotals(
            quantity: 2,
            unitAmountCents: 50000,
            taxRatePercent: 19.0,
            taxEnabled: false,
        );

        $this->assertSame(100000, $result['line_gross_cents']);
        $this->assertSame(0, $result['tax_cents']);
    }

    #[Test]
    public function it_computes_net_first_with_tax_when_enabled(): void
    {
        $result = MoneyMath::computeLineTotals(
            quantity: 1,
            unitAmountCents: 100000,
            taxRatePercent: 19.0,
            taxEnabled: true,
        );

        $this->assertSame(100000, $result['discounted_net_cents']);
        $this->assertSame(19000, $result['tax_cents']);
        $this->assertSame(119000, $result['line_gross_cents']);
    }

    #[Test]
    public function it_computes_gross_first_with_tax(): void
    {
        $result = MoneyMath::computeLineTotals(
            quantity: 1,
            unitAmountCents: 119000,
            taxRatePercent: 19.0,
            priceMode: MoneyMath::MODE_GROSS_FIRST,
            taxEnabled: true,
        );

        $this->assertSame(100000, $result['discounted_net_cents']);
        $this->assertSame(19000, $result['tax_cents']);
        $this->assertSame(119000, $result['line_gross_cents']);
    }
}
