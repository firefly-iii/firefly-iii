<?php
/**
 * BunqPrerequisites.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Prerequisites;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Bunq\Id\DeviceServerId;
use FireflyIII\Services\Bunq\Object\DeviceServer;
use FireflyIII\Services\Bunq\Object\ServerPublicKey;
use FireflyIII\Services\Bunq\Request\DeviceServerRequest;
use FireflyIII\Services\Bunq\Request\InstallationTokenRequest;
use FireflyIII\Services\Bunq\Request\ListDeviceServerRequest;
use FireflyIII\Services\Bunq\Token\InstallationToken;
use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Log;
use Preferences;
use Requests;
use Requests_Exception;

/**
 * This class contains all the routines necessary to connect to Bunq.
 *
 * @package FireflyIII\Support\Import\Prerequisites
 */
class BunqPrerequisites implements PrerequisitesInterface
{
    /** @var  User */
    private $user;

    /**
     * Returns view name that allows user to fill in prerequisites. Currently asks for the API key.
     *
     * @return string
     */
    public function getView(): string
    {
        return 'import.bunq.prerequisites';
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        return [];
    }

    /**
     * Returns if this import method has any special prerequisites such as config
     * variables or other things. The only thing we verify is the presence of the API key. Everything else
     * tumbles into place: no installation token? Will be requested. No device server? Will be created. Etc.
     *
     * @return bool
     */
    public function hasPrerequisites(): bool
    {
        $apiKey = Preferences::getForUser($this->user, 'bunq_api_key', false);

        return ($apiKey->data === false);
    }

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;

