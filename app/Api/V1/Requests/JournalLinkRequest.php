<?php
/**
 * JournalLinkRequest.php
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


/**
 *
 * Class JournalLinkRequest
 */
class JournalLinkRequest extends Request
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
            'link_type_id' => $this->integer('link_type_id'),
            'inward_id'    => $this->integer('inward_id'),
            'outward_id'   => $this->integer('outward_id'),
            'notes'        => $this->string('notes'),
        ];
    }

    /**
     * TODO include link-type name as optional parameter.
     * TODO be consistent and remove notes from this object.
     *
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'link_type_id' => 'required|exists:link_types,id',
            'inward_id'    => 'required|belongsToUser:transaction_journals,id',
            'outward_id'   => 'required|belongsToUser:transaction_journals,id',
            'notes'        => 'between:0,65000',
        ];
    }

}
