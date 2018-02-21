<?php
/**
 * AccountUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use Log;

/**
 * Class AccountUpdateService
 */
class AccountUpdateService
{
    use AccountServiceTrait;

    /** @var array */
    private $validAssetFields = ['accountRole', 'accountNumber', 'currency_id', 'BIC'];
    /** @var array */
    private $validCCFields = ['accountRole', 'ccMonthlyPaymentDate', 'ccType', 'accountNumber', 'currency_id', 'BIC'];
    /** @var array */
    private $validFields = ['accountNumber', 'currency_id', 'BIC'];

    /**
     * Update account data.
     *
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     * @throws FireflyException
     * @throws Exception
     */
    public function update(Account $account, array $data): Account
    {
        // update the account itself:
        $account->name            = $data['name'];
        $account->active          = $data['active'];
        $account->virtual_balance = trim($data['virtualBalance']) === '' ? '0' : $data['virtualBalance'];
        $account->iban            = $data['iban'];
        $account->save();

        // update all meta data:
        $this->updateMetaData($account, $data);

        // has valid initial balance (IB) data?
        if ($this->validIBData($data)) {
            // then do update!
            $this->updateIB($account, $data);
        }

        // if not, delete it when exist.
        if (!$this->validIBData($data)) {
            $this->deleteIB($account);
        }

        // update note:
        if (isset($data['notes']) && null !== $data['notes']) {
            $this->updateNote($account, strval($data['notes']));
        }

        return $account;
    }

    /**
     * Update meta data for account. Depends on type which fields are valid.
     *
     * @param Account $account
     * @param array   $data
     */
    protected function updateMetaData(Account $account, array $data)
    {
        $fields = $this->validFields;

        if ($account->accountType->type === AccountType::ASSET) {
            $fields = $this->validAssetFields;
        }
        if ($account->accountType->type === AccountType::ASSET && $data['accountRole'] === 'ccAsset') {
            $fields = $this->validCCFields;
        }
        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);
        foreach ($fields as $field) {
            /** @var AccountMeta $entry */
            $entry = $account->accountMeta()->where('name', $field)->first();

            // if $data has field and $entry is null, create new one:
            if (isset($data[$field]) && null === $entry) {
                Log::debug(sprintf('Created meta-field "%s":"%s" for account #%d ("%s") ', $field, $data[$field], $account->id, $account->name));
                $factory->create(['account_id' => $account->id, 'name' => $field, 'data' => $data[$field],]);
            }

            // if $data has field and $entry is not null, update $entry:
            // let's not bother with a service.
            if (isset($data[$field]) && null !== $entry) {
                $entry->data = $data[$field];
                $entry->save();
                Log::debug(sprintf('Updated meta-field "%s":"%s" for #%d ("%s") ', $field, $data[$field], $account->id, $account->name));
            }
        }
    }


}