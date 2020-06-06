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
    '404_header'              => 'Firefly III no puede encontrar esta página.',
    '404_page_does_not_exist' => 'La página que ha solicitado no existe. Por favor, compruebe que no ha introducido la URL incorrecta. ¿Ha cometido un error tipográfico?',
    '404_send_error'          => 'Si fue redirigido a esta página automáticamente, por favor acepte mis disculpas. Hay una mención de este error en sus archivos de registro y le agradecería que me enviara el error.',
    '404_github_link'         => 'Si está seguro de que esta página debería existir, abra un ticket en <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Ups',
    'fatal_error'             => 'Hubo un error fatal. Por favor, compruebe los archivos de registro en "almacenamiento/registro" o use "docker logs -f [container]" para ver lo que está sucediendo.',
    'maintenance_mode'        => 'Firefly III está en modo mantenimiento.',
    'be_right_back'           => '¡Enseguida vuelvo!',
    'check_back'              => 'Firefly III está apagado para el mantenimiento necesario. Por favor, vuelva en un segundo.',
    'error_occurred'          => '¡Uy! un error ha ocurrido.',
    'error_not_recoverable'   => 'Desafortunadamente, este error no se pudo recuperar :(. Firefly III se rompió. El error es:',
    'error'                   => 'Error',
    'error_location'          => 'Este error ocurrió en el archivo "<span style="font-family: monospace;">:file</span>" en línea :line con código :code.',
    'stacktrace'              => 'Seguimiento de la pila',
    'more_info'               => 'Más información',
    'collect_info'            => 'Por favor, recopile más información en el directorio <code>storage/logs</code> donde encontrará los archivos de registro. Si está ejecutando Docker, use <code>registros docker -f [container]</code>.',
    'collect_info_more'       => 'Puede leer más sobre la recolección de información de errores en <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">las Preguntas Frecuentes</a>.',
    'github_help'             => 'Obtener ayuda en GitHub',
    'github_instructions'     => 'Es bienvenido a abrir un nuevo issue <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">en GitHub</a></strong>.',
    'use_search'              => '¡Use la búsqueda!',
    'include_info'            => 'Incluya la información <a href=":link">de esta página de depuración</a>.',
    'tell_more'               => 'Cuéntenos más que "Dice: Ups"',
    'include_logs'            => 'Incluye registros de errores (ver arriba).',
    'what_did_you_do'         => 'Cuéntenos lo que estaba haciendo.',

];
