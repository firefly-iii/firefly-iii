<?php

/**
 * LinkTypeUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\TransactionLinkType;

use FireflyIII\Models\LinkType;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        return [
            'name'    => $this->convertString('name'),
            'outward' => $this->convertString('outward'),
            'inward'  => $this->convertString('inward'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var LinkType $linkType */
        $linkType = $this->route()->parameter('linkType');

        return [
            'name'    => [Rule::unique('link_types', 'name')->ignore($linkType->id), 'min:1', 'max:1024'],
            'outward' => ['different:inward', Rule::unique('link_types', 'outward')->ignore($linkType->id), 'min:1', 'max:1024'],
            'inward'  => ['different:outward', Rule::unique('link_types', 'inward')->ignore($linkType->id), 'min:1', 'max:1024'],
        ];
    }
}
