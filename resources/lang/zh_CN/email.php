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
    'greeting'                         => '你好，',
    'closing'                          => '哔——啵——',
    'signature'                        => 'Firefly III 邮件机器人',
    'footer_ps'                        => 'PS: 此消息是由于来自IP:ipAddress的请求触发而发送的。',

    // admin test
    'admin_test_subject'               => 'Firefly III 安装的测试消息',
    'admin_test_body'                  => '这是来自 Firefly III 实例的测试消息。它已被发送到 :email。',

    // access token created
    'access_token_created_subject'     => '创建了一个新的访问令牌',
    'access_token_created_body'        => '某人(希望是你) 刚刚为你的用户帐户创建了一个新的 Firefly III API 访问令牌。',
    'access_token_created_explanation' => '通过这个令牌，您的<strong>所有</strong>个人信息都可以通过Firefly III的API来访问。',
    'access_token_created_revoke'      => '如果不是您，请尽快在:url撤销此令牌。',

    // registered
    'registered_subject'               => '欢迎使用 Firefly III！',
    'registered_welcome'               => '欢迎来到 <a style="color:#337ab7" href=":address">Firefly III</a>。您的注册已经成功完成，此电子邮件即为确认信息。恭喜！',
    'registered_pw'                    => '如果您忘记了您的密码，请使用 <a style="color:#337ab7" href=":address/password/reset">重置密码工具</a> 重置密码。',
    'registered_help'                  => '每个页面右上角都有一个帮助图标。如果您需要帮助，请点击它！',
    'registered_doc_html'              => '如果您尚未阅读过，请阅读 <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">大统一理论</a>。',
    'registered_doc_text'              => '如果您尚未阅读，请阅读第一个使用指南和完整说明。',
    'registered_closing'               => '祝您使用愉快！',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => '密码已重置',
    'registered_doc_link'              => '文档',

    // email change
    'email_change_subject'             => '您的 Firefly III 电子邮件地址已更改',
    'email_change_body_to_new'         => '您或有人访问您的 Firefly III 帐户已更改您的电子邮件地址。 如果不是您操作的，请忽略并删除。',
    'email_change_body_to_old'         => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you <strong>must</strong> follow the "undo"-link below to protect your account!',
    'email_change_ignore'              => 'If you initiated this change, you may safely ignore this message.',
    'email_change_old'                 => 'The old email address was: :email',
    'email_change_old_strong'          => 'The old email address was: <strong>:email</strong>',
    'email_change_new'                 => 'The new email address is: :email',
    'email_change_new_strong'          => 'The new email address is: <strong>:email</strong>',
    'email_change_instructions'        => 'You cannot use Firefly III until you confirm this change. Please follow the link below to do so.',
    'email_change_undo_link'           => 'To undo the change, follow this link:',

    // OAuth token created
    'oauth_created_subject'            => 'A new OAuth client has been created',
    'oauth_created_body'               => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'With this client, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'oauth_created_undo'               => 'If this wasn\'t you, please revoke this client as soon as possible at :url.',

    // reset password
    'reset_pw_subject'                 => 'Your password reset request',
    'reset_pw_instructions'            => 'Somebody tried to reset your password. If it was you, please follow the link below to do so.',
    'reset_pw_warning'                 => '<strong>PLEASE</strong> verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                    => 'Caught an error in Firefly III',
    'error_intro'                      => 'Firefly III v:version ran into an error: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'The error was of type ":class".',
    'error_timestamp'                  => 'The error occurred on/at: :time.',
    'error_location'                   => 'This error occurred in file "<span style="font-family: monospace;">:file</span>" on line :line with code :code.',
    'error_user'                       => 'The error was encountered by user #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'There was no user logged in for this error or no user was detected.',
    'error_ip'                         => 'The IP address related to this error is: :ip',
    'error_url'                        => '网址为：:url',
    'error_user_agent'                 => '用户代理: :userAgent',
    'error_stacktrace'                 => '完整的堆栈跟踪如下。如果您认为这是Fifly III中的错误，您可以将此消息转发到 <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefresy-iii。 rg</a>。这可以帮助修复您刚刚遇到的错误。',
    'error_github_html'                => '如果你喜欢，你也可以在 <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a> 上打开一个新问题。',
    'error_github_text'                => '如果您喜欢，您也可以在 https://github.com/firefrechy-iii/firefrechy-iii/issues上打开一个新问题。',
    'error_stacktrace_below'           => 'The full stacktrace is below:',

    // report new journals
    'new_journals_subject'             => 'Firefly III has created a new transaction|Firefly III has created :count new transactions',
    'new_journals_header'              => 'Firefly III has created a transaction for you. You can find it in your Firefly III installation:|Firefly III has created :count transactions for you. You can find them in your Firefly III installation:',
];
