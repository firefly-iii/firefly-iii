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
    'closing'                                 => 'Beep boop,',
    'signature'                               => 'O robô de email Firefly III',
    'footer_ps'                               => 'PS: Esta mensagem foi enviada porque um pedido do IP :ipAddress a activou.',

    // admin test
    'admin_test_subject'                      => 'Uma mensagem de teste da instalação do Firefly III',
    'admin_test_body'                         => 'Esta é uma mensagem de teste da sua plataforma Firefly III. Foi enviada para :email.',

    // new IP
    'login_from_new_ip'                       => 'Nova sessão no Firefly III',
    'new_ip_body'                             => 'O Firefly III detectou uma nova sessão na sua conta de um endereço IP desconhecido. Se nunca iniciou sessão a partir endereço IP abaixo, ou foi há mais de seis meses, o Firefly III irá avisá-lo.',
    'new_ip_warning'                          => 'Se reconhecer este endereço IP ou sessão, pode ignorar esta mensagem. Se não iniciou sessão ou não tenha ideia do que possa ser este inicio de sessão, verifique a segurança da sua senha, altere-a e desconecte-se de todas as outras sessões iniciadas. Para fazer isso, vá á sua página de perfil. Claro que você já activou 2FA, não é? Mantenha-se seguro!',
    'ip_address'                              => 'Endereço IP',
    'host_name'                               => 'Servidor',
    'date_time'                               => 'Data + hora',

    // access token created
    'access_token_created_subject'            => 'Foi criado um novo token de acesso',
    'access_token_created_body'               => 'Alguém (em principio você) acabou de criar um novo Token de Acesso da API Firefly III para sua conta de utilizador.',
    'access_token_created_explanation'        => 'With this token, they can access **all** of your financial records through the Firefly III API.',
    'access_token_created_revoke'             => 'If this wasn\'t you, please revoke this token as soon as possible at :url',

    // registered
    'registered_subject'                      => 'Bem vindo ao Firefly III!',
    'registered_welcome'                      => 'Welcome to [Firefly III](:address). Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => 'Existe um ícone de ajuda no canto superior direito de cada página. Se precisar de ajuda, clique-lhe!',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => 'Aproveite!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Alteração da senha:',
    'registered_doc_link'                     => 'Documentação:',

    // email change
    'email_change_subject'                    => 'O seu endereço de e-mail do Firefly III mudou',
    'email_change_body_to_new'                => 'Ou você ou alguém com acesso à sua conta do Firefly III alterou o endereço de email associado. Se não estava a espera deste aviso, ignore o mesmo e apague-o.',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => 'Se iniciou esta mudança, pode ignorar esta mensagem sem medo.',
    'email_change_old'                        => 'O endereço de email antigo era: :email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => 'O novo endereço de email é: :email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => 'Não pode utilizar o Firefly III até confirmar esta alteração. Por favor carregue no link abaixo para confirmar a mesma.',
    'email_change_undo_link'                  => 'Para desfazer a mudança, carregue neste link:',

    // OAuth token created
    'oauth_created_subject'                   => 'Um novo cliente OAuth foi criado',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => 'O pedido de mudança de senha',
    'reset_pw_instructions'                   => 'Alguém acabou de tentar redefinir a sua palavra passe. Se foi você carregue no link abaixo para acabar o processo.',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Ocorreu um erro no Firefly III',
    'error_intro'                             => 'Firefly III v:version encontrou um erro: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'O erro foi do tipo ":class".',
    'error_timestamp'                         => 'Ocorreu um erro às: :time.',
    'error_location'                          => 'Este erro ocorreu no ficheiro "<span style="font-family: monospace;">:file</span>" na linha :line com o código :code.',
    'error_user'                              => 'O erro foi encontrado pelo utilizador #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'Não havia nenhum utilizador conectado para este erro ou nenhum utilizador foi detectado.',
    'error_ip'                                => 'O endereço de IP associado a este erro é: :ip',
    'error_url'                               => 'O URL é: :url',
    'error_user_agent'                        => 'User agent: :userAgent',
    'error_stacktrace'                        => 'O rastreamento da pilha completo abaixo. Se acha que é um bug no Firefly III, pode reencaminhar este email para <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>.
Isto pode ajudar a compor a bug que acabou de encontrar.',
    'error_github_html'                       => 'Se preferir, pode também abrir uma nova issue no <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                       => 'Se preferir, pode também abrir uma nova issue em https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'                  => 'O rastreamento da pilha completo é:',
    'error_headers'                           => 'The following headers may also be relevant:',

    // report new journals
    'new_journals_subject'                    => 'O Firefly III criou uma nova transação|O Firefly III criou :count novas transações',
    'new_journals_header'                     => 'O Firefly III criou uma transação para si. Pode encontrar a mesma na sua instância do Firefly III.|O Firefly III criou :count transações para si. Pode encontrar as mesmas na sua instância do Firefly III:',

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
