<?php
declare(strict_types=1);
/*
 * AppendNotesToDescription.php
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

use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Support\Steam;
use Illuminate\Support\Facades\Log;

/**
 * Class AppendNotesToDescription
 */
class AppendNotesToDescription implements ActionInterface
{
    use ConvertsDataTypes;
    /**
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        Log::debug('Now in AppendNotesToDescription');
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $journal) {
            Log::error(sprintf('No journal #%d belongs to user #%d.', $journal['transaction_journal_id'], $journal['user_id']));
            return false;
        }
        $note = $journal->notes()->first();
        if (null === $note) {
            Log::debug('Journal has no notes.');
            $note = new Note;
            $note->noteable()->associate($journal);
            $note->text = '';
        }
        // only append if there is something to append
        if ('' !== $note->text) {
            $journal->description = trim(sprintf("%s %s", $journal->description, (string) $this->clearString($note->text, false)));
            $journal->save();
            Log::debug(sprintf('Journal description is updated to "%s".', $journal->description));
            return true;
        }
        return false;
    }
}
