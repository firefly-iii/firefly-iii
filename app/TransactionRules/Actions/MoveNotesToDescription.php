<?php
declare(strict_types=1);
/*
 * MoveNotesToDescription.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Support\Facades\Log;

class MoveNotesToDescription implements ActionInterface
{
    use ConvertsDataTypes;

    /**
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $journal) {
            Log::error(sprintf('No journal #%d belongs to user #%d.', $journal['transaction_journal_id'], $journal['user_id']));
            return false;
        }
        $note = $journal->notes()->first();
        if (null === $note) {
            // nothing to move, return null
            return false;
        }
        if ('' === $note->text) {
            // nothing to move, return null
            $note->delete();
            return false;
        }
        $journal->description = (string) $this->clearString($note->text, false);
        $journal->save();
        $note->delete();

        return true;
    }
}
