<?php
/**
 * ExpandedProcessor.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export;


use Crypt;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Export\Collector\AttachmentCollector;
use FireflyIII\Export\Collector\UploadCollector;
use FireflyIII\Export\Entry\Entry;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Storage;
use ZipArchive;

/**
 * Class ExpandedProcessor
 *
 * @package FireflyIII\Export
 */
class ExpandedProcessor implements ProcessorInterface
{

    /** @var Collection */
    public $accounts;
    /** @var  string */
    public $exportFormat;
    /** @var  bool */
    public $includeAttachments;
    /** @var  bool */
    public $includeOldUploads;
    /** @var  ExportJob */
    public $job;
    /** @var array */
    public $settings;
    /** @var  Collection */
    private $exportEntries;
    /** @var  Collection */
    private $files;
    /** @var  Collection */
    private $journals;

    /**
     * Processor constructor.
     */
    public function __construct()
    {
        $this->journals      = new Collection;
        $this->exportEntries = new Collection;
        $this->files         = new Collection;
    }

    /**
     * @return bool
     */
    public function collectAttachments(): bool
    {
        /** @var AttachmentCollector $attachmentCollector */
        $attachmentCollector = app(AttachmentCollector::class);
        $attachmentCollector->setJob($this->job);
        $attachmentCollector->setDates($this->settings['startDate'], $this->settings['endDate']);
        $attachmentCollector->run();
        $this->files = $this->files->merge($attachmentCollector->getEntries());

        return true;
    }

    /**
     * @return bool
     */
    public function collectJournals(): bool
    {
        // use journal collector thing.
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($this->accounts)->setRange($this->settings['startDate'], $this->settings['endDate'])
                  ->withOpposingAccount()->withBudgetInformation()->withCategoryInformation()
                  ->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getJournals();
        // get some more meta data for each entry:
        $ids         = $transactions->pluck('journal_id')->toArray();
        $assetIds    = $transactions->pluck('account_id')->toArray();
        $opposingIds = $transactions->pluck('opposing_account_id')->toArray();
        $notes       = $this->getNotes($ids);
        $tags        = $this->getTags($ids);
        $ibans       = $this->getIbans($assetIds) + $this->getIbans($opposingIds);
        $currencies  = $this->getAccountCurrencies($ibans);
        $transactions->each(
            function (Transaction $transaction) use ($notes, $tags, $ibans, $currencies) {
                $journalId                            = intval($transaction->journal_id);
                $accountId                            = intval($transaction->account_id);
                $opposingId                           = intval($transaction->opposing_account_id);
                $currencyId                           = $ibans[$accountId]['currency_id'] ?? 0;
                $opposingCurrencyId                   = $ibans[$opposingId]['currency_id'] ?? 0;
                $transaction->notes                   = $notes[$journalId] ?? '';
                $transaction->tags                    = join(',', $tags[$journalId] ?? []);
                $transaction->account_number          = $ibans[$accountId]['accountNumber'] ?? '';
                $transaction->account_bic             = $ibans[$accountId]['BIC'] ?? '';
                $transaction->account_currency_code   = $currencies[$currencyId] ?? '';
                $transaction->opposing_account_number = $ibans[$opposingId]['accountNumber'] ?? '';
                $transaction->opposing_account_bic    = $ibans[$opposingId]['BIC'] ?? '';
                $transaction->opposing_currency_code  = $currencies[$opposingCurrencyId] ?? '';

            }
        );

        $this->journals = $transactions;

        return true;
    }

    /**
     * @return bool
     */
    public function collectOldUploads(): bool
    {
        /** @var UploadCollector $uploadCollector */
        $uploadCollector = app(UploadCollector::class);
        $uploadCollector->setJob($this->job);
        $uploadCollector->run();

        $this->files = $this->files->merge($uploadCollector->getEntries());

        return true;
    }

    /**
     * @return bool
     */
    public function convertJournals(): bool
    {
        $this->journals->each(
            function (Transaction $transaction) {
                $this->exportEntries->push(Entry::fromTransaction($transaction));
            }
        );
        Log::debug(sprintf('Count %d entries in exportEntries (convertJournals)', $this->exportEntries->count()));

        return true;
    }

