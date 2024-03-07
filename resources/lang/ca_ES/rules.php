<?php

/*
 * rules.php
 * Copyright (c) 2023 james@firefly-iii.org
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
    'main_message'                                => 'Acció ":action", present a la regla ":rule", no s\'ha pogut aplicar a la transacció #:group: :error',
    'find_or_create_tag_failed'                   => 'No s\'ha pogut trobar o crear l\'etiqueta ":tag"',
    'tag_already_added'                           => 'L\'etiqueta ":tag" ja està enllaçada a aquesta transacció',
    'inspect_transaction'                         => 'Inspecciona la transacció ":title" a Firefly III',
    'inspect_rule'                                => 'Inspecciona la norma ":title" a Firefly III',
    'journal_other_user'                          => 'Aquesta transacció no pertany a l\'usuari',
    'no_such_journal'                             => 'Aquesta transacció no existeix',
    'journal_already_no_budget'                   => 'Aquesta transacció no té cap pressupost, així que no es pot eliminar',
    'journal_already_no_category'                 => 'Aquesta transacció no tenia cap categoria, així que no es pot eliminar',
    'journal_already_no_notes'                    => 'Aquesta transacció no tenia notes, així que no es poden eliminar',
    'journal_not_found'                           => 'Firefly III no pot trobar la transacció sol·licitada',
    'split_group'                                 => 'Firefly III no pot executar aquesta acció a una transacció amb varies divisions',
    'is_already_withdrawal'                       => 'Aquesta transacció ja és una retirada',
    'is_already_deposit'                          => 'Aquesta transacció ja és un dipòsit',
    'is_already_transfer'                         => 'Aquesta transacció ja és una transferència',
    'is_not_transfer'                             => 'Aquesta transacció no és una transferència',
    'complex_error'                               => 'Una operació complicada ha anat malament. Disculpa. Per favor, revisa els registres de Firefly III',
    'no_valid_opposing'                           => 'La conversió ha fallat perquè no hi ha cap compte anomenat ":account"',
    'new_notes_empty'                             => 'Les notes a establir són buides',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III no pot convertir un/una ":type" a una retirada',
    'unsupported_transaction_type_deposit'        => 'Firefly III no pot convertir una ":type" a un dipòsit',
    'unsupported_transaction_type_transfer'       => 'Firefly III no pot convertir un/una ":type" a una transferència',
    'already_has_source_asset'                    => 'Aquesta transacció ja té ":name" com al compte d\'actius font',
    'already_has_destination_asset'               => 'Aquesta transacció ja té ":name" com al compte d\'actius destí',
    'already_has_destination'                     => 'Aquesta transacció ja té ":name" com al compte destí',
    'already_has_source'                          => 'Aquesta transacció ja té ":name" com al compte font',
    'already_linked_to_subscription'              => 'La transacció ja està enllaçada a la subscripció ":name"',
    'already_linked_to_category'                  => 'La transacció ja està enllaçada a la categoria ":name"',
    'already_linked_to_budget'                    => 'La transacció ja està enllaçada al pressupost ":name"',
    'cannot_find_subscription'                    => 'Firefly III no pot trobar la subscripció ":name"',
    'no_notes_to_move'                            => 'La transacció no té cap nota que moure al camp de descripció',
    'no_tags_to_remove'                           => 'La transacció no té etiquetes que eliminar',
    'not_withdrawal'                              => 'La transacció no és una retirada',
    'not_deposit'                                 => 'La transacció no és un dipòsit',
    'cannot_find_tag'                             => 'Firefly III no ha pogut trobar l\'etiqueta ":tag"',
    'cannot_find_asset'                           => 'Firefly III no ha pogut trobar el compte d\'actius ":name"',
    'cannot_find_accounts'                        => 'Firefly III no ha pogut trobar el compte destí o font',
    'cannot_find_source_transaction'              => 'Firefly III no pot trobar la transacció d\'origen',
    'cannot_find_destination_transaction'         => 'Firefly III no pot trobar la transacció de destinació',
    'cannot_find_source_transaction_account'      => 'Firefly III no pot trobar el compte font de la transacció',
    'cannot_find_destination_transaction_account' => 'Firefly III no pot trobar el compte de destinació de la transacció',
    'cannot_find_piggy'                           => 'Firefly III no pot trobar una guardiola anomenada ":name"',
    'no_link_piggy'                               => 'Els comptes d\'aquesta transacció no estan enllaçats a la guardiola, així que no es durà a terme cap acció',
    'cannot_unlink_tag'                           => 'L\'etiqueta ":tag" no està enllaçada a aquesta transacció',
    'cannot_find_budget'                          => 'Firefly III no pot trobar el pressupost ":name"',
    'cannot_find_category'                        => 'Firefly III no pot trobar la categoria ":name"',
    'cannot_set_budget'                           => 'Firefly III no pot establir el pressupost ":name" a una transacció del tipus ":type"',
];
