<?php

/**
 * demo.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    'no_demo_text'           => '抱歉，<abbr title=":route">此頁</abbr> 未提供額外的展示說明。',
    'see_help_icon'          => '不過，右上角的 <i class="fa fa-question-circle"></i> 圖示也許會給您一點資訊。',
    'index'                  => '歡迎使用 <strong>Firefly III</strong>！此頁可讓您快速概覽財務狀況。至於詳細資料，可見 帳戶 &rarr; <a href=":asset">資產帳戶</a>，還有 <a href=":budgets">預算</a> 和 <a href=":reports">報表</a> 頁面。當然，您也可以到處逛逛看。',
    'accounts-index'         => '資產帳戶比如是您的銀行個人帳戶。支出帳戶是您花錢的帳戶，如商家或其他友人。收入帳戶是您的財源，如工作、政府或其他收入來源。債務是您的借貸，如信用卡帳單或學生貸款。這些都可在此頁編輯或刪除。',
    'budgets-index'          => '此頁面顯示您的預算概覽。上方橫條顯示可用預算額，它可隨時透過點選右方的總額進行客製化。您已花費的額度則顯示在下方橫條，而以下則是每條預算的支出以及您已編列的預算。',
    'reports-index-start'    => 'Firefly III 支援數種不同的報表形式，您可以點選右上方的 <i class="fa fa-question-circle"></i>-圖示獲得更多資訊。',
    'reports-index-examples' => '請確認您以檢閱過以下範例：<a href=":one">月財務概覽</a>、<a href=":two">年度財務概覽</a> 以及 <a href=":three">預算概覽</a>。',
    'currencies-index'       => 'Firefly III 支援多種貨幣，即便預設為歐元，亦可設成美金或其他貨幣。如您所見，系統已包含了一小部分的貨幣種類，但您也可自行新增其他貨幣。修改預設貨幣並不會改變既有交易的貨幣種類，且 Firefly III 支援同時使用不同貨幣。',
    'transactions-index'     => '這些支出、儲蓄與轉帳並非蓄意虛構，而是自動產生的。',
    'piggy-banks-index'      => '如您所見，目前有3個小豬撲滿。使用 + 號與 - 號按鈕可改變每個小豬撲滿的總額，而點選小豬撲滿的名稱則可管理該撲滿。',
    'import-index'           => '任何 CSV 格式的檔案都可匯入 Firefly III，本程式也支援來自 bunq 與 Spectre 的檔案格式，其他銀行與金融機構則會在未來提供支援。而作為一名展示使用者，你只會看到「假的」供應者，系統會隨機產生交易紀錄以告知您如何運作。',
    'profile-index'          => '請謹記本展示網站每四小時會自動重新啟用，您的訪問憑證可能隨時被撤銷，這是自動發生而非錯誤。',
];
