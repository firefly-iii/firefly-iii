<?php
/**
 * AttachmentRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Models\Bill;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Rules\IsValidAttachmentModel;

/**
 * Class AttachmentRequest
 * @codeCoverageIgnore
 * TODO AFTER 4.8.0: split this into two request classes.
 */
class AttachmentRequest extends Request
{
    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        return [
            'filename' => $this->string('filename'),
            'title'    => $this->string('title'),
            'notes'    => $this->string('notes'),
            'model'    => $this->string('model'),
            'model_id' => $this->integer('model_id'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $models = implode(
            ',',
            [
                str_replace('FireflyIII\\Models\\', '', Bill::class),
                str_replace('FireflyIII\\Models\\', '', ImportJob::class),
                str_replace('FireflyIII\\Models\\', '', TransactionJournal::class),
            ]
        );
        $model  = $this->string('model');
        $rules  = [
            'filename' => 'required|between:1,255',
            'title'    => 'between:1,255',
            'notes'    => 'between:1,65000',
            'model'    => sprintf('required|in:%s', $models),
            'model_id' => ['required', 'numeric', new IsValidAttachmentModel($model)],
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                unset($rules['model'], $rules['model_id']);
                $rules['filename'] = 'between:1,255';
                break;
        }

        return $rules;
    }
}
