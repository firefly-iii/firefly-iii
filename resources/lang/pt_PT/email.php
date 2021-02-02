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
    'closing'                          => 'Beep boop,',
    'signature'                        => 'O robô de email Firefly III',
    'footer_ps'                        => 'PS: Esta mensagem foi enviada porque um pedido do IP :ipAddress a activou.',

    // admin test
    'admin_test_subject'               => 'Uma mensagem de teste da instalação do Firefly III',
    'admin_test_body'                  => 'Esta é uma mensagem de teste da sua plataforma Firefly III. Foi enviada para :email.',

    // new IP
    'login_from_new_ip'                => 'Nova sessão no Firefly III',
    'new_ip_body'                      => 'O Firefly III detectou uma nova sessão na sua conta de um endereço IP desconhecido. Se nunca iniciou sessão a partir endereço IP abaixo, ou foi há mais de seis meses, o Firefly III irá avisá-lo.',
    'new_ip_warning'                   => 'Se reconhecer este endereço IP ou sessão, pode ignorar esta mensagem. Se não iniciou sessão ou não tenha ideia do que possa ser este inicio de sessão, verifique a segurança da sua senha, altere-a e desconecte-se de todas as outras sessões iniciadas. Para fazer isso, vá á sua página de perfil. Claro que você já activou 2FA, não é? Mantenha-se seguro!',
    'ip_address'                       => 'Endereço IP',
    'host_name'                        => 'Servidor',
    'date_time'                        => 'Data + hora',

    // access token created
    'access_token_created_subject'     => 'Foi criado um novo token de acesso',
    'access_token_created_body'        => 'Alguém (em principio você) acabou de criar um novo Token de Acesso da API Firefly III para sua conta de utilizador.',
    'access_token_created_explanation' => 'Com este token, eles podem aceder <strong>todos</strong> os registos financeiros através da API do Firefly III.',
    'access_token_created_revoke'      => 'Se não foi você, por favor, revogue este token o mais rápido possível em :url.',

    // registered
    'registered_subject'               => 'Bem vindo ao Firefly III!',
    'registered_welcome'               => 'Bem-vindo ao <a style="color:#337ab7" href=":address">Firefly II</a>. O seu registo chegou, e este e-mail está aqui para confirmar. Yay!',
    'registered_pw'                    => 'Se esquecer da senha, altere-a com <a style="color:#337ab7" href=":address/password/reset">a ferramenta de mudança de senha</a>.',
    'registered_help'                  => 'Existe um ícone de ajuda no canto superior direito de cada página. Se precisar de ajuda, clique-lhe!',
    'registered_doc_html'              => 'Se ainda não fez, por favor leia a <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">Grã Teoria (grand theory, a teoria por detrás do Firefly III)</a>.',
    'registered_doc_text'              => 'Se ainda não o fez, por favor leia o primeiro guia de uso e a descrição completa.',
    'registered_closing'               => 'Aproveite!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Alteração da senha:',
    'registered_doc_link'              => 'Documentação:',

    // email change
    'email_change_subject'             => 'O seu endereço de e-mail do Firefly III mudou',
    'email_change_body_to_new'         => 'Ou você ou alguém com acesso à sua conta do Firefly III alterou o endereço de email associado. Se não estava a espera deste aviso, ignore o mesmo e apague-o.',
    'email_change_body_to_old'         => 'Ou você ou alguém com acesso à sua conta do Firefly III alterou o endereço de email associado. Se não pretendia que isto acontecesse, deve <strong>forçadamente</strong> seguir o link-"para desfazer a ação" abaixo e assim proteger a sua conta!',
    'email_change_ignore'              => 'Se iniciou esta mudança, pode ignorar esta mensagem sem medo.',
    'email_change_old'                 => 'O endereço de email antigo era: :email',
    'email_change_old_strong'          => 'O endereço de email antigo era: <strong>:email</strong>',
    'email_change_new'                 => 'O novo endereço de email é: :email',
    'email_change_new_strong'          => 'O novo endereço de email é: <strong>:email</strong>',
    'email_change_instructions'        => 'Não pode utilizar o Firefly III até confirmar esta alteração. Por favor carregue no link abaixo para confirmar a mesma.',
    'email_change_undo_link'           => 'Para desfazer a mudança, carregue neste link:',

    // OAuth token created
    'oauth_created_subject'            => 'Um novo cliente OAuth foi criado',
    'oauth_created_body'               => 'Alguém (esperemos que tenha sido você) acabou agora de criar um cliente OAuth da API do Firefly III para a sua conta. Está rotulada com ":name" e tem o URL de callback <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Com este cliente, podem aceder a <strong>todos</strong> os seus registos financeiros pela API do Firefly III.',
    'oauth_created_undo'               => 'Se não foi você a criar, por favor invalide o cliente o mais rápido possível em :url.',

    // reset password
    'reset_pw_subject'                 => 'O pedido de mudança de senha',
    'reset_pw_instructions'            => 'Alguém acabou de tentar redefinir a sua palavra passe. Se foi você carregue no link abaixo para acabar o processo.',
    'reset_pw_warning'                 => '<strong>POR FAVOR</strong> verifique que o link vai para a instância do Firefly III que você espera que vá!',

    // error
    'error_subject'                    => 'Ocorreu um erro no Firefly III',
    'error_intro'                      => 'Firefly III v:version encontrou um erro: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'O erro foi do tipo ":class".',
    'error_timestamp'                  => 'Ocorreu um erro às: :time.',
    'error_location'                   => 'Este erro ocorreu no ficheiro "<span style="font-family: monospace;">:file</span>" na linha :line com o código :code.',
    'error_user'                       => 'O erro foi encontrado pelo utilizador #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Não havia nenhum utilizador conectado para este erro ou nenhum utilizador foi detectado.',
    'error_ip'                         => 'O endereço de IP associado a este erro é: :ip',
    'error_url'                        => 'O URL é: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'O rastreamento da pilha completo abaixo. Se acha que é um bug no Firefly III, pode reencaminhar este email para <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>.
Isto pode ajudar a compor a bug que acabou de encontrar.',
    'error_github_html'                => 'Se preferir, pode também abrir uma nova issue no <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Se preferir, pode também abrir uma nova issue em https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'O rastreamento da pilha completo é:',

    // report new journals
    'new_journals_subject'             => 'O Firefly III criou uma nova transação|O Firefly III criou :count novas transações',
    'new_journals_header'              => 'O Firefly III criou uma transação para si. Pode encontrar a mesma na sua instância do Firefly III.|O Firefly III criou :count transações para si. Pode encontrar as mesmas na sua instância do Firefly III:',
];
