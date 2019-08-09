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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Tag;
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
            'account'  => ['destination_account_id'],
            'budget'   => ['budget_id'],
            'category' => ['category_id'],
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

        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
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

            /** @var GroupCollectorInterface $collector */
            $collector = app(GroupCollectorInterface::class);

            $collector->setUser($this->user);
            $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)->setTypes([TransactionType::WITHDRAWAL]);

            $sum             = $collector->getSum();
            $sum             = bcmul($sum, '-1');
            $sum             = bcsub($sum, $this->total);
            $chartData[$key] = $sum;
        }

        if ($this->collectOtherObjects && 'income' === $direction) {

            /** @var GroupCollectorInterface $collector */
            $collector = app(GroupCollectorInterface::class);

            $collector->setUser($this->user);
            $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)->setTypes([TransactionType::DEPOSIT]);
            $sum             = $collector->getSum();
            $sum             = bcsub($sum, $this->total);
            $chartData[$key] = $sum;
        }

        return $chartData;
    }

    /**
     * Get all transactions.
     *
     * @param string $direction
     *
     * @return array
     */
    protected function getTransactions(string $direction): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $types = [TransactionType::DEPOSIT, TransactionType::TRANSFER];
        if ('expense' === $direction) {
            $types = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
        }

        $collector->setUser($this->user);
        $collector->setAccounts($this->accounts);
        $collector->setRange($this->start, $this->end);
        $collector->setTypes($types);
        $collector->withAccountInformation();

        $collector->setBudgets($this->budgets);
        $collector->setCategories($this->categories);

        // @codeCoverageIgnoreStart
        if ($this->tags->count() > 0) {
            $collector->setTags($this->tags);
        }

        // @codeCoverageIgnoreEnd

        return $collector->getExtractedJournals();
    }

    /**
     * Group by a specific field.
     *
     * @param array $array
     * @param array $fields
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     */
    protected function groupByFields(array $array, array $fields): array
    {
        $grouped = [];
        if (0 === count($fields) && $this->tags->count() > 0) {
            // do a special group on tags:
            $grouped = $this->groupByTag($array); // @codeCoverageIgnore
        }

        if (0 !== count($fields) || $this->tags->count() <= 0) {
            $grouped = [];
            /** @var array $journal */
            foreach ($array as $journal) {
                $values = [];
                foreach ($fields as $field) {
                    $values[] = (int)$journal[$field];
                }
                $value           = max($values);
                $grouped[$value] = $grouped[$value] ?? '0';
                $grouped[$value] = bcadd($journal['amount'], $grouped[$value]);
            }
        }

        return $grouped;
    }

    /**
     * Group by tag (slightly different).
     *
     * @codeCoverageIgnore
     *
     * @param array $array
     *
     * @return array
     */
    private function groupByTag(array $array): array
    {
        $grouped = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            $tags = $journal['tags'] ?? [];
            /** @var Tag $tag */
            foreach ($tags as $id => $tag) {
                $grouped[$id] = $grouped[$id] ?? '0';
                $grouped[$id] = bcadd($journal['amount'], $grouped[$id]);
            }
        }

        return $grouped;
    }

    /**
     * Organise by certain type.
     *
     * @param string $type
     * @param array $array
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


}
