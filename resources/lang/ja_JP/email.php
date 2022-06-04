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
    'admin_test_subject'                      => 'あなたの Firefly III からのテストメッセージ',
    'admin_test_body'                         => 'これはあなたの Firefly III からのテストメッセージです。:email 宛に送信しました。',

    // new IP
    'login_from_new_ip'                       => 'Firefly III に新しいログイン',
    'new_ip_body'                             => 'Firefly III が未知のIPアドレスからあなたのアカウントへの新しいログインを検出しました。 以下のIPアドレスからログインしたことがないか、ログインから6ヶ月以上経過している場合、Firefly IIIは警告します。',
    'new_ip_warning'                          => 'この IP アドレスまたはログインに覚えがある場合は、このメッセージを無視してください。 ログインしていないか、これが何であるかがわからない場合、 パスワードの安全性を確認、変更し、すべてのセッションをログアウトしてください。 これはプロフィールページからできます。もちろん、すでに2要素認証は有効にしていますよね？ご安全に！',
    'ip_address'                              => 'IPアドレス',
    'host_name'                               => 'ホスト',
    'date_time'                               => '日付と時刻',

    // access token created
    'access_token_created_subject'            => '新しいアクセストークンが作成されました。',
    'access_token_created_body'               => 'あなたのユーザーアカウントの利用するために、新しいアクセストークンを作成した方がいます。',
    'access_token_created_explanation'        => 'このトークンを使用すると、Firefly III API を通してあなたの財務記録の **すべて** にアクセスできます。',
    'access_token_created_revoke'             => 'これがあなたではない場合、 :url にて即刻このトークンを無効化してください',

    // registered
    'registered_subject'                      => 'Firefly III へようこそ！',
    'registered_welcome'                      => '[Firefly III](:address) へようこそ。このメールにて登録が完了したことをお知らせします。やった！',
    'registered_pw'                           => 'パスワードを忘れた場合は、[パスワードリセットツール](:address/password/reset)を使用してリセットしてください。',
    'registered_help'                         => '各ページの右上にヘルプアイコンがあります。ヘルプが必要な場合は、クリックしてください。',
    'registered_doc_html'                     => 'まだ読んでいない場合、[基本的な考え](https://docs.firefly-iii.org/about-firefly-iii/personal-finances)を読んでください。',
    'registered_doc_text'                     => 'まだ読んでいない場合は、はじめての使用ガイドと詳細説明も読んでください。',
    'registered_closing'                      => 'では！',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'パスワードのリセット:',
    'registered_doc_link'                     => 'ドキュメント:',

    // email change
    'email_change_subject'                    => 'Firefly III のメールアドレスが変更されました',
    'email_change_body_to_new'                => 'あなた、もしくはあなたのFirefly IIIアカウントにアクセスできるユーザーが、メールアドレスを変更しました。 このメッセージに覚えがない場合は、無視して削除してください。',
    'email_change_body_to_old'                => 'あなた、もしくはあなたの Firefly III アカウントにアクセスできるユーザーが、メールアドレスを変更しました。 このメッセージに覚えがない場合は、 **必ず**以下の「元に戻す」リンクに従いアカウントを保護してください！',
    'email_change_ignore'                     => 'あなたがこの変更を開始した場合は、このメッセージを無視してください。',
    'email_change_old'                        => '古いメールアドレス: :email',
    'email_change_old_strong'                 => '古いメールアドレス: **:email**',
    'email_change_new'                        => '新しいメールアドレス: :email',
    'email_change_new_strong'                 => '新しいメールアドレス: **:email**',
    'email_change_instructions'               => 'この変更を確認するまで Firefly III を使用できません。以下のリンクに従ってください。',
    'email_change_undo_link'                  => '変更を元に戻すには、次のリンクに従ってください:',

    // OAuth token created
    'oauth_created_subject'                   => '新しいOAuthクライアントが作成されました',
    'oauth_created_body'                      => '誰か（おそらくあなた）があなたのユーザーアカウント用の新しい Firefly III API OAuth クライアントを作成しました。「:name」というラベルが付けられており、コールバック URL は「:url」です。',
    'oauth_created_explanation'               => 'このトークンがあれば、Firefly III API を通してあなたの財務記録の **すべて** にアクセスできます。',
    'oauth_created_undo'                      => 'これがあなたではない場合、:url にて即刻このトークンを無効化してください',

    // reset password
    'reset_pw_subject'                        => 'パスワードリセットのリクエスト',
    'reset_pw_instructions'                   => '誰かがあなたのパスワードをリセットしようとしました。もしあなたであれば、以下のリンクに従ってください。',
    'reset_pw_warning'                        => '**必ず** リンクが実際に Firefly III にリンクされていることを確認してください！',

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
    'error_headers'                           => '「headers」は技術用語「HTTP headers」を参照します',

    // report new journals
    'new_journals_subject'                    => 'Firefly III が取引を作成しました|Firefly III が:count件の取引を作成しました',
    'new_journals_header'                     => 'Firefly III が取引を作成しました。Firefly III で参照できます:|Firefly III が:count件の取引を作成しました。 Firefly III でそれらを参照できます。',

    // bill warning
    'bill_warning_subject_end_date'           => 'あなたの請求「:name」は :diff 日後に終了する予定です',
    'bill_warning_subject_now_end_date'       => 'あなたの請求「:name」は本日終了する予定です',
    'bill_warning_subject_extension_date'     => 'あなたの請求「:name」は :diff 日後に延長またはキャンセルされます',
    'bill_warning_subject_now_extension_date' => 'あなたの請求書「:name」は、本日延長またはキャンセルされます',
    'bill_warning_end_date'                   => 'あなたの請求「**":name"**」は :date に終了します。その時まで約 **:diff 日間**です。',
    'bill_warning_extension_date'             => 'あなたの請求「**":name"**」が :date に延長またはキャンセルされます。その時まで約**:diff 日間**です。',
    'bill_warning_end_date_zero'              => 'あなたの請求「**":name"**」は :date に終了します。 本日がその時です。',
    'bill_warning_extension_date_zero'        => 'あなたの請求「**":name"**」が :date に延長またはキャンセルされます。 本日がその時です。',
    'bill_warning_please_action'              => '適切に対処してください。',

];
