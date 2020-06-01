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
    '404_header'              => 'Firefly III ne peut pas trouver cette page.',
    '404_page_does_not_exist' => 'La page que vous avez demandée n\'existe pas. Veuillez vérifier que vous n\'avez pas saisi la mauvaise URL. Peut-être avez-vous fait une faute de frappe ?',
    '404_send_error'          => 'Si vous avez été redirigé automatiquement vers cette page, veuillez accepter mes excuses. Il y a une mention de cette erreur dans vos fichiers journaux et je vous serais reconnaissant de m\'envoyer l\'erreur.',
    '404_github_link'         => 'Si vous êtes sûr que cette page devrait exister, veuillez ouvrir un ticket sur <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong> (en anglais).',
    'whoops'                  => 'Oups',
    'fatal_error'             => 'Il y a eu une erreur fatale. Veuillez vérifier les fichiers journaux dans "storage/logs" ou utilisez "docker logs -f [container]" pour voir ce qui se passe.',
    'maintenance_mode'        => 'Firefly III est en mode maintenance.',
    'be_right_back'           => 'Je reviens tout de suite !',
    'check_back'              => 'Firefly III est fermé pour cause de maintenace. Veuillez revenir dans une seconde.',
    'error_occurred'          => 'Oups ! Une erreur est survenue.',
    'error_not_recoverable'   => 'Malheureusement, cette erreur n\'a pas pu être récupérée :(. Firefly III s\'est cassé. L\'erreur est :',
    'error'                   => 'Erreur',
    'error_location'          => 'Cette erreur est survenue dans le fichier "<span style="font-family: monospace;">:file</span>" à la ligne :line avec le code :code.',
    'stacktrace'              => 'Stack trace',
    'more_info'               => 'Plus d\'informations',
    'collect_info'            => 'Vous pouvez obtenir plus d\'informations dans le répertoire <code>stockage/logs</code> où vous trouverez des fichiers journaux. Si vous utilisez Docker, utilisez <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Vous pouvez en savoir plus sur la récupération des informations d\'erreur dans <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">la FAQ</a>.',
    'github_help'             => 'Obtenir de l\'aide sur GitHub',
    'github_instructions'     => 'Vous êtes encouragé à ouvrir un nouveau ticket <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">sur GitHub</a> (en anglais)</strong>.',
    'use_search'              => 'Utilisez la recherche !',
    'include_info'            => 'Incluez les informations <a href=":link">de cette page de débogage</a>.',
    'tell_more'               => 'Dites-nous plus que "ça dit Oups !"',
    'include_logs'            => 'Incluez les logs d\'erreur (voir plus bas).',
    'what_did_you_do'         => 'Dites-nous ce que vous faisiez.',

];
