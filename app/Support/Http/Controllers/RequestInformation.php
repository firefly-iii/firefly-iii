<?php
/**
 * RequestInformation.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Help\HelpInterface;
use FireflyIII\Http\Requests\SplitJournalFormRequest;
use FireflyIII\Http\Requests\TestRuleFormRequest;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\Binder\AccountList;
use FireflyIII\Transformers\TransactionTransformer;
use FireflyIII\User;
use Hash;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Log;
use Route as RouteFacade;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Trait RequestInformation
 *
 */
trait RequestInformation
{

    /**
     * Create data-array from a journal.
     *
     * @param SplitJournalFormRequest|Request $request
     * @param TransactionJournal              $journal
     *
     * @return array
     * @throws FireflyException
     */
    protected function arrayFromJournal(Request $request, TransactionJournal $journal): array // convert user input.
    {
        $sourceAccounts      = $this->repository->getJournalSourceAccounts($journal);
        $destinationAccounts = $this->repository->getJournalDestinationAccounts($journal);
        $array               = [
            'journal_description'    => $request->old('journal_description', $journal->description),
            'journal_amount'         => '0',
            'journal_foreign_amount' => '0',
            'sourceAccounts'         => $sourceAccounts,
            'journal_source_id'      => $request->old('journal_source_id', $sourceAccounts->first()->id),
            'journal_source_name'    => $request->old('journal_source_name', $sourceAccounts->first()->name),
            'journal_destination_id' => $request->old('journal_destination_id', $destinationAccounts->first()->id),
            'destinationAccounts'    => $destinationAccounts,
            'what'                   => strtolower($this->repository->getTransactionType($journal)),
            'date'                   => $request->old('date', $this->repository->getJournalDate($journal, null)),
            'tags'                   => implode(',', $journal->tags->pluck('tag')->toArray()),

            // all custom fields:
            'interest_date'          => $request->old('interest_date', $this->repository->getMetaField($journal, 'interest_date')),
            'book_date'              => $request->old('book_date', $this->repository->getMetaField($journal, 'book_date')),
            'process_date'           => $request->old('process_date', $this->repository->getMetaField($journal, 'process_date')),
            'due_date'               => $request->old('due_date', $this->repository->getMetaField($journal, 'due_date')),
            'payment_date'           => $request->old('payment_date', $this->repository->getMetaField($journal, 'payment_date')),
            'invoice_date'           => $request->old('invoice_date', $this->repository->getMetaField($journal, 'invoice_date')),
            'internal_reference'     => $request->old('internal_reference', $this->repository->getMetaField($journal, 'internal_reference')),
            'notes'                  => $request->old('notes', $this->repository->getNoteText($journal)),

            // transactions.
            'transactions'           => $this->getTransactionDataFromJournal($journal),
        ];
        // update transactions array with old request data.
        $array['transactions'] = $this->updateWithPrevious($array['transactions'], $request->old());

        // update journal amount and foreign amount:
        $array['journal_amount']         = array_sum(array_column($array['transactions'], 'amount'));
        $array['journal_foreign_amount'] = array_sum(array_column($array['transactions'], 'foreign_amount'));

        return $array;
    }

    /**
     * Get the domain of FF system.
     *
     * @return string
     */
    protected function getDomain(): string // get request info
    {
        $url   = url()->to('/');
        $parts = parse_url($url);

        return $parts['host'];
    }

    /**
     * Gets the help text.
     *
     * @param string $route
     * @param string $language
     *
     * @return string
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getHelpText(string $route, string $language): string // get from internet.
    {
        $help = app(HelpInterface::class);
        // get language and default variables.
        $content = '<p>' . trans('firefly.route_has_no_help') . '</p>';

        // if no such route, log error and return default text.
        if (!$help->hasRoute($route)) {
            Log::error('No such route: ' . $route);

            return $content;
        }

        // help content may be cached:
        if ($help->inCache($route, $language)) {
            $content = $help->getFromCache($route, $language);
            Log::debug(sprintf('Help text %s was in cache.', $language));

            return $content;
        }

        // get help content from Github:
        $content          = $help->getFromGitHub($route, $language);
        $originalLanguage = $language;
        // content will have 0 length when Github failed. Try en_US when it does:
        if ('' === $content) {
            $language = 'en_US';

            // also check cache first:
            if ($help->inCache($route, $language)) {
                Log::debug(sprintf('Help text %s was in cache.', $language));
                $content = $help->getFromCache($route, $language);

                return $content;
            }
            $baseHref   = route('index');
            $helpString = sprintf(
                '<p><em><img src="%s/images/flags/%s.png" /> %s</em></p>', $baseHref, $originalLanguage, (string)trans('firefly.help_translating')
            );
            $content    = $helpString . $help->getFromGitHub($route, $language);
        }

        // help still empty?
        if ('' !== $content) {
            $help->putInCache($route, $language, $content);

            return $content;
        }

        return '<p>' . trans('firefly.route_has_no_help') . '</p>';
    }

    /**
     * Get user's language.
     *
     * @return string
     */
    protected function getLanguage(): string // get preference
    {
        /** @var string $language */
        $language = app('preferences')->get('language', config('firefly.default_language', 'en_US'))->data;

        return $language;
    }

