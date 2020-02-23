<?php
/**
 * TransferSearch.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Support\Search;


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Log;

/**
 * Class TransferSearch
 */
class TransferSearch implements GenericSearchInterface
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var string */
    private $amount;
    /** @var Carbon */
    private $date;
    /** @var string */
    private $description;
    /** @var Account */
    private $destination;
    /** @var Account */
    private $source;
    /** @var array */
    private $types;

    public function __construct()
    {
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->types             = [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE];
    }

    /**
     * @return Collection
     */
    public function search(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        $query = $user->transactionJournals()
                      ->leftJoin(
                          'transactions as source', static function (JoinClause $join) {
                          $join->on('transaction_journals.id', '=', 'source.transaction_journal_id');
                          $join->where('source.amount', '<', '0');
                      }
                      )
                      ->leftJoin(
                          'transactions as destination', static function (JoinClause $join) {
                          $join->on('transaction_journals.id', '=', 'destination.transaction_journal_id');
                          $join->where('destination.amount', '>', '0');
                      }
                      )
                      ->where('source.account_id', $this->source->id)
                      ->where('destination.account_id', $this->destination->id)
                      ->where('transaction_journals.description', $this->description)
                      ->where('destination.amount', $this->amount)
                      ->where('transaction_journals.date', $this->date->format('Y-m-d 00:00:00'))
        ;

        return $query->get(['transaction_journals.id', 'transaction_journals.transaction_group_id']);
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date): void
    {
        try {
            $carbon = Carbon::createFromFormat('Y-m-d', $date);
        } catch (InvalidArgumentException $e) {
            Log::error($e->getMessage());
            $carbon = Carbon::now();
        }
        $this->date = $carbon;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param string $destination
     */
    public function setDestination(string $destination): void
    {
        if (is_numeric($destination)) {
            $this->destination = $this->accountRepository->findNull((int)$destination);
        }
        if (null === $this->destination) {
            $this->destination = $this->accountRepository->findByName($destination, $this->types);
        }
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        if (is_numeric($source)) {
            $this->source = $this->accountRepository->findNull((int)$source);
        }
        if (null === $this->source) {
            $this->source = $this->accountRepository->findByName($source, $this->types);
        }
    }
}