        return;
    }

    /**
     * This method responds to the user's submission of an API key. It tries to register this instance as a new Firefly III device.
     * If this fails, the error is returned in a message bag and the user is notified (this is fairly friendly).
     *
     * @param Request $request
     *
     * @return MessageBag
     */
    public function storePrerequisites(Request $request): MessageBag
    {
        $apiKey = $request->get('api_key');
        Log::debug('Storing bunq API key');
        Preferences::setForUser($this->user, 'bunq_api_key', $apiKey);
        // register Firefly III as a new device.
        $serverId = null;
        $messages = new MessageBag;
        try {
            $serverId = $this->registerDevice();
            Log::debug(sprintf('Found device server with id %d', $serverId->getId()));
        } catch (FireflyException $e) {
            $messages->add('error', $e->getMessage());
        }

        return $messages;
    }

    /**
     * This method creates a new public/private keypair for the user. This isn't really secure, since the key is generated on the fly with
     * no regards for HSM's, smart cards or other things. It would require some low level programming to get this right. But the private key
     * is stored encrypted in the database so it's something.
     */
    private function createKeyPair(): void
    {
        Log::debug('Generate new key pair for user.');
        $keyConfig = [
            "digest_alg"       => "sha512",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        // Create the private and public key
        $res = openssl_pkey_new($keyConfig);

        // Extract the private key from $res to $privKey
        $privKey = '';
        openssl_pkey_export($res, $privKey);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);

        Preferences::setForUser($this->user, 'bunq_private_key', $privKey);
        Preferences::setForUser($this->user, 'bunq_public_key', $pubKey['key']);
        Log::debug('Created key pair');

        return;

    }

    /**
     * When the device server cannot be registered for some reason (when previous attempts failed to be stored) this method can be used
     * to try and detect the server ID for this firefly instance.
     *
     * @return DeviceServerId
     * @throws FireflyException
     */
    private function getExistingDevice(): DeviceServerId
    {
        $installationToken = $this->getInstallationToken();
        $serverPublicKey   = $this->getServerPublicKey();
        $request           = new ListDeviceServerRequest;
        $remoteIp          = $this->getRemoteIp();
        $request->setInstallationToken($installationToken);
        $request->setServerPublicKey($serverPublicKey);
        $request->setPrivateKey($this->getPrivateKey());
        $request->setServer(config('firefly.bunq.server'));
        $request->call();
        $devices = $request->getDevices();
        /** @var DeviceServer $device */
        foreach ($devices as $device) {
            if ($device->getIp() === $remoteIp) {
                return $device->getId();
            }
        }
        throw new FireflyException('Cannot find existing Server Device that can be used by this instance of Firefly III.');
    }

    /**
     * Get the installation token, either from the users preferences or from Bunq.
     *
     * @return InstallationToken
     */
    private function getInstallationToken(): InstallationToken
    {
        Log::debug('Get installation token.');
        $token = Preferences::getForUser($this->user, 'bunq_installation_token', null);
        if (!is_null($token)) {
            return $token->data;
        }
        Log::debug('Have no token, request one.');

        // verify bunq api code:
        $publicKey = $this->getPublicKey();
        $request   = new InstallationTokenRequest;
        $request->setServer(strval(config('firefly.bunq.server')));
        $request->setPublicKey($publicKey);
        $request->call();
        Log::debug('Sent request');

        $installationToken = $request->getInstallationToken();
        $installationId    = $request->getInstallationId();
        $serverPublicKey   = $request->getServerPublicKey();

        Preferences::setForUser($this->user, 'bunq_installation_token', $installationToken);
        Preferences::setForUser($this->user, 'bunq_installation_id', $installationId);
        Preferences::setForUser($this->user, 'bunq_server_public_key', $serverPublicKey);

        return $installationToken;
    }

    /**
     * Get the private key from the users preferences.
     *
     * @return string
     */
    private function getPrivateKey(): string
    {
        Log::debug('get private key');
        $preference = Preferences::getForUser($this->user, 'bunq_private_key', null);
        if (is_null($preference)) {
            Log::debug('private key is null');
            // create key pair
            $this->createKeyPair();
        }
        $preference = Preferences::getForUser($this->user, 'bunq_private_key', null);
        Log::debug('Return private key for user');

        return $preference->data;
    }

    /**
     * Get a public key from the users preferences.
     *
     * @return string
     */
    private function getPublicKey(): string
    {
        Log::debug('get public key');
        $preference = Preferences::getForUser($this->user, 'bunq_public_key', null);
        if (is_null($preference)) {
            Log::debug('public key is null');
            // create key pair
            $this->createKeyPair();
        }
        $preference = Preferences::getForUser($this->user, 'bunq_public_key', null);
        Log::debug('Return public key for user');

        return $preference->data;
    }

    /**
     * Request users server remote IP. Let's assume this value will not change any time soon.
     *
     * @return string
     * @throws FireflyException
     */
    private function getRemoteIp(): string
    {
        $preference = Preferences::getForUser($this->user, 'external_ip', null);
        if (is_null($preference)) {
            try {
                $response = Requests::get('https://api.ipify.org');
            } catch (Requests_Exception $e) {
                throw new FireflyException(sprintf('Could not retrieve external IP: %s', $e->getMessage()));
            }
            if ($response->status_code !== 200) {
                throw new FireflyException(sprintf('Could not retrieve external IP: %d %s', $response->status_code, $response->body));
            }
            $ip = $response->body;
            Preferences::setForUser($this->user, 'external_ip', $ip);

            return $ip;
        }

        return $preference->data;
    }

    /**
     * Get the public key of the server, necessary to verify server signature.
     *
     * @return ServerPublicKey
     */
    private function getServerPublicKey(): ServerPublicKey
    {
        return Preferences::getForUser($this->user, 'bunq_server_public_key', null)->data;
    }

    /**
     * To install Firefly III as a new device:
     * - Send an installation token request.
     * - Use this token to send a device server request
     * - Store the installation token
     * - Use the installation token each time we need a session.
     */
    private function registerDevice(): DeviceServerId
    {
        Log::debug('Now in registerDevice');
        $deviceServerId = Preferences::getForUser($this->user, 'bunq_device_server_id', null);
        $serverIp       = $this->getRemoteIp();
        if (!is_null($deviceServerId)) {
            Log::debug('Have device server ID.');

            return $deviceServerId->data;
        }
        Log::debug('Device server id is null, do register.');
        $installationToken = $this->getInstallationToken();
        $serverPublicKey   = $this->getServerPublicKey();
        $apiKey            = Preferences::getForUser($this->user, 'bunq_api_key', '');
        $request           = new DeviceServerRequest;
        $request->setServer(strval(config('firefly.bunq.server')));
        $request->setPrivateKey($this->getPrivateKey());
        $request->setDescription('Firefly III v' . config('firefly.version') . ' for ' . $this->user->email);
        $request->setSecret($apiKey->data);
        $request->setPermittedIps([$serverIp]);
        $request->setInstallationToken($installationToken);
        $request->setServerPublicKey($serverPublicKey);
        $deviceServerId = null;
        // try to register device:
        try {
            $request->call();
            $deviceServerId = $request->getDeviceServerId();
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
        }
        if (is_null($deviceServerId)) {
            // try get the current from a list:
            $deviceServerId = $this->getExistingDevice();
        }

        Preferences::setForUser($this->user, 'bunq_device_server_id', $deviceServerId);
        Log::debug(sprintf('Server ID: %s', serialize($deviceServerId)));

        return $deviceServerId;
    }

}
