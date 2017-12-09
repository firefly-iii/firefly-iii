<?php
/**
 * BunqRequest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Services\Spectre\Request;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Log;
use Requests;
use Requests_Exception;

//use FireflyIII\Services\Bunq\Object\ServerPublicKey;

/**
 * Class BunqRequest.
 */
abstract class SpectreRequest
{
    /** @var string */
    protected $clientId  = '';
    protected $expiresAt = 0;
    /** @var ServerPublicKey */
    protected $serverPublicKey;
    /** @var string */
    protected $serviceSecret = '';
    /** @var string */
    private $privateKey = '';
    /** @var string */
    private $server = '';
    /** @var User */
    private $user;

    /**
     * SpectreRequest constructor.
     */
    public function __construct(User $user)
    {
        $this->user       = $user;
        $this->server     = config('firefly.spectre.server');
        $this->expiresAt  = time() + 180;
        $privateKey       = app('preferences')->get('spectre_private_key', null);
        $this->privateKey = $privateKey->data;

        // set client ID
        $clientId       = app('preferences')->get('spectre_client_id', null);
        $this->clientId = $clientId->data;

        // set service secret
        $serviceSecret       = app('preferences')->get('spectre_service_secret', null);
        $this->serviceSecret = $serviceSecret->data;
    }

    /**
     *
     */
    abstract public function call(): void;

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @return ServerPublicKey
     */
    public function getServerPublicKey(): ServerPublicKey
    {
        return $this->serverPublicKey;
    }

    /**
     * @param ServerPublicKey $serverPublicKey
     */
    public function setServerPublicKey(ServerPublicKey $serverPublicKey)
    {
        $this->serverPublicKey = $serverPublicKey;
    }

    /**
     * @return string
     */
    public function getServiceSecret(): string
    {
        return $this->serviceSecret;
    }

    /**
     * @param string $serviceSecret
     */
    public function setServiceSecret(string $serviceSecret): void
    {
        $this->serviceSecret = $serviceSecret;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivateKey(string $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
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
        if (0 === strlen($this->privateKey)) {
            throw new FireflyException('No private key present.');
        }
        if ('get' === strtolower($method) || 'delete' === strtolower($method)) {
            $data = '';
        }
        // base64(sha1_signature(private_key, "Expires-at|request_method|original_url|post_body|md5_of_uploaded_file|")))
        // Prepare the signature
        $toSign = $this->expiresAt . '|' . strtoupper($method) . '|' . $uri . '|' . $data . ''; // no file so no content there.
        Log::debug(sprintf('String to sign: %s', $toSign));
        $signature = '';

        // Sign the data
        openssl_sign($toSign, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);
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
            'Client-Id'      => $this->getClientId(),
            'Service-Secret' => $this->getServiceSecret(),
            'Accept'         => 'application/json',
            'Content-type'   => 'application/json',
            'Cache-Control'  => 'no-cache',
            'User-Agent'     => $userAgent,
            'Expires-at'     => $this->expiresAt,
        ];
    }

    /**
     * @param string $uri
     * @param array  $headers
     *
     * @return array
     *
     * @throws Exception
     */
    protected function sendSignedBunqDelete(string $uri, array $headers): array
    {
        if (0 === strlen($this->server)) {
            throw new FireflyException('No bunq server defined');
        }

        $fullUri                            = $this->server . $uri;
        $signature                          = $this->generateSignature('delete', $uri, $headers, '');
        $headers['X-Bunq-Client-Signature'] = $signature;
        try {
            $response = Requests::delete($fullUri, $headers);
        } catch (Requests_Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }

        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = intval($response->status_code);
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        Log::debug(sprintf('Response to DELETE %s is %s', $fullUri, $body));
        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        if (!$this->verifyServerSignature($body, $responseHeaders, $statusCode)) {
            throw new FireflyException(sprintf('Could not verify signature for request to "%s"', $uri));
        }

        return $array;
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return array
     *
     * @throws Exception
     */
    protected function sendSignedBunqPost(string $uri, array $data, array $headers): array
    {
        $body                               = json_encode($data);
        $fullUri                            = $this->server . $uri;
        $signature                          = $this->generateSignature('post', $uri, $headers, $body);
        $headers['X-Bunq-Client-Signature'] = $signature;
        try {
            $response = Requests::post($fullUri, $headers, $body);
        } catch (Requests_Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }

        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = intval($response->status_code);
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        if (!$this->verifyServerSignature($body, $responseHeaders, $statusCode)) {
            throw new FireflyException(sprintf('Could not verify signature for request to "%s"', $uri));
        }

        return $array;
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return array
     *
     * @throws Exception
     */
    protected function sendSignedSpectreGet(string $uri, array $data): array
    {
        if (0 === strlen($this->server)) {
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
        } catch (Requests_Exception $e) {
            throw new FireflyException(sprintf('Request Exception: %s', $e->getMessage()));
        }
        $statusCode = intval($response->status_code);

        if ($statusCode !== 200) {
            throw new FireflyException(sprintf('Status code %d: %s', $statusCode, $response->body));
        }

        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        return $array;
    }

    /**
     * @param string $uri
     * @param array  $headers
     *
     * @return array
     */
    protected function sendUnsignedBunqDelete(string $uri, array $headers): array
    {
        $fullUri = $this->server . $uri;
        try {
            $response = Requests::delete($fullUri, $headers);
        } catch (Requests_Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }
        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = $response->status_code;
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        return $array;
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return array
     */
    protected function sendUnsignedBunqPost(string $uri, array $data, array $headers): array
    {
        $body    = json_encode($data);
        $fullUri = $this->server . $uri;
        try {
            $response = Requests::post($fullUri, $headers, $body);
        } catch (Requests_Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }
        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = $response->status_code;
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        return $array;
    }
}
