<?php
declare(strict_types = 1);

namespace FireflyIII\Support\Twig;


use Amount;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\CacheProperties;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class Journal
 *
 * @package FireflyIII\Support\Twig
 */
class Journal extends Twig_Extension
{

    /**
     * @return Twig_SimpleFunction
     */
    public function formatPerspective(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatPerspective', function (TransactionJournal $journal, Account $account) {

            // get the account amount:
            $transaction = $journal->transactions()->where('transactions.account_id', $account->id)->first();
            $amount      = $transaction->amount;
            if ($journal->isWithdrawal()) {
                $amount = bcmul($amount, '-1');
            }

            $formatted = Amount::format($amount, true);

            return $formatted . ' (' . Amount::formatJournal($journal) . ')';
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function getDestinationAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'destinationAccount', function (TransactionJournal $journal) {
            $cache = new CacheProperties;
            $cache->addProperty($journal->id);
            $cache->addProperty('transaction-journal');
            $cache->addProperty('destination-account-string');
            if ($cache->has()) {
                return $cache->get();
            }

            $list  = TransactionJournal::destinationAccountList($journal);
            $array = [];
            /** @var Account $entry */
            foreach ($list as $entry) {
                if ($entry->accountType->type == 'Cash account') {
                    $array[] = '<span class="text-success">(cash)</span>';
                    continue;
                }
                $array[] = '<a title="' . e($entry->name) . '" href="' . route('accounts.show', $entry->id) . '">' . e($entry->name) . '</a>';
            }
            $array  = array_unique($array);
            $result = join(', ', $array);
            $cache->store($result);

            return $result;

        }
        );
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        $filters = [$this->typeIcon()];

        return $filters;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        $functions = [
            $this->getSourceAccount(),
            $this->getDestinationAccount(),
            $this->formatPerspective(),
            $this->journalBudgets(),
            $this->journalCategories(),
            $this->transactionBudgets(),
            $this->transactionCategories(),
        ];

        return $functions;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'FireflyIII\Support\Twig\Journals';
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function getSourceAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'sourceAccount', function (TransactionJournal $journal): string {

            $cache = new CacheProperties;
            $cache->addProperty($journal->id);
            $cache->addProperty('transaction-journal');
            $cache->addProperty('source-account-string');
            if ($cache->has()) {
                return $cache->get();
            }

            $list  = TransactionJournal::sourceAccountList($journal);
            $array = [];
            /** @var Account $entry */
            foreach ($list as $entry) {
                if ($entry->accountType->type == 'Cash account') {
                    $array[] = '<span class="text-success">(cash)</span>';
                    continue;
                }
                $array[] = '<a title="' . e($entry->name) . '" href="' . route('accounts.show', $entry->id) . '">' . e($entry->name) . '</a>';
            }
            $array  = array_unique($array);
            $result = join(', ', $array);
            $cache->store($result);

            return $result;


        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalBudgets(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalBudgets', function (TransactionJournal $journal): string {
            $cache = new CacheProperties;
            $cache->addProperty($journal->id);
            $cache->addProperty('transaction-journal');
            $cache->addProperty('budget-string');
            if ($cache->has()) {
                return $cache->get();
            }


            $budgets = [];
            // get all budgets:
            foreach ($journal->budgets as $budget) {
                $budgets[] = '<a href="' . route('budgets.show', [$budget->id]) . '" title="' . e($budget->name) . '">' . e($budget->name) . '</a>';
            }
            // and more!
            foreach ($journal->transactions as $transaction) {
                foreach ($transaction->budgets as $budget) {
                    $budgets[] = '<a href="' . route('budgets.show', [$budget->id]) . '" title="' . e($budget->name) . '">' . e($budget->name) . '</a>';
                }
            }
            $string = join(', ', array_unique($budgets));
            $cache->store($string);

            return $string;


        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalCategories(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalCategories', function (TransactionJournal $journal): string {
            $cache = new CacheProperties;
            $cache->addProperty($journal->id);
            $cache->addProperty('transaction-journal');
            $cache->addProperty('category-string');
            if ($cache->has()) {
                return $cache->get();
            }


            $categories = [];
            // get all budgets:
            foreach ($journal->categories as $category) {
                $categories[] = '<a href="' . route('categories.show', [$category->id]) . '" title="' . e($category->name) . '">' . e($category->name) . '</a>';
            }
            // and more!
            foreach ($journal->transactions as $transaction) {
                foreach ($transaction->categories as $category) {
                    $categories[] = '<a href="' . route('categories.show', [$category->id]) . '" title="' . e($category->name) . '">' . e($category->name)
                                    . '</a>';
                }
            }
            $string = join(', ', array_unique($categories));
            $cache->store($string);

            return $string;


        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionBudgets(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionBudgets', function (Transaction $transaction): string {
            $cache = new CacheProperties;
            $cache->addProperty($transaction->id);
            $cache->addProperty('transaction');
            $cache->addProperty('budget-string');
            if ($cache->has()) {
                return $cache->get();
            }

            $budgets = [];
            // get all budgets:
            foreach ($transaction->budgets as $budget) {
                $budgets[] = '<a href="' . route('budgets.show', [$budget->id]) . '" title="' . e($budget->name) . '">' . e($budget->name) . '</a>';
            }
            $string = join(', ', array_unique($budgets));
            $cache->store($string);

            return $string;


        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionCategories(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionCategories', function (Transaction $transaction): string {
            $cache = new CacheProperties;
            $cache->addProperty($transaction->id);
            $cache->addProperty('transaction');
            $cache->addProperty('category-string');
            if ($cache->has()) {
                return $cache->get();
            }


            $categories = [];
            // get all budgets:
            foreach ($transaction->categories as $category) {
                $categories[] = '<a href="' . route('categories.show', [$category->id]) . '" title="' . e($category->name) . '">' . e($category->name) . '</a>';
            }

            $string = join(', ', array_unique($categories));
            $cache->store($string);

            return $string;


        }
        );
    }

    /**
     * @return Twig_SimpleFilter
     */
    protected function typeIcon(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'typeIcon', function (TransactionJournal $journal): string {

            switch (true) {
                case $journal->isWithdrawal():
                    $txt = '<i class="fa fa-long-arrow-left fa-fw" title="' . trans('firefly.withdrawal') . '"></i>';
                    break;
                case $journal->isDeposit():
                    $txt = '<i class="fa fa-long-arrow-right fa-fw" title="' . trans('firefly.deposit') . '"></i>';
                    break;
                case $journal->isTransfer():
                    $txt = '<i class="fa fa-fw fa-exchange" title="' . trans('firefly.transfer') . '"></i>';
                    break;
                case $journal->isOpeningBalance():
                    $txt = '<i class="fa-fw fa fa-ban" title="' . trans('firefly.openingBalance') . '"></i>';
                    break;
                default:
                    $txt = '';
                    break;
            }

            return $txt;
        }, ['is_safe' => ['html']]
        );
    }
}
