<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

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

        try {
            $rate = Cache::remember($cacheKey, 3600, function () use ($fromCurrency, $toCurrency) {
                $apiKey = env('EXCHANGE_RATE_KEY');

                if (!$apiKey) {
                    throw new \Exception('Chave da API de câmbio não configurada.');
                }

                $response = Http::timeout(5)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$fromCurrency}");

                $response->throw(); // lança exceção se status HTTP >= 400

                $rates = $response->json('conversion_rates');

                if (!isset($rates[$toCurrency])) {
                    throw new \Exception("Taxa de câmbio para {$toCurrency} não encontrada.");
                }

                return $rates[$toCurrency];
            });
        } catch (\Exception $e) {
            $rate = 1;
        }

        return [
            'from_currency'    => $fromCurrency,
            'original_amount'  => $value,
            'to_currency'      => $toCurrency,
            'converted_amount' => round($value * $rate, 2),
            'rate_used'        => $rate,
        ];
    }
}
