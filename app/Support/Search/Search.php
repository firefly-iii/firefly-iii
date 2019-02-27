<?php
/**
 * Search.php
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

namespace FireflyIII\Support\Search;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\DoubleTransactionFilter;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Class Search.
 */
class Search implements SearchInterface
{
    /** @var int */
    private $limit = 100;
    /** @var Collection */
    private $modifiers;
    /** @var string */
    private $originalQuery = '';
    /** @var User */
    private $user;
    /** @var array */
    private $validModifiers;
    /** @var array */
    private $words = [];

    /**
     * Search constructor.
     */
    public function __construct()
    {
        $this->modifiers      = new Collection;
        $this->validModifiers = (array)config('firefly.search_modifiers');

        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @return Collection
     */
    public function getModifiers(): Collection
    {
        return $this->modifiers;
    }

    /**
     * @return string
     */
    public function getWordsAsString(): string
    {
        $string = implode(' ', $this->words);
        if ('' === $string) {
            return \is_string($this->originalQuery) ? $this->originalQuery : '';
        }

        return $string;
    }

    /**
     * @return bool
     */
    public function hasModifiers(): bool
    {
        return $this->modifiers->count() > 0;
    }

    /**
     * @param string $query
     */
    public function parseQuery(string $query): void
    {
        $filteredQuery       = $query;
        $this->originalQuery = $query;
        $pattern             = '/[a-z_]*:[0-9a-z-.]*/i';
        $matches             = [];
        preg_match_all($pattern, $query, $matches);

        foreach ($matches[0] as $match) {
            $this->extractModifier($match);
            $filteredQuery = str_replace($match, '', $filteredQuery);
        }
        $filteredQuery = trim(str_replace(['"', "'"], '', $filteredQuery));
        if ('' !== $filteredQuery) {
            $this->words = array_map('trim', explode(' ', $filteredQuery));
        }
    }

    /**
     * @return LengthAwarePaginator
     */
    public function searchTransactions(): LengthAwarePaginator
    {
        Log::debug('Start of searchTransactions()');
        $pageSize = 50;
        $page     = 1;

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setLimit($pageSize)->setPage($page)->withOpposingAccount();
        if ($this->hasModifiers()) {
            $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        }



        $collector->setSearchWords($this->words);
        $collector->removeFilter(InternalTransferFilter::class);
        $collector->addFilter(DoubleTransactionFilter::class);

        // Most modifiers can be applied to the collector directly.
        $collector = $this->applyModifiers($collector);

        return $collector->getPaginatedTransactions();

    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param TransactionCollectorInterface $collector
     *
     * @return TransactionCollectorInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function applyModifiers(TransactionCollectorInterface $collector): TransactionCollectorInterface
    {
        /*
         * TODO:
         * 'source', 'destination',
         * 'category','budget',
         * 'bill',
         */

        foreach ($this->modifiers as $modifier) {
            switch ($modifier['type']) {
                default:
                    die(sprintf('unsupported modifier: "%s"', $modifier['type']));
                case 'amount_is':
                case 'amount':
                    $amount = app('steam')->positive((string)$modifier['value']);
                    Log::debug(sprintf('Set "%s" using collector with value "%s"', $modifier['type'], $amount));
                    $collector->amountIs($amount);
                    break;
                case 'amount_max':
                case 'amount_less':
                    $amount = app('steam')->positive((string)$modifier['value']);
                    Log::debug(sprintf('Set "%s" using collector with value "%s"', $modifier['type'], $amount));
                    $collector->amountLess($amount);
                    break;
                case 'amount_min':
                case 'amount_more':
                    $amount = app('steam')->positive((string)$modifier['value']);
                    Log::debug(sprintf('Set "%s" using collector with value "%s"', $modifier['type'], $amount));
                    $collector->amountMore($amount);
                    break;
                case 'type':
                    $collector->setTypes([ucfirst($modifier['value'])]);
                    Log::debug(sprintf('Set "%s" using collector with value "%s"', $modifier['type'], $modifier['value']));
                    break;
                case 'date':
                case 'on':
                    Log::debug(sprintf('Set "%s" using collector with value "%s"', $modifier['type'], $modifier['value']));
                    $start = new Carbon($modifier['value']);
                    $collector->setRange($start, $start);
                    break;
                case 'date_before':
                case 'before':
                    Log::debug(sprintf('Set "%s" using collector with value "%s"', $modifier['type'], $modifier['value']));
                    $before = new Carbon($modifier['value']);
                    $collector->setBefore($before);
                    break;
                case 'date_after':
                case 'after':
                    Log::debug(sprintf('Set "%s" using collector with value "%s"', $modifier['type'], $modifier['value']));
                    $after = new Carbon($modifier['value']);
                    $collector->setAfter($after);
                    break;
            }
        }

        return $collector;
    }

    /**
     * @param string $string
     */
    private function extractModifier(string $string): void
    {
        $parts = explode(':', $string);
        if (2 === \count($parts) && '' !== trim((string)$parts[1]) && '' !== trim((string)$parts[0])) {
            $type  = trim((string)$parts[0]);
            $value = trim((string)$parts[1]);
            if (\in_array($type, $this->validModifiers, true)) {
                // filter for valid type
                $this->modifiers->push(['type' => $type, 'value' => $value]);
            }
        }
    }
}
