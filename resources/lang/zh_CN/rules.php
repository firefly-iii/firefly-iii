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
    'main_message'                                => '操作“:action”，存在于规则“:rule”，无法应用于交易 #:group: :error',
    'find_or_create_tag_failed'                   => '找不到或无法创建标签“:tag”',
    'tag_already_added'                           => '标签“:tag”已关联此交易',
    'inspect_transaction'                         => '检查交易“:title” @ Frefly III',
    'inspect_rule'                                => '检查规则“:title” @ Frefly III',
    'journal_other_user'                          => '此交易不属于用户',
    'no_such_journal'                             => '交易不存在',
    'journal_already_no_budget'                   => '此交易没有预算，无法删除',
    'journal_already_no_category'                 => '此交易没有分类，无法删除',
    'journal_already_no_notes'                    => '此交易没有备注，无法删除',
    'journal_not_found'                           => 'Firefly III 找不到请求的交易',
    'split_group'                                 => 'Firefly III 无法对具有多个拆分的交易执行此操作',
    'is_already_withdrawal'                       => '此交易已经作为支出',
    'is_already_deposit'                          => '此交易已经作为收入',
    'is_already_transfer'                         => '此交易已经作为转账',
    'is_not_transfer'                             => '此交易不是转账',
    'complex_error'                               => '很抱歉，产生了一些复杂的错误，请检查Firefly III 的日志',
    'no_valid_opposing'                           => '转换失败，没有名称为“:account”的有效账户',
    'new_notes_empty'                             => '要设置的备注为空',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III 无法将“:type”转换为支出',
    'unsupported_transaction_type_deposit'        => 'Firefly III 无法将“:type”转换为收入',
    'unsupported_transaction_type_transfer'       => 'Firefly III 无法将“:type”转换为转账',
    'already_has_source_asset'                    => '此交易已经以“:name”作为来源资产账户',
    'already_has_destination_asset'               => '此交易已经以“:name”作为目标资产账户',
    'already_has_destination'                     => '此交易已经以“:name”作为目标账户',
    'already_has_source'                          => '此交易已经以“:name”作为来源账户',
    'already_linked_to_subscription'              => '此交易已关联订阅“:name”',
    'already_linked_to_category'                  => '此交易已关联分类“:name”',
    'already_linked_to_budget'                    => '此交易已关联预算“:name”',
    'cannot_find_subscription'                    => 'Firefly III 找不到订阅“:name”',
    'no_notes_to_move'                            => '此交易没有备注可移动到描述字段',
    'no_tags_to_remove'                           => '此交易没有要删除的标签',
    'not_withdrawal'                              => 'The transaction is not a withdrawal',
    'not_deposit'                                 => 'The transaction is not a deposit',
    'cannot_find_tag'                             => 'Firefly III 找不到标签“:tag”',
    'cannot_find_asset'                           => 'Firefly III 找不到资产账户“:name”',
    'cannot_find_accounts'                        => 'Firefly III 找不到来源账户或目标账户',
    'cannot_find_source_transaction'              => 'Firefly III 找不到来源交易',
    'cannot_find_destination_transaction'         => 'Firefly III 找不到目标交易',
    'cannot_find_source_transaction_account'      => 'Firefly III 找不到来源交易账户',
    'cannot_find_destination_transaction_account' => 'Firefly III 找不到目标交易账户',
    'cannot_find_piggy'                           => 'Firefly III 找不到名为 ":name " 的存钱罐',
    'no_link_piggy'                               => '此交易的账户没有关联至存钱罐，因此不会采取任何操作',
    'cannot_unlink_tag'                           => '标签“:tag”没有关联此交易',
    'cannot_find_budget'                          => 'Firefly III 找不到预算“:name”',
    'cannot_find_category'                        => 'Firefly III 找不到分类“:name”',
    'cannot_set_budget'                           => 'Firefly III 无法设置预算“:name”为类型“:type”的交易',
];
