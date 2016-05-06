<?php
declare(strict_types = 1);

namespace FireflyIII\Support\Twig;


use FireflyIII\Models\Account;
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
            $result = join(', ', $array);
            $cache->store($result);

            return $result;


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
