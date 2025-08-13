<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyConverterService
{
    /**
     * @param float  $value
     * @param string $fromCurrency
     * @return array
     */
    
    public function convert(float $value, string $fromCurrency): array
    {
        $toCurrency = $fromCurrency === 'BRL' ? 'USD' : 'BRL';
        $cacheKey   = "exchange_{$fromCurrency}_{$toCurrency}";

        $rate = Cache::remember($cacheKey, 3600, function () use ($fromCurrency, $toCurrency) {
            $apiKey = env('EXCHANGE_RATE_KEY');

            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$fromCurrency}");

            return $response->json()['conversion_rates'][$toCurrency] ?? 1;
        });

        return [
            'from_currency'    => $fromCurrency,
            'original_amount'  => $value,
            'to_currency'      => $toCurrency,
            'converted_amount' => round($value * $rate, 2),
        ];
    }
}
