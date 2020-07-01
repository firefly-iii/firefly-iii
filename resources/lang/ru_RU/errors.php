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
    '404_header'              => 'Firefly III не может найти эту страницу.',
    '404_page_does_not_exist' => 'Запрошенная страница не существует. Пожалуйста, убедитесь, вы указали правильный URL. Возможно, вы допустили опечатку?',
    '404_send_error'          => 'Если вы были перенаправлены на эту страницу автоматически, пожалуйста, примите мои извинения. Информация об этой ошибке была записана в log-файл, и я буду признателен, если вы пришлёте эту информацию мне.',
    '404_github_link'         => 'Если вы уверены, что эта страница должна существовать, пожалуйста, откройте Заявку на <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Ууупс',
    'fatal_error'             => 'Произошла фатальная ошибка. Пожалуйста, проверьте файлы журнала в "storage/logs" или используйте "docker logs -f [container]", чтобы узнать, что происходит.',
    'maintenance_mode'        => 'Firefly III находится в режиме обслуживания.',
    'be_right_back'           => 'Временно недоступен!',
    'check_back'              => 'Firefly III отключён для необходимого обслуживания. Пожалуйста, зайдите через секунду.',
    'error_occurred'          => 'Упс! Произошла ошибка.',
    'error_not_recoverable'   => 'К сожалению, эта ошибка не была исправлена :(. Firefly III сломался. Ошибка:',
    'error'                   => 'Ошибка',
    'error_location'          => 'Эта ошибка произошла в файле <span style="font-family: monospace;">:file</span> в строке :line с кодом :code.',
    'stacktrace'              => 'Трассировка стека',
    'more_info'               => 'Подробности',
    'collect_info'            => 'Пожалуйста, соберите больше информации в каталоге <code>storage/logs</code>, где вы найдете файлы журнала. Если вы используете Docker, используйте <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Вы можете прочитать больше о сборе информации об ошибке в <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">FAQ</a>.',
    'github_help'             => 'Получить помощь на GitHub',
    'github_instructions'     => 'Я буду очень признателен, если вы откроете Заявку на <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'use_search'              => 'Используйте поиск!',
    'include_info'            => 'Включить информацию <a href=":link">с этой страницы отладки</a>.',
    'tell_more'               => 'Я хочу знать больше, чем просто "Упс!"',
    'include_logs'            => 'Прикрепить журналы ошибок (см. выше).',
    'what_did_you_do'         => 'Расскажите нам, что именно вы делали.',

];
