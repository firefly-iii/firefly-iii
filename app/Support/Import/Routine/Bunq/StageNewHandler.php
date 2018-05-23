<?php
/**
 * StageNewHandler.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Import\Routine\Bunq;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Object\Pointer;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
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
        /** @var Preference $preference */
        $preference = app('preferences')->getForUser($this->importJob->user, 'bunq_api_context', null);
        if (null !== $preference && '' !== (string)$preference->data) {
            // restore API context
            $apiContext = ApiContext::fromJson($preference->data);
            BunqContext::loadApiContext($apiContext);

            // list bunq accounts:
            $accounts = $this->listAccounts();

            // store in job:
            $config = $this->repository->getConfiguration($this->importJob);
            $config['accounts'] = $accounts;
            $this->repository->setConfiguration($this->importJob, $config);
            return;
        }
        throw new FireflyException('The bunq API context is unexpectedly empty.');
    }

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
     */
    private function listAccounts(): array
    {
        $accounts            = [];
        $monetaryAccountList = MonetaryAccount::listing();
        /** @var MonetaryAccount $monetaryAccount */
        foreach ($monetaryAccountList->getValue() as $monetaryAccount) {
            $mab        = $monetaryAccount->getMonetaryAccountBank();
            $array      = $this->processMab($mab);
            $accounts[] = $array;
        }

        return $accounts;
    }

    /**
     * @param MonetaryAccountBank $mab
     *
     * @return array
     */
    private function processMab(MonetaryAccountBank $mab): array
    {
        $return = [
            'id'            => $mab->getId(),
            'currency_code' => $mab->getCurrency(),
            'description'   => $mab->getDescription(),
            'balance'       => $mab->getBalance(),
            'status'        => $mab->getStatus(),
            'aliases'       => [],
            'settings'      => [
                'color'                 => $mab->getSetting()->getColor(),
                'default_avatar_status' => $mab->getSetting()->getDefaultAvatarStatus(),
                'restriction_chat'      => $mab->getSetting()->getRestrictionChat(),
            ],
        ];
        /** @var Pointer $alias */
        foreach ($mab->getAlias() as $alias) {
            $return['aliases'][] = [
                'type'  => $alias->getType(),
                'name'  => $alias->getName(),
                'value' => $alias->getValue(),
            ];
        }

        return $return;
    }
}