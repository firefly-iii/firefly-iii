<?php

/**
 * ValidJournals.php
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

namespace FireflyIII\Rules;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class ValidJournals
 */
class ValidJournals implements ValidationRule
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        app('log')->debug('In ValidJournals::passes');
        if (!is_array($value)) {
            return;
        }
        $userId = auth()->user()->id;
        foreach ($value as $journalId) {
            $count = TransactionJournal::where('id', $journalId)->where('user_id', $userId)->count();
            if (0 === $count) {
                app('log')->debug(sprintf('Count for transaction #%d and user #%d is zero! Return FALSE', $journalId, $userId));

                $fail('validation.invalid_selection')->translate();

                return;
            }
        }
        app('log')->debug('Return true!');
    }
}
