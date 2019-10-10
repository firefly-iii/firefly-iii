<?php
/**
 * OFXProcessor.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Import\Routine\File;


use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Log;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\Transaction;

/**
 *
 * Class OFXProcessor
 */
class OFXProcessor implements FileProcessorInterface
{
    /** @var AttachmentHelperInterface */
    private $attachments;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Fires the file processor.
     *
     * @return array
     */
    public function run(): array
    {
        $collection = $this->repository->getAttachments($this->importJob);
        $content    = '';
        /** @var Attachment $attachment */
        foreach ($collection as $attachment) {
            if ('import_file' === $attachment->filename) {
                $content = $this->attachments->getAttachmentContent($attachment);
                break;
            }
        }
        $config = $this->repository->getConfiguration($this->importJob);
        try {
            Log::debug('Now in OFXProcessor() run');
            $ofxParser = new \OfxParser\Parser();
            $ofx       = $ofxParser->loadFromString($content);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }
        reset($ofx->bankAccounts);
        /** @var BankAccount $bankAccount */
        foreach ($ofx->bankAccounts as $bankAccount) {
            /** @var Transaction $transaction */
            foreach ($bankAccount->statement->transactions as $transaction) {
                //var_dump($transaction);
            }
        }

        //
        //        // Get the statement start and end dates
        //        $startDate = $bankAccount->statement->startDate;
        //        $endDate   = $bankAccount->statement->endDate;
        //        var_dump($startDate);
        //        var_dump($endDate);
        //
        //        // Get the statement transactions for the account
        //        $transactions = $bankAccount->statement->transactions;
        //        foreach($transactions as $transaction) {
        //            var_dump($transaction);
        //        }
        //
        //        die('I am here.');


        exit;


        return [];
    }

    /**
     * Set values.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        Log::debug('Now in setImportJob()');
        $this->importJob   = $importJob;
        $this->repository  = app(ImportJobRepositoryInterface::class);
        $this->attachments = app(AttachmentHelperInterface::class);

        $this->repository->setUser($importJob->user);

    }
}
