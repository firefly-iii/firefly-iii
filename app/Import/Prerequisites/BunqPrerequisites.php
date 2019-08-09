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

use bunq\Util\BunqEnumApiEnvironmentType;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Bunq\ApiContext;
use FireflyIII\Services\IP\IPRetrievalInterface;
use FireflyIII\User;
use Illuminate\Support\MessageBag;
use Log;

/**
 * This class contains all the routines necessary to connect to Bunq.
 */
class BunqPrerequisites implements PrerequisitesInterface
{
    /** @var User The current user */
    private $user;

    /**
     * BunqPrerequisites constructor.
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
     * @codeCoverageIgnore
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
        Log::debug('Now in BunqPrerequisites::getViewParameters()');
        $key        = '';
        $externalIP = '';
        if ($this->hasApiKey()) {
            $key = app('preferences')->getForUser($this->user, 'bunq_api_key', null)->data;
        }
        if ($this->hasExternalIP()) {
            $externalIP = app('preferences')->getForUser($this->user, 'bunq_external_ip', null)->data;
        }
        if (!$this->hasExternalIP()) {
            /** @var IPRetrievalInterface $service */
            $service    = app(IPRetrievalInterface::class);
            $externalIP = (string)$service->getIP();
        }

        return ['api_key' => $key, 'external_ip' => $externalIP];
    }

    /**
     * Indicate if all prerequisites have been met.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->hasApiKey() && $this->hasExternalIP() && $this->hasApiContext();
    }

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     *
     * @codeCoverageIgnore
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
     *
     */
    public function storePrerequisites(array $data): MessageBag
    {
        $apiKey     = $data['api_key'] ?? '';
        $externalIP = $data['external_ip'] ?? '';
        Log::debug('Storing bunq API key');
        app('preferences')->setForUser($this->user, 'bunq_api_key', $apiKey);
        app('preferences')->setForUser($this->user, 'bunq_external_ip', $externalIP);
        $environment       = $this->getBunqEnvironment();
        $deviceDescription = 'Firefly III v' . config('firefly.version');
        $permittedIps      = [$externalIP];
        Log::debug(sprintf('Environment for bunq is %s', $environment->getChoiceString()));

        try {
            /** @var ApiContext $object */
            $object     = app(ApiContext::class);
            $apiContext = $object->create($environment, $apiKey, $deviceDescription, $permittedIps);
        } catch (FireflyException $e) {
            $messages = new MessageBag();
            $messages->add('bunq_error', $e->getMessage());

            return $messages;
        }
        // store context in JSON:
        try {
            $json = $apiContext->toJson();
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            $messages = new MessageBag();
            $messages->add('bunq_error', $e->getMessage());

            return $messages;
        }
        // @codeCoverageIgnoreEnd

        // and store for user:
        app('preferences')->setForUser($this->user, 'bunq_api_context', $json);

        return new MessageBag;
    }

    /**
     * Get correct bunq environment.
     *
     * @return BunqEnumApiEnvironmentType
     * @codeCoverageIgnore
     */
    private function getBunqEnvironment(): BunqEnumApiEnvironmentType
    {
        $env = config('firefly.bunq_use_sandbox');
        if (null === $env) {
            return BunqEnumApiEnvironmentType::PRODUCTION();
        }
        if (false === $env) {
            return BunqEnumApiEnvironmentType::PRODUCTION();
        }

        return BunqEnumApiEnvironmentType::SANDBOX();
    }

    /**
     * Check if we have API context.
     *
     * @return bool
     */
    private function hasApiContext(): bool
    {
        $apiContext = app('preferences')->getForUser($this->user, 'bunq_api_context', null);
        if (null === $apiContext) {
            return false;
        }
        if ('' === (string)$apiContext->data) {
            return false;
        }

        return true;
    }

    /**
     * Check if we have the API key.
     *
     * @return bool
     */
    private function hasApiKey(): bool
    {
        $apiKey = app('preferences')->getForUser($this->user, 'bunq_api_key', null);
        if (null === $apiKey) {
            return false;
        }
        if ('' === (string)$apiKey->data) {
            return false;
        }

        return true;
    }

    /**
     * Checks if we have an external IP.
     *
     * @return bool
     */
    private function hasExternalIP(): bool
    {
        $externalIP = app('preferences')->getForUser($this->user, 'bunq_external_ip', null);
        if (null === $externalIP) {
            return false;
        }
        if ('' === (string)$externalIP->data) {
            return false;
        }

        return true;
    }
}
