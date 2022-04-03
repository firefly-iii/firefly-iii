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
    'greeting'                                => 'Selam',
    'closing'                                 => 'Bip bop',
    'signature'                               => 'Firefly III Posta Robotu',
    'footer_ps'                               => 'Not: Bu ileti, IP:ıpaddress\'den gelen bir istek tetiklediği için gönderildi.',

    // admin test
    'admin_test_subject'                      => 'Firefly III kurulumunuzdan bir test mesajı',
    'admin_test_body'                         => 'Bu, Firefly III örneğinizden gelen bir test mesajıdır. Şu adrese gönderildi: e-posta.',

    // new IP
    'login_from_new_ip'                       => 'Firefly III yeni giriş',
    'new_ip_body'                             => 'Firefly III, hesabınızda bilinmeyen bir IP adresinden yeni bir giriş tespit etti. Aşağıdaki IP adresinden hiç giriş yapmadıysanız veya altı aydan daha uzun bir süre önce yapıldıysa, Firefly III sizi uyaracaktır.',
    'new_ip_warning'                          => 'Bu IP adresini veya oturum açmayı tanıyorsanız, bu iletiyi yoksayabilirsiniz. Eğer giriş eğer konuyla ilgili hiçbir fikriniz varsa, şifre güvenliğinizi doğrulamak, ve çıkış tüm oturumlar bu değişiklik olmadıysa. Bunu yapmak için profil sayfanıza gidin. Tabii ki zaten 2FA etkin, değil mi? Güvende kalın!',
    'ip_address'                              => 'IP adresi',
    'host_name'                               => 'Host',
    'date_time'                               => 'Tarih + saat',

    // access token created
    'access_token_created_subject'            => 'Yeni bir erişim belirteci oluşturuldu',
    'access_token_created_body'               => 'Birisi (umarız sensindir) hesabın için yeni bir Firefly III API Erişim Anahtarı oluşturdu.',
    'access_token_created_explanation'        => 'With this token, they can access **all** of your financial records through the Firefly III API.',
    'access_token_created_revoke'             => 'If this wasn\'t you, please revoke this token as soon as possible at :url',

    // registered
    'registered_subject'                      => 'Firefly III\'e hoşgeldiniz!',
    'registered_welcome'                      => 'Welcome to [Firefly III](:address). Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => 'Her sayfanın sağ üst köşesinde bir yardım simgesi bulunur. Yardıma ihtiyacınız olursa, tıklayın!',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => 'Tadını çıkarın!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Şifre sıfırlama:',
    'registered_doc_link'                     => 'Belge:',

    // email change
    'email_change_subject'                    => 'Firefly III e-posta adresiniz değişti',
    'email_change_body_to_new'                => 'Siz veya Firefly III hesabınıza erişimi olan biri e-posta adresinizi değiştirdi. Bu iletiyi beklemediyseniz, lütfen yoksayın ve silin.',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => 'Bu değişikliği başlattıysanız, bu iletiyi güvenle yoksayabilirsiniz.',
    'email_change_old'                        => 'Önceki e-posta adresi: :email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => 'Yeni e-posta adresi: :email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => 'Bu değişikliği onaylayana kadar Firefly Iıı\'ü kullanamazsınız. Lütfen bunu yapmak için aşağıdaki bağlantıyı takip edin.',
    'email_change_undo_link'                  => 'Değişikliği geri almak için bu bağlantıyı takip edin:',

    // OAuth token created
    'oauth_created_subject'                   => 'Yeni bir OAuth istemcisi oluşturuldu',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => 'Parola sıfırlama isteğin',
    'reset_pw_instructions'                   => 'Birisi şifrenizi sıfırlamaya çalıştı. Siz olsaydınız, bunu yapmak için lütfen aşağıdaki bağlantıyı takip edin.',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Firefly III\'te bir hata yakalandı',
    'error_intro'                             => 'Firefly III v: sürüm bir hatayla karşılaştı: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'Hata ":class türündeydi.',
    'error_timestamp'                         => 'Hata açık / kapalı olarak oluştu: :time.',
    'error_location'                          => 'Bu hata dosyada oluştu "<span style="font-family: monospace;">:file</span>" on line :line with code :code.',
    'error_user'                              => 'Hatayla kullanıcı karşılaştı #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'Bu hata için kullanıcı oturum açmadı veya kullanıcı algılanmadı.',
    'error_ip'                                => 'Bu hatayla ilgili IP adresi: :ip',
    'error_url'                               => 'Link: :url',
    'error_user_agent'                        => 'User agent: :userAgent',
    'error_stacktrace'                        => 'Tam stacktrace aşağıdadır. Bunun Firefly III\'TE bir hata olduğunu düşünüyorsanız, bu iletiyi şu adrese iletebilirsiniz: <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. Bu, az önce karşılaştığınız hatayı düzeltmenize yardımcı olabilir.',
    'error_github_html'                       => 'İsterseniz, yeni bir sayı da açabilirsiniz <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                       => 'İsterseniz, yeni bir sayı da açabilirsiniz https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'                  => 'Tam stacktrace aşağıdadır:',
    'error_headers'                           => 'Aşağıdaki başlıklar da alakalı olabilir:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III yeni bir işlem yarattı / Firefly III yarattı :count yeni işlemler',
    'new_journals_header'                     => 'Firefly III sizin için bir anlaşma yaptı. Firefly III kurulumunuzda bulabilirsiniz: / Firefly III sizin için :count sayım işlemleri. Bunları Firefly III kurulumunuzda bulabilirsiniz:',

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
