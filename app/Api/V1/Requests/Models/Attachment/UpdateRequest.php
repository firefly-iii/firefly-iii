<?php
/**
 * AttachmentUpdateRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\Models\Attachment;

use FireflyIII\Rules\IsValidAttachmentModel;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateRequest
 *
 * @codeCoverageIgnore
 */
class UpdateRequest extends FormRequest
{
    use ConvertsDataTypes, ChecksLogin;

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $fields = [
            'filename'        => ['filename', 'string'],
            'title'           => ['title', 'string'],
            'notes'           => ['notes', 'nlString'],
            'attachable_type' => ['attachable_type', 'string'],
            'attachable_id'   => ['attachable_id', 'integer'],
        ];

        return $this->getAllData($fields);
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $models = config('firefly.valid_attachment_models');
        $models = array_map(

            static function (string $className) {
                return str_replace('FireflyIII\\Models\\', '', $className);
            }, $models
        );
        $models = implode(',', $models);
        $model  = $this->string('attachable_type');


        return [
            'filename'        => 'between:1,255',
            'title'           => 'between:1,255',
            'notes'           => 'between:1,65000',
            'attachable_type' => sprintf('in:%s', $models),
            'attachable_id'   => ['numeric', new IsValidAttachmentModel($model)],
        ];
    }
}
