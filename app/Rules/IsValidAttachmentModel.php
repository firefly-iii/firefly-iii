<?php
/**
 * IsValidAttachmentModel.php
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

namespace FireflyIII\Rules;


use FireflyIII\Models\Bill;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalAPIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\User;
use Illuminate\Contracts\Validation\Rule;
use Log;

/**
 * Class IsValidAttachmentModel
 */
class IsValidAttachmentModel implements Rule
{
    /** @var string */
    private $model;

    /**
     * IsValidAttachmentModel constructor.
     *
     * @codeCoverageIgnore
     *
     * @param string $model
     */
    public function __construct(string $model)
    {
        $model       = $this->normalizeModel($model);
        $this->model = $model;
    }

    /**
     * Get the validation error message.
     * @codeCoverageIgnore
     * @return string
     */
    public function message(): string
    {
        return (string)trans('validation.model_id_invalid');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passes($attribute, $value): bool
    {
        if (!auth()->check()) {
            return false;
        }


        if (Bill::class === $this->model) {
            /** @var BillRepositoryInterface $repository */
            $repository = app(BillRepositoryInterface::class);
            /** @var User $user */
            $user = auth()->user();
            $repository->setUser($user);
            $bill = $repository->find((int)$value);

            return null !== $bill;
        }

        if (ImportJob::class === $this->model) {
            /** @var ImportJobRepositoryInterface $repository */
            $repository = app(ImportJobRepositoryInterface::class);
            /** @var User $user */
            $user = auth()->user();
            $repository->setUser($user);
            $importJob = $repository->find((int)$value);

            return null !== $importJob;
        }

        if (Transaction::class === $this->model) {
            /** @var JournalAPIRepositoryInterface $repository */
            $repository = app(JournalAPIRepositoryInterface::class);

            /** @var User $user */
            $user = auth()->user();
            $repository->setUser($user);
            $transaction = $repository->findTransaction((int)$value);

            return null !== $transaction;
        }

        if (TransactionJournal::class === $this->model) {
            $repository = app(JournalRepositoryInterface::class);
            $user       = auth()->user();
            $repository->setUser($user);
            $result = $repository->findNull((int)$value);

            return null !== $result;
        }
        Log::error(sprintf('No model was recognized from string "%s"', $this->model));

        return false;
    }

    /**
     * @param string $model
     *
     * @return string
     */
    private function normalizeModel(string $model): string
    {
        $search  = ['FireflyIII\Models\\'];
        $replace = '';
        $model   = str_replace($search, $replace, $model);

        $model = sprintf('FireflyIII\Models\%s', $model);
        return $model;
    }
}
