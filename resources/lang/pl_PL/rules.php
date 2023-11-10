<?php


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

declare(strict_types=1);

return [
    'main_message'                                => 'Action ":action", present in rule ":rule", could not be applied to transaction #:group: :error',
    'find_or_create_tag_failed'                   => 'Could not find or create tag ":tag"',
    'tag_already_added'                           => 'Tag ":tag" is already linked to this transaction',
    'inspect_transaction'                         => 'Inspect transaction ":title" @ Firefly III',
    'inspect_rule'                                => 'Inspect rule ":title" @ Firefly III',
    'journal_other_user'                          => 'This transaction doesn\'t belong to the user',
    'no_such_journal'                             => 'This transaction doesn\'t exist',
    'journal_already_no_budget'                   => 'This transaction has no budget, so it cannot be removed',
    'journal_already_no_category'                 => 'This transaction had no category, so it cannot be removed',
    'journal_already_no_notes'                    => 'This transaction had no notes, so they cannot be removed',
    'journal_not_found'                           => 'Firefly III can\'t find the requested transaction',
    'split_group'                                 => 'Firefly III cannot execute this action on a transaction with multiple splits',
    'is_already_withdrawal'                       => 'This transaction is already a withdrawal',
    'is_already_deposit'                          => 'This transaction is already a deposit',
    'is_already_transfer'                         => 'This transaction is already a transfer',
    'is_not_transfer'                             => 'This transaction is not a transfer',
    'complex_error'                               => 'Something complicated went wrong. Sorry about that. Please inspect the logs of Firefly III',
    'no_valid_opposing'                           => 'Conversion failed because there is no valid account named ":account"',
    'new_notes_empty'                             => 'The notes to be set are empty',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III cannot convert a ":type" to a withdrawal',
    'unsupported_transaction_type_deposit'        => 'Firefly III cannot convert a ":type" to a deposit',
    'unsupported_transaction_type_transfer'       => 'Firefly III nie może przekonwertować ":type" na transfer',
    'already_has_source_asset'                    => 'This transaction already has ":name" as the source asset account',
    'already_has_destination_asset'               => 'This transaction already has ":name" as the destination asset account',
    'already_has_destination'                     => 'This transaction already has ":name" as the destination account',
    'already_has_source'                          => 'This transaction already has ":name" as the source account',
    'already_linked_to_subscription'              => 'The transaction is already linked to subscription ":name"',
    'already_linked_to_category'                  => 'The transaction is already linked to category ":name"',
    'already_linked_to_budget'                    => 'The transaction is already linked to budget ":name"',
    'cannot_find_subscription'                    => 'Firefly III can\'t find subscription ":name"',
    'no_notes_to_move'                            => 'Transakcja nie ma notatek do przeniesienia do pola opisu',
    'no_tags_to_remove'                           => 'Transakcja nie ma tagów do usunięcia',
    'cannot_find_tag'                             => 'Firefly III nie może znaleźć tagu ":tag"',
    'cannot_find_asset'                           => 'Firefly III nie może znaleźć konta aktywów ":name"',
    'cannot_find_accounts'                        => 'Firefly III nie może znaleźć konta źródłowego lub docelowego',
    'cannot_find_source_transaction'              => 'Firefly III nie może znaleźć transakcji źródłowej',
    'cannot_find_destination_transaction'         => 'Firefly III nie może znaleźć docelowej transakcji',
    'cannot_find_source_transaction_account'      => 'Firefly III nie może znaleźć konta źródłowego transakcji',
    'cannot_find_destination_transaction_account' => 'Firefly III nie może znaleźć konta docelowego transakcji',
    'cannot_find_piggy'                           => 'Firefly III nie może znaleźć skarbonki o nazwie ":name"',
    'no_link_piggy'                               => 'Konta tej transakcji nie są powiązane ze skarbonką - więc nie zostaną podjęte żadne działania',
    'cannot_unlink_tag'                           => 'Tag ":tag" nie jest powiązany z tą transakcją',
    'cannot_find_budget'                          => 'Firefly III nie może znaleźć budżetu ":name"',
    'cannot_find_category'                        => 'Firefly III nie może znaleźć kategorii ":name"',
    'cannot_set_budget'                           => 'Firefly III nie może ustawić budżetu ":name" dla transakcji typu ":type"',
];
