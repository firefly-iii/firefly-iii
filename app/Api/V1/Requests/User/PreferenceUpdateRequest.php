<?php

declare(strict_types=1);
/*
 * PreferenceStoreRequest.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\User;

use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

class PreferenceUpdateRequest extends FormRequest
{
    use ChecksLogin, ConvertsDataTypes;

    /**
     * @return array
     */
    public function getAll(): array
    {
        $array = [
            'name' => $this->string('name'),
            'data' => $this->get('data'),
        ];
        if ('true' === $array['data']) {
            $array['data'] = true;
        }
        if ('false' === $array['data']) {
            $array['data'] = false;
        }
        if (is_numeric($array['data'])) {
            $array['data'] = (float)$array['data'];
        }

        return $array;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'data' => 'required',
        ];
    }

}
