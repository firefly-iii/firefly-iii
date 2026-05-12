<?php

/*
 * AbstractNationalRateProvider.php
 *
 * Common HTTP/log scaffolding for national bank providers.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

abstract class AbstractNationalRateProvider implements NationalRateProviderInterface
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $timeout      = (int) config('cer.national_http_timeout', 15);
        $this->client = $client ?? new Client([
            'timeout'         => $timeout,
            'connect_timeout' => $timeout,
            'headers'         => [
                'User-Agent' => 'FireflyIII-NationalRates/1.0',
                'Accept'     => '*/*',
            ],
        ]);
    }

    public static function name(): string
    {
        return static::class;
    }

    /**
     * Perform a GET request and return the response body as string.
     * Returns null on transport failure / non-200 status.
     */
    protected function httpGet(string $url): ?string
    {
        try {
            $response = $this->client->get($url);
        } catch (GuzzleException $e) {
            Log::warning(sprintf(
                '[%s] HTTP error fetching "%s": %s',
                static::name(),
                $url,
                $e->getMessage(),
            ));

            return null;
        }

        $status = $response->getStatusCode();
        if (200 !== $status) {
            Log::warning(sprintf(
                '[%s] Non-200 (%d) fetching "%s".',
                static::name(),
                $status,
                $url,
            ));

            return null;
        }

        return (string) $response->getBody();
    }

    /**
     * Convenience: convert raw "X base = Y quote" into per-unit rate.
     */
    protected function perUnit(float $rate, float $scale): float
    {
        if ($scale <= 0.0) {
            return $rate;
        }

        return $rate / $scale;
    }
}
