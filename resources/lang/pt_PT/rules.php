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
    'inspect_rule'                                => 'Inspecionar regra ":title" @ Firefly III',
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
    'already_has_destination'                     => 'Esta transação já tem ":name" como conta de destino',
    'already_has_source'                          => 'Esta transação já tem ":name" como a conta de origem',
    'already_linked_to_subscription'              => 'A transação já está vinculada à subscrição ":name"',
    'already_linked_to_category'                  => 'A transação já está vinculada à categoria ":name"',
    'already_linked_to_budget'                    => 'A transação já está vinculada ao orçamento ":name"',
    'cannot_find_subscription'                    => 'Firefly III não consegue encontrar a subscrição ":name"',
    'no_notes_to_move'                            => 'A transação não tem notas para mover para o campo de descrição',
    'no_tags_to_remove'                           => 'A transação não tem etiquetas para remover',
    'not_withdrawal'                              => 'A transação não é um levantamento',
    'not_deposit'                                 => 'A transação não é um depósito',
    'cannot_find_tag'                             => 'Firefly III não consegue encontrar a etiqueta ":tag"',
    'cannot_find_asset'                           => 'Firefly III não consegue encontrar a conta de ativos ":name"',
    'cannot_find_accounts'                        => 'Firefly III não conseguiu encontrar a conta de origem ou de destino',
    'cannot_find_source_transaction'              => 'Firefly III não conseguiu encontrar a transação de origem',
    'cannot_find_destination_transaction'         => 'Firefly III não conseguiu encontrar a transação de destino',
    'cannot_find_source_transaction_account'      => 'Firefly III não conseguiu encontrar a conta de origem da transação',
    'cannot_find_destination_transaction_account' => 'Firefly III não conseguiu encontrar a conta de destino da transação',
    'cannot_find_piggy'                           => 'Firefly III não conseguiu encontrar nenhum mealheiro chamado ":name"',
    'no_link_piggy'                               => 'As contas desta transação não estão ligadas ao mealheiro, logo nenhuma ação será tomada',
    'cannot_unlink_tag'                           => 'Etiqueta ":tag" não está vinculada a esta transação',
    'cannot_find_budget'                          => 'Firefly III não consegue encontrar o orçamento ":name"',
    'cannot_find_category'                        => 'Firefly III não consegue encontrar a categoria ":name"',
    'cannot_set_budget'                           => 'Firefly III não pode definir o orçamento ":name" para uma transação do tipo ":type"',
    'journal_invalid_amount'                      => 'Firefly III não pode definir a quantidade ":amount" porque não é um número válido.',
];
