<?php
/**
 * TransactionGroupTwig.php
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

declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Log;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class TransactionGroupTwig
 */
class TransactionGroupTwig extends AbstractExtension
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @return array
     *
     */
    public function getFunctions(): array
    {
        return [
            $this->journalArrayAmount(),
            $this->journalObjectAmount(),
            $this->groupAmount(),
            $this->journalHasMeta(),
            $this->journalGetMetaDate(),
            $this->journalGetMetaField(),
        ];
    }

    /**
     * @return TwigFunction
     */
    public function groupAmount(): TwigFunction
    {
        return new TwigFunction(
            'groupAmount',
            function (array $array, Account $account): string {
                $sums    = $array['sums'];
                $return  = [];
                $first   = reset($array['transactions']);
                $type    = $first['transaction_type_type'] ?? TransactionType::WITHDRAWAL;
                $colored = true;
                if ($type === TransactionType::TRANSFER) {
                    $colored = false;
                }


                /** @var array $sum */
                foreach ($sums as $sum) {
                    $amount = $sum['amount'];

                    $sourceType = $first['source_account_type'] ?? 'invalid';
                    $sourceAccountId = $first['source_account_id'];
                    $amount = $this->signAmountFromAccountPOV($amount, $type, $sourceType, $sourceAccountId, $account->id);

                    $return[] = app('amount')->formatFlat($sum['currency_symbol'], (int)$sum['currency_decimal_places'], $amount, $colored);
                }
                $result = implode(', ', $return);
                if ($type === TransactionType::TRANSFER) {
                    $result = sprintf('<span class="text-info">%s</span>', $result);
                }
                return $result;
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * @return TwigFunction
     */
    public function journalGetMetaDate(): TwigFunction
    {
        return new TwigFunction(
            'journalGetMetaDate',
            static function (int $journalId, string $metaField) {
                if ('testing' === config('app.env')) {
                    Log::warning('Twig TransactionGroup::journalGetMetaDate should NOT be called in the TEST environment!');
                }
                $entry = DB::table('journal_meta')
                           ->where('name', $metaField)
                           ->where('transaction_journal_id', $journalId)
                           ->whereNull('deleted_at')
                           ->first();
                if (null === $entry) {
                    return new Carbon;
                }

                return new Carbon(json_decode($entry->data, false));
            }
        );
    }

    /**
     * @return TwigFunction
     */
    public function journalGetMetaField(): TwigFunction
    {
        return new TwigFunction(
            'journalGetMetaField',
            static function (int $journalId, string $metaField) {
                if ('testing' === config('app.env')) {
                    Log::warning('Twig TransactionGroup::journalGetMetaField should NOT be called in the TEST environment!');
                }
                $entry = DB::table('journal_meta')
                           ->where('name', $metaField)
                           ->where('transaction_journal_id', $journalId)
                           ->whereNull('deleted_at')
                           ->first();
                if (null === $entry) {
                    return '';
                }

                return json_decode($entry->data, true);
            }
        );
    }

    /**
     * @return TwigFunction
     */
    public function journalHasMeta(): TwigFunction
    {
        return new TwigFunction(
            'journalHasMeta',
            static function (int $journalId, string $metaField) {
                $count = DB::table('journal_meta')
                           ->where('name', $metaField)
                           ->where('transaction_journal_id', $journalId)
                           ->whereNull('deleted_at')
                           ->count();

                return 1 === $count;
            }
        );
    }

    /**
     * Shows the amount for a single journal array.
     *
     * @return TwigFunction
     */
    public function journalArrayAmount(): TwigFunction
    {
        return new TwigFunction(
            'journalArrayAmount',
            function (array $journal, Account $account): string {
                // if is not a withdrawal, amount positive.
                $result = $this->normalJournalArrayAmount($journal, $account);
                // now append foreign amount, if any.
                if (null !== $journal['foreign_amount']) {
                    $foreign = $this->foreignJournalArrayAmount($journal, $account);
                    $result  = sprintf('%s (%s)', $result, $foreign);
                }

                return $result;
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * Shows the amount for a single journal object.
     *
     * @return TwigFunction
     */
    public function journalObjectAmount(): TwigFunction
    {
        return new TwigFunction(
            'journalObjectAmount',
            function (TransactionJournal $journal): string {
                $result = $this->normalJournalObjectAmount($journal);
                // now append foreign amount, if any.
                if ($this->journalObjectHasForeign($journal)) {
                    $foreign = $this->foreignJournalObjectAmount($journal);
                    $result  = sprintf('%s (%s)', $result, $foreign);
                }

                return $result;
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * Generate foreign amount for transaction from a transaction group.
     *
     * @param array $journal
     * @param Account $account
     *
     * @return string
     */
    private function foreignJournalArrayAmount(array $journal, Account $account): string
    {
        $type    = $journal['transaction_type_type'] ?? TransactionType::WITHDRAWAL;
        $amount  = $journal['foreign_amount'] ?? '0';
        $colored = true;

        $sourceType = $journal['source_account_type'] ?? 'invalid';
        $sourceAccountId = $journal['source_account_id'];
        $amount = $this->signAmountFromAccountPOV($amount, $type, $sourceType, $sourceAccountId, $account->id);
        
        if ($type === TransactionType::TRANSFER) {
            $colored = false;
        }
        $result = app('amount')->formatFlat($journal['foreign_currency_symbol'], (int)$journal['foreign_currency_decimal_places'], $amount, $colored);
        if ($type === TransactionType::TRANSFER) {
            $result = sprintf('<span class="text-info">%s</span>', $result);
        }

        return $result;
    }

    /**
     * Generate foreign amount for journal from a transaction group.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    private function foreignJournalObjectAmount(TransactionJournal $journal): string
    {
        $type = $journal->transactionType->type;
        /** @var Transaction $first */
        $first    = $journal->transactions()->where('amount', '<', 0)->first();
        $currency = $first->foreignCurrency;
        $amount   = $first->foreign_amount ?? '0';
        $colored  = true;
        $sourceType = $first->account()->first()->accountType()->first()->type;
        
        $amount = $this->signAmount($amount, $type, $sourceType);

        if ($type === TransactionType::TRANSFER) {
            $colored = false;
        }
        $result = app('amount')->formatFlat($currency->symbol, (int)$currency->decimal_places, $amount, $colored);
        if ($type === TransactionType::TRANSFER) {
            $result = sprintf('<span class="text-info">%s</span>', $result);
        }

        return $result;
    }

    /**
     * Generate normal amount for transaction from a transaction group.
     *
     * @param array $array
     * @param Account $account
     *
     * @return string
     */
    private function normalJournalArrayAmount(array $journal, Account $account): string
    {
        $type    = $journal['transaction_type_type'] ?? TransactionType::WITHDRAWAL;
        $amount  = $journal['amount'] ?? '0';
        $colored = true;
        $sourceType = $journal['source_account_type'] ?? 'invalid';
        $sourceAccountId = $journal['source_account_id'];
        $amount = $this->signAmount($amount, $type, $sourceType, $sourceAccountId, $account->id);
        
        if ($type === TransactionType::TRANSFER) {
            $colored = false;
        }

        $result = app('amount')->formatFlat($journal['currency_symbol'], (int)$journal['currency_decimal_places'], $amount, $colored);
        if ($type === TransactionType::TRANSFER) {
            $result = sprintf('<span class="text-info">%s</span>', $result);
        }

        return $result;
    }

    /**
     * Generate normal amount for transaction from a transaction group.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    private function normalJournalObjectAmount(TransactionJournal $journal): string
    {
        $type     = $journal->transactionType->type;
        $first    = $journal->transactions()->where('amount', '<', 0)->first();
        $currency = $journal->transactionCurrency;
        $amount   = $first->amount ?? '0';
        $colored  = true;
        $sourceType = $first->account()->first()->accountType()->first()->type;
        
        $amount = $this->signAmount($amount, $type, $sourceType);
        
        if ($type === TransactionType::TRANSFER) {
            $colored = false;
        }
        $result = app('amount')->formatFlat($currency->symbol, (int)$currency->decimal_places, $amount, $colored);
        if ($type === TransactionType::TRANSFER) {
            $result = sprintf('<span class="text-info">%s</span>', $result);
        }

        return $result;
    }

    /**
     * @param TransactionJournal $journal
     * @return bool
     */
    private function journalObjectHasForeign(TransactionJournal $journal): bool
    {
        /** @var Transaction $first */
        $first = $journal->transactions()->where('amount', '<', 0)->first();

        return null !== $first->foreign_amount;
    }

    private function signAmount( string $amount, string $transactionType, string $sourceType ): string {

        // withdrawals stay negative
        if ($transactionType !== TransactionType::WITHDRAWAL) {
            $amount = bcmul($amount, '-1');
        }

        // opening balance and it comes from initial balance? its expense.
        if ($transactionType === TransactionType::OPENING_BALANCE && AccountType::INITIAL_BALANCE !== $sourceType) {
            $amount = bcmul($amount, '-1');
        }

        // reconciliation and it comes from reconciliation?
        if ($transactionType === TransactionType::RECONCILIATION && AccountType::RECONCILIATION !== $sourceType) {
            $amount = bcmul($amount, '-1');
        }

        return $amount;
    }

    private function signAmountFromAccountPOV(string $amount, string $transactionType, string $sourceType, int $sourceAccountId, $displayedAccountId): string {
        $amount = $this->signAmount( $amount, $transactionType, $sourceType );

        // transfers stay negative from source point of view
        if ($transactionType === TransactionType::TRANSFER
            && $sourceAccountId === $displayedAccountId) {
            $amount = bcmul($amount, '-1');
        }
        
        return $amount;
    }
}
