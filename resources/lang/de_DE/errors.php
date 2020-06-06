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
    '404_header'              => 'Firefly III kann diese Seite nicht finden.',
    '404_page_does_not_exist' => 'Die angeforderte Seite existiert nicht. Bitte überprüfen Sie, ob Sie nicht die falsche URL eingegeben haben. Haben Sie vielleicht einen Tippfehler gemacht?',
    '404_send_error'          => 'Wenn Sie automatisch auf diese Seite weitergeleitet wurden, nehmen Sie bitte meine Entschuldigung an. Es gibt einen Hinweis auf diesen Fehler in Ihren Logdateien, und ich wäre Ihnen dankbar, wenn Sie mir den Fehler schicken würden.',
    '404_github_link'         => 'Wenn Sie sicher sind, dass diese Seite existieren soll, öffnen Sie bitte ein Ticket auf <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Hoppla',
    'fatal_error'             => 'Es gab einen fatalen Fehler. Bitte überprüfen Sie die Logdateien in "storage/logs" oder verwenden Sie "docker logs -f [container]", um zu sehen, was vor sich geht.',
    'maintenance_mode'        => 'Firefly III ist im Wartungsmodus.',
    'be_right_back'           => 'Gleich wieder zurück!',
    'check_back'              => 'Firefly III ist für eine notwendige Wartung nicht verfügbar. Bitte versuchen Sie es in einer Sekunde noch einmal.',
    'error_occurred'          => 'Hoppla! Ein Fehler ist aufgetreten.',
    'error_not_recoverable'   => 'Leider konnte dieser Fehler nicht wiederhergestellt werden :(. Firefly III ist kaputt. Der Fehler ist:',
    'error'                   => 'Fehler',
    'error_location'          => 'Dieser Fehler ist in der Datei <span style="font-family: monospace;">:file</span> in Zeile :line mit dem Code :code aufgetreten.',
    'stacktrace'              => 'Stack-Trace',
    'more_info'               => 'Weitere Informationen',
    'collect_info'            => 'Bitte sammeln Sie weitere Informationen im Verzeichnis <code>storage/logs</code> wo Sie Logdateien finden können. Wenn Sie Docker verwenden, verwenden Sie <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Lesen Sie mehr über das Sammeln von Fehlerinformationen in <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">der FAQ</a>.',
    'github_help'             => 'Hilfe auf GitHub erhalten',
    'github_instructions'     => 'Sie sind herzlich eingeladen, ein neues Ticket <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">auf GitHub</a></strong> zu öffnen.',
    'use_search'              => 'Benutzen Sie die Suche!',
    'include_info'            => 'Fügen Sie die Informationen <a href=":link">von dieser Debug-Seite</a> ein.',
    'tell_more'               => 'Sagen Sie uns mehr als "Da steht Hoppla!"',
    'include_logs'            => 'Fehlerprotokolle einschließen (siehe oben).',
    'what_did_you_do'         => 'Teilen Sie uns mit, was Sie getan haben.',

];
