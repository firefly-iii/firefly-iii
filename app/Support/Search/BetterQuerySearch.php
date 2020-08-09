<?php
/*
 * BetterQuerySearch.php
 * Copyright (c) 2020 james@firefly-iii.org
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Node;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\QueryParser\QueryParser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BetterQuerySearch
 * @package FireflyIII\Support\Search
 */
class BetterQuerySearch implements SearchInterface
{
    private AccountRepositoryInterface       $accountRepository;
    private BillRepositoryInterface          $billRepository;
    private BudgetRepositoryInterface        $budgetRepository;
    private CategoryRepositoryInterface      $categoryRepository;
    private TagRepositoryInterface           $tagRepository;
    private User                             $user;
    private ParsedQuery                      $query;
    private int                              $page;
    private array                            $words;
    private array                            $validOperators;
    private GroupCollectorInterface          $collector;
    private float                            $startTime;
    private Collection                       $modifiers;

    public function __construct()
    {
        $this->modifiers          = new Collection;
        $this->page               = 1;
        $this->words              = [];
        $this->validOperators     = config('firefly.search_modifiers');
        $this->startTime          = microtime(true);
        $this->accountRepository  = app(AccountRepositoryInterface::class);
        $this->categoryRepository = app(CategoryRepositoryInterface::class);
        $this->budgetRepository   = app(BudgetRepositoryInterface::class);
        $this->billRepository     = app(BillRepositoryInterface::class);
        $this->tagRepository      = app(TagRepositoryInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function getModifiers(): Collection
    {
        return $this->modifiers;
    }

    /**
     * @inheritDoc
     */
    public function getWordsAsString(): string
    {
        return implode(' ', $this->words);
    }

    /**
     * @inheritDoc
     */
    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    /**
     * @inheritDoc
     */
    public function hasModifiers(): bool
    {
        // TODO: Implement hasModifiers() method.
        die(__METHOD__);
    }

    /**
     * @inheritDoc
     */
    public function parseQuery(string $query)
    {
        $parser      = new QueryParser();
        $this->query = $parser->parse($query);

        // get limit from preferences.
        $pageSize = (int) app('preferences')->getForUser($this->user, 'listPageSize', 50)->data;
        $this->collector = app(GroupCollectorInterface::class);
        $this->collector->setLimit($pageSize)->setPage($this->page)->withAccountInformation()->withCategoryInformation()->withBudgetInformation();

        foreach ($this->query->getNodes() as $searchNode) {
            $this->handleSearchNode($searchNode);
        }

        $this->collector->setSearchWords($this->words);

    }

    /**
     * @inheritDoc
     */
    public function searchTime(): float
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * @inheritDoc
     */
    public function searchTransactions(): LengthAwarePaginator
    {
        return $this->collector->getPaginatedGroups();
    }

    /**
     * @inheritDoc
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->accountRepository->setUser($user);
        $this->billRepository->setUser($user);
        $this->categoryRepository->setUser($user);
        $this->budgetRepository->setUser($user);
    }

    /**
     * @param Node $searchNode
     * @throws FireflyException
     */
    private function handleSearchNode(Node $searchNode): void
    {
        $class = get_class($searchNode);
        switch ($class) {
            default:
                throw new FireflyException(sprintf('Firefly III search cant handle "%s"-nodes', $class));
            case Word::class:
                $this->words[] = $searchNode->getValue();
                break;
            case Field::class:
                /** @var Field $searchNode */
                // used to search for x:y
                $operator = $searchNode->getValue();
                $value    = $searchNode->getNode()->getValue();
                // must be valid operator:
                if (in_array($operator, $this->validOperators, true)) {
                    $this->updateCollector($operator, $value);
                    $this->modifiers->push([
                        'type'  => $operator,
                        'value' => $value,
                    ]);
                }
                break;
        }

    }

    /**
     * @param string $operator
     * @param string $value
     */
    private function updateCollector(string $operator, string $value): void
    {
        $allAccounts = new Collection;
        switch ($operator) {
            default:
                die(sprintf('Unsupported search operator: "%s"', $operator));
            case 'from':
            case 'source':
                // source can only be asset, liability or revenue account:
                $searchTypes = [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT, AccountType::REVENUE];
                $accounts    = $this->accountRepository->searchAccount($value, $searchTypes, 25);
                if ($accounts->count() > 0) {
                    $allAccounts = $accounts->merge($allAccounts);
                }
                $this->collector->setSourceAccounts($allAccounts);
                break;
            case 'to':
            case 'destination':
                // source can only be asset, liability or expense account:
                $searchTypes = [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT, AccountType::EXPENSE];
                $accounts    = $this->accountRepository->searchAccount($value, $searchTypes, 25);
                if ($accounts->count() > 0) {
                    $allAccounts = $accounts->merge($allAccounts);
                }
                $this->collector->setDestinationAccounts($allAccounts);
                break;
            case 'category':
                $result = $this->categoryRepository->searchCategory($value, 25);
                if ($result->count() > 0) {
                    $this->collector->setCategories($result);
                }
                break;
            case 'bill':
                $result = $this->billRepository->searchBill($value, 25);
                if ($result->count() > 0) {
                    $this->collector->setBills($result);
                }
                break;
            case 'tag':
                $result = $this->tagRepository->searchTag($value);
                if ($result->count() > 0) {
                    $this->collector->setTags($result);
                }
                break;
            case 'budget':
                $result = $this->budgetRepository->searchBudget($value, 25);
                if ($result->count() > 0) {
                    $this->collector->setBudgets($result);
                }
                break;
            case 'amount_is':
            case 'amount':
                $amount = app('steam')->positive((string) $value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountIs($amount);
                break;
            case 'amount_max':
            case 'amount_less':
                $amount = app('steam')->positive((string) $value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountLess($amount);
                break;
            case 'amount_min':
            case 'amount_more':
                $amount = app('steam')->positive((string) $value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountMore($amount);
                break;
            case 'type':
                $this->collector->setTypes([ucfirst($value)]);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                break;
            case 'date':
            case 'on':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $start = new Carbon($value);
                $this->collector->setRange($start, $start);
                break;
            case 'date_before':
            case 'before':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $before = new Carbon($value);
                $this->collector->setBefore($before);
                break;
            case 'date_after':
            case 'after':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $after = new Carbon($value);
                $this->collector->setAfter($after);
                break;
            case 'created_on':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $createdAt = new Carbon($value);
                $this->collector->setCreatedAt($createdAt);
                break;
            case 'updated_on':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $updatedAt = new Carbon($value);
                $this->collector->setUpdatedAt($updatedAt);
                break;
            case 'external_id':
                $this->collector->setExternalId($value);
                break;
            case 'internal_reference':
                $this->collector->setInternalReference($value);
                break;
        }
    }
}