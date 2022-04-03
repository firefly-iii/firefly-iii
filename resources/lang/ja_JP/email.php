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
    'greeting'                                => 'ようこそ',
    'closing'                                 => 'ピーピー',
    'signature'                               => 'Firely-iiiのメールロボット',
    'footer_ps'                               => ':ipAddressにリクエストされたので、このメール送信されました。',

    // admin test
    'admin_test_subject'                      => 'Firefly-iiiのテストメッセージ',
    'admin_test_body'                         => 'Firefly-iiiのテストメッセージ。:emailに送信しました。',

    // new IP
    'login_from_new_ip'                       => 'Firefly III に新しいログイン',
    'new_ip_body'                             => 'Firefly III が未知のIPアドレスからあなたのアカウントへの新しいログインを検出しました。 以下のIPアドレスからログインしたことがないか、ログインから6ヶ月以上経過している場合、Firefly IIIは警告します。',
    'new_ip_warning'                          => 'このIPアドレスまたはログインに覚えがある場合は、このメッセージを無視できます。 ログインしていないか、これが何であるかがわからない場合、 パスワードのセキュリティを確認、変更し、すべてのセッションをログアウトしてください。 これはあなたのプロフィールページから行えます。もちろん、あなたはすでに2FAが有効になっていますよね?',
    'ip_address'                              => 'IPアドレス',
    'host_name'                               => 'ホスト',
    'date_time'                               => '日付と時刻',

    // access token created
    'access_token_created_subject'            => '新しいアクセストークンが作成されました。',
    'access_token_created_body'               => 'あなたのユーザーアカウントの利用するために、新しいアクセストークンを作成した方がいます。',
    'access_token_created_explanation'        => 'With this token, they can access **all** of your financial records through the Firefly III API.',
    'access_token_created_revoke'             => 'If this wasn\'t you, please revoke this token as soon as possible at :url',

    // registered
    'registered_subject'                      => 'Firefly III へようこそ！',
    'registered_welcome'                      => 'Welcome to [Firefly III](:address). Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => '各ページの右上にヘルプアイコンがあります。ヘルプが必要な場合は、クリックしてください。',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => 'では！',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'パスワードのリセット:',
    'registered_doc_link'                     => 'ドキュメント:',

    // email change
    'email_change_subject'                    => 'Firefly III のメールアドレスが変更されました',
    'email_change_body_to_new'                => 'あなた、もしくはあなたのFirefly IIIアカウントにアクセスできるユーザーが、メールアドレスを変更しました。 このメッセージに覚えがない場合は、無視して削除してください。',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => 'あなたがこの変更を開始した場合は、このメッセージを無視してください。',
    'email_change_old'                        => '古いメールアドレス: :email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => '新しいメールアドレス: :email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => 'この変更を確認するまで Firefly III を使用できません。以下のリンクに従ってください。',
    'email_change_undo_link'                  => '変更を元に戻すには、次のリンクに従ってください:',

    // OAuth token created
    'oauth_created_subject'                   => '新しいOAuthクライアントが作成されました',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => 'パスワードリセットのリクエスト',
    'reset_pw_instructions'                   => '誰かがあなたのパスワードをリセットしようとしました。もしあなたであれば、以下のリンクに従ってください。',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Firefly III でエラーが発生しました',
    'error_intro'                             => 'Firefly III v:version でエラーが発生しました: <span style="font-family: monospace;">:errorMessage</span>。',
    'error_type'                              => 'エラー種別は ":class" でした。',
    'error_timestamp'                         => 'エラーは :time に発生しました',
    'error_location'                          => 'このエラーは、ファイル "<span style="font-family: monospace;">:file</span>" :line 行目のコード :code で発生しました。',
    'error_user'                              => 'ユーザー #:id <a href="mailto::email">:email</a> がエラーに遭遇しました。',
    'error_no_user'                           => 'このエラーの際、ユーザーはログインしていないか、ユーザーは検出されませんでした。',
    'error_ip'                                => 'このエラーに関連する IP アドレス: :ip',
    'error_url'                               => 'URL: :url',
    'error_user_agent'                        => 'ユーザーエージェント: :userAgent',
    'error_stacktrace'                        => '完全なスタックトレースは以下の通りです。これがバグだと考えるなら、このメッセージを<a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>に届けることができます。これは先ほど遭遇したバグの修正に役立ちます。',
    'error_github_html'                       => 'ご希望の場合は、<a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>で新しいissueを作ることもできます。',
    'error_github_text'                       => 'ご希望の場合は、https://github.com/fofoflifly-iii/firelify-ii/issuesで新しいissueを作ることもできます。',
    'error_stacktrace_below'                  => '完全なスタックトレースは以下の通りです:',
    'error_headers'                           => 'The following headers may also be relevant:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III が取引を作成しました|Firefly III が:count件の取引を作成しました',
    'new_journals_header'                     => 'Firefly III が取引を作成しました。Firefly III で参照できます:|Firefly III が:count件の取引を作成しました。 Firefly III でそれらを参照できます。',

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
