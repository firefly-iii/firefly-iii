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


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Contracts\Validation\Rule;

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
     * @param string $model
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * Get the validation error message.
     *
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
     * @throws FireflyException
     */
    public function passes($attribute, $value): bool
    {
        if (!auth()->check()) {
            return false;
        }
        $user = auth()->user();
        switch ($this->model) {
            default:
                throw new FireflyException(sprintf('Model "%s" cannot be validated.', $this->model));
            case TransactionJournal::class:
                /** @var JournalRepositoryInterface $repository */
                $repository = app(JournalRepositoryInterface::class);
                $repository->setUser($user);
                $result = $repository->findNull((int)$value);

                return null !== $result;
                break;
        }
    }
}