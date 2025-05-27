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
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use Illuminate\Support\Facades\DB;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Override;

use function Safe\json_decode;

/**
 * Class TransactionGroupTwig
 */
class TransactionGroupTwig extends AbstractExtension
{
    #[Override]
    public function getFunctions(): array
    {
        return [
            $this->journalArrayAmount(),
            $this->journalObjectAmount(),
            $this->journalHasMeta(),
            $this->journalGetMetaDate(),
            $this->journalGetMetaField(),
        ];
    }

    /**
     * Shows the amount for a single journal array.
     */
    public function journalArrayAmount(): TwigFunction
    {
        return new TwigFunction(
            'journalArrayAmount',
            function (array $array): string {
                // if is not a withdrawal, amount positive.
                $result = $this->normalJournalArrayAmount($array);
                // now append foreign amount, if any.
                if (null !== $array['foreign_amount']) {
                    $foreign = $this->foreignJournalArrayAmount($array);
                    $result  = sprintf('%s (%s)', $result, $foreign);
                }

                return $result;
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * Generate normal amount for transaction from a transaction group.
     */
    private function normalJournalArrayAmount(array $array): string
    {
        $type       = $array['transaction_type_type'] ?? TransactionTypeEnum::WITHDRAWAL->value;
        $amount     = $array['amount'] ?? '0';
        $colored    = true;
        $sourceType = $array['source_account_type'] ?? 'invalid';
        $amount     = $this->signAmount($amount, $type, $sourceType);

        if (TransactionTypeEnum::TRANSFER->value === $type) {
            $colored = false;
        }

        $result     = app('amount')->formatFlat($array['currency_symbol'], (int) $array['currency_decimal_places'], $amount, $colored);
        if (TransactionTypeEnum::TRANSFER->value === $type) {
            return sprintf('<span class="text-info money-transfer">%s</span>', $result);
        }

        return $result;
    }

    private function signAmount(string $amount, string $transactionType, string $sourceType): string
    {
        // withdrawals stay negative
        if (TransactionTypeEnum::WITHDRAWAL->value !== $transactionType) {
            $amount = bcmul($amount, '-1');
        }

        // opening balance and it comes from initial balance? its expense.
        if (TransactionTypeEnum::OPENING_BALANCE->value === $transactionType && AccountTypeEnum::INITIAL_BALANCE->value !== $sourceType) {
            $amount = bcmul($amount, '-1');
        }

        // reconciliation and it comes from reconciliation?
        if (TransactionTypeEnum::RECONCILIATION->value === $transactionType && AccountTypeEnum::RECONCILIATION->value !== $sourceType) {
            return bcmul($amount, '-1');
        }

        return $amount;
    }

    /**
     * Generate foreign amount for transaction from a transaction group.
     */
    private function foreignJournalArrayAmount(array $array): string
    {
        $type       = $array['transaction_type_type'] ?? TransactionTypeEnum::WITHDRAWAL->value;
        $amount     = $array['foreign_amount'] ?? '0';
        $colored    = true;

        $sourceType = $array['source_account_type'] ?? 'invalid';
        $amount     = $this->signAmount($amount, $type, $sourceType);

        if (TransactionTypeEnum::TRANSFER->value === $type) {
            $colored = false;
        }
        $result     = app('amount')->formatFlat($array['foreign_currency_symbol'], (int) $array['foreign_currency_decimal_places'], $amount, $colored);
        if (TransactionTypeEnum::TRANSFER->value === $type) {
            return sprintf('<span class="text-info money-transfer">%s</span>', $result);
        }

        return $result;
    }

    /**
     * Shows the amount for a single journal object.
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
     * Generate normal amount for transaction from a transaction group.
     */
    private function normalJournalObjectAmount(TransactionJournal $journal): string
    {
        $type       = $journal->transactionType->type;

        /** @var Transaction $first */
        $first      = $journal->transactions()->where('amount', '<', 0)->first();
        $currency   = $journal->transactionCurrency;
        $amount     = $first->amount ?? '0';
        $colored    = true;
        $sourceType = $first->account->accountType()->first()->type;

        $amount     = $this->signAmount($amount, $type, $sourceType);

        if (TransactionTypeEnum::TRANSFER->value === $type) {
            $colored = false;
        }
        $result     = app('amount')->formatFlat($currency->symbol, $currency->decimal_places, $amount, $colored);
        if (TransactionTypeEnum::TRANSFER->value === $type) {
            return sprintf('<span class="text-info money-transfer">%s</span>', $result);
        }

        return $result;
    }

    private function journalObjectHasForeign(TransactionJournal $journal): bool
    {
        /** @var Transaction $first */
        $first = $journal->transactions()->where('amount', '<', 0)->first();

        return '' !== $first->foreign_amount;
    }

    /**
     * Generate foreign amount for journal from a transaction group.
     */
    private function foreignJournalObjectAmount(TransactionJournal $journal): string
    {
        $type       = $journal->transactionType->type;

        /** @var Transaction $first */
        $first      = $journal->transactions()->where('amount', '<', 0)->first();
        $currency   = $first->foreignCurrency;
        $amount     = '' === $first->foreign_amount ? '0' : $first->foreign_amount;
        $colored    = true;
        $sourceType = $first->account->accountType()->first()->type;

        $amount     = $this->signAmount($amount, $type, $sourceType);

        if (TransactionTypeEnum::TRANSFER->value === $type) {
            $colored = false;
        }
        $result     = app('amount')->formatFlat($currency->symbol, $currency->decimal_places, $amount, $colored);
        if (TransactionTypeEnum::TRANSFER->value === $type) {
            return sprintf('<span class="text-info money-transfer">%s</span>', $result);
        }

        return $result;
    }

    public function journalHasMeta(): TwigFunction
    {
        return new TwigFunction(
            'journalHasMeta',
            static function (int $journalId, string $metaField) {
                $count = DB::table('journal_meta')
                    ->where('name', $metaField)
                    ->where('transaction_journal_id', $journalId)
                    ->whereNull('deleted_at')
                    ->count()
                ;

                return 1 === $count;
            }
        );
    }

    public function journalGetMetaDate(): TwigFunction
    {
        return new TwigFunction(
            'journalGetMetaDate',
            static function (int $journalId, string $metaField) {
                /** @var null|TransactionJournalMeta $entry */
                $entry = DB::table('journal_meta')
                    ->where('name', $metaField)
                    ->where('transaction_journal_id', $journalId)
                    ->whereNull('deleted_at')
                    ->first()
                ;
                if (null === $entry) {
                    return today(config('app.timezone'));
                }

                return new Carbon(json_decode((string) $entry->data, false));
            }
        );
    }

    public function journalGetMetaField(): TwigFunction
    {
        return new TwigFunction(
            'journalGetMetaField',
            static function (int $journalId, string $metaField) {
                /** @var null|TransactionJournalMeta $entry */
                $entry = DB::table('journal_meta')
                    ->where('name', $metaField)
                    ->where('transaction_journal_id', $journalId)
                    ->whereNull('deleted_at')
                    ->first()
                ;
                if (null === $entry) {
                    return '';
                }

                return json_decode((string) $entry->data, true);
            }
        );
    }
}
