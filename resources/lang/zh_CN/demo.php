<?php

/**
 * demo.php
 * Copyright (c) 2019 james@firefly-iii.org
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
    'no_demo_text'           => '很抱歉，<abbr title=":route">此页面</abbr>没有额外的演示解释文本。',
    'see_help_icon'          => '不过，右上角的<i class="fa fa-question-circle"></i>-图标可能会告诉您更多信息。',
    'index'                  => '欢迎来到 <strong>Firefly III</strong>！您可在此页快速概览您的财务状况。如需更多信息， 请前往账户 &rarr; <a href=":asset">资产账户</a>，或者<a href=":budgets">预算</a>及<a href=":reports">报表</a>页面。您也可以继续浏览当前页面。',
    'accounts-index'         => '资产账户是您的个人银行帐户。支出帐户是记录您支出资金的地方，例如商店或朋友。收入账户是记录您获得收入的地方，例如您的工作、政府或其他收入来源。债务是您的负债或者贷款，例如信用卡账单或学生贷款。您可以在此页面编辑或删除这些项目。',
    'budgets-index'          => '此页面显示您的预算概览。上方横条显示可用预算金额，可以点击右侧金额为任意周期进行自定义。下方横条显示您实际支出的金额。最下方是每笔预算的实际支出及上限。',
    'reports-index-start'    => 'Firefly III 支持多种不同类型的报表，您可以点击右上方的<i class="fa fa-question-circle"></i>-图标获取更多信息。',
    'reports-index-examples' => '请您务必看看这些示例：<a href=":one">月度财务概览</a>、<a href=":two">年度财务概览</a>，以及<a href=":three">预算概览</a>。',
    'currencies-index'       => 'Firefly III 支持多种货币。默认货币为欧元，您可将其设为美元或其他货币。如您所见，系统已包含了一小部分的货币种类，但您也可自行新增其他货币。修改默认货币并不会改变已有交易的货币，不过，Firefly III 支持同时使用不同货币。',
    'transactions-index'     => '这些支出、收入与转账不是凭空出现的，而是自动生成的。',
    'piggy-banks-index'      => '如您所见，目前有3个存钱罐。使用 + 号与 - 号按钮可改变每个存钱罐的金额，点击存钱罐名称可进行管理。',
    'profile-index'          => '请注意，此演示站点每四小时重置一次，您的操作可能随时被删除。此为完全自动过程，而不是站点出现问题。',
];
