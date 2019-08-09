<?php
/**
 * CreateStuff.php
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

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Requests\NewUserFormRequest;
use FireflyIII\Import\JobConfiguration\JobConfigurationInterface;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Laravel\Passport\Passport;
use Log;
use phpseclib\Crypt\RSA;

/**
 * Trait CreateStuff
 *
 */
trait CreateStuff
{

    /**
     * Creates an asset account.
     *
     * @param NewUserFormRequest $request
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    protected function createAssetAccount(NewUserFormRequest $request, TransactionCurrency $currency): bool // create stuff
    {
        /** @var AccountRepositoryInterface $repository */
        $repository   = app(AccountRepositoryInterface::class);
        $assetAccount = [
            'name'                 => $request->get('bank_name'),
            'iban'                 => null,
            'account_type'         => 'asset',
            'virtual_balance'      => 0,
            'account_type_id'      => null,
            'active'               => true,
            'account_role'          => 'defaultAsset',
            'opening_balance'      => $request->input('bank_balance'),
            'opening_balance_date' => new Carbon,
            'currency_id'          => $currency->id,
        ];

        $repository->store($assetAccount);

        return true;
    }

    /**
     * Creates a cash wallet.
     *
     * @param TransactionCurrency $currency
     * @param string $language
     *
     * @return bool
     */
    protected function createCashWalletAccount(TransactionCurrency $currency, string $language): bool // create stuff
    {
        /** @var AccountRepositoryInterface $repository */
        $repository   = app(AccountRepositoryInterface::class);
        $assetAccount = [
            'name'                 => (string)trans('firefly.cash_wallet', [], $language),
            'iban'                 => null,
            'account_type'         => 'asset',
            'virtual_balance'      => 0,
            'account_type_id'      => null,
            'active'               => true,
            'account_role'          => 'cashWalletAsset',
            'opening_balance'      => null,
            'opening_balance_date' => null,
            'currency_id'          => $currency->id,
        ];

        $repository->store($assetAccount);

        return true;
    }

    /**
     * Create new RSA keys.
     */
    protected function createOAuthKeys(): void // create stuff
    {
        $rsa  = new RSA();
        $keys = $rsa->createKey(4096);

        [$publicKey, $privateKey] = [
            Passport::keyPath('oauth-public.key'),
            Passport::keyPath('oauth-private.key'),
        ];

        if (file_exists($publicKey) || file_exists($privateKey)) {
            return;
        }
        // @codeCoverageIgnoreStart
        Log::alert('NO OAuth keys were found. They have been created.');

        file_put_contents($publicKey, array_get($keys, 'publickey'));
        file_put_contents($privateKey, array_get($keys, 'privatekey'));
    }

    /**
     * Create a savings account.
     *
     * @param NewUserFormRequest $request
     * @param TransactionCurrency $currency
     * @param string $language
     *
     * @return bool
     */
    protected function createSavingsAccount(NewUserFormRequest $request, TransactionCurrency $currency, string $language): bool // create stuff
    {
        /** @var AccountRepositoryInterface $repository */
        $repository     = app(AccountRepositoryInterface::class);
        $savingsAccount = [
            'name'                 => (string)trans('firefly.new_savings_account', ['bank_name' => $request->get('bank_name')], $language),
            'iban'                 => null,
            'account_type'         => 'asset',
            'account_type_id'      => null,
            'virtual_balance'      => 0,
            'active'               => true,
            'account_role'         => 'savingAsset',
            'opening_balance'      => $request->input('savings_balance'),
            'opening_balance_date' => new Carbon,
            'currency_id'          => $currency->id,
        ];
        $repository->store($savingsAccount);

        return true;
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     *
     * @return \FireflyIII\User
     */
    protected function createUser(array $data): User // create object
    {
        return User::create(
            [
                'email'    => $data['email'],
                'password' => bcrypt($data['password']),
            ]
        );
    }

    /**
     * Make a configurator object.
     *
     * @param ImportJob $importJob
     *
     * @return JobConfigurationInterface
     *
     * @throws FireflyException
     */
    protected function makeConfigurator(ImportJob $importJob): JobConfigurationInterface // make object
    {
        $key       = sprintf('import.configuration.%s', $importJob->provider);
        $className = (string)config($key);
        if (null === $className || !class_exists($className)) {
            throw new FireflyException(sprintf('Cannot find configurator class for job with provider "%s".', $importJob->provider)); // @codeCoverageIgnore
        }
        Log::debug(sprintf('Going to create class "%s"', $className));
        /** @var JobConfigurationInterface $configurator */
        $configurator = app($className);
        $configurator->setImportJob($importJob);

        return $configurator;
    }

    /**
     * Store the transactions.
     *
     * @param ImportJob $importJob
     *
     * @throws FireflyException
     */
    protected function storeTransactions(ImportJob $importJob): void // make object + execute
    {
        /** @var ImportArrayStorage $storage */
        $storage = app(ImportArrayStorage::class);
        $storage->setImportJob($importJob);
        try {
            $storage->store();
        } catch (FireflyException|Exception $e) {
            throw new FireflyException($e->getMessage());
        }
    }
}
