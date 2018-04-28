<?php
/**
 * SpectreRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Services\Spectre\Request;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Log;
use Requests;
use Requests_Response;

/**
 * Class BunqRequest.
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
     * SpectreRequest constructor.
     *
     * @param User $user
     *
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public function __construct(User $user)
    {
        $this->user       = $user;
        $this->server     = config('firefly.spectre.server');
        $this->expiresAt  = time() + 180;
        $privateKey       = app('preferences')->get('spectre_private_key', null);
        $this->privateKey = $privateKey->data;

        // set client ID
        $appId       = app('preferences')->get('spectre_app_id', null);
        $this->appId = $appId->data;

        // set service secret
        $secret       = app('preferences')->get('spectre_secret', null);
        $this->secret = $secret->data;
    }

    /**
     *
     */
    abstract public function call(): void;

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
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
        $body                 = json_encode($data);
        $fullUri              = $this->server . $uri;
        $signature            = $this->generateSignature('get', $fullUri, $body);
        $headers['Signature'] = $signature;

        Log::debug('Final headers for spectre signed get request:', $headers);
        try {
            $response = Requests::get($fullUri, $headers);
        } catch (Exception $e) {
            throw new FireflyException(sprintf('Request Exception: %s', $e->getMessage()));
        }
        $this->detectError($response);
        $statusCode = (int)$response->status_code;

        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
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
            $response = Requests::post($fullUri, $headers, $body);
        } catch (Exception $e) {
            throw new FireflyException(sprintf('Request Exception: %s', $e->getMessage()));
        }
        $this->detectError($response);
        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $response->status_code;

        return $array;
    }

    /**
     * @param Requests_Response $response
     *
     * @throws FireflyException
     */
    private function detectError(Requests_Response $response): void
    {
        $body  = $response->body;
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

        $statusCode = (int)$response->status_code;
        if (200 !== $statusCode) {
            throw new FireflyException(sprintf('Status code %d: %s', $statusCode, $response->body));
        }
    }
}
