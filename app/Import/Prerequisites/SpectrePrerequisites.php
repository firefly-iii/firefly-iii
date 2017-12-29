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
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Log;
use Preferences;

/**
 * This class contains all the routines necessary to connect to Spectre.
 */
class SpectrePrerequisites implements PrerequisitesInterface
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
        return 'import.spectre.prerequisites';
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        $publicKey    = $this->getPublicKey();
        $subTitle     = strval(trans('import.spectre_title'));
        $subTitleIcon = 'fa-archive';

        return compact('publicKey', 'subTitle', 'subTitleIcon');
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
        $values = [
            Preferences::getForUser($this->user, 'spectre_client_id', false),
            Preferences::getForUser($this->user, 'spectre_app_secret', false),
            Preferences::getForUser($this->user, 'spectre_service_secret', false),
        ];
        /** @var Preference $value */
        foreach ($values as $value) {
            if (false === $value->data || null === $value->data) {
                Log::info(sprintf('Config var "%s" is missing.', $value->name));

                return true;
            }
        }
        Log::debug('All prerequisites are here!');

        return false;
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
        Log::debug('Storing Spectre API keys..');
        Preferences::setForUser($this->user, 'spectre_client_id', $request->get('client_id'));
        Preferences::setForUser($this->user, 'spectre_app_secret', $request->get('app_secret'));
        Preferences::setForUser($this->user, 'spectre_service_secret', $request->get('service_secret'));
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

        Preferences::setForUser($this->user, 'spectre_private_key', $privKey);
        Preferences::setForUser($this->user, 'spectre_public_key', $pubKey['key']);
        Log::debug('Created key pair');

        return;
    }

    /**
     * Get a public key from the users preferences.
     *
     * @return string
     */
    private function getPublicKey(): string
    {
        Log::debug('get public key');
        $preference = Preferences::getForUser($this->user, 'spectre_public_key', null);
        if (null === $preference) {
            Log::debug('public key is null');
            // create key pair
            $this->createKeyPair();
        }
        $preference = Preferences::getForUser($this->user, 'spectre_public_key', null);
        Log::debug('Return public key for user');

        return $preference->data;
    }
}
