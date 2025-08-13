<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CurrencyConverterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyConverterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('EXCHANGE_RATE_KEY=test_key');
    }

    protected function tearDown(): void
    {
        putenv('EXCHANGE_RATE_KEY');
        parent::tearDown();
    }

    public function testItConvertsCurrencyUsingCachedRate()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('exchange_rate_BRL_USD', 3600, \Closure::class)
            ->andReturn(0.2);

        $service = new CurrencyConverterService();
        $result = $service->convert(100, 'BRL');

        $this->assertEquals(20.0, $result['converted_amount']);
    }

    public function testItFetchesRateFromApiWhenNotCached()
    {
        Http::fake([
            '*/pair/BRL/USD' => Http::response([
                'conversion_rate' => 0.18
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn ($k, $t, $c) => $c());

        $service = new CurrencyConverterService();
        $result = $service->convert(50, 'BRL');

        $this->assertEquals(9.0, $result['converted_amount']);
        Http::assertSentCount(1);
    }

    public function testItReturns1RateIfApiFails()
    {
        Http::fake([
            '*/pair/BRL/USD' => Http::response([], 500)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn ($k, $t, $c) => $c());

        $service = new CurrencyConverterService();
        $result = $service->convert(100, 'BRL');

        $this->assertEquals(1, $result['rate_used']);
        $this->assertEquals(100, $result['converted_amount']);
    }
}
