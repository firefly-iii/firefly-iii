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
    'greeting'                         => 'Olá,',
    'closing'                          => 'Bip Bop,',
    'signature'                        => 'Firefly III Robô de Email',
    'footer_ps'                        => 'PS: Esta mensagem foi enviada porque uma solicitação do IP :ipAddress a ativou.',

    // admin test
    'admin_test_subject'               => 'Uma mensagem de teste de sua instalação do Firefly III',
    'admin_test_body'                  => 'Essa é uma mensagem de teste de sua instância do Firefly III. Foi enviada para :email.',

    // access token created
    'access_token_created_subject'     => 'Um novo token de acesso foi criado',
    'access_token_created_body'        => 'Alguém (esperamos que você) acabou de criar um novo token de acesso a API do Firefly III, para sua conta.',
    'access_token_created_explanation' => 'Com esse token, eles podem acessar <strong>todos</strong> os seus registros financeiros através da API do Firefly III.',
    'access_token_created_revoke'      => 'Se não foi você, favor revogue este token o mais rápido possível em :url.',

    // registered
    'registered_subject'               => 'Bem-vindo(a) ao Firefly III!',
    'registered_welcome'               => 'Bem-vindo ao <a style="color:#337ab7" href=":address">Firefly II</a>. Seu registro foi feito, e este e-mail está aqui para confirmar. Yeah!',
    'registered_pw'                    => 'Se você já esqueceu sua senha, redefina-a usando <a style="color:#337ab7" href=":address/password/reset">a ferramenta de redefinição de senha</a>.',
    'registered_help'                  => 'Há um ícone de ajuda no canto superior direito de cada página. Se você precisar de ajuda, clique nele!',
    'registered_doc_html'              => 'Se você ainda não o fez, por favor leia a <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">grande teoria</a>.',
    'registered_doc_text'              => 'Se você ainda não o fez, por favor leia o guia de primeiro uso e a descrição completa.',
    'registered_closing'               => 'Aproveite!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Redefinição de senha:',
    'registered_doc_link'              => 'Documentação:',

    // email change
    'email_change_subject'             => 'O seu endereço de email no Firefly III mudou',
    'email_change_body_to_new'         => 'Você ou alguém com acesso à sua conta Firefly III alterou seu endereço de e-mail. Se não esperava esta mensagem, por favor, ignore e apague-a.',
    'email_change_body_to_old'         => 'Você ou alguém com acesso à sua conta Firefly III alterou seu endereço de e-mail. Se você não esperava que isso acontecesse, você <strong>deve</strong> seguir o "desfazer" link abaixo para proteger a sua conta!',
    'email_change_ignore'              => 'If you initiated this change, you may safely ignore this message.',
    'email_change_old'                 => 'The old email address was: :email',
    'email_change_old_strong'          => 'The old email address was: <strong>:email</strong>',
    'email_change_new'                 => 'The new email address is: :email',
    'email_change_new_strong'          => 'The new email address is: <strong>:email</strong>',
    'email_change_instructions'        => 'You cannot use Firefly III until you confirm this change. Please follow the link below to do so.',
    'email_change_undo_link'           => 'To undo the change, follow this link:',

    // OAuth token created
    'oauth_created_subject'            => 'Um novo cliente OAuth foi criado',
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
    'error_location'                   => 'Esse erro ocorreu no arquivo "<span style="font-family: monospace;">:file</span>", na linha :line com o código :code.',
    'error_user'                       => 'The error was encountered by user #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'There was no user logged in for this error or no user was detected.',
    'error_ip'                         => 'The IP address related to this error is: :ip',
    'error_url'                        => 'URL is: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'The full stacktrace is below. If you think this is a bug in Firefly III, you can forward this message to <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. This can help fix the bug you just encountered.',
    'error_github_html'                => 'If you prefer, you can also open a new issue on <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'If you prefer, you can also open a new issue on https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'The full stacktrace is below:',

    // report new journals
    'new_journals_subject'             => 'Firefly III has created a new transaction|Firefly III has created :count new transactions',
    'new_journals_header'              => 'Firefly III has created a transaction for you. You can find it in your Firefly III installation:|Firefly III has created :count transactions for you. You can find them in your Firefly III installation:',
];
