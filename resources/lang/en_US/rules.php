<?php

declare(strict_types=1);
/*
 * rules.php
 * Copyright (c) 2023 james@firefly-iii.org
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


return [
    'main_message'                => 'Action ":action", present in rule ":rule", could not be applied to transaction #:group: :error',
    'find_or_create_tag_failed'   => 'Could not find or create tag ":tag"',
    'tag_already_added'           => 'Tag ":tag" is already linked to this transaction.',
    'inspect_transaction'         => 'Inspect transaction ":title" @ Firefly III',
    'inspect_rule'                => 'Inspect rule ":title" @ Firefly III',
    'journal_other_user'          => 'This transaction doesn\'t belong to the user',
    'journal_already_no_budget'   => 'This transaction has no budget, so it cannot be removed.',
    'journal_already_no_category' => 'This transaction had no category, so it cannot be removed',
    'journal_already_no_notes'    => 'This transaction had no notes, so they cannot be removed',
    'journal_not_found'           => 'Firefly III can\'t find the requested transaction.',
    'split_group'                 => 'Firefly III cannot execute this action on a transaction with multiple splits.',
    'is_already_deposit'          => 'This transaction is already a deposit.',
    'is_already_transfer'         => 'This transaction is already a transfer.',
    'complex_error'               => 'Something complicated went wrong. Please inspect the logs of Firefly III.',
    'no_valid_opposing'           => 'Conversion failed because there is no valid account named ":account".',
];
