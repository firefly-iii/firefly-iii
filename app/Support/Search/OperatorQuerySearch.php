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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\ParseDateString;
use FireflyIII\User;
use Gdbots\QueryParser\Node\Date;
use Gdbots\QueryParser\Node\Emoji;
use Gdbots\QueryParser\Node\Emoticon;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Hashtag;
use Gdbots\QueryParser\Node\Mention;
use Gdbots\QueryParser\Node\Node;
use Gdbots\QueryParser\Node\Numbr;
use Gdbots\QueryParser\Node\Phrase;
use Gdbots\QueryParser\Node\Subquery;
use Gdbots\QueryParser\Node\Url;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\QueryParser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use LogicException;
use TypeError;

/**
 * Class OperatorQuerySearch
 */
class OperatorQuerySearch implements SearchInterface
{
    private AccountRepositoryInterface  $accountRepository;
    private BillRepositoryInterface     $billRepository;
    private BudgetRepositoryInterface   $budgetRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private GroupCollectorInterface     $collector;
    private CurrencyRepositoryInterface $currencyRepository;
    private Carbon                      $date;
    private array                       $invalidOperators;
    private int                         $limit;
    private Collection                  $operators;
    private int                         $page;
    private float                       $startTime;
    private TagRepositoryInterface      $tagRepository;
    private array                       $validOperators;
    private array                       $words;

