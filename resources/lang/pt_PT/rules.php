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
    'main_message'                                => 'A ação ":action", presenta na regra ":rule" não pôde ser aplicada à transação #:group: :error',
    'find_or_create_tag_failed'                   => 'Não foi possível encontrar ou criar a etiqueta ":tag"',
    'tag_already_added'                           => 'A etiqueta ":tag" já está vinculada a esta transação',
    'inspect_transaction'                         => 'Inspecionar transação ":title" @ Firefly III',
    'inspect_rule'                                => 'Inspect rule ":title" @ Firefly III',
    'journal_other_user'                          => 'Esta transação não pertence ao utilizador',
    'no_such_journal'                             => 'Esta transação não existe',
    'journal_already_no_budget'                   => 'Esta transação não tem orçamento, então não pode ser removido',
    'journal_already_no_category'                 => 'Esta transação não tinha nenhuma categoria, por isso não esta pode ser removida',
    'journal_already_no_notes'                    => 'Esta transação não tinha notas, por isso elas não podem ser removidas',
    'journal_not_found'                           => 'O Firefly III não consegue encontrar a transação que solicitou',
    'split_group'                                 => 'O Firefly III não pode executar essa ação numa transação com múltiplas divisões',
    'is_already_withdrawal'                       => 'Esta transação já e um levantamento',
    'is_already_deposit'                          => 'Esta transação já e um depósito',
    'is_already_transfer'                         => 'Esta transação já é uma transferência',
    'is_not_transfer'                             => 'Esta transação não é uma transferência',
    'complex_error'                               => 'Algo complicou deu errado. Desculpe por isso. Por favor, inspecione os logs do Firefly III',
    'no_valid_opposing'                           => 'A conversão falhou porque não há nenhuma conta válida chamada ":account"',
    'new_notes_empty'                             => 'As notas a serem definidas estão vazias',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III não pode converter um ":type" para um levantamento',
    'unsupported_transaction_type_deposit'        => 'Firefly III não pode converter um ":type" para um depósito',
    'unsupported_transaction_type_transfer'       => 'Firefly III não pode converter um ":type" para uma transferência',
    'already_has_source_asset'                    => 'Esta transação já tem ":name" como a conta do ativo de origem',
    'already_has_destination_asset'               => 'Esta transação já tem ":name" como a conta de ativo de destino',
    'already_has_destination'                     => 'This transaction already has ":name" as the destination account',
    'already_has_source'                          => 'This transaction already has ":name" as the source account',
    'already_linked_to_subscription'              => 'The transaction is already linked to subscription ":name"',
    'already_linked_to_category'                  => 'The transaction is already linked to category ":name"',
    'already_linked_to_budget'                    => 'The transaction is already linked to budget ":name"',
    'cannot_find_subscription'                    => 'Firefly III can\'t find subscription ":name"',
    'no_notes_to_move'                            => 'The transaction has no notes to move to the description field',
    'no_tags_to_remove'                           => 'The transaction has no tags to remove',
    'not_withdrawal'                              => 'The transaction is not a withdrawal',
    'not_deposit'                                 => 'The transaction is not a deposit',
    'cannot_find_tag'                             => 'Firefly III can\'t find tag ":tag"',
    'cannot_find_asset'                           => 'Firefly III can\'t find asset account ":name"',
    'cannot_find_accounts'                        => 'Firefly III can\'t find the source or destination account',
    'cannot_find_source_transaction'              => 'Firefly III can\'t find the source transaction',
    'cannot_find_destination_transaction'         => 'Firefly III can\'t find the destination transaction',
    'cannot_find_source_transaction_account'      => 'Firefly III can\'t find the source transaction account',
    'cannot_find_destination_transaction_account' => 'Firefly III can\'t find the destination transaction account',
    'cannot_find_piggy'                           => 'Firefly III can\'t find a piggy bank named ":name"',
    'no_link_piggy'                               => 'This transaction\'s accounts are not linked to the piggy bank, so no action will be taken',
    'cannot_unlink_tag'                           => 'Tag ":tag" isn\'t linked to this transaction',
    'cannot_find_budget'                          => 'Firefly III can\'t find budget ":name"',
    'cannot_find_category'                        => 'Firefly III can\'t find category ":name"',
    'cannot_set_budget'                           => 'Firefly III can\'t set budget ":name" to a transaction of type ":type"',
];
