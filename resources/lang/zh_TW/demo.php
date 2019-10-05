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
    'no_demo_text'           => '抱歉，<abbr title=":route">此頁</abbr>未提供額外的展示說明。',
    'see_help_icon'          => '不過，右上角的 <i class="fa fa-question-circle"></i> 圖示也許會給您一點資訊。',
    'index'                  => '歡迎使用 <strong>Firefly III</strong>！此頁可讓您快速概覽財務狀況。至於詳細資料，可見 帳戶 &rarr; <a href=":asset">資產帳戶</a>，另見 <a href=":budgets">預算</a> 和 <a href=":reports">報表</a> 頁面。當然，您也可以到處逛逛看。',
    'accounts-index'         => '資產帳戶比如是您的銀行個人帳戶。支出帳戶是您花錢的帳戶，如商家或其他友人。收入帳戶是您的財源，如工作、政府或其他收入來源。債務是您的借貸，如信用卡帳單或學生貸款。這些都可在此頁編輯或刪除。',
    'budgets-index'          => '此頁顯示您的預算概覽。上方橫條顯示可用預算額，按一下右方的總額就可自訂該時期的預算額。您已花費的額度則在下方橫條顯示，隨後是每一預算的支出及編製預算額。',
    'reports-index-start'    => 'Firefly III 支援數種不同類型的報表，按一下右上方的 <i class="fa fa-question-circle"></i> 圖示可查看更多資訊。',
    'reports-index-examples' => '舉例說：<a href=":one">月財務概覽</a>、<a href=":two">年度財務概覽</a> 及 <a href=":three">預算概覽</a>，記得去看看。',
    'currencies-index'       => 'Firefly III 支援多種貨幣，預設為歐元，但亦可設成美元或其他貨幣。您可見到系統已預設包含一些貨幣種類，但您也可自行新增其他貨幣。修改預設貨幣並不會改變現有交易的貨幣種類：Firefly III 是支援同時使用多種貨幣的。',
    'transactions-index'     => '這些支出、存款與轉帳談不上別出心裁：這些範例是自動產生的。',
    'piggy-banks-index'      => '您可見到有 3 個小豬撲滿。使用 + 號、- 號按鈕控制每個小豬撲滿的存款額，按一下小豬撲滿的名稱則可查看管理詳情。',
    'import-index'           => '任何 CSV 格式的檔案都可匯入 Firefly III，也支援自 bunq 與 Spectre 匯入資料，日後或會支援其他銀行與金融機構。展示使用者只會看到「虛擬」提供者的示範，系統會隨機產生交易紀錄以示範操作過程。',
    'profile-index'          => '請注意，本展示網站每 4 小時會自動重設，存取權限可能隨時撤銷。這是自動安排的，不是錯誤。',
];
