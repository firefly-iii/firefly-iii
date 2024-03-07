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
    'main_message'                                => 'A ação ":action", presente na regra ":rule", não pode ser aplicada à transação #:group: :error',
    'find_or_create_tag_failed'                   => 'Não foi possível encontrar ou criar a tag ":tag"',
    'tag_already_added'                           => 'A tag ":tag" já está vinculada a esta transação',
    'inspect_transaction'                         => 'Inspecionar transação ":title" no Firefly III',
    'inspect_rule'                                => 'Inspecionar regra ":title" no Firefly III',
    'journal_other_user'                          => 'Esta transação não pertence ao usuário',
    'no_such_journal'                             => 'Esta transação não existe',
    'journal_already_no_budget'                   => 'Esta transação não tem orçamento, então não pode ser removida',
    'journal_already_no_category'                 => 'Esta transação não tem categoria, então não pode ser removida',
    'journal_already_no_notes'                    => 'Esta transação não tem notas, então não pode ser removida',
    'journal_not_found'                           => 'O Firefly III não pode encontrar a transação solicitada',
    'split_group'                                 => 'O Firefly III não pode executar esta ação em uma transação com múltiplas divisões',
    'is_already_withdrawal'                       => 'Esta transação já é uma retirada',
    'is_already_deposit'                          => 'Esta transação já é um depósito',
    'is_already_transfer'                         => 'Esta transação já é uma transferência',
    'is_not_transfer'                             => 'Esta transação não é uma transferência',
    'complex_error'                               => 'Alguma coisa complicada deu errado. Desculpe por isso. Por favor, inspecione os logs do Firefly III',
    'no_valid_opposing'                           => 'A conversão falhou porque não há uma conta válida chamada ":account"',
    'new_notes_empty'                             => 'As notas a serem definidas estão vazias',
    'unsupported_transaction_type_withdrawal'     => 'O Firefly III não pode converter ":type" para uma retirada',
    'unsupported_transaction_type_deposit'        => 'O Firefly III não pode converter ":type" em um depósito',
    'unsupported_transaction_type_transfer'       => 'O Firefly III não pode converter ":type" em uma transferência',
    'already_has_source_asset'                    => 'Esta transação já tem ":name" como conta de ativos origem',
    'already_has_destination_asset'               => 'Esta transação já tem ":name" como conta de ativos destino',
    'already_has_destination'                     => 'Esta transação já tem ":name" como conta destino',
    'already_has_source'                          => 'Esta transação já tem ":name" como conta origem',
    'already_linked_to_subscription'              => 'A transação já está vinculada à assinatura ":name"',
    'already_linked_to_category'                  => 'A transação já está vinculada à categoria ":name"',
    'already_linked_to_budget'                    => 'A transação já está vinculada ao orçamento ":name"',
    'cannot_find_subscription'                    => 'O Firefly III não pode encontrar a assinatura ":name"',
    'no_notes_to_move'                            => 'A transação não tem notas para mover para o campo descrição',
    'no_tags_to_remove'                           => 'A transação não tem tags para remover',
    'not_withdrawal'                              => 'A transação não é uma retirada',
    'not_deposit'                                 => 'A transação não é um depósito',
    'cannot_find_tag'                             => 'O Firefly III não pode encontrar a tag ":tag"',
    'cannot_find_asset'                           => 'O Firefly III não pode encontrar a conta de ativos ":name"',
    'cannot_find_accounts'                        => 'O Firefly III não pode encontrar a conta destino ou origem',
    'cannot_find_source_transaction'              => 'O Firefly III não pode encontrar a trasação origem',
    'cannot_find_destination_transaction'         => 'O Firefly III não pode encontrar a transação destino',
    'cannot_find_source_transaction_account'      => 'O Firefly III não pode encontrar a conta origem da transação',
    'cannot_find_destination_transaction_account' => 'O Firefly III não pode encontrar a conta destino da transação',
    'cannot_find_piggy'                           => 'O Firefly III não pode encontrar um cofrinho chamado ":name"',
    'no_link_piggy'                               => 'As contas da transação não estão vinculada ao cofrinho, então nenhuma ação será tomada',
    'cannot_unlink_tag'                           => 'A tag ":tag" não está vinculada a esta transação',
    'cannot_find_budget'                          => 'O Firefly III não pode encontrar o orçamento ":name"',
    'cannot_find_category'                        => 'O Firefly III não pode encontrar a categoria ":name"',
    'cannot_set_budget'                           => 'O Firefly III não pode definir o orçamento ":name" à transação de tipo ":type"',
];
