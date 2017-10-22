<?php
/**
 * LinkTypeFormRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;

/**
 * Class BillFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class LinkTypeFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged and admins
        return auth()->check() && auth()->user()->hasRole('owner');
    }

    /**
     * @return array
     */
    public function rules()
    {
        // fixed

        /** @var LinkTypeRepositoryInterface $repository */
        $repository = app(LinkTypeRepositoryInterface::class);
        $nameRule   = 'required|min:1|unique:link_types,name';
        $idRule     = '';
        if (!is_null($repository->find($this->integer('id'))->id)) {
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
