<?php
/**
 * LinkTypeFormRequest.php
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

/**
 * Class LinkTypeFormRequest.
 */
class LinkTypeFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged and admins
        return auth()->check();
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        // fixed
        $nameRule = 'required|min:1|unique:link_types,name';
        $idRule   = '';

        // get parameter link:
        $link = $this->route()->parameter('linkType');

        if (null !== $link) {
            $idRule   = 'exists:link_types,id';
            $nameRule = 'required|min:1';
        }

        $rules = [
            'id'      => $idRule,
            'name'    => $nameRule,
            'inward'  => 'required|min:1|different:outward',
            'outward' => 'required|min:1|different:inward',
        ];

        return $rules;
    }
}
