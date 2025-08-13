<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class CurrencyConverterService
{
    public function convert(float $value, string $fromCurrency): array
    {
        $toCurrency = $fromCurrency === 'BRL' ? 'USD' : 'BRL';
        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";

        try {
            $rate = Cache::remember($cacheKey, 3600, function () use ($fromCurrency, $toCurrency) {
                $apiKey = env('EXCHANGE_RATE_KEY');

                if (!$apiKey) {
                    return 1;
                }

                $response = Http::timeout(5)
                    ->get("https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$fromCurrency}/{$toCurrency}");

                if ($response->successful()) {
                    return $response->json('conversion_rate', 1);
                }

                return 1;
            });

            return [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'original_amount' => $value,
                'converted_amount' => round($value * $rate, 2),
                'rate_used' => $rate
            ];
        } catch (\Exception $e) {
            return [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'original_amount' => $value,
                'converted_amount' => $value,
                'rate_used' => 1,
                'error' => 'Conversion service unavailable'
            ];
        }
    }
}
