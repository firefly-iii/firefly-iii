<?php
/**
 * SpectreRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Services\Spectre\Request;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;
use RuntimeException;

/**
 * Class SpectreRequest
 * @codeCoverageIgnore
 */
abstract class SpectreRequest
{
    /** @var int */
    protected $expiresAt = 0;
    /** @var string */
    private $appId;
    /** @var string */
    private $privateKey;
    /** @var string */
    private $secret;
    /** @var string */
    private $server;
    /** @var User */
    private $user;

    /**
     *
     */
    abstract public function call(): void;

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $appId
     */
    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $privateKey
     */
    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user       = $user;
        $this->server     = 'https://' . config('import.options.spectre.server');
        $this->expiresAt  = time() + 180;
        $privateKey       = app('preferences')->getForUser($user, 'spectre_private_key', null);
        $this->privateKey = $privateKey->data;

        // set client ID
        $appId = app('preferences')->getForUser($user, 'spectre_app_id', null);
        if (null !== $appId && '' !== (string)$appId->data) {
            $this->appId = $appId->data;
        }

        // set service secret
        $secret = app('preferences')->getForUser($user, 'spectre_secret', null);
        if (null !== $secret && '' !== (string)$secret->data) {
            $this->secret = $secret->data;
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $data
     *
     * @return string
     *
     * @throws FireflyException
     */
    protected function generateSignature(string $method, string $uri, string $data): string
    {
        if ('' === $this->privateKey) {
            throw new FireflyException('No private key present.');
        }
        $method = strtolower($method);
        if ('get' === $method || 'delete' === $method) {
            $data = '';
        }
        $toSign = $this->expiresAt . '|' . strtoupper($method) . '|' . $uri . '|' . $data . ''; // no file so no content there.
        Log::debug(sprintf('String to sign: "%s"', $toSign));
        $signature = '';

        // Sign the data
        openssl_sign($toSign, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        return $signature;
    }

    /**
     * @return array
     */
    protected function getDefaultHeaders(): array
    {
        $userAgent = sprintf('FireflyIII v%s', config('firefly.version'));

        return [
            'App-id'        => $this->getAppId(),
            'Secret'        => $this->getSecret(),
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'Cache-Control' => 'no-cache',
            'User-Agent'    => $userAgent,
            'Expires-at'    => $this->expiresAt,
        ];
    }

    /**
     * @param string $uri
     * @param array  $data
     *
     * @return array
     *
     * @throws FireflyException
     */
    protected function sendSignedSpectreGet(string $uri, array $data): array
    {
        if ('' === $this->server) {
            throw new FireflyException('No Spectre server defined');
        }

        $headers              = $this->getDefaultHeaders();
        $sendBody             = json_encode($data); // OK
        $fullUri              = $this->server . $uri;
        $signature            = $this->generateSignature('get', $fullUri, $sendBody);
        $headers['Signature'] = $signature;

        Log::debug('Final headers for spectre signed get request:', $headers);
        try {
            $client = new Client;
            $res    = $client->request('GET', $fullUri, ['headers' => $headers]);
        } catch (GuzzleException|Exception $e) {
            throw new FireflyException(sprintf('Guzzle Exception: %s', $e->getMessage()));
        }
        $statusCode = $res->getStatusCode();
        try {
            $returnBody = $res->getBody()->getContents();
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not get body from SpectreRequest::GET result: %s', $e->getMessage()));
            $returnBody = '';
        }
        $this->detectError($returnBody, $statusCode);

        $array                       = json_decode($returnBody, true);
        $responseHeaders             = $res->getHeaders();
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        if (isset($array['error_class'])) {
            $message = $array['error_message'] ?? '(no message)';
            throw new FireflyException(sprintf('Error of class %s: %s', $array['error_class'], $message));
        }


        return $array;
    }

    /**
     * @param string $uri
     * @param array  $data
     *
     * @return array
     *
     * @throws FireflyException
     */
    protected function sendSignedSpectrePost(string $uri, array $data): array
    {
        if ('' === $this->server) {
            throw new FireflyException('No Spectre server defined');
        }

        $headers              = $this->getDefaultHeaders();
        $body                 = json_encode($data);
        $fullUri              = $this->server . $uri;
        $signature            = $this->generateSignature('post', $fullUri, $body);
        $headers['Signature'] = $signature;

        Log::debug('Final headers for spectre signed POST request:', $headers);
        try {
            $client = new Client;
            $res    = $client->request('POST', $fullUri, ['headers' => $headers, 'body' => $body]);
        } catch (GuzzleException|Exception $e) {
            throw new FireflyException(sprintf('Guzzle Exception: %s', $e->getMessage()));
        }

        try {
            $body = $res->getBody()->getContents();
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not get body from SpectreRequest::POST result: %s', $e->getMessage()));
            $body = '';
        }

        $statusCode = $res->getStatusCode();
        $this->detectError($body, $statusCode);

        $array                       = json_decode($body, true);
        $responseHeaders             = $res->getHeaders();
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        return $array;
    }

    /**
     * @param string $body
     *
     * @param int    $statusCode
     *
     * @throws FireflyException
     */
    private function detectError(string $body, int $statusCode): void
    {
        $array = json_decode($body, true);
        if (isset($array['error_class'])) {
            $message    = $array['error_message'] ?? '(no message)';
            $errorClass = $array['error_class'];
            $class      = sprintf('\\FireflyIII\\Services\\Spectre\Exception\\%sException', $errorClass);
            if (class_exists($class)) {
                throw new $class($message);
            }

            throw new FireflyException(sprintf('Error of class %s: %s', $errorClass, $message));
        }

        if (200 !== $statusCode) {
            throw new FireflyException(sprintf('Status code %d: %s', $statusCode, $body));
        }
    }
}
