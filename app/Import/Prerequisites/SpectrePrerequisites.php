<?php
/**
 * SpectrePrerequisites.php
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

use FireflyIII\Models\Preference;
use FireflyIII\User;
use Illuminate\Support\MessageBag;
use Log;

/**
 * This class contains all the routines necessary to connect to Spectre.
 */
class SpectrePrerequisites implements PrerequisitesInterface
{
    /** @var User The current user */
    private $user;

    /**
     * SpectrePrerequisites constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Returns view name that allows user to fill in prerequisites.
     *
     * @return string
     */
    public function getView(): string
    {
        return 'import.spectre.prerequisites';
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        /** @var Preference $appIdPreference */
        $appIdPreference = app('preferences')->getForUser($this->user, 'spectre_app_id', null);
        $appId           = null === $appIdPreference ? '' : $appIdPreference->data;
        /** @var Preference $secretPreference */
        $secretPreference = app('preferences')->getForUser($this->user, 'spectre_secret', null);
        $secret           = null === $secretPreference ? '' : $secretPreference->data;
        $publicKey        = $this->getPublicKey();

        return [
            'app_id'     => $appId,
            'secret'     => $secret,
            'public_key' => $publicKey,
        ];
    }

    /**
     * Indicate if all prerequisites have been met.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->hasAppId() && $this->hasSecret();
    }

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * This method responds to the user's submission of an API key. Should do nothing but store the value.
     *
     * Errors must be returned in the message bag under the field name they are requested by.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function storePrerequisites(array $data): MessageBag
    {
        Log::debug('Storing Spectre API keys..');
        app('preferences')->setForUser($this->user, 'spectre_app_id', $data['app_id'] ?? null);
        app('preferences')->setForUser($this->user, 'spectre_secret', $data['secret'] ?? null);
        Log::debug('Done!');

        return new MessageBag;
    }

    /**
     * This method creates a new public/private keypair for the user. This isn't really secure, since the key is generated on the fly with
     * no regards for HSM's, smart cards or other things. It would require some low level programming to get this right. But the private key
     * is stored encrypted in the database so it's something.
     */
    private function createKeyPair(): void
    {
        Log::debug('Generate new Spectre key pair for user.');
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

        app('preferences')->setForUser($this->user, 'spectre_private_key', $privKey);
        app('preferences')->setForUser($this->user, 'spectre_public_key', $pubKey['key']);
        Log::debug('Created key pair');

    }

    /**
     * Get a public key from the users preferences.
     *
     * @return string
     */
    private function getPublicKey(): string
    {
        Log::debug('get public key');
        $preference = app('preferences')->getForUser($this->user, 'spectre_public_key', null);
        if (null === $preference) {
            Log::debug('public key is null');
            // create key pair
            $this->createKeyPair();
        }
        $preference = app('preferences')->getForUser($this->user, 'spectre_public_key', null);
        Log::debug('Return public key for user');

        return $preference->data;
    }

    /**
     * Check if we have the App ID.
     *
     * @return bool
     */
    private function hasAppId(): bool
    {
        $appId = app('preferences')->getForUser($this->user, 'spectre_app_id', null);
        if (null === $appId) {
            return false;
        }
        if ('' === (string)$appId->data) {
            return false;
        }

        return true;
    }

    /**
     * Check if we have the secret.
     *
     * @return bool
     */
    private function hasSecret(): bool
    {
        $secret = app('preferences')->getForUser($this->user, 'spectre_secret', null);
        if (null === $secret) {
            return false;
        }
        if ('' === (string)$secret->data) {
            return false;
        }

        return true;
    }
}
