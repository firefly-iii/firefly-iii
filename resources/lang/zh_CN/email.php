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

declare(strict_types=1);

return [
    // common items
    'greeting'                                => '您好，',
    'closing'                                 => '哔——啵——',
    'signature'                               => 'Firefly III 邮件机器人',
    'footer_ps'                               => 'PS: 此消息是由来自 IP :ipAddress 的请求触发的。',

    // admin test
    'admin_test_subject'                      => '来自 Firefly III 安装的测试消息',
    'admin_test_body'                         => '这是来自 Firefly III 站点的测试消息，收件人是 :email。',

    // new IP
    'login_from_new_ip'                       => 'Firefly III 上有新的登录活动',
    'new_ip_body'                             => 'Firefly III 检测到了来自未知 IP 地址的登录活动。如果您从未在下列 IP 地址登录，或上次登录已超过6个月，Firefly III 会提醒您。',
    'new_ip_warning'                          => '如果您认识该 IP 地址或知道该次登录，您可以忽略此信息。如果您没有登录，或者您不知道发生了什么，请立即前往个人档案页面，确认您的密码安全、修改新密码，并立即退出登录其他所有设备。为了保证帐户的安全性，请务必启用两步验证功能。',
    'ip_address'                              => 'IP 地址',
    'host_name'                               => '主机',
    'date_time'                               => '日期与时间',

    // access token created
    'access_token_created_subject'            => '创建了一个新的访问令牌',
    'access_token_created_body'               => '有人（希望是您）刚刚为您的帐户创建了一个新的 Firefly III API 访问令牌。',
    'access_token_created_explanation'        => 'With this token, they can access **all** of your financial records through the Firefly III API.',
    'access_token_created_revoke'             => 'If this wasn\'t you, please revoke this token as soon as possible at :url',

    // registered
    'registered_subject'                      => '欢迎使用 Firefly III！',
    'registered_welcome'                      => 'Welcome to [Firefly III](:address). Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => '每个页面右上角都有一个帮助图标。如果您需要帮助，请点击它！',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => '祝您使用愉快！',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => '密码已重置',
    'registered_doc_link'                     => '文档',

    // email change
    'email_change_subject'                    => '您的 Firefly III 电子邮件地址已更改',
    'email_change_body_to_new'                => '您或有人访问您的 Firefly III 帐户已更改您的电子邮件地址。 如果不是您操作的，请忽略并删除。',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => '如果该操作由您本人进行，您可以安全地忽略此消息。',
    'email_change_old'                        => '旧的电子邮件地址为：:email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => '新的电子邮件地址为：:email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => '在您确认该项更改前，您无法使用 Firefly III。请点击下方链接进行操作。',
    'email_change_undo_link'                  => '若要撤销改动，请点击此链接：',

    // OAuth token created
    'oauth_created_subject'                   => '新的 OAuth 客户端完成创建',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => '您的密码重置请求',
    'reset_pw_instructions'                   => '有人尝试重置您的密码。如果是您本人的操作，请点击下方链接进行重置。',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Firefly III 发生了错误',
    'error_intro'                             => 'Firefly III v:version 发生了错误：<span style="font-family: monospace;">:errorMessage</span>。',
    'error_type'                              => '错误类型为“:class”。',
    'error_timestamp'                         => '错误发生于“:time”。',
    'error_location'                          => '错误产生于文件“<span style="font-family: monospace;">:file</span>” 第 :line 行代码 :code。',
    'error_user'                              => '错误由用户 #:id（<a href="mailto::email">:email</a>）遇到。',
    'error_no_user'                           => '没有已登录用户遇到该错误，或未检测到用户信息。',
    'error_ip'                                => '与该错误关联的 IP 地址是：:ip',
    'error_url'                               => '网址为：:url',
    'error_user_agent'                        => '用户代理: :userAgent',
    'error_stacktrace'                        => '完整的堆栈跟踪如下。如果您认为这是Fifly III中的错误，您可以将此消息转发到 <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefresy-iii.org</a>。这可以帮助修复您刚刚遇到的错误。',
    'error_github_html'                       => '如果您愿意，您也可以在 <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a> 上创建新工单。',
    'error_github_text'                       => '如果您愿意，您也可以在 https://github.com/firefrechy-iii/firefrechy-iii/issues 上创建新工单。',
    'error_stacktrace_below'                  => '完整的堆栈跟踪如下：',
    'error_headers'                           => 'The following headers may also be relevant:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III 创建了一笔新的交易|Firefly III 创建了 :count 笔新的交易',
    'new_journals_header'                     => 'Firefly III 为您创建了一笔交易，您可以在您的 Firefly III 站点中查看：|Firefly III 为您创建了 :count 笔交易，您可以在您的 Firefly III 站点中查看：',

    // bill warning
    'bill_warning_subject_end_date'           => 'Your bill ":name" is due to end in :diff days',
    'bill_warning_subject_now_end_date'       => 'Your bill ":name" is due to end TODAY',
    'bill_warning_subject_extension_date'     => 'Your bill ":name" is due to be extended or cancelled in :diff days',
    'bill_warning_subject_now_extension_date' => 'Your bill ":name" is due to be extended or cancelled TODAY',
    'bill_warning_end_date'                   => 'Your bill **":name"** is due to end on :date. This moment will pass in about **:diff days**.',
    'bill_warning_extension_date'             => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass in about **:diff days**.',
    'bill_warning_end_date_zero'              => 'Your bill **":name"** is due to end on :date. This moment will pass **TODAY!**',
    'bill_warning_extension_date_zero'        => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass **TODAY!**',
    'bill_warning_please_action'              => 'Please take the appropriate action.',

];
