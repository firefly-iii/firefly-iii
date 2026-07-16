<?php

/**
 * TransactionLinkRequest.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\Models\Tag;

use FireflyIII\Support\Request\ChecksLogin;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TransactionLinkRequest
 */
class TransactionLinkRequest extends FormRequest
{
    use ChecksLogin;

    protected array $acceptedRoles = [];

    /**
     * Returns the list of transaction journal IDs from the validated payload, as integers.
     */
    public function getJournalIds(): array
    {
        return array_map(static fn ($id) => (int) $id, $this->validated('transaction_journal_ids'));
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'transaction_journal_ids'   => ['required', 'array', 'min:1'],
            'transaction_journal_ids.*' => ['required', 'integer', 'min:1'],
        ];
    }
}
