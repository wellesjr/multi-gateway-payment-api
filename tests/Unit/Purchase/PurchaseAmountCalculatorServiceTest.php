<?php

namespace Tests\Unit\Purchase;

use App\Models\Product;
use App\Services\Purchase\PurchaseAmountCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseAmountCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_total_amount_for_multiple_products(): void
    {
        $productA = Product::factory()->create(['amount' => 10.00]);
        $productB = Product::factory()->create(['amount' => 4.50]);

        $service = app(PurchaseAmountCalculatorService::class);

        $result = $service->calculate([
            ['id' => $productA->id, 'quantity' => 2],
            ['id' => $productB->id, 'quantity' => 3],
        ]);

        $this->assertEquals(33.50, $result->amount);
        $this->assertSame(3350, $result->amountInCents);
        $this->assertSame([
            ['id' => $productA->id, 'quantity' => 2],
            ['id' => $productB->id, 'quantity' => 3],
        ], $result->products);
    }

    public function test_it_calculates_cents_without_float_precision_drift(): void
    {
        $productA = Product::factory()->create(['amount' => 0.10]);
        $productB = Product::factory()->create(['amount' => 0.20]);

        $service = app(PurchaseAmountCalculatorService::class);

        $result = $service->calculate([
            ['id' => $productA->id, 'quantity' => 1],
            ['id' => $productB->id, 'quantity' => 1],
        ]);

        $this->assertSame(30, $result->amountInCents);
        $this->assertEquals(0.30, $result->amount);
    }

    public function test_it_throws_exception_when_product_is_missing(): void
    {
        Product::factory()->create();

        $service = app(PurchaseAmountCalculatorService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Produto informado não foi encontrado.');

        $service->calculate([
            ['id' => 999999, 'quantity' => 1],
        ]);
    }
}
