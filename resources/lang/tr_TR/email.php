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
    'greeting'                         => 'Selam,',
    'closing'                          => 'Bip bop,',
    'signature'                        => 'Firefly III Posta Robotu',
    'footer_ps'                        => 'Not: Bu ileti, IP:ıpaddress\'den gelen bir istek tetiklediği için gönderildi.',

    // admin test
    'admin_test_subject'               => 'Firefly III kurulumunuzdan bir test mesajı',
    'admin_test_body'                  => 'Bu, Firefly III örneğinizden gelen bir test mesajıdır. Şu adrese gönderildi: e-posta.',

    // new IP
    'login_from_new_ip'                => 'Firefly III yeni giriş',
    'new_ip_body'                      => 'Firefly III, hesabınızda bilinmeyen bir IP adresinden yeni bir giriş tespit etti. Aşağıdaki IP adresinden hiç giriş yapmadıysanız veya altı aydan daha uzun bir süre önce yapıldıysa, Firefly III sizi uyaracaktır.',
    'new_ip_warning'                   => 'Bu IP adresini veya oturum açmayı tanıyorsanız, bu iletiyi yoksayabilirsiniz. Eğer giriş eğer konuyla ilgili hiçbir fikriniz varsa, şifre güvenliğinizi doğrulamak, ve çıkış tüm oturumlar bu değişiklik olmadıysa. Bunu yapmak için profil sayfanıza gidin. Tabii ki zaten 2FA etkin, değil mi? Güvende kalın!',
    'ip_address'                       => 'IP adresi',
    'host_name'                        => 'Host',
    'date_time'                        => 'Tarih + saat',

    // access token created
    'access_token_created_subject'     => 'Yeni bir erişim belirteci oluşturuldu',
    'access_token_created_body'        => 'Birisi (umarız sensindir) hesabın için yeni bir Firefly III API Erişim Anahtarı oluşturdu.',
    'access_token_created_explanation' => 'Bu belirteçle, mali kayıtlarınıza s <strong>all</strong> Firefly III API aracılığıyla erişebilirler.',
    'access_token_created_revoke'      => 'Bu siz olmadıysanız, lütfen bu belirteci mümkün olan en kısa sürede şu adresten iptal edin :url.',

    // registered
    'registered_subject'               => 'Firefly III\'e hoşgeldiniz!',
    'registered_welcome'               => 'Hoş geldiniz <a style="color:#337ab7" href=":address">Firefly III</a>. Kaydınız yapıldı ve bu e-posta onaylamak için burada. Yay!',
    'registered_pw'                    => 'Parolanızı zaten unuttuysanız, lütfen parolanızı kullanarak sıfırlayın <a style="color:#337ab7" href=":address/password/reset">the password reset tool</a>.',
    'registered_help'                  => 'Her sayfanın sağ üst köşesinde bir yardım simgesi bulunur. Yardıma ihtiyacınız olursa, tıklayın!',
    'registered_doc_html'              => 'Henüz yapmadıysanız, lütfen okuyun <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">grand theory</a>.',
    'registered_doc_text'              => 'Henüz yapmadıysanız, lütfen ilk kullanım kılavuzunu ve açıklamanın tamamını okuyun.',
    'registered_closing'               => 'Tadını çıkarın!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Şifre sıfırlama:',
    'registered_doc_link'              => 'Belge:',

    // email change
    'email_change_subject'             => 'Firefly III e-posta adresiniz değişti',
    'email_change_body_to_new'         => 'Siz veya Firefly III hesabınıza erişimi olan biri e-posta adresinizi değiştirdi. Bu iletiyi beklemediyseniz, lütfen yoksayın ve silin.',
    'email_change_body_to_old'         => 'Siz veya Firefly III hesabınıza erişimi olan biri e-posta adresinizi değiştirdi. Bunun olmasını beklemediysen, sen <strong>must</strong> hesabınızı korumak için aşağıdaki "geri al" bağlantısını takip edin!',
    'email_change_ignore'              => 'Bu değişikliği başlattıysanız, bu iletiyi güvenle yoksayabilirsiniz.',
    'email_change_old'                 => 'Önceki e-posta adresi: :email',
    'email_change_old_strong'          => 'Önceki e-posta adresi: <strong>:email</strong>',
    'email_change_new'                 => 'Yeni e-posta adresi: :email',
    'email_change_new_strong'          => 'Yeni e-posta adresi: <strong>:email</strong>',
    'email_change_instructions'        => 'Bu değişikliği onaylayana kadar Firefly Iıı\'ü kullanamazsınız. Lütfen bunu yapmak için aşağıdaki bağlantıyı takip edin.',
    'email_change_undo_link'           => 'Değişikliği geri almak için bu bağlantıyı takip edin:',

    // OAuth token created
    'oauth_created_subject'            => 'Yeni bir OAuth istemcisi oluşturuldu',
    'oauth_created_body'               => 'Birisi (umarız sensindir) senin hesabın için yeni bir Firefly III API OAuth İstemcisi oluşturdu. Adı ":name" ve yönlendirme linki <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Bu istemciyle, Firefly III API aracılığıyla mali kayıtlarınızın <strong>tümüne</strong> erişebilirler.',
    'oauth_created_undo'               => 'If this wasn\'t you, please revoke this client as soon as possible at:url.',

    // reset password
    'reset_pw_subject'                 => 'Parola sıfırlama isteğin',
    'reset_pw_instructions'            => 'Birisi şifrenizi sıfırlamaya çalıştı. Siz olsaydınız, bunu yapmak için lütfen aşağıdaki bağlantıyı takip edin.',
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
    'error_url'                        => 'Link: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'The full stacktrace is below. If you think this is a bug in Firefly III, you can forward this message to <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. This can help fix the bug you just encountered.',
    'error_github_html'                => 'If you prefer, you can also open a new issue on <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'If you prefer, you can also open a new issue on https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'The full stacktrace is below:',
    'error_headers'                    => 'The following headers may also be relevant:',

    // report new journals
    'new_journals_subject'             => 'Firefly III has created a new transaction|Firefly III has created :count new transactions',
    'new_journals_header'              => 'Firefly III has created a transaction for you. You can find it in your Firefly III installation:|Firefly III has created :count transactions for you. You can find them in your Firefly III installation:',
];
