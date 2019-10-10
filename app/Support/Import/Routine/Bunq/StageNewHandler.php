<?php
/**
 * StageNewHandler.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Import\Routine\Bunq;

use bunq\exception\BunqException;
use bunq\Model\Generated\Endpoint\MonetaryAccount as BunqMonetaryAccount;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Endpoint\MonetaryAccountJoint;
use bunq\Model\Generated\Endpoint\MonetaryAccountLight;
use bunq\Model\Generated\Endpoint\MonetaryAccountSavings;
use bunq\Model\Generated\Object\CoOwner;
use bunq\Model\Generated\Object\Pointer;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Bunq\ApiContext;
use FireflyIII\Services\Bunq\MonetaryAccount;
use Log;

/**
 * Class StageNewHandler
 */
class StageNewHandler
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::info('Now in StageNewHandler::run()');
        /** @var Preference $preference */
        $preference = app('preferences')->getForUser($this->importJob->user, 'bunq_api_context', null);
        if (null !== $preference && '' !== (string)$preference->data) {
            // restore API context
            /** @var ApiContext $apiContext */
            $apiContext = app(ApiContext::class);
            $apiContext->fromJson($preference->data);

            // list bunq accounts:
            $accounts = $this->listAccounts();

            // store in job:
            $config             = $this->repository->getConfiguration($this->importJob);
            $config['accounts'] = $accounts;
            $this->repository->setConfiguration($this->importJob, $config);

            return;
        }
        throw new FireflyException('The bunq API context is unexpectedly empty.'); // @codeCoverageIgnore
    } // @codeCoverageIgnore

    /**
     * @param ImportJob $importJob
     *
     * @return void
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }

    /**
     * @return array
     * @throws FireflyException
     */
    private function listAccounts(): array
    {
        Log::debug('Now in StageNewHandler::listAccounts()');
        $accounts = [];
        /** @var MonetaryAccount $lister */
        $lister = app(MonetaryAccount::class);
        $result = $lister->listing();

        /** @var BunqMonetaryAccount $monetaryAccount */
        foreach ($result->getValue() as $monetaryAccount) {
            try {
                $object = $monetaryAccount->getReferencedObject();
                // @codeCoverageIgnoreStart
            } catch (BunqException $e) {
                throw new FireflyException($e->getMessage());
            }
            // @codeCoverageIgnoreEnd
            if (null !== $object) {
                $array = null;
                switch (get_class($object)) {
                    case MonetaryAccountBank::class:
                        Log::debug('Going to convert a MonetaryAccountBank');
                        /** @var MonetaryAccountBank $object */
                        $array = $this->processMab($object);
                        break;
                    case MonetaryAccountJoint::class:
                        Log::debug('Going to convert a MonetaryAccountJoint');
                        /** @var MonetaryAccountJoint $object */
                        $array = $this->processMaj($object);
                        break;
                    case MonetaryAccountLight::class:
                        Log::debug('Going to convert a MonetaryAccountLight');
                        /** @var MonetaryAccountLight $object */
                        $array = $this->processMal($object);
                        break;
                    case MonetaryAccountSavings::class;
                        Log::debug('Going to convert a MonetaryAccountSavings');
                        /** @var MonetaryAccountSavings $object */
                        $array = $this->processMas($object);
                        break;
                    default:
                        // @codeCoverageIgnoreStart
                        throw new FireflyException(sprintf('Bunq import routine cannot handle account of type "%s".', get_class($object)));
                    // @codeCoverageIgnoreEnd
                }
                if (null !== $array) {
                    Log::debug('Array is not null');
                    $accounts[] = $array;
                    $this->reportFinding($array);
                }
            }
        }
        Log::info(sprintf('Found %d account(s) at bunq', count($accounts)), $accounts);

        return $accounts;
    }

    /**
     * @param MonetaryAccountBank $mab
     *
     * @return array
     */
    private function processMab(MonetaryAccountBank $mab): array
    {
        $setting = $mab->getSetting();
        $return  = [
            'id'            => $mab->getId(),
            'currency_code' => $mab->getCurrency(),
            'description'   => $mab->getDescription(),
            'balance'       => $mab->getBalance(),
            'status'        => $mab->getStatus(),
            'type'          => 'MonetaryAccountBank',
            'iban'          => null,
            'aliases'       => [],
        ];

        if (null !== $setting) {
            $return['settings'] = [
                'color'                 => $mab->getSetting()->getColor(),
                'default_avatar_status' => $mab->getSetting()->getDefaultAvatarStatus(),
                'restriction_chat'      => $mab->getSetting()->getRestrictionChat(),
            ];
        }
        if (null !== $mab->getAlias()) {
            /** @var Pointer $alias */
            foreach ($mab->getAlias() as $alias) {
                $return['aliases'][] = [
                    'type'  => $alias->getType(),
                    'name'  => $alias->getName(),
                    'value' => $alias->getValue(),
                ];

                // store IBAN alias separately:
                if ('IBAN' === $alias->getType()) {
                    $return['iban'] = $alias->getValue();
                }
            }
        }

        return $return;
    }

    /**
     * @param MonetaryAccountJoint $maj
     *
     * @return array
     */
    private function processMaj(MonetaryAccountJoint $maj): array
    {
        Log::debug('Now processing a MAJ');
        $setting = $maj->getSetting();
        $return  = [
            'id'            => $maj->getId(),
            'currency_code' => $maj->getCurrency(),
            'description'   => $maj->getDescription(),
            'balance'       => $maj->getBalance(),
            'status'        => $maj->getStatus(),
            'type'          => 'MonetaryAccountJoint',
            'co-owners'     => [],
            'aliases'       => [],
        ];

        if (null !== $setting) {
            $return['settings'] = [
                'color'                 => $maj->getSetting()->getColor(),
                'default_avatar_status' => $maj->getSetting()->getDefaultAvatarStatus(),
                'restriction_chat'      => $maj->getSetting()->getRestrictionChat(),
            ];
            Log::debug('Setting is not null.');
        }
        if (null !== $maj->getAlias()) {
            Log::debug(sprintf('Alias is not NULL. Count is %d', count($maj->getAlias())));
            /** @var Pointer $alias */
            foreach ($maj->getAlias() as $alias) {
                $return['aliases'][] = [
                    'type'  => $alias->getType(),
                    'name'  => $alias->getName(),
                    'value' => $alias->getValue(),
                ];
                // store IBAN alias separately:
                if ('IBAN' === $alias->getType()) {
                    $return['iban'] = $alias->getValue();
                }
            }
        }
        $coOwners = $maj->getAllCoOwner() ?? [];
        Log::debug(sprintf('Count of getAllCoOwner is %d', count($coOwners)));
        /** @var CoOwner $coOwner */
        foreach ($coOwners as $coOwner) {
            $alias = $coOwner->getAlias();
            if (null !== $alias) {
                Log::debug('Alias is not NULL');
                $name = (string)$alias->getDisplayName();
                Log::debug(sprintf('Name is "%s"', $name));
                if ('' !== $name) {
                    $return['co-owners'][] = $name;
                }
            }
        }

        return $return;
    }

    /**
     * @param MonetaryAccountLight $mal
     *
     * @return array
     */
    private function processMal(MonetaryAccountLight $mal): array
    {
        $setting = $mal->getSetting();
        $return  = [
            'id'            => $mal->getId(),
            'currency_code' => $mal->getCurrency(),
            'description'   => $mal->getDescription(),
            'balance'       => $mal->getBalance(),
            'status'        => $mal->getStatus(),
            'type'          => 'MonetaryAccountLight',
            'aliases'       => [],
        ];

        if (null !== $setting) {
            $return['settings'] = [
                'color'                 => $mal->getSetting()->getColor(),
                'default_avatar_status' => $mal->getSetting()->getDefaultAvatarStatus(),
                'restriction_chat'      => $mal->getSetting()->getRestrictionChat(),
            ];
        }
        if (null !== $mal->getAlias()) {
            /** @var Pointer $alias */
            foreach ($mal->getAlias() as $alias) {
                $return['aliases'][] = [
                    'type'  => $alias->getType(),
                    'name'  => $alias->getName(),
                    'value' => $alias->getValue(),
                ];
                // store IBAN alias separately:
                if ('IBAN' === $alias->getType()) {
                    $return['iban'] = $alias->getValue();
                }
            }
        }

        return $return;
    }

    /**
     * @param MonetaryAccountSavings $object
     *
     * @return array
     */
    private function processMas(MonetaryAccountSavings $object): array
    {
        Log::debug('Now in processMas()');
        $setting = $object->getSetting();
        $return  = [
            'id'            => $object->getId(),
            'currency_code' => $object->getCurrency(),
            'description'   => $object->getDescription(),
            'balance'       => $object->getBalance(),
            'status'        => $object->getStatus(),
            'type'          => 'MonetaryAccountSavings',
            'aliases'       => [],
            'savingsGoal'   => [],
        ];

        if (null !== $setting) {
            $return['settings'] = [
                'color'                 => $object->getSetting()->getColor(),
                'default_avatar_status' => $object->getSetting()->getDefaultAvatarStatus(),
                'restriction_chat'      => $object->getSetting()->getRestrictionChat(),
            ];
        }
        if (null !== $object->getAlias()) {
            Log::debug('MAS has aliases');
            /** @var Pointer $alias */
            foreach ($object->getAlias() as $alias) {
                Log::debug(sprintf('Alias type is "%s", with name "%s" and value "%s"', $alias->getType(), $alias->getName(), $alias->getValue()));
                $return['aliases'][] = [
                    'type'  => $alias->getType(),
                    'name'  => $alias->getName(),
                    'value' => $alias->getValue(),
                ];
                // store IBAN alias separately:
                if ('IBAN' === $alias->getType()) {
                    $return['iban'] = $alias->getValue();
                }
            }
        }
        $goal                  = $object->getSavingsGoal();
        $return['savingsGoal'] = [
            'currency'   => $goal->getCurrency(),
            'value'      => $goal->getValue(),
            'percentage' => $object->getSavingsGoalProgress(),
        ];
        Log::debug('End of processMas()', $return);

        return $return;
    }

    /**
     * Basic report method.
     *
     * @param array $array
     */
    private function reportFinding(array $array): void
    {
        $bunqId          = $array['id'] ?? '';
        $bunqDescription = $array['description'] ?? '';
        $bunqIBAN        = '';

        // find IBAN:
        $aliases = $array['aliases'] ?? [];
        foreach ($aliases as $alias) {
            $type = $alias['type'] ?? 'none';
            if ('IBAN' === $type) {
                $bunqIBAN = $alias['value'] ?? '';
            }
        }

        Log::info(sprintf('Found account at bunq. ID #%d, title "%s" and IBAN "%s" ', $bunqId, $bunqDescription, $bunqIBAN));
    }
}