    /**
     * @return string
     */
    protected function getPageName(): string // get request info
    {
        return str_replace('.', '_', RouteFacade::currentRouteName());
    }

    /**
     * Get the specific name of a page for intro.
     *
     * @return string
     */
    protected function getSpecificPageName(): string // get request info
    {
        return null === RouteFacade::current()->parameter('what') ? '' : '_' . RouteFacade::current()->parameter('what');
    }

    /**
     * Get transaction overview from journal.
     *
     * @param TransactionJournal $journal
     *
     * @return array
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getTransactionDataFromJournal(TransactionJournal $journal): array // convert object
    {
        // use collector to collect transactions.
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        // filter on specific journals.
        $collector->setJournals(new Collection([$journal]));
        $set          = $collector->getTransactions();
        $transactions = [];
        $transformer  = new TransactionTransformer(new ParameterBag);
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $res = [];
            if ((float)$transaction->transaction_amount > 0 && $journal->transactionType->type === TransactionType::DEPOSIT) {
                $res = $transformer->transform($transaction);
            }
            if ((float)$transaction->transaction_amount < 0 && $journal->transactionType->type !== TransactionType::DEPOSIT) {
                $res = $transformer->transform($transaction);
            }

            if (\count($res) > 0) {
                $res['amount']         = app('steam')->positive((string)$res['amount']);
                $res['foreign_amount'] = app('steam')->positive((string)$res['foreign_amount']);
                $transactions[]        = $res;
            }
        }

        return $transactions;
    }

    /**
     * Get a list of triggers.
     *
     * @param TestRuleFormRequest $request
     *
     * @return array
     */
    protected function getValidTriggerList(TestRuleFormRequest $request): array // process input
    {
        $triggers = [];
        $data     = $request->get('rule_triggers');
        if (\is_array($data)) {
            foreach ($data as $index => $triggerInfo) {
                $triggers[] = [
                    'type'            => $triggerInfo['name'] ?? '',
                    'value'           => $triggerInfo['value'] ?? '',
                    'stop_processing' => 1 === (int)($triggerInfo['stop_processing'] ?? '0'),
                ];
            }
        }

        return $triggers;
    }

    /**
     * Returns if user has seen demo.
     *
     * @return bool
     */
    protected function hasSeenDemo(): bool // get request info + get preference
    {
        $page         = $this->getPageName();
        $specificPage = $this->getSpecificPageName();

        // indicator if user has seen the help for this page ( + special page):
        $key = 'shown_demo_' . $page . $specificPage;
        // is there an intro for this route?
        $intro        = config('intro.' . $page) ?? [];
        $specialIntro = config('intro.' . $page . $specificPage) ?? [];
        // some routes have a "what" parameter, which indicates a special page:

        $shownDemo = true;
        // both must be array and either must be > 0
        if (\count($intro) > 0 || \count($specialIntro) > 0) {
            $shownDemo = app('preferences')->get($key, false)->data;
            Log::debug(sprintf('Check if user has already seen intro with key "%s". Result is %d', $key, $shownDemo));
        }

        return $shownDemo;
    }

    /**
     * Check if date is outside session range.
     *
     * @param Carbon $date
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function notInSessionRange(Carbon $date): bool // Validate a preference
    {
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end    = session('end', Carbon::now()->endOfMonth());
        $result = false;
        if ($start->greaterThanOrEqualTo($date) && $end->greaterThanOrEqualTo($date)) {
            $result = true;
        }
        // start and end in the past? use $end
        if ($start->lessThanOrEqualTo($date) && $end->lessThanOrEqualTo($date)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Parses attributes from URI.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function parseAttributes(array $attributes): array // parse input + return result
    {
        $attributes['location'] = $attributes['location'] ?? '';
        $attributes['accounts'] = AccountList::routeBinder($attributes['accounts'] ?? '', new Route('get', '', []));
        try {
            $attributes['startDate'] = Carbon::createFromFormat('Ymd', $attributes['startDate']);
        } catch (InvalidArgumentException $e) {
            Log::debug(sprintf('Not important error message: %s', $e->getMessage()));
            $date                    = Carbon::now()->startOfMonth();
            $attributes['startDate'] = $date;
        }

        try {
            $attributes['endDate'] = Carbon::createFromFormat('Ymd', $attributes['endDate']);
        } catch (InvalidArgumentException $e) {
            Log::debug(sprintf('Not important error message: %s', $e->getMessage()));
            $date                  = Carbon::now()->startOfMonth();
            $attributes['endDate'] = $date;
        }

        return $attributes;
    }

    /**
     * Validate users new password.
     *
     * @param User   $user
     * @param string $current
     * @param string $new
     *
     * @return bool
     *
     * @throws ValidationException
     */
    protected function validatePassword(User $user, string $current, string $new): bool //get request info
    {
        if (!Hash::check($current, $user->password)) {
            throw new ValidationException((string)trans('firefly.invalid_current_password'));
        }

        if ($current === $new) {
            throw new ValidationException((string)trans('firefly.should_change'));
        }

        return true;
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
     * @return ValidatorContract
     */
    protected function validator(array $data): ValidatorContract
    {
        return Validator::make(
            $data,
            [
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|secure_password|confirmed',
            ]
        );
    }

}