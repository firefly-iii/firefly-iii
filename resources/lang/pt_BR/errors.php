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
    '404_header'              => 'Firefly III não conseguiu encontrar esta página.',
    '404_page_does_not_exist' => 'A página que você solicitou não existe. Por favor, verifique se você não digitou o endereço errado. Talvez você tenha cometido um erro de digitação?',
    '404_send_error'          => 'Se você foi redirecionado para esta página, por favor aceite minhas desculpas. Há uma referência para este erro nos seus arquivos de registo e ficarei agradecido se você me enviar o erro.',
    '404_github_link'         => 'Se você tem certeza que esta página deveria existir, abra um ticket no <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Ops',
    'fatal_error'             => 'Houve um erro fatal. Por favor, verifique os arquivos de log em "storage/logs" ou use "docker logs -f [container]" para ver o que está acontecendo.',
    'maintenance_mode'        => 'Firefly III está em modo de manutenção.',
    'be_right_back'           => 'Volto já!',
    'check_back'              => 'Firefly III está fora do ar devido a manutenção necessária. Acesse novamente em alguns instantes.',
    'error_occurred'          => 'Ops! Aconteceu um erro.',
    'error_not_recoverable'   => 'Infelizmente este erro não é recuperável :(. Firefly III quebrou. O erro é:',
    'error'                   => 'Erro',
    'error_location'          => 'Esse erro ocorreu no arquivo "<span style="font-family: monospace;">:file</span>", na linha :line com o código :code.',
    'stacktrace'              => 'Stack trace',
    'more_info'               => 'Mais informações',
    'collect_info'            => 'Por favor recupere mais informações no diretório <code>storage/logs</code> onde você encontrará os arquivos de log. Se você estiver executando o Docker, use <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Você pode ler mais sobre a coleta de informações de erro em <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">Perguntas Frequentes</a>.',
    'github_help'             => 'Obtenha ajuda no GitHub',
    'github_instructions'     => 'Você é mais do que bem-vindo para abrir uma nova issue <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">no GitHub</a>.</strong>.',
    'use_search'              => 'Use a busca!',
    'include_info'            => 'Incluir a informação <a href=":link">desta página de debug</a>.',
    'tell_more'               => 'Nos diga mais do que "ele retorna Ops!"',
    'include_logs'            => 'Inclua os logs de erro (veja acima).',
    'what_did_you_do'         => 'Nos diga o que você estava fazendo.',

];
