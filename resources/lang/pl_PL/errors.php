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
    '404_header'              => 'Firefly III nie może znaleźć tej strony.',
    '404_page_does_not_exist' => 'Żądana strona nie istnieje. Sprawdź, czy nie wprowadziłeś nieprawidłowego adresu URL. Może zrobiłeś literówkę?',
    '404_send_error'          => 'Jeśli zostałeś automatycznie przekierowany na tę stronę, proszę przyjmij moje przeprosiny. Błąd został zapisany w plikach dziennika i byłbym wdzięczny za wysłanie mi tego błędu.',
    '404_github_link'         => 'Jeśli jesteś pewien, że ta strona powinna istnieć, otwórz zgłoszenie na <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Ups',
    'fatal_error'             => 'Wystąpił błąd krytyczny. Sprawdź pliki dziennika w "storage/logs" lub użyj "docker logs -f [container]", aby zobaczyć co się dzieje.',
    'maintenance_mode'        => 'Firefly III jest w trybie konserwacji.',
    'be_right_back'           => 'Zaraz wracam!',
    'check_back'              => 'Firefly III jest wyłączony na potrzeby wymaganej konserwacji. Sprawdź ponownie za sekundę.',
    'error_occurred'          => 'Ups! Wystąpił błąd.',
    'error_not_recoverable'   => 'Niestety, nie mogliśmy się pozbierać po tym błędzie :(. Firefly III się popsuło. Błąd to:',
    'error'                   => 'Błąd',
    'error_location'          => 'Błąd wystąpił w pliku <span style="font-family: monospace;">:file</span> linia :line z kodem :code.',
    'stacktrace'              => 'Ślad stosu',
    'more_info'               => 'Więcej informacji',
    'collect_info'            => 'Więcej informacji znajdziesz w katalogu <code>storage/logs</code>, w który zawiera pliki dziennika. Jeśli używasz Docker, użyj <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Więcej informacji o zbieraniu informacji o błędach możesz znaleźć w <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">FAQ</a>.',
    'github_help'             => 'Uzyskaj pomoc na GitHub',
    'github_instructions'     => 'Możesz otworzyć nowy problem <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">na GitHub</a></strong>.',
    'use_search'              => 'Użyj wyszukiwania!',
    'include_info'            => 'Dołącz informacje <a href=":link">z tej strony debugowania</a>.',
    'tell_more'               => 'Powiedz nam więcej niż "Nie działa!"',
    'include_logs'            => 'Dołącz dzienniki błędów (patrz powyżej).',
    'what_did_you_do'         => 'Powiedz nam, co robisz.',

];
