<?php
/**
 * BunqRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Request;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Bunq\Object\ServerPublicKey;
use Log;
use Requests;
use Requests_Exception;

/**
 * Class BunqRequest
 *
 * @package Bunq\Request
 */
abstract class BunqRequest
{
    /** @var string */
    protected $secret = '';
    /** @var string */
    private $privateKey = '';
    /** @var string */
    private $server = '';
    /** @var  ServerPublicKey */
    private $serverPublicKey;
    private $upperCaseHeaders
        = [
            'x-bunq-client-response-id' => 'X-Bunq-Client-Response-Id',
            'x-bunq-client-request-id'  => 'X-Bunq-Client-Request-Id',
        ];

    /**
     * BunqRequest constructor.
     */
    public function __construct()
    {
        $this->server = config('firefly.bunq.server');
    }

    /**
     *
     */
    abstract public function call(): void;

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
     * @param ServerPublicKey $serverPublicKey
     */
    public function setServerPublicKey(ServerPublicKey $serverPublicKey)
    {
        $this->serverPublicKey = $serverPublicKey;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $headers
     * @param string $data
     *
     * @return string
     * @throws FireflyException
     */
    protected function generateSignature(string $method, string $uri, array $headers, string $data): string
    {
        if (strlen($this->privateKey) === 0) {
            throw new FireflyException('No private key present.');
        }
        if (strtolower($method) === 'get' || strtolower($method) === 'delete') {
            $data = '';
        }

        $uri           = str_replace(['https://api.bunq.com', 'https://sandbox.public.api.bunq.com'], '', $uri);
        $toSign        = sprintf("%s %s\n", strtoupper($method), $uri);
        $headersToSign = ['Cache-Control', 'User-Agent'];
        ksort($headers);
        foreach ($headers as $name => $value) {
            if (in_array($name, $headersToSign) || substr($name, 0, 7) === 'X-Bunq-') {
                $toSign .= sprintf("%s: %s\n", $name, $value);
            }
        }
        $toSign    .= "\n" . $data;
        $signature = '';

        openssl_sign($toSign, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        return $signature;
    }

    /**
     * @param string $key
     * @param array  $response
     *
     * @return array
     */
    protected function getArrayFromResponse(string $key, array $response): array
    {
        $result = [];
        if (isset($response['Response'])) {
            foreach ($response['Response'] as $entry) {
                $currentKey = key($entry);
                $data       = current($entry);
                if ($currentKey === $key) {
                    $result[] = $data;
                }
            }
        }

        return $result;
    }

    protected function getDefaultHeaders(): array
    {
        $userAgent = sprintf('FireflyIII v%s', config('firefly.version'));

        return [
            'X-Bunq-Client-Request-Id' => uniqid('FFIII'),
            'Cache-Control'            => 'no-cache',
            'User-Agent'               => $userAgent,
            'X-Bunq-Language'          => 'en_US',
            'X-Bunq-Region'            => 'nl_NL',
            'X-Bunq-Geolocation'       => '0 0 0 0 NL',
        ];
    }

    /**
     * @param string $key
     * @param array  $response
     *
     * @return array
     */
    protected function getKeyFromResponse(string $key, array $response): array
    {
        if (isset($response['Response'])) {
            foreach ($response['Response'] as $entry) {
                $currentKey = key($entry);
                $data       = current($entry);
                if ($currentKey === $key) {
                    return $data;
                }
            }
        }

        return [];
    }

    /**
     * @param string $uri
     * @param array  $headers
     *
     * @return array
     * @throws Exception
     */
    protected function sendSignedBunqDelete(string $uri, array $headers): array
    {
        if (strlen($this->server) === 0) {
            throw new FireflyException('No bunq server defined');
        }

        $fullUri                            = $this->server . $uri;
        $signature                          = $this->generateSignature('delete', $uri, $headers, '');
        $headers['X-Bunq-Client-Signature'] = $signature;
        try {
            $response = Requests::delete($fullUri, $headers);
        } catch (Requests_Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()],]];
        }

        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = $response->status_code;
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
     * @throws Exception
     */
    protected function sendSignedBunqGet(string $uri, array $data, array $headers): array
    {
        if (strlen($this->server) === 0) {
            throw new FireflyException('No bunq server defined');
        }

        $body                               = json_encode($data);
        $fullUri                            = $this->server . $uri;
        $signature                          = $this->generateSignature('get', $uri, $headers, $body);
        $headers['X-Bunq-Client-Signature'] = $signature;
        try {
            $response = Requests::get($fullUri, $headers);
        } catch (Requests_Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()],]];
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
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()],]];
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

        if (!$this->verifyServerSignature($body, $responseHeaders, $statusCode)) {
            throw new FireflyException(sprintf('Could not verify signature for request to "%s"', $uri));
        }


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
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()],]];
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
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()],]];
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
     * @param array $response
     *
     * @return bool
     */
    private function isErrorResponse(array $response): bool
    {
        $key = key($response);
        if ($key === 'Error') {
            return true;
        }

        return false;
    }

    /**
     * @param array $response
     *
     * @throws Exception
     */
    private function throwResponseError(array $response)
    {
        $message = [];
        if (isset($response['Error'])) {
            foreach ($response['Error'] as $error) {
                $message[] = $error['error_description'];
            }
        }
        throw new FireflyException('Bunq ERROR ' . $response['ResponseStatusCode'] . ': ' . join(', ', $message));
    }

    /**
     * @param string $body
     * @param array  $headers
     * @param int    $statusCode
     *
     * @return bool
     * @throws Exception
     */
    private function verifyServerSignature(string $body, array $headers, int $statusCode): bool
    {
        Log::debug('Going to verify signature for body+headers+status');
        $dataToVerify  = $statusCode . "\n";
        $verifyHeaders = [];

        // false when no public key is present
        if (is_null($this->serverPublicKey)) {
            Log::error('No public key present in class, so return FALSE.');

            return false;
        }
        foreach ($headers as $header => $value) {

            // skip non-bunq headers or signature
            if (substr($header, 0, 7) !== 'x-bunq-' || $header === 'x-bunq-server-signature') {
                continue;
            }
            // need to have upper case variant of header:
            if (!isset($this->upperCaseHeaders[$header])) {
                throw new FireflyException(sprintf('No upper case variant for header "%s"', $header));
            }
            $header                 = $this->upperCaseHeaders[$header];
            $verifyHeaders[$header] = $value[0];
        }
        // sort verification headers:
        ksort($verifyHeaders);

        // add them to data to sign:
        foreach ($verifyHeaders as $header => $value) {
            $dataToVerify .= $header . ': ' . trim($value) . "\n";
        }

        $signature    = $headers['x-bunq-server-signature'][0];
        $dataToVerify .= "\n" . $body;
        $result       = openssl_verify($dataToVerify, base64_decode($signature), $this->serverPublicKey->getPublicKey(), OPENSSL_ALGO_SHA256);

        if (is_int($result) && $result < 1) {
            Log::error(sprintf('Result of verification is %d, return false.', $result));

            return false;
        }
        if (!is_int($result)) {
            Log::error(sprintf('Result of verification is a boolean (%d), return false.', $result));
        }
        Log::info('Signature is a match, return true.');

        return true;
    }
}
