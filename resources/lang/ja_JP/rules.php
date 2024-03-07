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
    'main_message'                                => 'アクション「:action"」はルール「:rule」にありますが、取引 #:group に適用できませんでした: :error',
    'find_or_create_tag_failed'                   => 'タグ「:tag」が見つからないか作成できませんでした',
    'tag_already_added'                           => 'タグ「:tag」はすでにこの取引にリンクされています',
    'inspect_transaction'                         => '取引「:title」@ Firefly IIIの検査',
    'inspect_rule'                                => 'ルール「:title」@ Firefly IIIの検査',
    'journal_other_user'                          => 'この取引はそのユーザーに属していません',
    'no_such_journal'                             => 'この取引は存在しません',
    'journal_already_no_budget'                   => 'この取引には予算がないため削除できません',
    'journal_already_no_category'                 => 'この取引にはカテゴリがないため削除できません',
    'journal_already_no_notes'                    => 'この取引にはメモがないため削除できません',
    'journal_not_found'                           => 'Firefly IIIは要求された取引を見つけられませんでした',
    'split_group'                                 => 'Firefly IIIは取引の複数分割でこのアクションを実行できません',
    'is_already_withdrawal'                       => 'この取引はすでに出金です',
    'is_already_deposit'                          => 'この取引はすでに入金です',
    'is_already_transfer'                         => 'この取引はすでに送金です',
    'is_not_transfer'                             => 'この取引は送金ではありません',
    'complex_error'                               => '問題が発生しました。申し訳ありません。Firefly IIIのログを調べてください。',
    'no_valid_opposing'                           => '「":account"」という名前の有効な口座がないため変換に失敗しました',
    'new_notes_empty'                             => 'メモに空をセットしようとしています',
    'unsupported_transaction_type_withdrawal'     => 'Firefly IIIは「:type」を出金に変更できません',
    'unsupported_transaction_type_deposit'        => 'Firefly IIIは「:type」を入金に変更できません',
    'unsupported_transaction_type_transfer'       => 'Firefly IIIは「:type」を送金に変更できません',
    'already_has_source_asset'                    => 'この取引はすでに引き出し口座は「:name」です',
    'already_has_destination_asset'               => 'この取引はすでに預け入れ口座は「:name」です',
    'already_has_destination'                     => 'この取引はすでに宛先の口座は「:name」です',
    'already_has_source'                          => 'この取引はすでに元となる口座は「:name」です',
    'already_linked_to_subscription'              => 'この取引はすでにサブスクリプション「:name」にリンクされています',
    'already_linked_to_category'                  => '取引はすでにカテゴリ「:name」にリンクされています',
    'already_linked_to_budget'                    => '取引はすでに予算「:name」にリンクされています',
    'cannot_find_subscription'                    => 'Firefly IIIはサブスクリプション「":name"」を見つけられませんでした',
    'no_notes_to_move'                            => '取引には概要に設定できるメモがありません',
    'no_tags_to_remove'                           => '取引には削除できるタグがありません',
    'not_withdrawal'                              => 'The transaction is not a withdrawal',
    'not_deposit'                                 => 'The transaction is not a deposit',
    'cannot_find_tag'                             => 'Firefly IIIはタグ「:tag」を見つけられませんでした',
    'cannot_find_asset'                           => 'Firefly IIIは資産口座「:name」を見つけられませんでした',
    'cannot_find_accounts'                        => 'Firefly IIIは引き出し元または預け入れ先口座を見つけられませんでした',
    'cannot_find_source_transaction'              => 'Firefly IIIは元となる取引を見つけられませんでした',
    'cannot_find_destination_transaction'         => 'Firefly IIIは対象となる取引を見つけられませんでした',
    'cannot_find_source_transaction_account'      => 'Firefly IIIは元となる取引の口座を見つけられませんでした',
    'cannot_find_destination_transaction_account' => 'Firefly IIIは対象となる取引の口座を見つけられませんでした',
    'cannot_find_piggy'                           => 'Firefly IIIは貯金箱「:name」を見つけられませんでした',
    'no_link_piggy'                               => 'この取引の口座は貯金箱にリンクされていないため操作は行われません',
    'cannot_unlink_tag'                           => 'タグ「:tag」はこの取引にリンクされていません',
    'cannot_find_budget'                          => 'Firefly IIIは予算「:name」を見つけらませんでした',
    'cannot_find_category'                        => 'Firefly IIIはカテゴリ「:name」を見つけらませんでした',
    'cannot_set_budget'                           => 'Firefly IIIは予算「:name」を取引種別「:type」に設定できません',
];
