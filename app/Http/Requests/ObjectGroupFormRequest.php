<?php
/**
 * ObjectGroupFormRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\ObjectGroup;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ObjectGroupFormRequest.
 */
class ObjectGroupFormRequest extends FormRequest
{
    use ConvertsDataTypes, ChecksLogin;

    /**
     * Returns the data required by the controller.
     *
     * @return array
     */
    public function getObjectGroupData(): array
    {
        return [
            'title' => $this->string('title'),
        ];
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        /** @var ObjectGroup $objectGroup */
        $objectGroup = $this->route()->parameter('objectGroup');
        $titleRule = 'required|between:1,255|uniqueObjectGroup';

        if (null !== $objectGroup) {
            $titleRule = sprintf('required|between:1,255|uniqueObjectGroup:%d', $objectGroup->id);
        }

        return [
            'title' => $titleRule,
        ];
    }
}
