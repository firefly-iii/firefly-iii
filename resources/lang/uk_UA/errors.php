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
    '404_header'              => 'Firefly III не може знайти цю сторінку.',
    '404_page_does_not_exist' => 'Запитувана сторінка не існує. Будь ласка, перевірте, правильність URL. Можливо зробили помилку при наборі?',
    '404_send_error'          => 'Якщо ви автоматично перенаправлені на цю сторінку, будь ласка, прийміть мої вибачення. У вашому лог файлі існує згадка про цю помилку і я б був дуже вдячний, якщо б ви відправили мені її.',
    '404_github_link'         => 'Якщо ви впевнені, що ця сторінка має існувати, відкрийте квиток <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Йой',
    'fatal_error'             => 'Відбулася фатальна помилка. Будь ласка, перевірте файли журналів у "storage/logs" або використайте "docker logs -f - [container]", щоб побачити, що сталось.',
    'maintenance_mode'        => 'Firefly III знаходиться в режимі обслуговування.',
    'be_right_back'           => 'Скоро повернусь!',
    'check_back'              => 'Firefly III вимкнувся для проведення необхідного обслуговуванням. Будь ласка, повторіть спробу через секунду.',
    'error_occurred'          => 'Уупс! Сталася помилка.',
    'db_error_occurred'       => 'Whoops! A database error occurred.',
    'error_not_recoverable'   => 'На жаль, цю помилку не можна виправили :(. Firefly III пошкоджено. Помилка:',
    'error'                   => 'Помилка',
    'error_location'          => 'Ця помилка сталася у файлі <span style="font-family: monospace;">:file</span> в рядку :line з кодом :code.',
    'stacktrace'              => 'Трасування стеку',
    'more_info'               => 'Додаткова інформація',
    'collect_info'            => 'Будь ласка, зберіть більше інформації в директорії <code>storage/logs</code> де знаходяться файли журналу. Якщо ви використовуєте Docker, скористайтесь <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Дізнатись більше про збір інформації щодо помилок можете прочитати у розділі<a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">частих запитань</a>.',
    'github_help'             => 'Отримати допомогу на GitHub',
    'github_instructions'     => 'Запрошуємо відкрити новий звіт про проблему <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">на GitHub</a></strong>.',
    'use_search'              => 'Використовуйте пошук!',
    'include_info'            => 'Додати інформацію <a href=":link">з цієї сторінки налагодження</a>.',
    'tell_more'               => 'Скажіть нам більше, ніж "Воно каже: Уупс!"',
    'include_logs'            => 'Додайте журнали помилок (див. вище).',
    'what_did_you_do'         => 'Розкажіть нам, що ви робили.',
    'offline_header'          => 'You are probably offline',
    'offline_unreachable'     => 'Firefly III is unreachable. Your device is currently offline or the server is not working.',
    'offline_github'          => 'If you are sure both your device and the server are online, please open a ticket on <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',

];
