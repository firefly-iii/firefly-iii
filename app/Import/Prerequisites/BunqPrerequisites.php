<?php
/**
 * BunqPrerequisites.php
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

namespace FireflyIII\Import\Prerequisites;

use Exception;
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
 */
class BunqPrerequisites implements PrerequisitesInterface
{
    /** @var User */
    private $user;

    /**
     * Returns view name that allows user to fill in prerequisites. Currently asks for the API key.
     *
     * @return string
     */
    public function getView(): string
    {
        Log::debug('Now in BunqPrerequisites::getView()');

        return 'import.bunq.prerequisites';
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        Log::debug('Now in BunqPrerequisites::getViewParameters()');
        $apiKey = Preferences::getForUser($this->user, 'bunq_api_key', null);
        $string = '';
        if (!is_null($apiKey)) {
            $string = $apiKey->data;
        }

        return ['key' => $string];
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
        Log::debug('Now in BunqPrerequisites::hasPrerequisites()');
        $apiKey   = Preferences::getForUser($this->user, 'bunq_api_key', false);
        $deviceId = Preferences::getForUser($this->user, 'bunq_device_server_id', null);
        $result   = (false === $apiKey->data || null === $apiKey->data) || is_null($deviceId);

        Log::debug(sprintf('Is device ID NULL? %s', var_export(null === $deviceId, true)));
        Log::debug(sprintf('Is apiKey->data false? %s', var_export(false === $apiKey->data, true)));
        Log::debug(sprintf('Is apiKey->data NULL? %s', var_export(null === $apiKey->data, true)));
        Log::debug(sprintf('Result is: %s', var_export($result, true)));

        return $result;
    }

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        Log::debug(sprintf('Now in setUser(#%d)', $user->id));
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
            Log::debug('Going to try and get the device registered.');
            $serverId = $this->registerDevice();
            Log::debug(sprintf('Found device server with id %d', $serverId->getId()));
        } catch (FireflyException $e) {
            Log::error(sprintf('Could not register device because: %s: %s', $e->getMessage(), $e->getTraceAsString()));
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
        Log::debug('Now in createKeyPair()');
        $private = Preferences::getForUser($this->user, 'bunq_private_key', null);
        $public  = Preferences::getForUser($this->user, 'bunq_public_key', null);

        if (!(null === $private && null === $public)) {
            Log::info('Already have public and private key, return NULL.');

            return;
        }

        Log::debug('Generate new key pair for user.');
        $keyConfig = [
            'digest_alg'       => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
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
        Log::debug('Created and stored key pair');

        return;
    }

    /**
     * When the device server cannot be registered for some reason (when previous attempts failed to be stored) this method can be used
     * to try and detect the server ID for this firefly instance.
     *
     * @return DeviceServerId
     *
     * @throws FireflyException
     */
    private function getExistingDevice(): ?DeviceServerId
    {
        Log::debug('Now in getExistingDevice()');
        $installationToken = $this->getInstallationToken();
        $serverPublicKey   = $this->getServerPublicKey();
        $request           = new ListDeviceServerRequest;
        $remoteIp          = $this->getRemoteIp();
        $request->setInstallationToken($installationToken);
        $request->setServerPublicKey($serverPublicKey);
        $request->setPrivateKey($this->getPrivateKey());
        $request->call();
        $devices = $request->getDevices();
        /** @var DeviceServer $device */
        foreach ($devices as $device) {
            if ($device->getIp() === $remoteIp) {
                return $device->getId();
            }
        }

        return null;
    }

    /**
     * Get the installation token, either from the users preferences or from Bunq.
     *
     * @return InstallationToken
     * @throws FireflyException
     */
    private function getInstallationToken(): InstallationToken
    {
        Log::debug('Now in getInstallationToken().');
        $token = Preferences::getForUser($this->user, 'bunq_installation_token', null);
        if (null !== $token) {
            Log::debug('Have installation token, return it.');

            return $token->data;
        }
        Log::debug('Have no installation token, request one.');

        // verify bunq api code:
        $publicKey = $this->getPublicKey();
        $request   = new InstallationTokenRequest;
        $request->setPublicKey($publicKey);
        $request->call();
        Log::debug('Sent request for installation token.');

        $installationToken = $request->getInstallationToken();
        $installationId    = $request->getInstallationId();
        $serverPublicKey   = $request->getServerPublicKey();

        Preferences::setForUser($this->user, 'bunq_installation_token', $installationToken);
        Preferences::setForUser($this->user, 'bunq_installation_id', $installationId);
        Preferences::setForUser($this->user, 'bunq_server_public_key', $serverPublicKey);

        Log::debug('Stored token, ID and pub key.');

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
        if (null === $preference) {
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
        Log::debug('Now in getPublicKey()');
        $preference = Preferences::getForUser($this->user, 'bunq_public_key', null);
        if (null === $preference) {
            Log::debug('public key is NULL.');
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
     *
     * @throws FireflyException
     */
    private function getRemoteIp(): string
    {
        $preference = Preferences::getForUser($this->user, 'external_ip', null);
        if (null === $preference) {
            try {
                $response = Requests::get('https://api.ipify.org');
            } catch (Requests_Exception|Exception $e) {
                throw new FireflyException(sprintf('Could not retrieve external IP: %s', $e->getMessage()));
            }
            if (200 !== $response->status_code) {
                throw new FireflyException(sprintf('Could not retrieve external IP: %d %s', $response->status_code, $response->body));
            }
            $serverIp = $response->body;
            Preferences::setForUser($this->user, 'external_ip', $serverIp);

            return $serverIp;
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
     *
     * @throws FireflyException
     */
    private function registerDevice(): DeviceServerId
    {
        Log::debug('Now in registerDevice');
        $deviceServerId = Preferences::getForUser($this->user, 'bunq_device_server_id', null);
        $serverIp       = $this->getRemoteIp();
        if (null !== $deviceServerId) {
            Log::debug('Have device server ID.');

            return $deviceServerId->data;
        }
        Log::debug('Device server ID is null, we have to find an existing one or register a new one.');
        $installationToken = $this->getInstallationToken();
        $serverPublicKey   = $this->getServerPublicKey();
        $apiKey            = Preferences::getForUser($this->user, 'bunq_api_key', '');

        // try get the current from a list:
        $deviceServerId = $this->getExistingDevice();
        if (null !== $deviceServerId) {
            Log::debug('Found device server ID in existing devices list.');

            return $deviceServerId;
        }

        Log::debug('Going to create new DeviceServerRequest() because nothing found in existing list.');
        $request = new DeviceServerRequest;
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
            // we really have to quit at this point :(
            //throw new FireflyException($e->getMessage());
        }
        if(is_null($deviceServerId)) {
            throw new FireflyException('Was not able to register server with bunq. Please see the log files.');
        }

        Preferences::setForUser($this->user, 'bunq_device_server_id', $deviceServerId);
        Log::debug(sprintf('Server ID: %s', serialize($deviceServerId)));

        return $deviceServerId;
    }
}
