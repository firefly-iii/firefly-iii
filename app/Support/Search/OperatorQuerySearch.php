<?php

/*
 * OperatorQuerySearch.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Support\Search;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\SearchDirection;
use FireflyIII\Enums\StringPosition;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Search\QueryParser\QueryParserInterface;
use FireflyIII\Support\Search\QueryParser\Node;
use FireflyIII\Support\Search\QueryParser\FieldNode;
use FireflyIII\Support\Search\QueryParser\StringNode;
use FireflyIII\Support\Search\QueryParser\NodeGroup;
use FireflyIII\Support\ParseDateString;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class OperatorQuerySearch
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class OperatorQuerySearch implements SearchInterface
{
    protected Carbon                    $date;
    private readonly AccountRepositoryInterface  $accountRepository;
    private readonly BillRepositoryInterface     $billRepository;
    private readonly BudgetRepositoryInterface   $budgetRepository;
    private readonly CategoryRepositoryInterface $categoryRepository;
    private GroupCollectorInterface     $collector;
    private readonly CurrencyRepositoryInterface $currencyRepository;
    private array                       $excludeTags;
    private array                       $includeAnyTags;
    // added to fix #8632
    private array                  $includeTags;
    private array                  $invalidOperators;
    private int                    $limit;
    private readonly Collection             $operators;
    private int                    $page;
    private array                  $prohibitedWords;
    private readonly float                  $startTime;
    private readonly TagRepositoryInterface $tagRepository;
    private readonly array                  $validOperators;
    private array                  $words;

    /**
     * OperatorQuerySearch constructor.
     */
    public function __construct()
    {
        app('log')->debug('Constructed OperatorQuerySearch');
        $this->operators          = new Collection();
        $this->page               = 1;
        $this->words              = [];
        $this->excludeTags        = [];
        $this->includeAnyTags     = [];
        $this->includeTags        = [];
        $this->prohibitedWords    = [];
        $this->invalidOperators   = [];
        $this->limit              = 25;
        $this->validOperators     = array_keys(config('search.operators'));
        $this->startTime          = microtime(true);
        $this->accountRepository  = app(AccountRepositoryInterface::class);
        $this->categoryRepository = app(CategoryRepositoryInterface::class);
        $this->budgetRepository   = app(BudgetRepositoryInterface::class);
        $this->billRepository     = app(BillRepositoryInterface::class);
        $this->tagRepository      = app(TagRepositoryInterface::class);
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
    }

    public function getInvalidOperators(): array
    {
        return $this->invalidOperators;
    }

    public function getModifiers(): Collection
    {
        return $this->getOperators();
    }

    public function getOperators(): Collection
    {
        return $this->operators;
    }

    public function getWordsAsString(): string
    {
        return implode(' ', $this->words);
    }

    public function getWords(): array
    {
        return $this->words;
    }

    public function getExcludedWords(): array
    {
        return $this->prohibitedWords;
    }

    /**
     * @throws FireflyException
     */
    public function hasModifiers(): bool
    {
        throw new FireflyException('Not implemented');
    }

    /**
     * @throws FireflyException
     */
    public function parseQuery(string $query): void
    {
        app('log')->debug(sprintf('Now in parseQuery("%s")', $query));

        /** @var QueryParserInterface $parser */
        $parser = app(QueryParserInterface::class);
        app('log')->debug(sprintf('Using %s as implementation for QueryParserInterface', $parser::class));

        try {
            $parsedQuery = $parser->parse($query);
        } catch (\LogicException|\TypeError $e) {
            app('log')->error($e->getMessage());
            app('log')->error(sprintf('Could not parse search: "%s".', $query));

            throw new FireflyException(sprintf('Invalid search value "%s". See the logs.', e($query)), 0, $e);
        }

        app('log')->debug(sprintf('Found %d node(s) at top-level', count($parsedQuery->getNodes())));
        $this->handleSearchNode($parsedQuery, $parsedQuery->isProhibited(false));

        // add missing information
        $this->collector->withBillInformation();

        $this->collector->setSearchWords($this->words);
        $this->collector->excludeSearchWords($this->prohibitedWords);
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function handleSearchNode(Node $node, bool $flipProhibitedFlag): void
    {
        app('log')->debug(sprintf('Now in handleSearchNode(%s)', $node::class));

        switch (true) {
            case $node instanceof StringNode:
                $this->handleStringNode($node, $flipProhibitedFlag);

                break;

            case $node instanceof FieldNode:
                $this->handleFieldNode($node, $flipProhibitedFlag);

                break;

            case $node instanceof NodeGroup:
                $this->handleNodeGroup($node, $flipProhibitedFlag);

                break;

            default:
                app('log')->error(sprintf('Cannot handle node %s', $node::class));

                throw new FireflyException(sprintf('Firefly III search can\'t handle "%s"-nodes', $node::class));
        }
    }

    private function handleNodeGroup(NodeGroup $node, bool $flipProhibitedFlag): void
    {
        $prohibited = $node->isProhibited($flipProhibitedFlag);

        foreach ($node->getNodes() as $subNode) {
            $this->handleSearchNode($subNode, $prohibited);
        }
    }

    private function handleStringNode(StringNode $node, bool $flipProhibitedFlag): void
    {
        $string     = $node->getValue();

        $prohibited = $node->isProhibited($flipProhibitedFlag);

        if ($prohibited) {
            app('log')->debug(sprintf('Exclude string "%s" from search string', $string));
            $this->prohibitedWords[] = $string;
        }
        if (!$prohibited) {
            app('log')->debug(sprintf('Add string "%s" to search string', $string));
            $this->words[] = $string;
        }
    }

    /**
     * @throws FireflyException
     */
    private function handleFieldNode(FieldNode $node, bool $flipProhibitedFlag): void
    {
        $operator   = strtolower($node->getOperator());
        $value      = $node->getValue();
        $prohibited = $node->isProhibited($flipProhibitedFlag);

        $context    = config(sprintf('search.operators.%s.needs_context', $operator));

        // is an operator that needs no context, and value is false, then prohibited = true.
        if ('false' === $value && in_array($operator, $this->validOperators, true) && false === $context && !$prohibited) {
            $prohibited = true;
            $value      = 'true';
        }
        // if the operator is prohibited, but the value is false, do an uno reverse
        if ('false' === $value && $prohibited && in_array($operator, $this->validOperators, true) && false === $context) {
            $prohibited = false;
            $value      = 'true';
        }

        // must be valid operator:
        $inArray    = in_array($operator, $this->validOperators, true);
        if ($inArray) {
            if ($this->updateCollector($operator, $value, $prohibited)) {
                $this->operators->push([
                    'type'       => self::getRootOperator($operator),
                    'value'      => $value,
                    'prohibited' => $prohibited,
                ]);
                app('log')->debug(sprintf('Added operator type "%s"', $operator));
            }
        }
        if (!$inArray) {
            app('log')->debug(sprintf('Added INVALID operator type "%s"', $operator));
            $this->invalidOperators[] = [
                'type'  => $operator,
                'value' => $value,
            ];
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function updateCollector(string $operator, string $value, bool $prohibited): bool
    {
        if ($prohibited) {
            app('log')->debug(sprintf('Operator "%s" is now "%s"', $operator, sprintf('-%s', $operator)));
            $operator = sprintf('-%s', $operator);
        }

        app('log')->debug(sprintf('Now in updateCollector("%s", "%s")', $operator, $value));

        // check if alias, replace if necessary:
        $operator = self::getRootOperator($operator);

        switch ($operator) {
            default:
                app('log')->error(sprintf('No such operator: %s', $operator));

                throw new FireflyException(sprintf('Unsupported search operator: "%s"', $operator));

                // some search operators are ignored, basically:
            case 'user_action':
                app('log')->info(sprintf('Ignore search operator "%s"', $operator));

                return false;

                //
                // all account related searches:
                //
            case 'account_is':
                $this->searchAccount($value, SearchDirection::BOTH, StringPosition::IS);

                break;

            case '-account_is':
                $this->searchAccount($value, SearchDirection::BOTH, StringPosition::IS, true);

                break;

            case 'account_contains':
                $this->searchAccount($value, SearchDirection::BOTH, StringPosition::CONTAINS);

                break;

            case '-account_contains':
                $this->searchAccount($value, SearchDirection::BOTH, StringPosition::CONTAINS, true);

                break;

            case 'account_ends':
                $this->searchAccount($value, SearchDirection::BOTH, StringPosition::ENDS);

                break;

            case '-account_ends':
                $this->searchAccount($value, SearchDirection::BOTH, StringPosition::ENDS, true);

                break;

            case 'account_starts':
                $this->searchAccount($value, SearchDirection::BOTH, StringPosition::STARTS);

                break;

            case '-account_starts':
                $this->searchAccount($value, SearchDirection::BOTH, StringPosition::STARTS, true);

                break;

            case 'account_nr_is':
                $this->searchAccountNr($value, SearchDirection::BOTH, StringPosition::IS);

                break;

            case '-account_nr_is':
                $this->searchAccountNr($value, SearchDirection::BOTH, StringPosition::IS, true);

                break;

            case 'account_nr_contains':
                $this->searchAccountNr($value, SearchDirection::BOTH, StringPosition::CONTAINS);

                break;

            case '-account_nr_contains':
                $this->searchAccountNr($value, SearchDirection::BOTH, StringPosition::CONTAINS, true);

                break;

            case 'account_nr_ends':
                $this->searchAccountNr($value, SearchDirection::BOTH, StringPosition::ENDS);

                break;

            case '-account_nr_ends':
                $this->searchAccountNr($value, SearchDirection::BOTH, StringPosition::ENDS, true);

                break;

            case 'account_nr_starts':
                $this->searchAccountNr($value, SearchDirection::BOTH, StringPosition::STARTS);

                break;

            case '-account_nr_starts':
                $this->searchAccountNr($value, SearchDirection::BOTH, StringPosition::STARTS, true);

                break;

            case 'source_account_starts':
                $this->searchAccount($value, SearchDirection::SOURCE, StringPosition::STARTS);

                break;

            case '-source_account_starts':
                $this->searchAccount($value, SearchDirection::SOURCE, StringPosition::STARTS, true);

                break;

            case 'source_account_ends':
                $this->searchAccount($value, SearchDirection::SOURCE, StringPosition::ENDS);

                break;

            case '-source_account_ends':
                $this->searchAccount($value, SearchDirection::SOURCE, StringPosition::ENDS, true);

                break;

            case 'source_account_is':
                $this->searchAccount($value, SearchDirection::SOURCE, StringPosition::IS);

                break;

            case '-source_account_is':
                $this->searchAccount($value, SearchDirection::SOURCE, StringPosition::IS, true);

                break;

            case 'source_account_nr_starts':
                $this->searchAccountNr($value, SearchDirection::SOURCE, StringPosition::STARTS);

                break;

            case '-source_account_nr_starts':
                $this->searchAccountNr($value, SearchDirection::SOURCE, StringPosition::STARTS, true);

                break;

            case 'source_account_nr_ends':
                $this->searchAccountNr($value, SearchDirection::SOURCE, StringPosition::ENDS);

                break;

            case '-source_account_nr_ends':
                $this->searchAccountNr($value, SearchDirection::SOURCE, StringPosition::ENDS, true);

                break;

            case 'source_account_nr_is':
                $this->searchAccountNr($value, SearchDirection::SOURCE, StringPosition::IS);

                break;

            case '-source_account_nr_is':
                $this->searchAccountNr($value, SearchDirection::SOURCE, StringPosition::IS, true);

                break;

            case 'source_account_nr_contains':
                $this->searchAccountNr($value, SearchDirection::SOURCE, StringPosition::CONTAINS);

                break;

            case '-source_account_nr_contains':
                $this->searchAccountNr($value, SearchDirection::SOURCE, StringPosition::CONTAINS, true);

                break;

            case 'source_account_contains':
                $this->searchAccount($value, SearchDirection::SOURCE, StringPosition::CONTAINS);

                break;

            case '-source_account_contains':
                $this->searchAccount($value, SearchDirection::SOURCE, StringPosition::CONTAINS, true);

                break;

            case 'source_account_id':
                $account                 = $this->accountRepository->find((int) $value);
                if (null !== $account) {
                    $this->collector->setSourceAccounts(new Collection([$account]));
                }
                if (null === $account) {
                    // since the source does not exist, cannot return results:
                    $this->collector->findNothing();
                }

                break;

            case '-source_account_id':
                $account                 = $this->accountRepository->find((int) $value);
                if (null !== $account) {
                    $this->collector->excludeSourceAccounts(new Collection([$account]));
                }
                if (null === $account) {
                    // since the source does not exist, cannot return results:
                    $this->collector->findNothing();
                }

                break;

            case 'journal_id':
                $parts                   = explode(',', $value);
                $this->collector->setJournalIds($parts);

                break;

            case '-journal_id':
                $parts                   = explode(',', $value);
                $this->collector->excludeJournalIds($parts);

                break;

            case 'id':
                $parts                   = explode(',', $value);
                $this->collector->setIds($parts);

                break;

            case '-id':
                $parts                   = explode(',', $value);
                $this->collector->excludeIds($parts);

                break;

            case 'destination_account_starts':
                $this->searchAccount($value, SearchDirection::DESTINATION, StringPosition::STARTS);

                break;

            case '-destination_account_starts':
                $this->searchAccount($value, SearchDirection::DESTINATION, StringPosition::STARTS, true);

                break;

            case 'destination_account_ends':
                $this->searchAccount($value, SearchDirection::DESTINATION, StringPosition::ENDS);

                break;

            case '-destination_account_ends':
                $this->searchAccount($value, SearchDirection::DESTINATION, StringPosition::ENDS, true);

                break;

            case 'destination_account_nr_starts':
                $this->searchAccountNr($value, SearchDirection::DESTINATION, StringPosition::STARTS);

                break;

            case '-destination_account_nr_starts':
                $this->searchAccountNr($value, SearchDirection::DESTINATION, StringPosition::STARTS, true);

                break;

            case 'destination_account_nr_ends':
                $this->searchAccountNr($value, SearchDirection::DESTINATION, StringPosition::ENDS);

                break;

            case '-destination_account_nr_ends':
                $this->searchAccountNr($value, SearchDirection::DESTINATION, StringPosition::ENDS, true);

                break;

            case 'destination_account_nr_is':
                $this->searchAccountNr($value, SearchDirection::DESTINATION, StringPosition::IS);

                break;

            case '-destination_account_nr_is':
                $this->searchAccountNr($value, SearchDirection::DESTINATION, StringPosition::IS, true);

                break;

            case 'destination_account_is':
                $this->searchAccount($value, SearchDirection::DESTINATION, StringPosition::IS);

                break;

            case '-destination_account_is':
                $this->searchAccount($value, SearchDirection::DESTINATION, StringPosition::IS, true);

                break;

            case 'destination_account_nr_contains':
                $this->searchAccountNr($value, SearchDirection::DESTINATION, StringPosition::CONTAINS);

                break;

            case '-destination_account_nr_contains':
                $this->searchAccountNr($value, SearchDirection::DESTINATION, StringPosition::CONTAINS, true);

                break;

            case 'destination_account_contains':
                $this->searchAccount($value, SearchDirection::DESTINATION, StringPosition::CONTAINS);

                break;

            case '-destination_account_contains':
                $this->searchAccount($value, SearchDirection::DESTINATION, StringPosition::CONTAINS, true);

                break;

            case 'destination_account_id':
                $account                 = $this->accountRepository->find((int) $value);
                if (null !== $account) {
                    $this->collector->setDestinationAccounts(new Collection([$account]));
                }
                if (null === $account) {
                    $this->collector->findNothing();
                }

                break;

            case '-destination_account_id':
                $account                 = $this->accountRepository->find((int) $value);
                if (null !== $account) {
                    $this->collector->excludeDestinationAccounts(new Collection([$account]));
                }
                if (null === $account) {
                    $this->collector->findNothing();
                }

                break;

            case 'account_id':
                $parts                   = explode(',', $value);
                $collection              = new Collection();
                foreach ($parts as $accountId) {
                    $account = $this->accountRepository->find((int) $accountId);
                    if (null !== $account) {
                        $collection->push($account);
                    }
                }
                if ($collection->count() > 0) {
                    $this->collector->setAccounts($collection);
                }
                if (0 === $collection->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-account_id':
                $parts                   = explode(',', $value);
                $collection              = new Collection();
                foreach ($parts as $accountId) {
                    $account = $this->accountRepository->find((int) $accountId);
                    if (null !== $account) {
                        $collection->push($account);
                    }
                }
                if ($collection->count() > 0) {
                    $this->collector->setNotAccounts($collection);
                }
                if (0 === $collection->count()) {
                    $this->collector->findNothing();
                }

                break;

                //
                // cash account
                //
            case 'source_is_cash':
                $account                 = $this->getCashAccount();
                $this->collector->setSourceAccounts(new Collection([$account]));

                break;

            case '-source_is_cash':
                $account                 = $this->getCashAccount();
                $this->collector->excludeSourceAccounts(new Collection([$account]));

                break;

            case 'destination_is_cash':
                $account                 = $this->getCashAccount();
                $this->collector->setDestinationAccounts(new Collection([$account]));

                break;

            case '-destination_is_cash':
                $account                 = $this->getCashAccount();
                $this->collector->excludeDestinationAccounts(new Collection([$account]));

                break;

            case 'account_is_cash':
                $account                 = $this->getCashAccount();
                $this->collector->setAccounts(new Collection([$account]));

                break;

            case '-account_is_cash':
                $account                 = $this->getCashAccount();
                $this->collector->excludeAccounts(new Collection([$account]));

                break;

                //
                // description
                //
            case 'description_starts':
                $this->collector->descriptionStarts([$value]);

                break;

            case '-description_starts':
                $this->collector->descriptionDoesNotStart([$value]);

                break;

            case 'description_ends':
                $this->collector->descriptionEnds([$value]);

                break;

            case '-description_ends':
                $this->collector->descriptionDoesNotEnd([$value]);

                break;

            case 'description_contains':
                $this->words[]           = $value;

                return false;

            case '-description_contains':
                $this->prohibitedWords[] = $value;

                break;

            case 'description_is':
                $this->collector->descriptionIs($value);

                break;

            case '-description_is':
                $this->collector->descriptionIsNot($value);

                break;

                //
                // currency
                //
            case 'currency_is':
                $currency                = $this->findCurrency($value);
                if (null !== $currency) {
                    $this->collector->setCurrency($currency);
                }
                if (null === $currency) {
                    $this->collector->findNothing();
                }

                break;

            case '-currency_is':
                $currency                = $this->findCurrency($value);
                if (null !== $currency) {
                    $this->collector->excludeCurrency($currency);
                }
                if (null === $currency) {
                    $this->collector->findNothing();
                }

                break;

            case 'foreign_currency_is':
                $currency                = $this->findCurrency($value);
                if (null !== $currency) {
                    $this->collector->setForeignCurrency($currency);
                }
                if (null === $currency) {
                    $this->collector->findNothing();
                }

                break;

            case '-foreign_currency_is':
                $currency                = $this->findCurrency($value);
                if (null !== $currency) {
                    $this->collector->excludeForeignCurrency($currency);
                }
                if (null === $currency) {
                    $this->collector->findNothing();
                }

                break;

                //
                // attachments
                //
            case 'has_attachments':
            case '-has_no_attachments':
                app('log')->debug('Set collector to filter on attachments.');
                $this->collector->hasAttachments();

                break;

            case 'has_no_attachments':
            case '-has_attachments':
                app('log')->debug('Set collector to filter on NO attachments.');
                $this->collector->hasNoAttachments();

                break;

                //
                // categories
            case '-has_any_category':
            case 'has_no_category':
                $this->collector->withoutCategory();

                break;

            case '-has_no_category':
            case 'has_any_category':
                $this->collector->withCategory();

                break;

            case 'category_is':
                $category                = $this->categoryRepository->findByName($value);
                if (null !== $category) {
                    $this->collector->setCategory($category);

                    break;
                }
                $this->collector->findNothing();

                break;

            case '-category_is':
                $category                = $this->categoryRepository->findByName($value);
                if (null !== $category) {
                    $this->collector->excludeCategory($category);

                    break;
                }

                break;

            case 'category_ends':
                $result                  = $this->categoryRepository->categoryEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-category_ends':
                $result                  = $this->categoryRepository->categoryEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case 'category_starts':
                $result                  = $this->categoryRepository->categoryStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-category_starts':
                $result                  = $this->categoryRepository->categoryStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case 'category_contains':
                $result                  = $this->categoryRepository->searchCategory($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-category_contains':
                $result                  = $this->categoryRepository->searchCategory($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

                //
                // budgets
                //
            case '-has_any_budget':
            case 'has_no_budget':
                $this->collector->withoutBudget();

                break;

            case 'has_any_budget':
            case '-has_no_budget':
                $this->collector->withBudget();

                break;

            case 'budget_contains':
                $result                  = $this->budgetRepository->searchBudget($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-budget_contains':
                $result                  = $this->budgetRepository->searchBudget($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case 'budget_is':
                $budget                  = $this->budgetRepository->findByName($value);
                if (null !== $budget) {
                    $this->collector->setBudget($budget);

                    break;
                }
                $this->collector->findNothing();

                break;

            case '-budget_is':
                $budget                  = $this->budgetRepository->findByName($value);
                if (null !== $budget) {
                    $this->collector->excludeBudget($budget);

                    break;
                }
                $this->collector->findNothing();

                break;

            case 'budget_ends':
                $result                  = $this->budgetRepository->budgetEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-budget_ends':
                $result                  = $this->budgetRepository->budgetEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case 'budget_starts':
                $result                  = $this->budgetRepository->budgetStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-budget_starts':
                $result                  = $this->budgetRepository->budgetStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

                //
                // bill
                //
            case '-has_any_bill':
            case 'has_no_bill':
                $this->collector->withoutBill();

                break;

            case '-has_no_bill':
            case 'has_any_bill':
                $this->collector->withBill();

                break;

            case 'bill_contains':
                $result                  = $this->billRepository->searchBill($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBills($result);

                    break;
                }
                $this->collector->findNothing();

                break;

            case '-bill_contains':
                $result                  = $this->billRepository->searchBill($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeBills($result);

                    break;
                }
                $this->collector->findNothing();

                break;

            case 'bill_is':
                $bill                    = $this->billRepository->findByName($value);
                if (null !== $bill) {
                    $this->collector->setBill($bill);

                    break;
                }
                $this->collector->findNothing();

                break;

            case '-bill_is':
                $bill                    = $this->billRepository->findByName($value);
                if (null !== $bill) {
                    $this->collector->excludeBills(new Collection([$bill]));

                    break;
                }
                $this->collector->findNothing();

                break;

            case 'bill_ends':
                $result                  = $this->billRepository->billEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBills($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-bill_ends':
                $result                  = $this->billRepository->billEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeBills($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case 'bill_starts':
                $result                  = $this->billRepository->billStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBills($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

            case '-bill_starts':
                $result                  = $this->billRepository->billStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->excludeBills($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }

                break;

                //
                // tags
                //
            case '-has_any_tag':
            case 'has_no_tag':
                $this->collector->withoutTags();

                break;

            case '-has_no_tag':
            case 'has_any_tag':
                $this->collector->hasAnyTag();

                break;

            case '-tag_is_not':
            case 'tag_is':
                $result                  = $this->tagRepository->findByTag($value);
                if (null !== $result) {
                    $this->includeTags[] = $result->id;
                    $this->includeTags   = array_unique($this->includeTags);
                }
                // no tags found means search must result in nothing.
                if (null === $result) {
                    app('log')->info(sprintf('No valid tags in "%s"-operator, so search will not return ANY results.', $operator));
                    $this->collector->findNothing();
                }

                break;

            case 'tag_contains':
                $tags                    = $this->tagRepository->searchTag($value);
                if (0 === $tags->count()) {
                    app('log')->info(sprintf('No valid tags in "%s"-operator, so search will not return ANY results.', $operator));
                    $this->collector->findNothing();
                }
                if ($tags->count() > 0) {
                    // changed from includeTags to includeAnyTags for #8632
                    $ids                  = array_values($tags->pluck('id')->toArray());
                    $this->includeAnyTags = array_unique(array_merge($this->includeAnyTags, $ids));
                }

                break;

            case 'tag_starts':
                $tags                    = $this->tagRepository->tagStartsWith($value);
                if (0 === $tags->count()) {
                    app('log')->info(sprintf('No valid tags in "%s"-operator, so search will not return ANY results.', $operator));
                    $this->collector->findNothing();
                }
                if ($tags->count() > 0) {
                    // changed from includeTags to includeAnyTags for #8632
                    $ids                  = array_values($tags->pluck('id')->toArray());
                    $this->includeAnyTags = array_unique(array_merge($this->includeAnyTags, $ids));
                }

                break;

            case '-tag_starts':
                $tags                    = $this->tagRepository->tagStartsWith($value);
                if (0 === $tags->count()) {
                    app('log')->info(sprintf('No valid tags in "%s"-operator, so search will not return ANY results.', $operator));
                    $this->collector->findNothing();
                }
                if ($tags->count() > 0) {
                    $ids               = array_values($tags->pluck('id')->toArray());
                    $this->excludeTags = array_unique(array_merge($this->includeTags, $ids));
                }

                break;

            case 'tag_ends':
                $tags                    = $this->tagRepository->tagEndsWith($value);
                if (0 === $tags->count()) {
                    app('log')->info(sprintf('No valid tags in "%s"-operator, so search will not return ANY results.', $operator));
                    $this->collector->findNothing();
                }
                if ($tags->count() > 0) {
                    $ids               = array_values($tags->pluck('id')->toArray());
                    $this->includeTags = array_unique(array_merge($this->includeTags, $ids));
                }

                break;

            case '-tag_ends':
                $tags                    = $this->tagRepository->tagEndsWith($value);
                if (0 === $tags->count()) {
                    app('log')->info(sprintf('No valid tags in "%s"-operator, so search will not return ANY results.', $operator));
                    $this->collector->findNothing();
                }
                if ($tags->count() > 0) {
                    $ids               = array_values($tags->pluck('id')->toArray());
                    $this->excludeTags = array_unique(array_merge($this->includeTags, $ids));
                }

                break;

            case '-tag_contains':
                $tags                    = $this->tagRepository->searchTag($value)->keyBy('id');

                if (0 === $tags->count()) {
                    app('log')->info(sprintf('No valid tags in "%s"-operator, so search will not return ANY results.', $operator));
                    $this->collector->findNothing();
                }
                if ($tags->count() > 0) {
                    $ids               = array_values($tags->pluck('id')->toArray());
                    $this->excludeTags = array_unique(array_merge($this->excludeTags, $ids));
                }

                break;

            case '-tag_is':
            case 'tag_is_not':
                $result                  = $this->tagRepository->findByTag($value);
                if (null !== $result) {
                    $this->excludeTags[] = $result->id;
                    $this->excludeTags   = array_unique($this->excludeTags);
                }

                break;

                //
                // notes
                //
            case 'notes_contains':
                $this->collector->notesContain($value);

                break;

            case '-notes_contains':
                $this->collector->notesDoNotContain($value);

                break;

            case 'notes_starts':
                $this->collector->notesStartWith($value);

                break;

            case '-notes_starts':
                $this->collector->notesDontStartWith($value);

                break;

            case 'notes_ends':
                $this->collector->notesEndWith($value);

                break;

            case '-notes_ends':
                $this->collector->notesDontEndWith($value);

                break;

            case 'notes_is':
                $this->collector->notesExactly($value);

                break;

            case '-notes_is':
                $this->collector->notesExactlyNot($value);

                break;

            case '-any_notes':
            case 'no_notes':
                $this->collector->withoutNotes();

                break;

            case 'any_notes':
            case '-no_notes':
                $this->collector->withAnyNotes();

                break;

            case 'reconciled':
                $this->collector->isReconciled();

                break;

            case '-reconciled':
                $this->collector->isNotReconciled();

                break;

                //
                // amount
                //
            case 'amount_is':
                // strip comma's, make dots.
                app('log')->debug(sprintf('Original value "%s"', $value));
                $value                   = str_replace(',', '.', $value);
                $amount                  = app('steam')->positive($value);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountIs($amount);

                break;

            case '-amount_is':
                // strip comma's, make dots.
                app('log')->debug(sprintf('Original value "%s"', $value));
                $value                   = str_replace(',', '.', $value);
                $amount                  = app('steam')->positive($value);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountIsNot($amount);

                break;

            case 'foreign_amount_is':
                // strip comma's, make dots.
                $value                   = str_replace(',', '.', $value);

                $amount                  = app('steam')->positive($value);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->foreignAmountIs($amount);

                break;

            case '-foreign_amount_is':
                // strip comma's, make dots.
                $value                   = str_replace(',', '.', $value);

                $amount                  = app('steam')->positive($value);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->foreignAmountIsNot($amount);

                break;

            case '-amount_more':
            case 'amount_less':
                // strip comma's, make dots.
                $value                   = str_replace(',', '.', $value);

                $amount                  = app('steam')->positive($value);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountLess($amount);

                break;

            case '-foreign_amount_more':
            case 'foreign_amount_less':
                // strip comma's, make dots.
                $value                   = str_replace(',', '.', $value);

                $amount                  = app('steam')->positive($value);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->foreignAmountLess($amount);

                break;

            case '-amount_less':
            case 'amount_more':
                app('log')->debug(sprintf('Now handling operator "%s"', $operator));
                // strip comma's, make dots.
                $value                   = str_replace(',', '.', $value);
                $amount                  = app('steam')->positive($value);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountMore($amount);

                break;

            case '-foreign_amount_less':
            case 'foreign_amount_more':
                app('log')->debug(sprintf('Now handling operator "%s"', $operator));
                // strip comma's, make dots.
                $value                   = str_replace(',', '.', $value);
                $amount                  = app('steam')->positive($value);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->foreignAmountMore($amount);

                break;

                //
                // transaction type
                //
            case 'transaction_type':
                $this->collector->setTypes([ucfirst($value)]);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));

                break;

            case '-transaction_type':
                $this->collector->excludeTypes([ucfirst($value)]);
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));

                break;

                //
                // dates
                //
            case '-date_on':
            case 'date_on':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactDateParams($range, $prohibited);

                return false;

            case 'date_before':
            case '-date_after':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setDateBeforeParams($range);

                return false;

            case 'date_after':
            case '-date_before':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setDateAfterParams($range);

                return false;

            case 'interest_date_on':
            case '-interest_date_on':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactMetaDateParams('interest_date', $range, $prohibited);

                return false;

            case 'interest_date_before':
            case '-interest_date_after':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateBeforeParams('interest_date', $range);

                return false;

            case 'interest_date_after':
            case '-interest_date_before':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateAfterParams('interest_date', $range);

                return false;

            case 'book_date_on':
            case '-book_date_on':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactMetaDateParams('book_date', $range, $prohibited);

                return false;

            case 'book_date_before':
            case '-book_date_after':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateBeforeParams('book_date', $range);

                return false;

            case 'book_date_after':
            case '-book_date_before':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateAfterParams('book_date', $range);

                return false;

            case 'process_date_on':
            case '-process_date_on':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactMetaDateParams('process_date', $range, $prohibited);

                return false;

            case 'process_date_before':
            case '-process_date_after':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateBeforeParams('process_date', $range);

                return false;

            case 'process_date_after':
            case '-process_date_before':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateAfterParams('process_date', $range);

                return false;

            case 'due_date_on':
            case '-due_date_on':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactMetaDateParams('due_date', $range, $prohibited);

                return false;

            case 'due_date_before':
            case '-due_date_after':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateBeforeParams('due_date', $range);

                return false;

            case 'due_date_after':
            case '-due_date_before':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateAfterParams('due_date', $range);

                return false;

            case 'payment_date_on':
            case '-payment_date_on':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactMetaDateParams('payment_date', $range, $prohibited);

                return false;

            case 'payment_date_before':
            case '-payment_date_after':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateBeforeParams('payment_date', $range);

                return false;

            case 'payment_date_after':
            case '-payment_date_before':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateAfterParams('payment_date', $range);

                return false;

            case 'invoice_date_on':
            case '-invoice_date_on':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactMetaDateParams('invoice_date', $range, $prohibited);

                return false;

            case 'invoice_date_before':
            case '-invoice_date_after':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateBeforeParams('invoice_date', $range);

                return false;

            case 'invoice_date_after':
            case '-invoice_date_before':
                $range                   = $this->parseDateRange($operator, $value);
                $this->setMetaDateAfterParams('invoice_date', $range);

                return false;

            case 'created_at_on':
            case '-created_at_on':
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactObjectDateParams('created_at', $range, $prohibited);

                return false;

            case 'created_at_before':
            case '-created_at_after':
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range                   = $this->parseDateRange($operator, $value);
                $this->setObjectDateBeforeParams('created_at', $range);

                return false;

            case 'created_at_after':
            case '-created_at_before':
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range                   = $this->parseDateRange($operator, $value);
                $this->setObjectDateAfterParams('created_at', $range);

                return false;

            case 'updated_at_on':
            case '-updated_at_on':
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range                   = $this->parseDateRange($operator, $value);
                $this->setExactObjectDateParams('updated_at', $range, $prohibited);

                return false;

            case 'updated_at_before':
            case '-updated_at_after':
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range                   = $this->parseDateRange($operator, $value);
                $this->setObjectDateBeforeParams('updated_at', $range);

                return false;

            case 'updated_at_after':
            case '-updated_at_before':
                app('log')->debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range                   = $this->parseDateRange($operator, $value);
                $this->setObjectDateAfterParams('updated_at', $range);

                return false;

                //
                // external URL
                //
            case '-any_external_url':
            case 'no_external_url':
                $this->collector->withoutExternalUrl();

                break;

            case '-no_external_url':
            case 'any_external_url':
                $this->collector->withExternalUrl();

                break;

            case '-any_external_id':
            case 'no_external_id':
                $this->collector->withoutExternalId();

                break;

            case '-no_external_id':
            case 'any_external_id':
                $this->collector->withExternalId();

                break;

            case 'external_url_is':
                $this->collector->setExternalUrl($value);

                break;

            case '-external_url_is':
                $this->collector->excludeExternalUrl($value);

                break;

            case 'external_url_contains':
                $this->collector->externalUrlContains($value);

                break;

            case '-external_url_contains':
                $this->collector->externalUrlDoesNotContain($value);

                break;

            case 'external_url_starts':
                $this->collector->externalUrlStarts($value);

                break;

            case '-external_url_starts':
                $this->collector->externalUrlDoesNotStart($value);

                break;

            case 'external_url_ends':
                $this->collector->externalUrlEnds($value);

                break;

            case '-external_url_ends':
                $this->collector->externalUrlDoesNotEnd($value);

                break;

                //
                // other fields
                //
            case 'external_id_is':
                $this->collector->setExternalId($value);

                break;

            case '-external_id_is':
                $this->collector->excludeExternalId($value);

                break;

            case 'recurrence_id':
                $this->collector->setRecurrenceId($value);

                break;

            case '-recurrence_id':
                $this->collector->excludeRecurrenceId($value);

                break;

            case 'external_id_contains':
                $this->collector->externalIdContains($value);

                break;

            case '-external_id_contains':
                $this->collector->externalIdDoesNotContain($value);

                break;

            case 'external_id_starts':
                $this->collector->externalIdStarts($value);

                break;

            case '-external_id_starts':
                $this->collector->externalIdDoesNotStart($value);

                break;

            case 'external_id_ends':
                $this->collector->externalIdEnds($value);

                break;

            case '-external_id_ends':
                $this->collector->externalIdDoesNotEnd($value);

                break;

            case 'internal_reference_is':
                $this->collector->setInternalReference($value);

                break;

            case '-internal_reference_is':
                $this->collector->excludeInternalReference($value);

                break;

            case 'internal_reference_contains':
                $this->collector->internalReferenceContains($value);

                break;

            case '-internal_reference_contains':
                $this->collector->internalReferenceDoesNotContain($value);

                break;

            case 'internal_reference_starts':
                $this->collector->internalReferenceStarts($value);

                break;

            case '-internal_reference_starts':
                $this->collector->internalReferenceDoesNotStart($value);

                break;

            case 'internal_reference_ends':
                $this->collector->internalReferenceEnds($value);

                break;

            case '-internal_reference_ends':
                $this->collector->internalReferenceDoesNotEnd($value);

                break;

            case 'attachment_name_is':
                $this->collector->attachmentNameIs($value);

                break;

            case '-attachment_name_is':
                $this->collector->attachmentNameIsNot($value);

                break;

            case 'attachment_name_contains':
                $this->collector->attachmentNameContains($value);

                break;

            case '-attachment_name_contains':
                $this->collector->attachmentNameDoesNotContain($value);

                break;

            case 'attachment_name_starts':
                $this->collector->attachmentNameStarts($value);

                break;

            case '-attachment_name_starts':
                $this->collector->attachmentNameDoesNotStart($value);

                break;

            case 'attachment_name_ends':
                $this->collector->attachmentNameEnds($value);

                break;

            case '-attachment_name_ends':
                $this->collector->attachmentNameDoesNotEnd($value);

                break;

            case 'attachment_notes_are':
                $this->collector->attachmentNotesAre($value);

                break;

            case '-attachment_notes_are':
                $this->collector->attachmentNotesAreNot($value);

                break;

            case 'attachment_notes_contains':
                $this->collector->attachmentNotesContains($value);

                break;

            case '-attachment_notes_contains':
                $this->collector->attachmentNotesDoNotContain($value);

                break;

            case 'attachment_notes_starts':
                $this->collector->attachmentNotesStarts($value);

                break;

            case '-attachment_notes_starts':
                $this->collector->attachmentNotesDoNotStart($value);

                break;

            case 'attachment_notes_ends':
                $this->collector->attachmentNotesEnds($value);

                break;

            case '-attachment_notes_ends':
                $this->collector->attachmentNotesDoNotEnd($value);

                break;

            case 'exists':
                $this->collector->exists();

                break;

            case '-exists':
                $this->collector->findNothing();

                break;

            case 'sepa_ct_is':
                $this->collector->setSepaCT($value);

                break;

            case 'source_balance_gte':
            case '-source_balance_lt':
                $this->collector->accountBalanceIs('source', '>=', $value);

                break;

            case '-source_balance_gte':
            case 'source_balance_lt':
                $this->collector->accountBalanceIs('source', '<', $value);

                break;

            case 'source_balance_gt':
            case '-source_balance_lte':
                $this->collector->accountBalanceIs('source', '>', $value);

                break;

            case '-source_balance_gt':
            case 'source_balance_lte':
                $this->collector->accountBalanceIs('source', '<=', $value);

                break;

            case 'source_balance_is':
                $this->collector->accountBalanceIs('source', '==', $value);

                break;

            case '-source_balance_is':
                $this->collector->accountBalanceIs('source', '!=', $value);

                break;

            case 'destination_balance_gte':
            case '-destination_balance_lt':
                $this->collector->accountBalanceIs('destination', '>=', $value);

                break;

            case '-destination_balance_gte':
            case 'destination_balance_lt':
                $this->collector->accountBalanceIs('destination', '<', $value);

                break;

            case 'destination_balance_gt':
            case '-destination_balance_lte':
                $this->collector->accountBalanceIs('destination', '>', $value);

                break;

            case '-destination_balance_gt':
            case 'destination_balance_lte':
                $this->collector->accountBalanceIs('destination', '<=', $value);

                break;

            case 'destination_balance_is':
                $this->collector->accountBalanceIs('destination', '==', $value);

                break;

            case '-destination_balance_is':
                $this->collector->accountBalanceIs('destination', '!=', $value);

                break;
        }

        return true;
    }

    /**
     * @throws FireflyException
     */
    public static function getRootOperator(string $operator): string
    {
        $original = $operator;
        // if the string starts with "-" (not), we can remove it and recycle
        // the configuration from the original operator.
        if (str_starts_with($operator, '-')) {
            $operator = substr($operator, 1);
        }

        $config   = config(sprintf('search.operators.%s', $operator));
        if (null === $config) {
            throw new FireflyException(sprintf('No configuration for search operator "%s"', $operator));
        }
        if (true === $config['alias']) {
            $return = $config['alias_for'];
            if (str_starts_with($original, '-')) {
                $return = sprintf('-%s', $config['alias_for']);
            }
            app('log')->debug(sprintf('"%s" is an alias for "%s", so return that instead.', $original, $return));

            return $return;
        }
        app('log')->debug(sprintf('"%s" is not an alias.', $operator));

        return $original;
    }

    /**
     * searchDirection: 1 = source (default), 2 = destination, 3 = both
     * stringPosition: 1 = start (default), 2 = end, 3 = contains, 4 = is
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    private function searchAccount(string $value, SearchDirection $searchDirection, StringPosition $stringPosition, bool $prohibited = false): void
    {
        app('log')->debug(sprintf('searchAccount("%s", %s, %s)', $value, $stringPosition->name, $searchDirection->name));

        // search direction (default): for source accounts
        $searchTypes     = [AccountTypeEnum::ASSET->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::REVENUE->value];
        $collectorMethod = 'setSourceAccounts';
        if ($prohibited) {
            $collectorMethod = 'excludeSourceAccounts';
        }

        // search direction: for destination accounts
        if (SearchDirection::DESTINATION === $searchDirection) { // destination
            // destination can be
            $searchTypes     = [AccountTypeEnum::ASSET->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::EXPENSE->value];
            $collectorMethod = 'setDestinationAccounts';
            if ($prohibited) {
                $collectorMethod = 'excludeDestinationAccounts';
            }
        }
        // either account could be:
        if (SearchDirection::BOTH === $searchDirection) {
            $searchTypes     = [AccountTypeEnum::ASSET->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::EXPENSE->value, AccountTypeEnum::REVENUE->value];
            $collectorMethod = 'setAccounts';
            if ($prohibited) {
                $collectorMethod = 'excludeAccounts';
            }
        }
        // string position (default): starts with:
        $stringMethod    = 'str_starts_with';

        // string position: ends with:
        if (StringPosition::ENDS === $stringPosition) {
            $stringMethod = 'str_ends_with';
        }
        if (StringPosition::CONTAINS === $stringPosition) {
            $stringMethod = 'str_contains';
        }
        if (StringPosition::IS === $stringPosition) {
            $stringMethod = 'stringIsEqual';
        }

        // get accounts:
        $accounts        = $this->accountRepository->searchAccount($value, $searchTypes, 1337);
        if (0 === $accounts->count() && false === $prohibited) {
            app('log')->debug('Found zero accounts, search for non existing account, NO results will be returned.');
            $this->collector->findNothing();

            return;
        }
        if (0 === $accounts->count() && true === $prohibited) {
            app('log')->debug('Found zero accounts, but the search is negated, so effectively we ignore the search parameter.');

            return;
        }
        app('log')->debug(sprintf('Found %d accounts, will filter.', $accounts->count()));
        $filtered        = $accounts->filter(
            static fn(Account $account) => $stringMethod(strtolower($account->name), strtolower($value))
        );

        if (0 === $filtered->count()) {
            app('log')->debug('Left with zero accounts, so cannot find anything, NO results will be returned.');
            $this->collector->findNothing();

            return;
        }
        app('log')->debug(sprintf('Left with %d, set as %s().', $filtered->count(), $collectorMethod));
        $this->collector->{$collectorMethod}($filtered); // @phpstan-ignore-line
    }

    /**
     * TODO make enums
     * searchDirection: 1 = source (default), 2 = destination, 3 = both
     * stringPosition: 1 = start (default), 2 = end, 3 = contains, 4 = is
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    private function searchAccountNr(string $value, SearchDirection $searchDirection, StringPosition $stringPosition, bool $prohibited = false): void
    {
        app('log')->debug(sprintf('searchAccountNr(%s, %d, %d)', $value, $searchDirection->name, $stringPosition->name));

        // search direction (default): for source accounts
        $searchTypes     = [AccountTypeEnum::ASSET->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::REVENUE->value];
        $collectorMethod = 'setSourceAccounts';
        if (true === $prohibited) {
            $collectorMethod = 'excludeSourceAccounts';
        }

        // search direction: for destination accounts
        if (SearchDirection::DESTINATION === $searchDirection) {
            // destination can be
            $searchTypes     = [AccountTypeEnum::ASSET->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::EXPENSE->value];
            $collectorMethod = 'setDestinationAccounts';
            if (true === $prohibited) {
                $collectorMethod = 'excludeDestinationAccounts';
            }
        }

        // either account could be:
        if (SearchDirection::BOTH === $searchDirection) {
            $searchTypes     = [AccountTypeEnum::ASSET->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::EXPENSE->value, AccountTypeEnum::REVENUE->value];
            $collectorMethod = 'setAccounts';
            if (true === $prohibited) {
                $collectorMethod = 'excludeAccounts';
            }
        }

        // string position (default): starts with:
        $stringMethod    = 'str_starts_with';

        // string position: ends with:
        if (StringPosition::ENDS === $stringPosition) {
            $stringMethod = 'str_ends_with';
        }
        if (StringPosition::CONTAINS === $stringPosition) {
            $stringMethod = 'str_contains';
        }
        if (StringPosition::IS === $stringPosition) {
            $stringMethod = 'stringIsEqual';
        }

        // search for accounts:
        $accounts        = $this->accountRepository->searchAccountNr($value, $searchTypes, 1337);
        if (0 === $accounts->count()) {
            app('log')->debug('Found zero accounts, search for invalid account.');
            $this->collector->findNothing();

            return;
        }

        // if found, do filter
        app('log')->debug(sprintf('Found %d accounts, will filter.', $accounts->count()));
        $filtered        = $accounts->filter(
            static function (Account $account) use ($value, $stringMethod) {
                // either IBAN or account number
                $ibanMatch      = $stringMethod(strtolower((string) $account->iban), strtolower($value));
                $accountNrMatch = false;

                /** @var AccountMeta $meta */
                foreach ($account->accountMeta as $meta) {
                    if ('account_number' === $meta->name && $stringMethod(strtolower((string) $meta->data), strtolower($value))) {
                        $accountNrMatch = true;
                    }
                }

                return $ibanMatch || $accountNrMatch;
            }
        );

        if (0 === $filtered->count()) {
            app('log')->debug('Left with zero, search for invalid account');
            $this->collector->findNothing();

            return;
        }
        app('log')->debug(sprintf('Left with %d, set as %s().', $filtered->count(), $collectorMethod));
        $this->collector->{$collectorMethod}($filtered); // @phpstan-ignore-line
    }

    private function getCashAccount(): Account
    {
        return $this->accountRepository->getCashAccount();
    }

    private function findCurrency(string $value): ?TransactionCurrency
    {
        if (str_contains($value, '(') && str_contains($value, ')')) {
            // bad method to split and get the currency code:
            $parts = explode(' ', $value);
            $value = trim($parts[count($parts) - 1], "() \t\n\r\0\x0B");
        }
        $result = $this->currencyRepository->findByCode($value);
        if (null === $result) {
            $result = $this->currencyRepository->findByName($value);
        }

        return $result;
    }

    /**
     * @throws FireflyException
     */
    private function parseDateRange(string $type, string $value): array
    {
        $parser = new ParseDateString();
        if ($parser->isDateRange($value)) {
            return $parser->parseRange($value);
        }

        try {
            $parsedDate = $parser->parseDate($value);
        } catch (FireflyException) {
            app('log')->debug(sprintf('Could not parse date "%s", will return empty array.', $value));
            $this->invalidOperators[] = [
                'type'  => $type,
                'value' => $value,
            ];

            return [];
        }

        return [
            'exact' => $parsedDate,
        ];
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setExactDateParams(array $range, bool $prohibited = false): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setExactParameters()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        app('log')->debug(sprintf('Set date_is_exact value "%s"', $value->format('Y-m-d')));
                        $this->collector->setRange($value, $value);
                        $this->operators->push(['type' => 'date_on', 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'exact_not':
                    if ($value instanceof Carbon) {
                        $this->collector->excludeRange($value, $value);
                        $this->operators->push(['type' => 'not_date_on', 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_exact YEAR value "%s"', $value));
                        $this->collector->yearIs($value);
                        $this->operators->push(['type' => 'date_on_year', 'value' => $value]);
                    }

                    break;

                case 'year_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_exact_not YEAR value "%s"', $value));
                        $this->collector->yearIsNot($value);
                        $this->operators->push(['type' => 'not_date_on_year', 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_exact MONTH value "%s"', $value));
                        $this->collector->monthIs($value);
                        $this->operators->push(['type' => 'date_on_month', 'value' => $value]);
                    }

                    break;

                case 'month_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_exact not MONTH value "%s"', $value));
                        $this->collector->monthIsNot($value);
                        $this->operators->push(['type' => 'not_date_on_month', 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_exact DAY value "%s"', $value));
                        $this->collector->dayIs($value);
                        $this->operators->push(['type' => 'date_on_day', 'value' => $value]);
                    }

                    break;

                case 'day_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set not date_is_exact DAY value "%s"', $value));
                        $this->collector->dayIsNot($value);
                        $this->operators->push(['type' => 'not_date_on_day', 'value' => $value]);
                    }

                    break;
            }
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setDateBeforeParams(array $range, bool $prohibited = false): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setDateBeforeParams()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        $this->collector->setBefore($value);
                        $this->operators->push(['type' => 'date_before', 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_before YEAR value "%s"', $value));
                        $this->collector->yearBefore($value);
                        $this->operators->push(['type' => 'date_before_year', 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_before MONTH value "%s"', $value));
                        $this->collector->monthBefore($value);
                        $this->operators->push(['type' => 'date_before_month', 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_before DAY value "%s"', $value));
                        $this->collector->dayBefore($value);
                        $this->operators->push(['type' => 'date_before_day', 'value' => $value]);
                    }

                    break;
            }
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setDateAfterParams(array $range, bool $prohibited = false): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setDateAfterParams()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        $this->collector->setAfter($value);
                        $this->operators->push(['type' => 'date_after', 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_after YEAR value "%s"', $value));
                        $this->collector->yearAfter($value);
                        $this->operators->push(['type' => 'date_after_year', 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_after MONTH value "%s"', $value));
                        $this->collector->monthAfter($value);
                        $this->operators->push(['type' => 'date_after_month', 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_after DAY value "%s"', $value));
                        $this->collector->dayAfter($value);
                        $this->operators->push(['type' => 'date_after_day', 'value' => $value]);
                    }

                    break;
            }
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setExactMetaDateParams(string $field, array $range, bool $prohibited = false): void
    {
        app('log')->debug('Now in setExactMetaDateParams()');

        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setExactMetaDateParams()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        app('log')->debug(sprintf('Set %s_is_exact value "%s"', $field, $value->format('Y-m-d')));
                        $this->collector->setMetaDateRange($value, $value, $field);
                        $this->operators->push(['type' => sprintf('%s_on', $field), 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'exact_not':
                    if ($value instanceof Carbon) {
                        app('log')->debug(sprintf('Set NOT %s_is_exact value "%s"', $field, $value->format('Y-m-d')));
                        $this->collector->excludeMetaDateRange($value, $value, $field);
                        $this->operators->push(['type' => sprintf('not_%s_on', $field), 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_exact YEAR value "%s"', $field, $value));
                        $this->collector->metaYearIs($value, $field);
                        $this->operators->push(['type' => sprintf('%s_on_year', $field), 'value' => $value]);
                    }

                    break;

                case 'year_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set NOT %s_is_exact YEAR value "%s"', $field, $value));
                        $this->collector->metaYearIsNot($value, $field);
                        $this->operators->push(['type' => sprintf('not_%s_on_year', $field), 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_exact MONTH value "%s"', $field, $value));
                        $this->collector->metaMonthIs($value, $field);
                        $this->operators->push(['type' => sprintf('%s_on_month', $field), 'value' => $value]);
                    }

                    break;

                case 'month_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set NOT %s_is_exact MONTH value "%s"', $field, $value));
                        $this->collector->metaMonthIsNot($value, $field);
                        $this->operators->push(['type' => sprintf('not_%s_on_month', $field), 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_exact DAY value "%s"', $field, $value));
                        $this->collector->metaDayIs($value, $field);
                        $this->operators->push(['type' => sprintf('%s_on_day', $field), 'value' => $value]);
                    }

                    break;

                case 'day_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set NOT %s_is_exact DAY value "%s"', $field, $value));
                        $this->collector->metaDayIsNot($value, $field);
                        $this->operators->push(['type' => sprintf('not_%s_on_day', $field), 'value' => $value]);
                    }

                    break;
            }
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setMetaDateBeforeParams(string $field, array $range, bool $prohibited = false): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setMetaDateBeforeParams()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        $this->collector->setMetaBefore($value, $field);
                        $this->operators->push(['type' => sprintf('%s_before', $field), 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_before YEAR value "%s"', $field, $value));
                        $this->collector->metaYearBefore($value, $field);
                        $this->operators->push(['type' => sprintf('%s_before_year', $field), 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_before MONTH value "%s"', $field, $value));
                        $this->collector->metaMonthBefore($value, $field);
                        $this->operators->push(['type' => sprintf('%s_before_month', $field), 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_before DAY value "%s"', $field, $value));
                        $this->collector->metaDayBefore($value, $field);
                        $this->operators->push(['type' => sprintf('%s_before_day', $field), 'value' => $value]);
                    }

                    break;
            }
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setMetaDateAfterParams(string $field, array $range, bool $prohibited = false): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setMetaDateAfterParams()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        $this->collector->setMetaAfter($value, $field);
                        $this->operators->push(['type' => sprintf('%s_after', $field), 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_after YEAR value "%s"', $field, $value));
                        $this->collector->metaYearAfter($value, $field);
                        $this->operators->push(['type' => sprintf('%s_after_year', $field), 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_after MONTH value "%s"', $field, $value));
                        $this->collector->metaMonthAfter($value, $field);
                        $this->operators->push(['type' => sprintf('%s_after_month', $field), 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_after DAY value "%s"', $field, $value));
                        $this->collector->metaDayAfter($value, $field);
                        $this->operators->push(['type' => sprintf('%s_after_day', $field), 'value' => $value]);
                    }

                    break;
            }
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setExactObjectDateParams(string $field, array $range, bool $prohibited = false): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setExactObjectDateParams()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        app('log')->debug(sprintf('Set %s_is_exact value "%s"', $field, $value->format('Y-m-d')));
                        $this->collector->setObjectRange($value, clone $value, $field);
                        $this->operators->push(['type' => sprintf('%s_on', $field), 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'exact_not':
                    if ($value instanceof Carbon) {
                        app('log')->debug(sprintf('Set NOT %s_is_exact value "%s"', $field, $value->format('Y-m-d')));
                        $this->collector->excludeObjectRange($value, clone $value, $field);
                        $this->operators->push(['type' => sprintf('not_%s_on', $field), 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_exact YEAR value "%s"', $field, $value));
                        $this->collector->objectYearIs($value, $field);
                        $this->operators->push(['type' => sprintf('%s_on_year', $field), 'value' => $value]);
                    }

                    break;

                case 'year_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set NOT %s_is_exact YEAR value "%s"', $field, $value));
                        $this->collector->objectYearIsNot($value, $field);
                        $this->operators->push(['type' => sprintf('not_%s_on_year', $field), 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_exact MONTH value "%s"', $field, $value));
                        $this->collector->objectMonthIs($value, $field);
                        $this->operators->push(['type' => sprintf('%s_on_month', $field), 'value' => $value]);
                    }

                    break;

                case 'month_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set NOT %s_is_exact MONTH value "%s"', $field, $value));
                        $this->collector->objectMonthIsNot($value, $field);
                        $this->operators->push(['type' => sprintf('not_%s_on_month', $field), 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set %s_is_exact DAY value "%s"', $field, $value));
                        $this->collector->objectDayIs($value, $field);
                        $this->operators->push(['type' => sprintf('%s_on_day', $field), 'value' => $value]);
                    }

                    break;

                case 'day_not':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set NOT %s_is_exact DAY value "%s"', $field, $value));
                        $this->collector->objectDayIsNot($value, $field);
                        $this->operators->push(['type' => sprintf('not_%s_on_day', $field), 'value' => $value]);
                    }

                    break;
            }
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setObjectDateBeforeParams(string $field, array $range, bool $prohibited = false): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setObjectDateBeforeParams()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        $this->collector->setObjectBefore($value, $field);
                        $this->operators->push(['type' => sprintf('%s_before', $field), 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_before YEAR value "%s"', $value));
                        $this->collector->objectYearBefore($value, $field);
                        $this->operators->push(['type' => sprintf('%s_before_year', $field), 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_before MONTH value "%s"', $value));
                        $this->collector->objectMonthBefore($value, $field);
                        $this->operators->push(['type' => sprintf('%s_before_month', $field), 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_before DAY value "%s"', $value));
                        $this->collector->objectDayBefore($value, $field);
                        $this->operators->push(['type' => sprintf('%s_before_day', $field), 'value' => $value]);
                    }

                    break;
            }
        }
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function setObjectDateAfterParams(string $field, array $range, bool $prohibited = false): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            $key = $prohibited ? sprintf('%s_not', $key) : $key;

            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setObjectDateAfterParams()', $key));

                case 'exact':
                    if ($value instanceof Carbon) {
                        $this->collector->setObjectAfter($value, $field);
                        $this->operators->push(['type' => sprintf('%s_after', $field), 'value' => $value->format('Y-m-d')]);
                    }

                    break;

                case 'year':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_after YEAR value "%s"', $value));
                        $this->collector->objectYearAfter($value, $field);
                        $this->operators->push(['type' => sprintf('%s_after_year', $field), 'value' => $value]);
                    }

                    break;

                case 'month':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_after MONTH value "%s"', $value));
                        $this->collector->objectMonthAfter($value, $field);
                        $this->operators->push(['type' => sprintf('%s_after_month', $field), 'value' => $value]);
                    }

                    break;

                case 'day':
                    if (is_string($value)) {
                        app('log')->debug(sprintf('Set date_is_after DAY value "%s"', $value));
                        $this->collector->objectDayAfter($value, $field);
                        $this->operators->push(['type' => sprintf('%s_after_day', $field), 'value' => $value]);
                    }

                    break;
            }
        }
    }

    public function searchTime(): float
    {
        return microtime(true) - $this->startTime;
    }

    public function searchTransactions(): LengthAwarePaginator
    {
        $this->parseTagInstructions();
        if (0 === count($this->getWords()) && 0 === count($this->getExcludedWords()) && 0 === count($this->getOperators())) {
            return new LengthAwarePaginator([], 0, 5, 1);
        }

        return $this->collector->getPaginatedGroups();
    }

    private function parseTagInstructions(): void
    {
        app('log')->debug('Now in parseTagInstructions()');
        // if exclude tags, remove excluded tags.
        if (count($this->excludeTags) > 0) {
            app('log')->debug(sprintf('%d exclude tag(s)', count($this->excludeTags)));
            $collection = new Collection();
            foreach ($this->excludeTags as $tagId) {
                $tag = $this->tagRepository->find($tagId);
                if (null !== $tag) {
                    app('log')->debug(sprintf('Exclude tag "%s"', $tag->tag));
                    $collection->push($tag);
                }
            }
            app('log')->debug(sprintf('Selecting all tags except %d excluded tag(s).', $collection->count()));
            $this->collector->setWithoutSpecificTags($collection);
        }
        // if include tags, include them:
        if (count($this->includeTags) > 0) {
            app('log')->debug(sprintf('%d include tag(s)', count($this->includeTags)));
            $collection = new Collection();
            foreach ($this->includeTags as $tagId) {
                $tag = $this->tagRepository->find($tagId);
                if (null !== $tag) {
                    app('log')->debug(sprintf('Include tag "%s"', $tag->tag));
                    $collection->push($tag);
                }
            }
            $this->collector->setAllTags($collection);
        }
        // if include ANY tags, include them: (see #8632)
        if (count($this->includeAnyTags) > 0) {
            app('log')->debug(sprintf('%d include ANY tag(s)', count($this->includeAnyTags)));
            $collection = new Collection();
            foreach ($this->includeAnyTags as $tagId) {
                $tag = $this->tagRepository->find($tagId);
                if (null !== $tag) {
                    app('log')->debug(sprintf('Include ANY tag "%s"', $tag->tag));
                    $collection->push($tag);
                }
            }
            $this->collector->setTags($collection);
        }
    }

    public function setDate(Carbon $date): void
    {
        $this->date = $date;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
        $this->collector->setPage($this->page);
    }

    public function setUser(User $user): void
    {
        $this->accountRepository->setUser($user);
        $this->billRepository->setUser($user);
        $this->categoryRepository->setUser($user);
        $this->budgetRepository->setUser($user);
        $this->tagRepository->setUser($user);
        $this->collector = app(GroupCollectorInterface::class);
        $this->collector->setUser($user);
        $this->collector->withAccountInformation()->withCategoryInformation()->withBudgetInformation();

        $this->setLimit((int) app('preferences')->getForUser($user, 'listPageSize', 50)->data);
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
        $this->collector->setLimit($this->limit);
    }
}
