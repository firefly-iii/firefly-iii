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
    'greeting'                                => 'Olá,',
    'closing'                                 => 'Bip Bop,',
    'signature'                               => 'Firefly III Robô de Email',
    'footer_ps'                               => 'PS: Esta mensagem foi enviada porque uma solicitação do IP :ipAddress a ativou.',

    // admin test
    'admin_test_subject'                      => 'Uma mensagem de teste de sua instalação do Firefly III',
    'admin_test_body'                         => 'Essa é uma mensagem de teste de sua instância do Firefly III. Foi enviada para :email.',

    // new IP
    'login_from_new_ip'                       => 'Novo login no Firefly III',
    'new_ip_body'                             => 'O Firefly III detectou um novo login em sua conta de um endereço IP desconhecido. Caso você nunca tenha logado do endereço IP abaixo, ou o fez há mais de seis meses, o Firefly III irá avisá-lo.',
    'new_ip_warning'                          => 'Caso você reconheça este endereço IP ou o login, você pode ignorar esta mensagem. Ou se você não fez login, se não tem ideia do que se trata, verifique a segurança da sua senha, altere-a e desconecte-se de todas as outras sessões. Para fazer isso, vá para sua página de perfil. Claro que você já habilitou 2FA, né? Mantenha-se seguro!',
    'ip_address'                              => 'Endereço IP',
    'host_name'                               => 'Servidor',
    'date_time'                               => 'Data + hora',

    // access token created
    'access_token_created_subject'            => 'Um novo token de acesso foi criado',
    'access_token_created_body'               => 'Alguém (esperamos que você) acabou de criar um novo token de acesso a API do Firefly III, para sua conta.',
    'access_token_created_explanation'        => 'With this token, they can access **all** of your financial records through the Firefly III API.',
    'access_token_created_revoke'             => 'If this wasn\'t you, please revoke this token as soon as possible at :url',

    // registered
    'registered_subject'                      => 'Bem-vindo(a) ao Firefly III!',
    'registered_welcome'                      => 'Welcome to [Firefly III](:address). Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => 'Há um ícone de ajuda no canto superior direito de cada página. Se você precisar de ajuda, clique nele!',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => 'Aproveite!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Redefinição de senha:',
    'registered_doc_link'                     => 'Documentação:',

    // email change
    'email_change_subject'                    => 'O seu endereço de email no Firefly III mudou',
    'email_change_body_to_new'                => 'Você ou alguém com acesso à sua conta Firefly III alterou seu endereço de e-mail. Se não esperava esta mensagem, por favor, ignore e apague-a.',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => 'Se você iniciou esta alteração, você pode ignorar esta mensagem.',
    'email_change_old'                        => 'O endereço de e-mail antigo era: :email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => 'O novo endereço de e-mail é: :email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => 'Você não pode usar o Firefly III até confirmar esta alteração. Siga o link abaixo para fazer isso.',
    'email_change_undo_link'                  => 'Para desfazer a alteração, abra este link:',

    // OAuth token created
    'oauth_created_subject'                   => 'Um novo cliente OAuth foi criado',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => 'Seu pedido de redefinição de senha',
    'reset_pw_instructions'                   => 'Alguém tentou redefinir sua senha. Se foi você, por favor, abra o link abaixo para fazê-lo.',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Ocorreu um erro no Firefly III',
    'error_intro'                             => 'Firefly III v:version encontrou um erro: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'O erro foi do tipo ":class".',
    'error_timestamp'                         => 'O erro aconteceu em/às: :time.',
    'error_location'                          => 'Esse erro ocorreu no arquivo "<span style="font-family: monospace;">:file</span>", na linha :line com o código :code.',
    'error_user'                              => 'O erro foi encontrado pelo usuário #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'Não houve nenhum usuário conectado para esse erro ou nenhum usuário foi detectado.',
    'error_ip'                                => 'O endereço de IP relacionado a este erro é: :ip',
    'error_url'                               => 'URL é: :url',
    'error_user_agent'                        => 'Agente de usuário: :userAgent',
    'error_stacktrace'                        => 'O caminho completo do erro está abaixo. Se você acha que isso é um bug no Firefly III, você pode encaminhar essa mensagem para <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. Isso pode ajudar a corrigir o erro que você acabou de encontrar.',
    'error_github_html'                       => 'Se você preferir, também pode abrir uma nova issue no <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                       => 'Se preferir, você também pode abrir uma nova issue em https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'                  => 'O rastreamento completo está abaixo:',
    'error_headers'                           => 'Os seguintes cabeçalhos também podem ser relevantes:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III criou uma nova transação.|Firefly III criou :count novas transações',
    'new_journals_header'                     => 'Firefly III criou uma transação para você. Você pode encontrá-la em sua instalação do Firefly III:|Firefly III criou :count transações para você. Você pode encontrá-los em sua instalação do Firefly II:',

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