    /**
     * @return bool
     * @throws FireflyException
     */
    public function createZipFile(): bool
    {
        $zip      = new ZipArchive;
        $file     = $this->job->key . '.zip';
        $fullPath = storage_path('export') . '/' . $file;

        if ($zip->open($fullPath, ZipArchive::CREATE) !== true) {
            throw new FireflyException('Cannot store zip file.');
        }
        // for each file in the collection, add it to the zip file.
        $disk = Storage::disk('export');
        foreach ($this->getFiles() as $entry) {
            // is part of this job?
            $zipFileName = str_replace($this->job->key . '-', '', $entry);
            $zip->addFromString($zipFileName, $disk->get($entry));
        }

        $zip->close();

        // delete the files:
        $this->deleteFiles();

        return true;
    }

    /**
     * @return bool
     */
    public function exportJournals(): bool
    {
        $exporterClass = config('firefly.export_formats.' . $this->exportFormat);
        $exporter      = app($exporterClass);
        $exporter->setJob($this->job);
        $exporter->setEntries($this->exportEntries);
        $exporter->run();
        $this->files->push($exporter->getFileName());

        return true;
    }

    /**
     * @return Collection
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    /**
     * Save export job settings to class.
     *
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        // save settings
        $this->settings           = $settings;
        $this->accounts           = $settings['accounts'];
        $this->exportFormat       = $settings['exportFormat'];
        $this->includeAttachments = $settings['includeAttachments'];
        $this->includeOldUploads  = $settings['includeOldUploads'];
        $this->job                = $settings['job'];
    }

    /**
     *
     */
    private function deleteFiles()
    {
        $disk = Storage::disk('export');
        foreach ($this->getFiles() as $file) {
            $disk->delete($file);
        }
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function getAccountCurrencies(array $array): array
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $return     = [];
        $ids        = [];
        $repository->setUser($this->job->user);
        foreach ($array as $value) {
            $ids[] = $value['currency_id'] ?? 0;
        }
        $ids    = array_unique($ids);
        $result = $repository->getByIds($ids);

        foreach ($result as $currency) {
            $return[$currency->id] = $currency->code;
        }

        return $return;
    }

    /**
     * Get all IBAN / SWIFT / account numbers
     *
     * @param array $array
     *
     * @return array
     */
    private function getIbans(array $array): array
    {
        $array  = array_unique($array);
        $return = [];
        $set    = AccountMeta::whereIn('account_id', $array)
                             ->leftJoin('accounts', 'accounts.id', 'account_meta.account_id')
                             ->where('accounts.user_id', $this->job->user_id)
                             ->whereIn('account_meta.name', ['accountNumber', 'BIC', 'currency_id'])
                             ->get(['account_meta.id', 'account_meta.account_id', 'account_meta.name', 'account_meta.data']);
        /** @var AccountMeta $meta */
        foreach ($set as $meta) {
            $id                       = intval($meta->account_id);
            $return[$id][$meta->name] = $meta->data;
        }

        return $return;
    }

    /**
     * Returns, if present, for the given journal ID's the notes.
     *
     * @param array $array
     *
     * @return array
     */
    private function getNotes(array $array): array
    {
        $array  = array_unique($array);
        $set    = TransactionJournalMeta::whereIn('journal_meta.transaction_journal_id', $array)
                                        ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
                                        ->where('transaction_journals.user_id', $this->job->user_id)
                                        ->where('journal_meta.name', 'notes')->get(
                ['journal_meta.transaction_journal_id', 'journal_meta.data', 'journal_meta.id']
            );
        $return = [];
        /** @var TransactionJournalMeta $meta */
        foreach ($set as $meta) {
            $id          = intval($meta->transaction_journal_id);
            $return[$id] = $meta->data;
        }

        return $return;
    }

    /**
     * Returns a comma joined list of all the users tags linked to these journals.
     *
     * @param array $array
     *
     * @return array
     */
    private function getTags(array $array): array
    {
        $set    = DB::table('tag_transaction_journal')
                    ->whereIn('tag_transaction_journal.transaction_journal_id', $array)
                    ->leftJoin('tags', 'tag_transaction_journal.tag_id', '=', 'tags.id')
                    ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'tag_transaction_journal.transaction_journal_id')
                    ->where('transaction_journals.user_id', $this->job->user_id)
                    ->get(['tag_transaction_journal.transaction_journal_id', 'tags.tag']);
        $result = [];
        foreach ($set as $entry) {
            $id            = intval($entry->transaction_journal_id);
            $result[$id]   = isset($result[$id]) ? $result[$id] : [];
            $result[$id][] = Crypt::decrypt($entry->tag);
        }

        return $result;
    }
}