<?php
/**
 * MetaPieChart.php
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

namespace FireflyIII\Helpers\Chart;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class MetaPieChart.
 */
class MetaPieChart implements MetaPieChartInterface
{
    /** @var array The ways to group transactions, given the type of chart. */
    static protected $grouping
        = [
            'account'  => ['opposing_account_id'],
            'budget'   => ['transaction_journal_budget_id', 'transaction_budget_id'],
            'category' => ['transaction_journal_category_id', 'transaction_category_id'],
            'tag'      => [],
        ];
    /** @var Collection Involved accounts. */
    protected $accounts;
    /** @var Collection The budgets. */
    protected $budgets;
    /** @var Collection The categories. */
    protected $categories;
    /** @var bool Collect other objects. */
    protected $collectOtherObjects = false;
    /** @var Carbon The end date./ */
    protected $end;
    /** @var array The repositories. */
    protected $repositories
        = [
            'account'  => AccountRepositoryInterface::class,
            'budget'   => BudgetRepositoryInterface::class,
            'category' => CategoryRepositoryInterface::class,
            'tag'      => TagRepositoryInterface::class,
        ];
    /** @var Carbon The start date. */
    protected $start;
    /** @var Collection The involved tags/ */
    protected $tags;
    /** @var string The total amount. */
    protected $total = '0';
    /** @var User The user. */
    protected $user;

    /**
     * MetaPieChart constructor.
     */
    public function __construct()
    {
        $this->accounts   = new Collection;
        $this->budgets    = new Collection;
        $this->categories = new Collection;
        $this->tags       = new Collection;

        if ('testing' === env('APP_ENV')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }

    }

    /**
     * Generate the chart.
     *
     * @param string $direction
     * @param string $group
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function generate(string $direction, string $group): array
    {
        $transactions = $this->getTransactions($direction);
        $grouped      = $this->groupByFields($transactions, self::$grouping[$group]);
        $chartData    = $this->organizeByType($group, $grouped);
        $key          = (string)trans('firefly.everything_else');

        // also collect all other transactions
        if ($this->collectOtherObjects && 'expense' === $direction) {
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setUser($this->user);
            $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)->setTypes([TransactionType::WITHDRAWAL]);

            $journals        = $collector->getTransactions();
            $sum             = (string)$journals->sum('transaction_amount');
            $sum             = bcmul($sum, '-1');
            $sum             = bcsub($sum, $this->total);
            $chartData[$key] = $sum;
        }

        if ($this->collectOtherObjects && 'income' === $direction) {
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setUser($this->user);
            $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)->setTypes([TransactionType::DEPOSIT]);
            $journals        = $collector->getTransactions();
            $sum             = (string)$journals->sum('transaction_amount');
            $sum             = bcsub($sum, $this->total);
            $chartData[$key] = $sum;
        }

        return $chartData;
    }

    /**
     * Accounts setter.
     *
     * @codeCoverageIgnore
     *
     * @param Collection $accounts
     *
     * @return MetaPieChartInterface
     */
    public function setAccounts(Collection $accounts): MetaPieChartInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Budgets setter.
     *
     * @codeCoverageIgnore
     *
     * @param Collection $budgets
     *
     * @return MetaPieChartInterface
     */
    public function setBudgets(Collection $budgets): MetaPieChartInterface
    {
        $this->budgets = $budgets;

        return $this;
    }

    /**
     * Categories setter.
     *
     * @codeCoverageIgnore
     *
     * @param Collection $categories
     *
     * @return MetaPieChartInterface
     */
    public function setCategories(Collection $categories): MetaPieChartInterface
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * Set if other objects should be collected.
     *
     * @codeCoverageIgnore
     *
     * @param bool $collectOtherObjects
     *
     * @return MetaPieChartInterface
     */
    public function setCollectOtherObjects(bool $collectOtherObjects): MetaPieChartInterface
    {
        $this->collectOtherObjects = $collectOtherObjects;

        return $this;
    }

