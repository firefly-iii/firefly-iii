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

use Bunq\Object\ServerPublicKey;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Requests;
use Requests_Exception;

/**
 * Class BunqRequest
 *
 * @package Bunq\Request
 */
abstract class BunqRequest
{
    /** @var bool */
    protected $fake = false;
    /** @var string */
    protected $secret = '';
    /** @var  Logger */
    private $logger;
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

    public function __construct()
    {


        // create a log channel
        $this->logger = new Logger('bunq-request');
        $this->logger->pushHandler(new StreamHandler('logs/bunq.log', Logger::DEBUG));
        $this->logger->debug('Hallo dan');
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
     * @param string $server
     */
    public function setServer(string $server)
    {
        $this->server = $server;
    }

    /**
     * @param bool $fake
     */
    public function setFake(bool $fake)
    {
        $this->fake = $fake;
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
     */
    protected function generateSignature(string $method, string $uri, array $headers, string $data): string
    {
        if (strlen($this->privateKey) === 0) {
            throw new Exception('No private key present.');
        }
        if (strtolower($method) === 'get') {
            $data = '';
        }

        $uri           = str_replace(['https://api.bunq.com', 'https://sandbox.public.api.bunq.com'], '', $uri);
        $toSign        = strtoupper($method) . ' ' . $uri . "\n";
        $headersToSign = ['Cache-Control', 'User-Agent'];
        ksort($headers);
        foreach ($headers as $name => $value) {
            if (in_array($name, $headersToSign) || substr($name, 0, 7) === 'X-Bunq-') {
                $toSign .= $name . ': ' . $value . "\n";
            }
        }
        $toSign    .= "\n" . $data;
        $signature = '';

        openssl_sign($toSign, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        return $signature;
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'X-Bunq-Client-Request-Id' => uniqid('sander'),
            'Cache-Control'            => 'no-cache',
            'User-Agent'               => 'pre-Firefly III test thing',
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
     * @param array  $data
     * @param array  $headers
     *
     * @return array
     * @throws Exception
     */
    protected function sendSignedBunqGet(string $uri, array $data, array $headers): array
    {
        if (strlen($this->server) === 0) {
            throw new Exception('No bunq server defined');
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

        $body  = $response->body;
        $array = json_decode($body, true);
        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }
        $responseHeaders = $response->headers->getAll();
        $statusCode      = $response->status_code;
        if (!$this->verifyServerSignature($body, $responseHeaders, $statusCode)) {
            throw new Exception(sprintf('Could not verify signature for request to "%s"', $uri));
        }
        $array['ResponseHeaders'] = $responseHeaders;

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

        $body  = $response->body;
        $array = json_decode($body, true);
        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }
        $responseHeaders = $response->headers->getAll();
        $statusCode      = $response->status_code;
        if (!$this->verifyServerSignature($body, $responseHeaders, $statusCode)) {
            throw new Exception(sprintf('Could not verify signature for request to "%s"', $uri));
        }
        $array['ResponseHeaders'] = $responseHeaders;

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
        $body            = $response->body;
        $responseHeaders = $response->headers->getAll();
        $array           = json_decode($body, true);
        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }
        $array['ResponseHeaders'] = $responseHeaders;

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
        echo '<hr><pre>' . print_r($response, true) . '</pre><hr>';
        $message = [];
        if (isset($response['Error'])) {
            foreach ($response['Error'] as $error) {
                $message[] = $error['error_description'];
            }
        }
        throw new Exception(join(', ', $message));
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
        $this->logger->debug('Going to verify signature for body+headers+status');
        $dataToVerify  = $statusCode . "\n";
        $verifyHeaders = [];

        // false when no public key is present
        if (is_null($this->serverPublicKey)) {
            $this->logger->error('No public key present in class, so return FALSE.');

            return false;
        }
        //$this->logger->debug('Given headers', $headers);
        foreach ($headers as $header => $value) {

            // skip non-bunq headers or signature
            if (substr($header, 0, 7) !== 'x-bunq-' || $header === 'x-bunq-server-signature') {
                continue;
            }
            // need to have upper case variant of header:
            if (!isset($this->upperCaseHeaders[$header])) {
                throw new Exception(sprintf('No upper case variant for header "%s"', $header));
            }
            $header                 = $this->upperCaseHeaders[$header];
            $verifyHeaders[$header] = $value[0];
        }
        // sort verification headers:
        ksort($verifyHeaders);

        //$this->logger->debug('Final headers for verification', $verifyHeaders);

        // add them to data to sign:
        foreach ($verifyHeaders as $header => $value) {
            $dataToVerify .= $header . ': ' . trim($value) . "\n";
        }

        $signature    = $headers['x-bunq-server-signature'][0];
        $dataToVerify .= "\n" . $body;

        //$this->logger->debug(sprintf('Signature to verify: "%s"', $signature));

        $result = openssl_verify($dataToVerify, base64_decode($signature), $this->serverPublicKey->getPublicKey(), OPENSSL_ALGO_SHA256);

        if (is_int($result) && $result < 1) {
            $this->logger->error(sprintf('Result of verification is %d, return false.', $result));

            return false;
        }
        if (!is_int($result)) {
            $this->logger->error(sprintf('Result of verification is a boolean (%d), return false.', $result));
        }
        $this->logger->info('Signature is a match, return true.');

        return true;
    }
}