<?php

/**
 * firefly.php
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
    '404_header'              => 'Firefly III não encontrou esta página.',
    '404_page_does_not_exist' => 'A página solicitada não existe. Por favor, verifique se não inseriu a URL errada. Pode se ter enganado?',
    '404_send_error'          => 'Se você foi redirecionado para esta página automaticamente, por favor aceite as minhas desculpas. Há uma referência a este erro nos seus ficheiros de registo e ficaria muito agradecido se me pudesse enviar.',
    '404_github_link'         => 'Se você tem certeza de que esta página existe, abra um ticket no <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Oops',
    'fatal_error'             => 'Aconteceu um erro fatal. Por favor verifique os ficheiros de log em "storage/logs" ou use "docker logs -f [container]" para verificar o que se passa.',
    'maintenance_mode'        => 'O Firefly III está em modo de manutenção.',
    'be_right_back'           => 'Volto já!',
    'check_back'              => 'Firefly III está desligado para manutenção. Volte já a seguir.',
    'error_occurred'          => 'Oops! Ocorreu um erro.',
    'error_not_recoverable'   => 'Infelizmente, este erro não era recuperável :(. Firefly III avariou. O erro é:',
    'error'                   => 'Erro',
    'error_location'          => 'O erro ocorreu no ficheiro "<span style="font-family: monospace;">:file</span>" na linha :line com o código :code.',
    'stacktrace'              => 'Rasteamento da pilha',
    'more_info'               => 'Mais informação',
    'collect_info'            => 'Por favor recolha mais informação na diretoria <code>storage/logs</code> que é onde encontra os ficheiros de log. Se estiver a utilizar Docker, utilize <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Pode ler mais sobre a recolha de informação de erros em <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">nas FAQ</a>.',
    'github_help'             => 'Obter ajuda no GitHub',
    'github_instructions'     => 'É mais que bem vindo a abrir uma nova issue <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">no GitHub</a></strong>.',
    'use_search'              => 'Use a pesquisa!',
    'include_info'            => 'Inclua a informação <a href=":link">da página de depuração</a>.',
    'tell_more'               => 'Diga-nos mais que "diz Whoops! no ecrã"',
    'include_logs'            => 'Incluir relatório de erros (ver acima).',
    'what_did_you_do'         => 'Diga-nos o que estava a fazer.',

];