    /**
     * Set the end date.
     *
     * @codeCoverageIgnore
     *
     * @param Carbon $end
     *
     * @return MetaPieChartInterface
     */
    public function setEnd(Carbon $end): MetaPieChartInterface
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Set the start date.
     *
     * @codeCoverageIgnore
     *
     * @param Carbon $start
     *
     * @return MetaPieChartInterface
     */
    public function setStart(Carbon $start): MetaPieChartInterface
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Set the tags.
     *
     * @codeCoverageIgnore
     *
     * @param Collection $tags
     *
     * @return MetaPieChartInterface
     */
    public function setTags(Collection $tags): MetaPieChartInterface
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Set the user.
     *
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return MetaPieChartInterface
     */
    public function setUser(User $user): MetaPieChartInterface
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get all transactions.
     *
     * @param string $direction
     *
     * @return Collection
     */
    protected function getTransactions(string $direction): Collection
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $types     = [TransactionType::DEPOSIT, TransactionType::TRANSFER];
        $collector->addFilter(NegativeAmountFilter::class);
        if ('expense' === $direction) {
            $types = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
            $collector->addFilter(PositiveAmountFilter::class);
            $collector->removeFilter(NegativeAmountFilter::class);
        }

        $collector->setUser($this->user);
        $collector->setAccounts($this->accounts);
        $collector->setRange($this->start, $this->end);
        $collector->setTypes($types);
        $collector->withOpposingAccount();
        $collector->addFilter(OpposingAccountFilter::class);

        if ('income' === $direction) {
            $collector->removeFilter(TransferFilter::class);
        }

        $collector->setBudgets($this->budgets);
        $collector->setCategories($this->categories);

        // @codeCoverageIgnoreStart
        if ($this->tags->count() > 0) {
            $collector->setTags($this->tags);
            $collector->withCategoryInformation();
            $collector->withBudgetInformation();
        }

        // @codeCoverageIgnoreEnd

        return $collector->getTransactions();
    }

    /**
     * Group by a specific field.
     *
     * @param Collection $set
     * @param array      $fields
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     */
    protected function groupByFields(Collection $set, array $fields): array
    {
        $grouped = [];
        if (0 === \count($fields) && $this->tags->count() > 0) {
            // do a special group on tags:
            $grouped = $this->groupByTag($set); // @codeCoverageIgnore
        }

        if (0 !== \count($fields) || $this->tags->count() <= 0) {
            $grouped = [];
            /** @var Transaction $transaction */
            foreach ($set as $transaction) {
                $values = [];
                foreach ($fields as $field) {
                    $values[] = (int)$transaction->$field;
                }
                $value           = max($values);
                $grouped[$value] = $grouped[$value] ?? '0';
                $grouped[$value] = bcadd($transaction->transaction_amount, $grouped[$value]);
            }
        }

        return $grouped;
    }

    /**
     * Organise by certain type.
     *
     * @param string $type
     * @param array  $array
     *
     * @return array
     */
    protected function organizeByType(string $type, array $array): array
    {
        $chartData  = [];
        $names      = [];
        $repository = app($this->repositories[$type]);
        $repository->setUser($this->user);
        foreach ($array as $objectId => $amount) {
            if (!isset($names[$objectId])) {
                $object           = $repository->findNull((int)$objectId);
                $name             = null === $object ? '(no name)' : $object->name;
                $names[$objectId] = $name ?? $object->tag;
            }
            $amount                       = app('steam')->positive($amount);
            $this->total                  = bcadd($this->total, $amount);
            $chartData[$names[$objectId]] = $amount;
        }

        return $chartData;
    }

    /**
     * Group by tag (slightly different).
     *
     * @codeCoverageIgnore
     *
     * @param Collection $set
     *
     * @return array
     */
    private function groupByTag(Collection $set): array
    {
        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $journal = $transaction->transactionJournal;
            $tags    = $journal->tags;
            /** @var Tag $tag */
            foreach ($tags as $tag) {
                $tagId           = $tag->id;
                $grouped[$tagId] = $grouped[$tagId] ?? '0';
                $grouped[$tagId] = bcadd($transaction->transaction_amount, $grouped[$tagId]);
            }
        }

        return $grouped;
    }
}
