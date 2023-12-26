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
    'main_message'                                => 'Действие ":action", присутствующее в правиле ":rule", не может быть применено к транзакции #:group: :error',
    'find_or_create_tag_failed'                   => 'Не удалось найти или создать тег ":tag"',
    'tag_already_added'                           => 'Тег ":tag" уже связан с этой транзакцией',
    'inspect_transaction'                         => 'Inspect transaction ":title" @ Firefly III',
    'inspect_rule'                                => 'Inspect rule ":title" @ Firefly III',
    'journal_other_user'                          => 'Эта транзакция не принадлежит пользователю',
    'no_such_journal'                             => 'Эта транзакция не существует',
    'journal_already_no_budget'                   => 'Эта транзакция не имеет бюджета, поэтому её нельзя удалить',
    'journal_already_no_category'                 => 'Эта транзакция не имеет категории, поэтому ее нельзя удалить',
    'journal_already_no_notes'                    => 'This transaction had no notes, so they cannot be removed',
    'journal_not_found'                           => 'Firefly III не может найти запрошенную транзакцию',
    'split_group'                                 => 'Firefly III cannot execute this action on a transaction with multiple splits',
    'is_already_withdrawal'                       => 'Эта транзакция уже является расходом',
    'is_already_deposit'                          => 'Эта транзакция уже является доходом',
    'is_already_transfer'                         => 'Эта транзакция уже является переводом',
    'is_not_transfer'                             => 'Эта транзакция не является переводом',
    'complex_error'                               => 'Что-то сложное пошло не так. Извините за это. Пожалуйста, проверьте журналы Firefly III',
    'no_valid_opposing'                           => 'Преобразование не удалось, так как нет действующего счета с именем ":account"',
    'new_notes_empty'                             => 'Заметки должны быть заполнены',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III не может конвертировать ":type" в расход',
    'unsupported_transaction_type_deposit'        => 'Firefly III не может конвертировать ":type" в доход',
    'unsupported_transaction_type_transfer'       => 'Firefly III не может конвертировать ":type" в перевод',
    'already_has_source_asset'                    => 'Эта транзакция уже имеет ":name" в качестве основного счета-источника',
    'already_has_destination_asset'               => 'Эта транзакция уже имеет ":name" в качестве основного счета назначения',
    'already_has_destination'                     => 'Эта транзакция уже имеет ":name" в качестве счета назначения',
    'already_has_source'                          => 'Эта транзакция уже имеет ":name" в качестве счета-источника',
    'already_linked_to_subscription'              => 'Транзакция уже связана с подпиской ":name"',
    'already_linked_to_category'                  => 'Транзакция уже связана с категорией ":name"',
    'already_linked_to_budget'                    => 'Транзакция уже связана с бюджетом ":name"',
    'cannot_find_subscription'                    => 'Firefly III не может найти подписку ":name"',
    'no_notes_to_move'                            => 'Транзакция не имеет заметок для перемещения в поле описания',
    'no_tags_to_remove'                           => 'У транзакции нет тегов для удаления',
    'not_withdrawal'                              => 'Транзакция не является снятием',
    'not_deposit'                                 => 'Транзакция не является депозитом',
    'cannot_find_tag'                             => 'Firefly III не может найти тег ":tag"',
    'cannot_find_asset'                           => 'Firefly III не может найти основной счет ":name"',
    'cannot_find_accounts'                        => 'Firefly III не может найти счет источника или назначения',
    'cannot_find_source_transaction'              => 'Firefly III не может найти транзакцию источника',
    'cannot_find_destination_transaction'         => 'Firefly III не может найти транзакцию назначения',
    'cannot_find_source_transaction_account'      => 'Firefly III не может найти счет транзакции источника',
    'cannot_find_destination_transaction_account' => 'Firefly III не может найти счет транзакции назначения',
    'cannot_find_piggy'                           => 'Firefly III не может найти копилку с именем ":name"',
    'no_link_piggy'                               => 'Счета этих транзакций не привязаны к копилке, поэтому никакие действия не будут предприняты',
    'cannot_unlink_tag'                           => 'Тег ":tag" не связан с этой транзакцией',
    'cannot_find_budget'                          => 'Firefly III не может найти бюджет ":name"',
    'cannot_find_category'                        => 'Firefly III не может найти категорию ":name"',
    'cannot_set_budget'                           => 'Firefly III не может установить бюджет ":name" транзакции типа ":type"',
];
