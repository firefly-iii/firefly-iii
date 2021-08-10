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
    'greeting'                         => 'ようこそ',
    'closing'                          => 'ピーピー',
    'signature'                        => 'Firely-iiiのメールロボット',
    'footer_ps'                        => ':ipAddressにリクエストされたので、このメール送信されました。',

    // admin test
    'admin_test_subject'               => 'Firefly-iiiのテストメッセージ',
    'admin_test_body'                  => 'Firefly-iiiのテストメッセージ。:emailに送信しました。',

    // new IP
    'login_from_new_ip'                => 'Firefly III に新しいログイン',
    'new_ip_body'                      => 'Firefly III が未知のIPアドレスからあなたのアカウントへの新しいログインを検出しました。 以下のIPアドレスからログインしたことがないか、ログインから6ヶ月以上経過している場合、Firefly IIIは警告します。',
    'new_ip_warning'                   => 'このIPアドレスまたはログインに覚えがある場合は、このメッセージを無視できます。 ログインしていないか、これが何であるかがわからない場合、 パスワードのセキュリティを確認、変更し、すべてのセッションをログアウトしてください。 これはあなたのプロフィールページから行えます。もちろん、あなたはすでに2FAが有効になっていますよね?',
    'ip_address'                       => 'IPアドレス',
    'host_name'                        => 'ホスト',
    'date_time'                        => '日付と時刻',

    // access token created
    'access_token_created_subject'     => '新しいアクセストークンが作成されました。',
    'access_token_created_body'        => 'あなたのユーザーアカウントの利用するために、新しいアクセストークンを作成した方がいます。',
    'access_token_created_explanation' => 'このトークンがあれば、Firefly III APIを使用してあなたの財務記録の <strong>すべて</strong> にアクセスできます。',
    'access_token_created_revoke'      => 'これがあなたではない場合は、できるだけ早く :url からこのトークンを無効にしてください。',

    // registered
    'registered_subject'               => 'Firefly-iiiへようこそ！',
    'registered_welcome'               => '<a style="color:#337ab7" href=":address">Firefly III</a>へようこそ。登録がこのメールで確認できました。やった！',
    'registered_pw'                    => 'パスワードを忘れた場合は、 <a style="color:#337ab7" href=":address/password/reset">パスワードリセットツール</a> を使用してリセットしてください。',
    'registered_help'                  => '各ページの右上にヘルプアイコンがあります。ヘルプが必要な場合は、クリックしてください。',
    'registered_doc_html'              => 'まだ読んでいない場合は、 <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">概論</a>をご覧ください。',
    'registered_doc_text'              => 'まだ読んでいない場合は、初回利用ガイドと詳細説明をご覧ください。',
    'registered_closing'               => 'では！',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'パスワードのリセット:',
    'registered_doc_link'              => 'ドキュメント:',

    // email change
    'email_change_subject'             => 'Firefly III のメールアドレスが変更されました',
    'email_change_body_to_new'         => 'あなた、もしくはあなたのFirefly IIIアカウントにアクセスできるユーザーが、メールアドレスを変更しました。 このメッセージに覚えがない場合は、無視して削除してください。',
    'email_change_body_to_old'         => 'あなた、もしくはあなたのFirefly IIIアカウントにアクセスできるユーザーが、メールアドレスを変更しました。 このメッセージに覚えがない場合は、 <strong>必ず</strong>以下の「元に戻す」リンクに従いアカウントを保護してください。',
    'email_change_ignore'              => 'あなたがこの変更を開始した場合は、このメッセージを無視してください。',
    'email_change_old'                 => '古いメールアドレス: :email',
    'email_change_old_strong'          => '古いメールアドレス: <strong>:email</strong>',
    'email_change_new'                 => '新しいメールアドレス: :email',
    'email_change_new_strong'          => '新しいメールアドレスは: <strong>:email</strong>',
    'email_change_instructions'        => 'この変更を確認するまで Firefly III を使用できません。以下のリンクに従ってください。',
    'email_change_undo_link'           => '変更を元に戻すには、次のリンクに従ってください:',

    // OAuth token created
    'oauth_created_subject'            => '新しいOAuthクライアントが作成されました',
    'oauth_created_body'               => '誰か(あなただと幸いです)があなたのユーザーアカウント用の新しいFirefly III API OAuth クライアントを作成しました。 ":name" というラベルが付けられており、コールバックURL <span style="font-family: monospace;">:url</span> があります。',
    'oauth_created_explanation'        => 'このクライアントは、Firefly III APIを介してあなたの <strong>すべて</strong> の財務記録のにアクセスできます。',
    'oauth_created_undo'               => 'これがあなたではない場合は、できるだけ早く:url からこのクライアントを無効にしてください。',

    // reset password
    'reset_pw_subject'                 => 'パスワードリセットのリクエスト',
    'reset_pw_instructions'            => '誰かがあなたのパスワードをリセットしようとしました。もしあなたであれば、以下のリンクに従ってください。',
    'reset_pw_warning'                 => '<strong>必ず</strong>実際にリンクが Firefly III に遷移することを確認してください！',

    // error
    'error_subject'                    => 'Firefly III でエラーが発生しました',
    'error_intro'                      => 'Firefly III v:version でエラーが発生しました: <span style="font-family: monospace;">:errorMessage</span>。',
    'error_type'                       => 'エラー種別は ":class" でした。',
    'error_timestamp'                  => 'エラーは :time に発生しました',
    'error_location'                   => 'このエラーは、ファイル "<span style="font-family: monospace;">:file</span>" :line 行目のコード :code で発生しました。',
    'error_user'                       => 'ユーザー #:id <a href="mailto::email">:email</a> がエラーに遭遇しました。',
    'error_no_user'                    => 'このエラーの際、ユーザーはログインしていないか、ユーザーは検出されませんでした。',
    'error_ip'                         => 'このエラーに関連する IP アドレス: :ip',
    'error_url'                        => 'URL: :url',
    'error_user_agent'                 => 'ユーザーエージェント: :userAgent',
    'error_stacktrace'                 => '完全なスタックトレースは以下の通りです。これがバグだと考えるなら、このメッセージを<a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>に届けることができます。これは先ほど遭遇したバグの修正に役立ちます。',
    'error_github_html'                => 'ご希望の場合は、<a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>で新しいissueを作ることもできます。',
    'error_github_text'                => 'ご希望の場合は、https://github.com/fofoflifly-iii/firelify-ii/issuesで新しいissueを作ることもできます。',
    'error_stacktrace_below'           => '完全なスタックトレースは以下の通りです:',

    // report new journals
    'new_journals_subject'             => 'Firefly III が取引を作成しました|Firefly III が:count件の取引を作成しました',
    'new_journals_header'              => 'Firefly III が取引を作成しました。Firefly III で参照できます:|Firefly III が:count件の取引を作成しました。 Firefly III でそれらを参照できます。',
];
