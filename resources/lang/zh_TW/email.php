<?php

/**
 * email.php
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

// Ignore this comment

declare(strict_types=1);

return [
    // common items
    'greeting'                                => '嗨，您好！',
    'closing'                                 => '嗶嗶嗶嗶嗶',
    'signature'                               => 'The Firefly III 郵件機器人',
    'footer_ps'                               => '備註：這個訊息是因為 IP 位址 :ipAddress 觸發的要求所遞出。',

    // admin test
    'admin_test_subject'                      => '來自 Firefly III 安裝程式的測試訊息',
    'admin_test_body'                         => '這是您 Firefly III 載體的測試訊息，是寄給 :email 的。',

    // Ignore this comment

    // invite
    'invitation_created_subject'              => '已建立邀請',
    'invitation_created_body'                 => '管理員 ":email" 已透過電郵 ":invitee" 邀請用戶建立一個新的帳戶。該邀請有效期為48小時。',
    'invite_user_subject'                     => '你已被邀請建立Firefly III 帳戶',
    'invitation_introduction'                 => '你已經被邀請在 **:host** 建立一個Firefly III 帳戶。 Firefly III 是一個私隱、自架、個人的財富管理。',
    'invitation_invited_by'                   => '你已被:admin 邀請，而該邀請已發送到:invitee',
    'invitation_url'                          => '此邀請有效期為48 小時，並可在 [Firefly III](:url) 兌換。祝您使用愉快！',

    // new IP
    'login_from_new_ip'                       => '自 Firefly III 的新登入',
    'slack_login_from_new_ip'                 => '從 IP :ip (:host) 新的 Firefly III 登入',
    'new_ip_body'                             => 'Firefly III 監測到未知 IP 位址在您帳號的1筆新登入訊息，若您未曾使用下列 IP 位址，或是使用該位址登入已超過6個月餘，Firefly III 會警示您。',
    'new_ip_warning'                          => '如果你知道這個IP 地址或登入紀錄，你可以無視這個訊息。如果你沒有登入或不知道在發生什麼事，請到你的個人資料頁面，確認你的密碼強度、更改密碼並登出所有其他登入階段。我們強烈建議你啟用多重要素驗證以確保你的資料安全。',
    'ip_address'                              => 'IP 地址',
    'host_name'                               => '主機',
    'date_time'                               => '日期和時間',

    // access token created
    'access_token_created_subject'            => '已建立新的存取權杖',
    'access_token_created_body'               => '有人（希望是你）剛剛在你的Firefly III 帳戶中建立了新的 Firefly III API 存取權杖。',
    'access_token_created_explanation'        => '你可以利用這個權杖透過Firefly 應用程式介面(API) 存取你**所有**的財務紀錄',
    'access_token_created_revoke'             => '如果你沒有建立權杖，請盡快到 :url 撤銷權杖。',

    // registered
    'registered_subject'                      => '歡迎使用 Firefly III！',
    'registered_subject_admin'                => '一名新用戶已經註冊',
    'admin_new_user_registered'               => '一名新用戶已經註冊。用戶 **:email** 的ID為 #:id',
    'registered_welcome'                      => '歡迎使用 [Firefly III](:address)！此電郵用於確認你已經成功註冊！',
    'registered_pw'                           => '如果你忘記了你的密碼，請使用[密碼重設工具](:address/password/reset)重設你的密碼。',
    'registered_help'                         => '求助按鈕在畫面的右上方。如需協助時，請按一下按鈕。',
    'registered_closing'                      => '祝您使用愉快！',
    'registered_firefly_iii_link'             => 'Firefly III：',
    'registered_pw_reset_link'                => '重置密碼：',
    'registered_doc_link'                     => '說明文件：',

    // Ignore this comment

    // new version
    'new_version_email_subject'               => '有新的Firefly III 版本',

    // email change
    'email_change_subject'                    => '你的Firefly III 電郵地址已被更變',
    'email_change_body_to_new'                => '你或其他人在你的 Firefly III 帳戶更改了你的電郵地址。如果你沒有提出該請求，請無視並刪除此電郵。',
    'email_change_body_to_old'                => '你或其他人於你的 Firefly III 帳戶更改你的電郵你的電郵地址。如果這不是你，你**必須**使用以下的「撤消」連結保護你的帳戶！',
    'email_change_ignore'                     => '如果你提出了這項更變，你可以無視這段訊息。',
    'email_change_old'                        => '舊的電郵地址為：:email',
    'email_change_old_strong'                 => '舊的電郵地址為：**:email**',
    'email_change_new'                        => '新的電郵地址為：**:email**',
    'email_change_new_strong'                 => '新的電郵地址為：**:email**',
    'email_change_instructions'               => '在確認這項更變前你不能使用你的Firefly III 帳戶。請使用以下連結確認。',
    'email_change_undo_link'                  => '如要重設這個變更，請使用以下連結：',

    // OAuth token created
    'oauth_created_subject'                   => '已建立新的 OAuth 客戶端',
    'oauth_created_body'                      => '有人 (希望是你) 剛剛在你的 Firefly III 帳戶建立了新的 Firefly III API OAuth 客戶端。該標籤為 ":name"、回調連結為 `:url`。',
    'oauth_created_explanation'               => '你可以利用這個客戶端透過Firefly 應用程式介面(API) 存取你**所有**的財務紀錄',
    'oauth_created_undo'                      => '如果這不是你，請盡快在 `:url` 撤消這個客戶端',

    // reset password
    'reset_pw_subject'                        => '你的密碼重設請求',
    'reset_pw_instructions'                   => '有人嘗試重設你的密碼。如果你發送到重設請求，請透過以下連結繼續：',
    'reset_pw_warning'                        => '請**確認該連結**為你所想造訪的Firefly III 站台！',

    // error
    'error_subject'                           => '偵測到Firefly III 發生錯誤',
    'error_intro'                             => 'Firefly III v:version 發生了錯誤：<span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => '錯誤類別為 ":class"',
    'error_timestamp'                         => '錯誤發生在 :time',
    'error_location'                          => '該錯誤發生在檔案 "<span style="font-family: monospace;">:file</span>" 於第 :line 行；代碼為 :code.',
    'error_user'                              => '用戶#:id, <a href="mailto::email">:email</a> 遭遇了該錯誤',
    'error_no_user'                           => '在錯誤發生時，未偵測到有用戶在登入',
    'error_ip'                                => '有關該錯誤的IP 地址: :ip',
    'error_url'                               => '連結為 :url',
    'error_user_agent'                        => '使用者代理： :userAgent',
    'error_stacktrace'                        => 'The full stacktrace is below. If you think this is a bug in Firefly III, you can forward this message to <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. This can help fix the bug you just encountered.',
    'error_github_html'                       => '你亦可以在<a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a> 建立新的問題 (issue)。',
    'error_github_text'                       => '你亦可以在 https://github.com/firefly-iii/firefly-iii/issues 建立新的問題 (issue)。',
    'error_stacktrace_below'                  => 'The full stacktrace is below:',
    'error_headers'                           => '有關的HTTP頭欄位如下：',
    'error_post'                              => '提交此的用戶:',

    // Ignore this comment

    // report new journals
    'new_journals_subject'                    => 'Firefly III 已建立了一項新的交易|Firefly III 已建立了 :count項新的交易',
    'new_journals_header'                     => 'Firefly III has created a transaction for you. You can find it in your Firefly III installation:|Firefly III has created :count transactions for you. You can find them in your Firefly III installation:',

    // bill warning
    'bill_warning_subject_end_date'           => '你的帳單 ":name" 將於 :diff 日內到期',
    'bill_warning_subject_now_end_date'       => '你的帳單 ":name" 將於今天到期',
    'bill_warning_subject_extension_date'     => '你的帳單 ":name" 將於 :diff 日內延期或取消',
    'bill_warning_subject_now_extension_date' => '你的帳單 ":name" 將於今天延期或取消',
    'bill_warning_end_date'                   => '你的帳單 ":name" 將於 :date 到期，即大約 **:diff 日**',
    'bill_warning_extension_date'             => '你的帳單 ":name" 將於 :date 延期或取消，即大約 **:diff 日**',
    'bill_warning_end_date_zero'              => '你的帳單 ":name" 將於 :date ，即**今天**到期',
    'bill_warning_extension_date_zero'        => '你的帳單 ":name" 將於 :date 即**今天**延期或消取',
    'bill_warning_please_action'              => '請採取適當的行動。',
];
// Ignore this comment
