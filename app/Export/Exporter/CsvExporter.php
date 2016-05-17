<?php
declare(strict_types = 1);
/**
 * CsvExporter.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Exporter;

use FireflyIII\Export\Entry\Entry;
use FireflyIII\Export\Entry\EntryAccount;
use FireflyIII\Models\ExportJob;
use League\Csv\Writer;
use SplFileObject;

/**
 * Class CsvExporter
 *
 * @package FireflyIII\Export\Exporter
 */
class CsvExporter extends BasicExporter implements ExporterInterface
{
    /** @var  string */
    private $fileName;

    /**
     * CsvExporter constructor.
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        parent::__construct($job);

    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return bool
     */
    public function run(): bool
    {
        // create temporary file:
        $this->tempFile();

        // necessary for CSV writer:
        $fullPath = storage_path('export') . DIRECTORY_SEPARATOR . $this->fileName;

        // create CSV writer:
        $writer = Writer::createFromPath(new SplFileObject($fullPath, 'a+'), 'w');

        // all rows:
        $rows = [];

        /*
         * Count the maximum number of sources and destinations
         * each entry has. May need to expand the number of export fields:
         */
        $maxSourceAccounts      = 1;
        $maxDestinationAccounts = 1;
        /** @var Entry $entry */
        foreach ($this->getEntries() as $entry) {
            $sources                = $entry->sourceAccounts->count();
            $destinations           = $entry->destinationAccounts->count();
            $maxSourceAccounts      = max($maxSourceAccounts, $sources);
            $maxDestinationAccounts = max($maxDestinationAccounts, $destinations);
        }

        // add header:
        $rows[] = array_keys($this->getFieldsAndTypes($maxSourceAccounts, $maxDestinationAccounts));

        // then the rest:
        /** @var Entry $entry */
        foreach ($this->getEntries() as $entry) {
            // order is defined in Entry::getFieldsAndTypes.
            $current = [
                $entry->description,
                $entry->amount,
                $entry->date];
            for ($i = 0; $i < $maxSourceAccounts; $i++) {
                /** @var EntryAccount $source */
                $source        = $entry->sourceAccounts->get($i);
                $currentId     = '';
                $currentName   = '';
                $currentIban   = '';
                $currentType   = '';
                $currentNumber = '';
                if ($source) {
                    $currentId     = $source->accountId;
                    $currentName   = $source->name;
                    $currentIban   = $source->iban;
                    $currentType   = $source->type;
                    $currentNumber = $source->number;
                }
                $current[] = $currentId;
                $current[] = $currentName;
                $current[] = $currentIban;
                $current[] = $currentType;
                $current[] = $currentNumber;
            }
            unset($source);
            for ($i = 0; $i < $maxDestinationAccounts; $i++) {
                /** @var EntryAccount $destination */
                $destination   = $entry->destinationAccounts->get($i);
                $currentId     = '';
                $currentName   = '';
                $currentIban   = '';
                $currentType   = '';
                $currentNumber = '';
                if ($destination) {
                    $currentId     = $destination->accountId;
                    $currentName   = $destination->name;
                    $currentIban   = $destination->iban;
                    $currentType   = $destination->type;
                    $currentNumber = $destination->number;
                }
                $current[] = $currentId;
                $current[] = $currentName;
                $current[] = $currentIban;
                $current[] = $currentType;
                $current[] = $currentNumber;
            }
            unset($destination);


            $current[] = $entry->budget->budgetId;
            $current[] = $entry->budget->name;
            $current[] = $entry->category->categoryId;
            $current[] = $entry->category->name;
            $current[] = $entry->bill->billId;
            $current[] = $entry->bill->name;
            $rows[]    = $current;

        }
        $writer->insertAll($rows);

        return true;
    }

    /**
     * @return array
     */
    private function getFieldsAndTypes(int $sources, int $destinations): array
    {
        // key = field name (see top of class)
        // value = field type (see csv.php under 'roles')
        $array = [
            'description' => 'description',
            'amount'      => 'amount',
            'date'        => 'date-transaction',
        ];
        for ($i = 0; $i < $sources; $i++) {
            $array['source_account_' . $i . '_id']     = 'account-id';
            $array['source_account_' . $i . '_name']   = 'account-name';
            $array['source_account_' . $i . '_iban']   = 'account-iban';
            $array['source_account_' . $i . '_type']   = '_ignore';
            $array['source_account_' . $i . '_number'] = 'account-number';
        }
        for ($i = 0; $i < $destinations; $i++) {
            $array['destination_account_' . $i . '_id']     = 'account-id';
            $array['destination_account_' . $i . '_name']   = 'account-name';
            $array['destination_account_' . $i . '_iban']   = 'account-iban';
            $array['destination_account_' . $i . '_type']   = '_ignore';
            $array['destination_account_' . $i . '_number'] = 'account-number';
        }

        $array['budget_id']     = 'budget-id';
        $array['budget_name']   = 'budget-name';
        $array['category_id']   = 'category-id';
        $array['category_name'] = 'category-name';
        $array['bill_id']       = 'bill-id';
        $array['bill_name']     = 'bill-name';

        return $array;
    }

    private function tempFile()
    {
        $this->fileName = $this->job->key . '-records.csv';
    }
}
