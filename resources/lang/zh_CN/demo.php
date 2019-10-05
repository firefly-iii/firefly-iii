<?php

/**
 * demo.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
    'no_demo_text'           => '抱歉，没有额外的展示说明文字可供 <abbr title=":route">此页</abbr>。',
    'see_help_icon'          => '不过，右上角的这个 <i class="fa fa-question-circle"></i>-图示或许可以告诉你更多资讯。',
    'index'                  => '欢迎来到 <strong>Firefly III</strong>！您可在此页快速概览您的财务状况。如需更多资， 请前往帐户 &rarr; <a href=":asset">资产帐户</a> 亦或是 <a href=":budgets">预算</a> 以及 <a href=":reports">报表</a> 页面。您也可以继续浏览此页。',
    'accounts-index'         => '资产帐户是您的个人银行帐户。支出帐户是您花费金钱的帐户，如商家或其他友人。收入帐户是您获得收入的地方，如您的工作、政府或其他收入源。债务是您的借贷，如信用卡帐单或学生贷款。在此页面您可以编辑或删除这些项目。',
    'budgets-index'          => '此页面显示您的预算概览。上方横条显示可用预算额，它可随时透过点选右方的总额进行客製化。您已花费的额度则显示在下方横条，而以下则是每条预算的支出以及您已编列的预算。',
    'reports-index-start'    => 'Firefly III 支持数种不同的报表形式，您可以点选右上方的 <i class="fa fa-question-circle"></i>-图示获得更多资讯。',
    'reports-index-examples' => '请确认您以检阅过以下范例：<a href=":one">月财务概览</a>、<a href=":two">年度财务概览</a> 以及 <a href=":three">预算概览</a>。',
    'currencies-index'       => 'Firefly III 支持多种货币，即便预设为欧元，亦可设成美金或其他货币。如您所见，系统已包含了一小部分的货币种类，但您也可自行新增其他货币。修改预设货币并不会改变既有交易的货币种类，且 Firefly III 支持同时使用不同货币。',
    'transactions-index'     => '这些支出、储蓄与转帐并非蓄意虚构，而是自动产生的。',
    'piggy-banks-index'      => '如您所见，目前有3个小猪扑满。使用 + 号与 - 号按钮可改变每个小猪扑满的总额，而点选小猪扑满的名称则可管理该扑满。',
    'import-index'           => '任何 CSV 格式的档案都可导入 Firefly III，本程式也支持来自 bunq 与 Spectre 的档案格式，其他银行与金融机构则会在未来提供支持。而作为一名展示使用者，你只会看到「假的」供应者，系统会随机产生交易纪录以告知您如何运作。',
    'profile-index'          => '请谨记本展示网站每四小时会自动重新启用，您的访问凭证可能随时被撤销，这是自动发生而非错误。',
];
