<?php
/**
 * LinkTypeRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Models\LinkType;
use Illuminate\Validation\Rule;

/**
 *
 * Class LinkTypeRequest
 *
 * @codeCoverageIgnore
 * TODO AFTER 4.8,0: split this into two request classes.
 */
class LinkTypeRequest extends Request
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
            'name'    => $this->string('name'),
            'outward' => $this->string('outward'),
            'inward'  => $this->string('inward'),
        ];


    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'name'    => 'required|unique:link_types,name|min:1',
            'outward' => 'required|unique:link_types,outward|min:1|different:inward',
            'inward'  => 'required|unique:link_types,inward|min:1|different:outward',
        ];


        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                /** @var LinkType $linkType */
                $linkType         = $this->route()->parameter('linkType');
                $rules['name']    = ['required', Rule::unique('link_types', 'name')->ignore($linkType->id), 'min:1'];
                $rules['outward'] = ['required', 'different:inward', Rule::unique('link_types', 'outward')->ignore($linkType->id), 'min:1'];
                $rules['inward']  = ['required', 'different:outward', Rule::unique('link_types', 'inward')->ignore($linkType->id), 'min:1'];
                break;
        }

        return $rules;
    }
}