    /**
     * OperatorQuerySearch constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        Log::debug('Constructed OperatorQuerySearch');
        $this->operators          = new Collection;
        $this->page               = 1;
        $this->words              = [];
        $this->invalidOperators   = [];
        $this->limit              = 25;
        $this->date               = today(config('app.timezone'));
        $this->validOperators     = array_keys(config('search.operators'));
        $this->startTime          = microtime(true);
        $this->accountRepository  = app(AccountRepositoryInterface::class);
        $this->categoryRepository = app(CategoryRepositoryInterface::class);
        $this->budgetRepository   = app(BudgetRepositoryInterface::class);
        $this->billRepository     = app(BillRepositoryInterface::class);
        $this->tagRepository      = app(TagRepositoryInterface::class);
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
    }

    /**
     * @return array
     */
    public function getInvalidOperators(): array
    {
        return $this->invalidOperators;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function getModifiers(): Collection
    {
        return $this->getOperators();
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function getOperators(): Collection
    {
        return $this->operators;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function getWordsAsString(): string
    {
        return implode(' ', $this->words);
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function hasModifiers(): bool
    {
        die(__METHOD__);
    }

    /**
     * @inheritDoc
     * @throws FireflyException
     */
    public function parseQuery(string $query)
    {
        Log::debug(sprintf('Now in parseQuery(%s)', $query));
        $parser = new QueryParser();
        try {
            $query1 = $parser->parse($query);
        } catch (TypeError|LogicException $e) {
            Log::error($e->getMessage());
            Log::error(sprintf('Could not parse search: "%s".', $query));
            throw new FireflyException('Invalid search value. See the logs.', 0, $e);
        }

        Log::debug(sprintf('Found %d node(s)', count($query1->getNodes())));
        foreach ($query1->getNodes() as $searchNode) {
            $this->handleSearchNode($searchNode);
        }

        $this->collector->setSearchWords($this->words);
    }

    /**
     * @param Node $searchNode
     *
     * @throws FireflyException
     */
    private function handleSearchNode(Node $searchNode): void
    {
        $class = get_class($searchNode);
        Log::debug(sprintf('Now in handleSearchNode(%s)', $class));
        switch ($class) {
            default:
                Log::error(sprintf('Cannot handle node %s', $class));
                throw new FireflyException(sprintf('Firefly III search cant handle "%s"-nodes', $class));
            case Subquery::class:
                // loop all notes in subquery:
                foreach ($searchNode->getNodes() as $subNode) { // @phpstan-ignore-line
                    $this->handleSearchNode($subNode);          // let's hope it's not too recursive!
                }
                break;
            case Word::class:
            case Phrase::class:
            case Numbr::class:
            case Url::class:
            case Date::class:
            case Hashtag::class:
            case Emoticon::class:
            case Emoji::class:
            case Mention::class:
                $allWords = (string) $searchNode->getValue();
                Log::debug(sprintf('Add words "%s" to search string, because Node class is "%s"', $allWords, $class));
                $this->words[] = $allWords;
                break;
            case Field::class:
                Log::debug(sprintf('Now handle Node class %s', $class));
                /** @var Field $searchNode */
                // used to search for x:y
                $operator = strtolower($searchNode->getValue());
                $value    = $searchNode->getNode()->getValue();
                // must be valid operator:
                if (in_array($operator, $this->validOperators, true) && $this->updateCollector($operator, (string) $value)) {
                    $this->operators->push(
                        [
                            'type'  => self::getRootOperator($operator),
                            'value' => (string) $value,
                        ]
                    );
                    Log::debug(sprintf('Added operator type "%s"', $operator));
                }
                if (!in_array($operator, $this->validOperators, true)) {
                    Log::debug(sprintf('Added INVALID operator type "%s"', $operator));
                    $this->invalidOperators[] = [
                        'type'  => $operator,
                        'value' => (string) $value,
                    ];
                }
        }

    }

    /**
     * @param string $operator
     * @param string $value
     *
     * @return bool
     * @throws FireflyException
     */
    private function updateCollector(string $operator, string $value): bool
    {
        Log::debug(sprintf('Now in updateCollector("%s", "%s")', $operator, $value));

        // check if alias, replace if necessary:
        $operator = self::getRootOperator($operator);

        switch ($operator) {
            default:
                Log::error(sprintf('No such operator: %s', $operator));
                throw new FireflyException(sprintf('Unsupported search operator: "%s"', $operator));
            // some search operators are ignored, basically:
            case 'user_action':
                Log::info(sprintf('Ignore search operator "%s"', $operator));

                return false;
            //
            // all account related searches:
            //
            case 'account_is':
                $this->searchAccount($value, 3, 4);
                break;
            case 'account_contains':
                $this->searchAccount($value, 3, 3);
                break;
            case 'account_ends':
                $this->searchAccount($value, 3, 2);
                break;
            case 'account_starts':
                $this->searchAccount($value, 3, 1);
                break;
            case 'account_nr_is':
                $this->searchAccountNr($value, 3, 4);
                break;
            case 'account_nr_contains':
                $this->searchAccountNr($value, 3, 3);
                break;
            case 'account_nr_ends':
                $this->searchAccountNr($value, 3, 2);
                break;
            case 'account_nr_starts':
                $this->searchAccountNr($value, 3, 1);
                break;
            case 'source_account_starts':
                $this->searchAccount($value, 1, 1);
                break;
            case 'source_account_ends':
                $this->searchAccount($value, 1, 2);
                break;
            case 'source_account_is':
                $this->searchAccount($value, 1, 4);
                break;
            case 'source_account_nr_starts':
                $this->searchAccountNr($value, 1, 1);
                break;
            case 'source_account_nr_ends':
                $this->searchAccountNr($value, 1, 2);
                break;
            case 'source_account_nr_is':
                $this->searchAccountNr($value, 1, 4);
                break;
            case 'source_account_nr_contains':
                $this->searchAccountNr($value, 1, 3);
                break;
            case 'source_account_contains':
                $this->searchAccount($value, 1, 3);
                break;
            case 'source_account_id':
                $account = $this->accountRepository->find((int) $value);
                if (null !== $account) {
                    $this->collector->setSourceAccounts(new Collection([$account]));
                }
                if (null === $account) {
                    // since the source does not exist, cannot return results:
                    $this->collector->findNothing();
                }
                break;
            case 'journal_id':
                $parts = explode(',', $value);
                $this->collector->setJournalIds($parts);
                break;
            case 'id':
                $parts = explode(',', $value);
                $this->collector->setIds($parts);
                break;
            case 'destination_account_starts':
                $this->searchAccount($value, 2, 1);
                break;
            case 'destination_account_ends':
                $this->searchAccount($value, 2, 2);
                break;
            case 'destination_account_nr_starts':
                $this->searchAccountNr($value, 2, 1);
                break;
            case 'destination_account_nr_ends':
                $this->searchAccountNr($value, 2, 2);
                break;
            case 'destination_account_nr_is':
                $this->searchAccountNr($value, 2, 4);
                break;
            case 'destination_account_is':
                $this->searchAccount($value, 2, 4);
                break;
            case 'destination_account_nr_contains':
                $this->searchAccountNr($value, 2, 3);
                break;
            case 'destination_account_contains':
                $this->searchAccount($value, 2, 3);
                break;
            case 'destination_account_id':
                $account = $this->accountRepository->find((int) $value);
                if (null !== $account) {
                    $this->collector->setDestinationAccounts(new Collection([$account]));
                }
                if (null === $account) {
                    $this->collector->findNothing();
                }
                break;
            case 'account_id':
                $parts      = explode(',', $value);
                $collection = new Collection;
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
            //
            // cash account
            //
            case 'source_is_cash':
                $account = $this->getCashAccount();
                $this->collector->setSourceAccounts(new Collection([$account]));
                break;
            case 'destination_is_cash':
                $account = $this->getCashAccount();
                $this->collector->setDestinationAccounts(new Collection([$account]));
                break;
            case 'account_is_cash':
                $account = $this->getCashAccount();
                $this->collector->setAccounts(new Collection([$account]));
                break;
            //
            // description
            //
            case 'description_starts':
                $this->collector->descriptionStarts([$value]);
                break;
            case 'description_ends':
                $this->collector->descriptionEnds([$value]);
                break;
            case 'description_contains':
                $this->words[] = $value;

                return false;
            case 'description_is':
                $this->collector->descriptionIs($value);
                break;
            //
            // currency
            //
            case 'currency_is':
                $currency = $this->findCurrency($value);
                if (null !== $currency) {
                    $this->collector->setCurrency($currency);
                }
                if (null === $currency) {
                    $this->collector->findNothing();
                }
                break;
            case 'foreign_currency_is':
                $currency = $this->findCurrency($value);
                if (null !== $currency) {
                    $this->collector->setForeignCurrency($currency);
                }
                if (null === $currency) {
                    $this->collector->findNothing();
                }
                break;
            //
            // attachments
            //
            case 'has_attachments':
                Log::debug('Set collector to filter on attachments.');
                $this->collector->hasAttachments();
                break;
            case 'has_no_attachments':
                Log::debug('Set collector to filter on NO attachments.');
                $this->collector->hasNoAttachments();
                break;
            //
            // categories
            case 'has_no_category':
                $this->collector->withoutCategory();
                break;
            case 'has_any_category':
                $this->collector->withCategory();
                break;
            case 'category_is':
                $category = $this->categoryRepository->findByName($value);
                if (null !== $category) {
                    $this->collector->setCategory($category);
                    break;
                }
                $this->collector->findNothing();
                break;
            case 'category_ends':
                $result = $this->categoryRepository->categoryEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }
                break;
            case 'category_starts':
                $result = $this->categoryRepository->categoryStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }
                break;
            case 'category_contains':
                $result = $this->categoryRepository->searchCategory($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setCategories($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }
                break;
            //
            // budgets
            //
            case 'has_no_budget':
                $this->collector->withoutBudget();
                break;
            case 'has_any_budget':
                $this->collector->withBudget();
                break;
            case 'budget_contains':
                $result = $this->budgetRepository->searchBudget($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }
                break;
            case 'budget_is':
                $budget = $this->budgetRepository->findByName($value);
                if (null !== $budget) {
                    $this->collector->setBudget($budget);
                    break;
                }
                $this->collector->findNothing();
                break;
            case 'budget_ends':
                $result = $this->budgetRepository->budgetEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }
                break;
            case 'budget_starts':
                $result = $this->budgetRepository->budgetStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBudgets($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }
                break;
            //
            // bill
            //
            case 'has_no_bill':
                $this->collector->withoutBill();
                break;
            case 'has_any_bill':
                $this->collector->withBill();
                break;
            case 'bill_contains':
                $result = $this->billRepository->searchBill($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBills($result);
                    break;
                }
                $this->collector->findNothing();
                break;
            case 'bill_is':
                $bill = $this->billRepository->findByName($value);
                if (null !== $bill) {
                    $this->collector->setBill($bill);
                    break;
                }
                $this->collector->findNothing();
                break;
            case 'bill_ends':
                $result = $this->billRepository->billEndsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBills($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }
                break;
            case 'bill_starts':
                $result = $this->billRepository->billStartsWith($value, 1337);
                if ($result->count() > 0) {
                    $this->collector->setBills($result);
                }
                if (0 === $result->count()) {
                    $this->collector->findNothing();
                }
                break;
            //
            // tags
            //
            case 'has_no_tag':
                $this->collector->withoutTags();
                break;
            case 'has_any_tag':
                $this->collector->hasAnyTag();
                break;
            case 'tag_is':
                $result = $this->tagRepository->searchTag($value);
                if ($result->count() > 0) {
                    $this->collector->setTags($result);
                }
                // no tags found means search must result in nothing.
                if (0 === $result->count()) {
                    Log::info(sprintf('No valid tags in "%s"-operator, so search will not return ANY results.', $operator));
                    $this->collector->findNothing();
                }
                break;
            case 'tag_is_not':
                $result = $this->tagRepository->searchTag($value);
                if ($result->count() > 0) {
                    $this->collector->setWithoutSpecificTags($result);
                }
                break;
            //
            // notes
            //
            case 'notes_contains':
                $this->collector->notesContain($value);
                break;
            case 'notes_starts':
                $this->collector->notesStartWith($value);
                break;
            case 'notes_ends':
                $this->collector->notesEndWith($value);
                break;
            case 'notes_is':
                $this->collector->notesExactly($value);
                break;
            case 'no_notes':
                $this->collector->withoutNotes();
                break;
            case 'any_notes':
                $this->collector->withAnyNotes();
                break;
            //
            // amount
            //
            case 'amount_is':
                // strip comma's, make dots.
                Log::debug(sprintf('Original value "%s"', $value));
                $value = str_replace(',', '.', (string) $value);
                $amount = app('steam')->positive($value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountIs($amount);
                break;
            case 'foreign_amount_is':

                // strip comma's, make dots.
                $value = str_replace(',', '.', (string) $value);

                $amount = app('steam')->positive($value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->foreignAmountIs($amount);
                break;
            case 'amount_less':
                // strip comma's, make dots.
                $value = str_replace(',', '.', (string) $value);

                $amount = app('steam')->positive($value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountLess($amount);
                break;
            case 'foreign_amount_less':
                // strip comma's, make dots.
                $value = str_replace(',', '.', (string) $value);

                $amount = app('steam')->positive($value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->foreignAmountLess($amount);
                break;
            case 'amount_more':
                Log::debug(sprintf('Now handling operator "%s"', $operator));
                // strip comma's, make dots.
                $value  = str_replace(',', '.', (string) $value);
                $amount = app('steam')->positive($value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->amountMore($amount);
                break;
            case 'foreign_amount_more':
                Log::debug(sprintf('Now handling operator "%s"', $operator));
                // strip comma's, make dots.
                $value  = str_replace(',', '.', (string) $value);
                $amount = app('steam')->positive($value);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $amount));
                $this->collector->foreignAmountMore($amount);
                break;
            //
            // transaction type
            //
            case 'transaction_type':
                $this->collector->setTypes([ucfirst($value)]);
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                break;
            //
            // dates
            //
            case 'date_on':
                $range = $this->parseDateRange($value);
                $this->setExactDateParams($range);
                return false;
            case 'date_before':
                $range = $this->parseDateRange($value);
                $this->setDateBeforeParams($range);
                return false;
            case 'date_after':
                $range = $this->parseDateRange($value);
                $this->setDateAfterParams($range);
                return false;
            case 'interest_date_on':
                $range = $this->parseDateRange($value);
                $this->setExactMetaDateParams('interest_date', $range);
                return false;
            case 'interest_date_before':
                $range = $this->parseDateRange($value);
                $this->setMetaDateBeforeParams('interest_date', $range);
                return false;
            case 'interest_date_after':
                $range = $this->parseDateRange($value);
                $this->setMetaDateAfterParams('interest_date', $range);
                return false;

            case 'book_date_on':
                $range = $this->parseDateRange($value);
                $this->setExactMetaDateParams('book_date', $range);
                return false;
            case 'book_date_before':
                $range = $this->parseDateRange($value);
                $this->setMetaDateBeforeParams('book_date', $range);
                return false;
            case 'book_date_after':
                $range = $this->parseDateRange($value);
                $this->setMetaDateAfterParams('book_date', $range);
                return false;

            case 'process_date_on':
                $range = $this->parseDateRange($value);
                $this->setExactMetaDateParams('process_date', $range);
                return false;
            case 'process_date_before':
                $range = $this->parseDateRange($value);
                $this->setMetaDateBeforeParams('process_date', $range);
                return false;
            case 'process_date_after':
                $range = $this->parseDateRange($value);
                $this->setMetaDateAfterParams('process_date', $range);
                return false;
            case 'due_date_on':
                $range = $this->parseDateRange($value);
                $this->setExactMetaDateParams('due_date', $range);
                return false;
            case 'due_date_before':
                $range = $this->parseDateRange($value);
                $this->setMetaDateBeforeParams('due_date', $range);
                return false;
            case 'due_date_after':
                $range = $this->parseDateRange($value);
                $this->setMetaDateAfterParams('due_date', $range);
                return false;
            case 'payment_date_on':
                $range = $this->parseDateRange($value);
                $this->setExactMetaDateParams('payment_date', $range);
                return false;
            case 'payment_date_before':
                $range = $this->parseDateRange($value);
                $this->setMetaDateBeforeParams('payment_date', $range);
                return false;
            case 'payment_date_after':
                $range = $this->parseDateRange($value);
                $this->setMetaDateAfterParams('payment_date', $range);
                return false;
            case 'invoice_date_on':
                $range = $this->parseDateRange($value);
                $this->setExactMetaDateParams('invoice_date', $range);
                return false;
            case 'invoice_date_before':
                $range = $this->parseDateRange($value);
                $this->setMetaDateBeforeParams('invoice_date', $range);
                return false;
            case 'invoice_date_after':
                $range = $this->parseDateRange($value);
                $this->setMetaDateAfterParams('invoice_date', $range);
                return false;
            case 'created_at_on':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range = $this->parseDateRange($value);
                $this->setExactObjectDateParams('created_at', $range);
                return false;
            case 'created_at_before':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range = $this->parseDateRange($value);
                $this->setObjectDateBeforeParams('created_at', $range);
                return false;
            case 'created_at_after':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range = $this->parseDateRange($value);
                $this->setObjectDateAfterParams('created_at', $range);
                return false;
            case 'updated_at_on':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range = $this->parseDateRange($value);
                $this->setExactObjectDateParams('updated_at', $range);
                return false;
            case 'updated_at_before':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range = $this->parseDateRange($value);
                $this->setObjectDateBeforeParams('updated_at', $range);
                return false;
            case 'updated_at_after':
                Log::debug(sprintf('Set "%s" using collector with value "%s"', $operator, $value));
                $range = $this->parseDateRange($value);
                $this->setObjectDateAfterParams('updated_at', $range);
                return false;
            //
            // external URL
            //
            case 'no_external_url':
                $this->collector->withoutExternalUrl();
                break;
            case 'any_external_url':
                $this->collector->withExternalUrl();
                break;
            case 'external_url_is':
                $this->collector->setExternalUrl($value);
                break;
            case 'external_url_contains':
                $this->collector->externalUrlContains($value);
                break;
            case 'external_url_starts':
                $this->collector->externalUrlStarts($value);
                break;
            case 'external_url_ends':
                $this->collector->externalUrlEnds($value);
                break;
            //
            // other fields
            //
            case 'external_id_is':
                $this->collector->setExternalId($value);
                break;
            case 'recurrence_id':
                $this->collector->setRecurrenceId($value);
                break;
            case 'external_id_contains':
                $this->collector->externalIdContains($value);
                break;
            case 'external_id_starts':
                $this->collector->externalIdStarts($value);
                break;
            case 'external_id_ends':
                $this->collector->externalIdEnds($value);
                break;
            case 'internal_reference_is':
                $this->collector->setInternalReference($value);
                break;
            case 'internal_reference_contains':
                $this->collector->internalReferenceContains($value);
                break;
            case 'internal_reference_starts':
                $this->collector->internalReferenceStarts($value);
                break;
            case 'internal_reference_ends':
                $this->collector->internalReferenceEnds($value);
                break;
            case 'attachment_name_is':
                $this->collector->attachmentNameIs($value);
                break;
            case 'attachment_name_contains':
                $this->collector->attachmentNameContains($value);
                break;
            case 'attachment_name_starts':
                $this->collector->attachmentNameStarts($value);
                break;
            case 'attachment_name_ends':
                $this->collector->attachmentNameEnds($value);
                break;
            case 'attachment_notes_are':
                $this->collector->attachmentNotesAre($value);
                break;
            case 'attachment_notes_contains':
                $this->collector->attachmentNotesContains($value);
                break;
            case 'attachment_notes_starts':
                $this->collector->attachmentNotesStarts($value);
                break;
            case 'attachment_notes_ends':
                $this->collector->attachmentNotesEnds($value);
                break;

        }

        return true;
    }

    /**
     * @param string $operator
     *
     * @return string
     * @throws FireflyException
     */
    public static function getRootOperator(string $operator): string
    {
        $config = config(sprintf('search.operators.%s', $operator));
        if (null === $config) {
            throw new FireflyException(sprintf('No configuration for search operator "%s"', $operator));
        }
        if (true === $config['alias']) {
            Log::debug(sprintf('"%s" is an alias for "%s", so return that instead.', $operator, $config['alias_for']));

            return $config['alias_for'];
        }
        Log::debug(sprintf('"%s" is not an alias.', $operator));

        return $operator;
    }

    /**
     * searchDirection: 1 = source (default), 2 = destination, 3 = both
     * stringPosition: 1 = start (default), 2 = end, 3 = contains, 4 = is
     *
     * @param string $value
     * @param int    $searchDirection
     * @param int    $stringPosition
     */
    private function searchAccount(string $value, int $searchDirection, int $stringPosition): void
    {
        Log::debug(sprintf('searchAccount("%s", %d, %d)', $value, $stringPosition, $searchDirection));

        // search direction (default): for source accounts
        $searchTypes     = [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT, AccountType::REVENUE];
        $collectorMethod = 'setSourceAccounts';

        // search direction: for destination accounts
        if (2 === $searchDirection) {
            // destination can be
            $searchTypes     = [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT, AccountType::EXPENSE];
            $collectorMethod = 'setDestinationAccounts';
        }
        // either account could be:
        if (3 === $searchDirection) {
            $searchTypes     = [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT, AccountType::EXPENSE, AccountType::REVENUE];
            $collectorMethod = 'setAccounts';
        }
        // string position (default): starts with:
        $stringMethod = 'str_starts_with';

        // string position: ends with:
        if (2 === $stringPosition) {
            $stringMethod = 'str_ends_with';
        }
        if (3 === $stringPosition) {
            $stringMethod = 'str_contains';
        }
        if (4 === $stringPosition) {
            $stringMethod = 'str_is_equal';
        }

        // get accounts:
        $accounts = $this->accountRepository->searchAccount($value, $searchTypes, 1337);
        if (0 === $accounts->count()) {
            Log::debug('Found zero accounts, search for non existing account, NO results will be returned.');
            $this->collector->findNothing();

            return;
        }
        Log::debug(sprintf('Found %d accounts, will filter.', $accounts->count()));
        $filtered = $accounts->filter(
            function (Account $account) use ($value, $stringMethod) {
                return $stringMethod(strtolower($account->name), strtolower($value));
            }
        );

        if (0 === $filtered->count()) {
            Log::debug('Left with zero accounts, so cannot find anything, NO results will be returned.');
            $this->collector->findNothing();

            return;
        }
        Log::debug(sprintf('Left with %d, set as %s().', $filtered->count(), $collectorMethod));
        $this->collector->$collectorMethod($filtered);
    }

    /**
     * searchDirection: 1 = source (default), 2 = destination, 3 = both
     * stringPosition: 1 = start (default), 2 = end, 3 = contains, 4 = is
     *
     * @param string $value
     * @param int    $searchDirection
     * @param int    $stringPosition
     */
    private function searchAccountNr(string $value, int $searchDirection, int $stringPosition): void
    {
        Log::debug(sprintf('searchAccountNr(%s, %d, %d)', $value, $searchDirection, $stringPosition));

        // search direction (default): for source accounts
        $searchTypes     = [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT, AccountType::REVENUE];
        $collectorMethod = 'setSourceAccounts';

        // search direction: for destination accounts
        if (2 === $searchDirection) {
            // destination can be
            $searchTypes     = [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT, AccountType::EXPENSE];
            $collectorMethod = 'setDestinationAccounts';
        }

        // either account could be:
        if (3 === $searchDirection) {
            $searchTypes     = [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT, AccountType::EXPENSE, AccountType::REVENUE];
            $collectorMethod = 'setAccounts';
        }

        // string position (default): starts with:
        $stringMethod = 'str_starts_with';

        // string position: ends with:
        if (2 === $stringPosition) {
            $stringMethod = 'str_ends_with';
        }
        if (3 === $stringPosition) {
            $stringMethod = 'str_contains';
        }
        if (4 === $stringPosition) {
            $stringMethod = 'str_is_equal';
        }

        // search for accounts:
        $accounts = $this->accountRepository->searchAccountNr($value, $searchTypes, 1337);
        if (0 === $accounts->count()) {
            Log::debug('Found zero accounts, search for invalid account.');
            $this->collector->findNothing();

            return;
        }

        // if found, do filter
        Log::debug(sprintf('Found %d accounts, will filter.', $accounts->count()));
        $filtered = $accounts->filter(
            function (Account $account) use ($value, $stringMethod) {
                // either IBAN or account number!
                $ibanMatch      = $stringMethod(strtolower((string) $account->iban), strtolower((string) $value));
                $accountNrMatch = false;
                /** @var AccountMeta $meta */
                foreach ($account->accountMeta as $meta) {
                    if ('account_number' === $meta->name && $stringMethod(strtolower($meta->data), strtolower($value))) {
                        $accountNrMatch = true;
                    }
                }

                return $ibanMatch || $accountNrMatch;
            }
        );

        if (0 === $filtered->count()) {
            Log::debug('Left with zero, search for invalid account');
            $this->collector->findNothing();

            return;
        }
        Log::debug(sprintf('Left with %d, set as %s().', $filtered->count(), $collectorMethod));
        $this->collector->$collectorMethod($filtered);
    }

    /**
     * @return Account
     */
    private function getCashAccount(): Account
    {
        return $this->accountRepository->getCashAccount();
    }

    /**
     * @param string $value
     *
     * @return TransactionCurrency|null
     */
    private function findCurrency(string $value): ?TransactionCurrency
    {
        if (str_contains($value, '(') && str_contains($value, ')')) {
            // bad method to split and get the currency code:
            $parts = explode(' ', $value);
            $value = trim($parts[count($parts) - 1], "() \t\n\r\0\x0B");
        }
        $result = $this->currencyRepository->findByCodeNull($value);
        if (null === $result) {
            $result = $this->currencyRepository->findByNameNull($value);
        }

        return $result;
    }

    /**
     * @param string $value
     *
     * @return array
     * @throws FireflyException
     */
    private function parseDateRange(string $value): array
    {
        $parser = new ParseDateString;
        if ($parser->isDateRange($value)) {
            return $parser->parseRange($value);
        }
        $parsedDate = $parser->parseDate($value);

        return [
            'exact' => $parsedDate,
        ];
    }

    /**
     * @param array $range
     *
     * @throws FireflyException
     */
    private function setExactDateParams(array $range): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setExactParameters()', $key));
                case 'exact':
                    Log::debug(sprintf('Set date_is_exact value "%s"', $value->format('Y-m-d')));
                    $this->collector->setRange($value, $value);
                    $this->operators->push(['type' => 'date_on', 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set date_is_exact YEAR value "%s"', $value));
                    $this->collector->yearIs($value);
                    $this->operators->push(['type' => 'date_on_year', 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set date_is_exact MONTH value "%s"', $value));
                    $this->collector->monthIs($value);
                    $this->operators->push(['type' => 'date_on_month', 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set date_is_exact DAY value "%s"', $value));
                    $this->collector->dayIs($value);
                    $this->operators->push(['type' => 'date_on_day', 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @param array $range
     *
     * @throws FireflyException
     */
    private function setDateBeforeParams(array $range): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setDateBeforeParams()', $key));
                case 'exact':
                    $this->collector->setBefore($value);
                    $this->operators->push(['type' => 'date_before', 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set date_is_before YEAR value "%s"', $value));
                    $this->collector->yearBefore($value);
                    $this->operators->push(['type' => 'date_before_year', 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set date_is_before MONTH value "%s"', $value));
                    $this->collector->monthBefore($value);
                    $this->operators->push(['type' => 'date_before_month', 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set date_is_before DAY value "%s"', $value));
                    $this->collector->dayBefore($value);
                    $this->operators->push(['type' => 'date_before_day', 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @param array $range
     *
     * @throws FireflyException
     */
    private function setDateAfterParams(array $range)
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setDateAfterParams()', $key));
                case 'exact':
                    $this->collector->setAfter($value);
                    $this->operators->push(['type' => 'date_after', 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set date_is_after YEAR value "%s"', $value));
                    $this->collector->yearAfter($value);
                    $this->operators->push(['type' => 'date_after_year', 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set date_is_after MONTH value "%s"', $value));
                    $this->collector->monthAfter($value);
                    $this->operators->push(['type' => 'date_after_month', 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set date_is_after DAY value "%s"', $value));
                    $this->collector->dayAfter($value);
                    $this->operators->push(['type' => 'date_after_day', 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @param string $field
     * @param array  $range
     * @return void
     * @throws FireflyException
     */
    private function setExactMetaDateParams(string $field, array $range): void
    {
        Log::debug('Now in setExactMetaDateParams()');
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setExactMetaDateParams()', $key));
                case 'exact':
                    Log::debug(sprintf('Set %s_is_exact value "%s"', $field, $value->format('Y-m-d')));
                    $this->collector->setMetaDateRange($value, $value, $field);
                    $this->operators->push(['type' => sprintf('%s_on', $field), 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set %s_is_exact YEAR value "%s"', $field, $value));
                    $this->collector->metaYearIs($value, $field);
                    $this->operators->push(['type' => sprintf('%s_on_year', $field), 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set %s_is_exact MONTH value "%s"', $field, $value));
                    $this->collector->metaMonthIs($value, $field);
                    $this->operators->push(['type' => sprintf('%s_on_month', $field), 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set %s_is_exact DAY value "%s"', $field, $value));
                    $this->collector->metaDayIs($value, $field);
                    $this->operators->push(['type' => sprintf('%s_on_day', $field), 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @param string $field
     * @param array  $range
     * @return void
     * @throws FireflyException
     */
    private function setMetaDateBeforeParams(string $field, array $range): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setMetaDateBeforeParams()', $key));
                case 'exact':
                    $this->collector->setMetaBefore($value, $field);
                    $this->operators->push(['type' => sprintf('%s_before', $field), 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set %s_is_before YEAR value "%s"', $field, $value));
                    $this->collector->metaYearBefore($value, $field);
                    $this->operators->push(['type' => sprintf('%s_before_year', $field), 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set %s_is_before MONTH value "%s"', $field, $value));
                    $this->collector->metaMonthBefore($value, $field);
                    $this->operators->push(['type' => sprintf('%s_before_month', $field), 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set %s_is_before DAY value "%s"', $field, $value));
                    $this->collector->metaDayBefore($value, $field);
                    $this->operators->push(['type' => sprintf('%s_before_day', $field), 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @param string $field
     * @param array  $range
     * @return void
     * @throws FireflyException
     */
    private function setMetaDateAfterParams(string $field, array $range): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setMetaDateAfterParams()', $key));
                case 'exact':
                    $this->collector->setMetaAfter($value, $field);
                    $this->operators->push(['type' => sprintf('%s_after', $field), 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set %s_is_after YEAR value "%s"', $field, $value));
                    $this->collector->metaYearAfter($value, $field);
                    $this->operators->push(['type' => sprintf('%s_after_year', $field), 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set %s_is_after MONTH value "%s"', $field, $value));
                    $this->collector->metaMonthAfter($value, $field);
                    $this->operators->push(['type' => sprintf('%s_after_month', $field), 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set %s_is_after DAY value "%s"', $field, $value));
                    $this->collector->metaDayAfter($value, $field);
                    $this->operators->push(['type' => sprintf('%s_after_day', $field), 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @param string $field
     * @param array  $range
     * @return void
     * @throws FireflyException
     */
    private function setExactObjectDateParams(string $field, array $range): void
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setExactObjectDateParams()', $key));
                case 'exact':
                    Log::debug(sprintf('Set %s_is_exact value "%s"', $field, $value->format('Y-m-d')));
                    $this->collector->setObjectRange($value, clone $value, $field);
                    $this->operators->push(['type' => sprintf('%s_on', $field), 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set %s_is_exact YEAR value "%s"', $field, $value));
                    $this->collector->objectYearIs($value, $field);
                    $this->operators->push(['type' => sprintf('%s_on_year', $field), 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set %s_is_exact MONTH value "%s"', $field, $value));
                    $this->collector->objectMonthIs($value, $field);
                    $this->operators->push(['type' => sprintf('%s_on_month', $field), 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set %s_is_exact DAY value "%s"', $field, $value));
                    $this->collector->objectDayIs($value, $field);
                    $this->operators->push(['type' => sprintf('%s_on_day', $field), 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @param string $field
     * @param array  $range
     *
     * @throws FireflyException
     */
    private function setObjectDateBeforeParams(string $field, array $range)
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setObjectDateBeforeParams()', $key));
                case 'exact':
                    $this->collector->setObjectBefore($value, $field);
                    $this->operators->push(['type' => sprintf('%s_before', $field), 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set date_is_before YEAR value "%s"', $value));
                    $this->collector->objectYearBefore($value, $field);
                    $this->operators->push(['type' => sprintf('%s_before_year', $field), 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set date_is_before MONTH value "%s"', $value));
                    $this->collector->objectMonthBefore($value, $field);
                    $this->operators->push(['type' => sprintf('%s_before_month', $field), 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set date_is_before DAY value "%s"', $value));
                    $this->collector->objectDayBefore($value, $field);
                    $this->operators->push(['type' => sprintf('%s_before_day', $field), 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @param string $field
     * @param array  $range
     *
     * @throws FireflyException
     */
    private function setObjectDateAfterParams(string $field, array $range)
    {
        /**
         * @var string        $key
         * @var Carbon|string $value
         */
        foreach ($range as $key => $value) {
            switch ($key) {
                default:
                    throw new FireflyException(sprintf('Cannot handle key "%s" in setObjectDateAfterParams()', $key));
                case 'exact':
                    $this->collector->setObjectAfter($value, $field);
                    $this->operators->push(['type' => sprintf('%s_after', $field), 'value' => $value->format('Y-m-d'),]);
                    break;
                case 'year':
                    Log::debug(sprintf('Set date_is_after YEAR value "%s"', $value));
                    $this->collector->objectYearAfter($value, $field);
                    $this->operators->push(['type' => sprintf('%s_after_year', $field), 'value' => $value,]);
                    break;
                case 'month':
                    Log::debug(sprintf('Set date_is_after MONTH value "%s"', $value));
                    $this->collector->objectMonthAfter($value, $field);
                    $this->operators->push(['type' => sprintf('%s_after_month', $field), 'value' => $value,]);
                    break;
                case 'day':
                    Log::debug(sprintf('Set date_is_after DAY value "%s"', $value));
                    $this->collector->objectDayAfter($value, $field);
                    $this->operators->push(['type' => sprintf('%s_after_day', $field), 'value' => $value,]);
                    break;
            }
        }
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
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
        if (empty($this->getWords()) && empty($this->getOperators())) {
            return new LengthAwarePaginator([], 0, 5, 1);
        }

        return $this->collector->getPaginatedGroups();
    }

    /**
     * @return array
     */
    public function getWords(): array
    {
        return $this->words;
    }

    /**
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->date = $date;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function setPage(int $page): void
    {
        $this->page = $page;
        $this->collector->setPage($this->page);
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
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

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
        $this->collector->setLimit($this->limit);
    }
}
