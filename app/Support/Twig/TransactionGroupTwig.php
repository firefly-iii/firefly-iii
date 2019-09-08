<?php
/**
 * TransactionGroupTwig.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Twig;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Log;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class TransactionGroupTwig
 */
class TransactionGroupTwig extends Twig_Extension
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
     * @return Twig_SimpleFunction
     */
    public function groupAmount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'groupAmount',
            static function (array $array): string {
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

                    // do multiplication thing.
                    if ($type !== TransactionType::WITHDRAWAL) {
                        $amount = bcmul($amount, '-1');
                    }

                    $return[] = app('amount')->formatFlat($sum['currency_symbol'], (int)$sum['currency_decimal_places'], $amount, $colored);
                }

                return implode(', ', $return);
            },
            ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalGetMetaDate(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
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
     * @return Twig_SimpleFunction
     */
    public function journalGetMetaField(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
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
     * @return Twig_SimpleFunction
     */
    public function journalHasMeta(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
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
     * @return Twig_SimpleFunction
     */
    public function journalArrayAmount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
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
     * Shows the amount for a single journal object.
     *
     * @return Twig_SimpleFunction
     */
    public function journalObjectAmount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalObjectAmount',
            function (TransactionJournal $journal): string {
                // if is not a withdrawal, amount positive.
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
     * @param array $array
     *
     * @return string
     */
    private function foreignJournalArrayAmount(array $array): string
    {
        $type    = $array['transaction_type_type'] ?? TransactionType::WITHDRAWAL;
        $amount  = $array['foreign_amount'] ?? '0';
        $colored = true;
        if ($type !== TransactionType::WITHDRAWAL) {
            $amount = bcmul($amount, '-1');
        }
        if ($type === TransactionType::TRANSFER) {
            $colored = false;
        }
        $result = app('amount')->formatFlat($array['foreign_currency_symbol'], (int)$array['foreign_currency_decimal_places'], $amount, $colored);
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
        if ($type !== TransactionType::WITHDRAWAL) {
            $amount = bcmul($amount, '-1');
        }
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
     *
     * @return string
     */
    private function normalJournalArrayAmount(array $array): string
    {
        $type    = $array['transaction_type_type'] ?? TransactionType::WITHDRAWAL;
        $amount  = $array['amount'] ?? '0';
        $colored = true;
        if ($type !== TransactionType::WITHDRAWAL) {
            $amount = bcmul($amount, '-1');
        }
        if ($type === TransactionType::TRANSFER) {
            $colored = false;
        }
        $result = app('amount')->formatFlat($array['currency_symbol'], (int)$array['currency_decimal_places'], $amount, $colored);
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
        if ($type !== TransactionType::WITHDRAWAL) {
            $amount = bcmul($amount, '-1');
        }
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
}
