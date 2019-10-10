<?php
/**
 * CategoryRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Models\Category;

/**
 * Class CategoryRequest
 *
 * @codeCoverageIgnore
 * TODO AFTER 4.8,0: split this into two request classes.
 */
class CategoryRequest extends Request
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
            'name' => $this->string('name'),
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
            'name' => 'required|between:1,100|uniqueObjectForUser:categories,name',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                /** @var Category $category */
                $category      = $this->route()->parameter('category');
                $rules['name'] = sprintf('required|between:1,100|uniqueObjectForUser:categories,name,%d', $category->id);
                break;
        }

        return $rules;
    }
}
