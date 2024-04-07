/*
 * inline-edit.js
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

import DarkEditable from "../libraries/dark-editable/dark-editable.js";

function inlineJournalDescription(element) {
    const journalId = parseInt(element.dataset.id);
    const groupId = parseInt(element.dataset.group);
    const opts = {
        pk: groupId,
        mode: 'inline',
        url: './api/v2/transactions/' + groupId,
        formType: 'journal_description',
        journalId: journalId,
        method: 'PUT',
    };
    new DarkEditable(element,
        opts
    );
}

export {inlineJournalDescription};
