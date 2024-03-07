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
    'main_message'                                => 'Handling ":action", til stede i regel ":rule", kunne ikke anvendes på transaktion #:group: :error',
    'find_or_create_tag_failed'                   => 'Kunne ikke finde eller oprette tag ":tag"',
    'tag_already_added'                           => 'Tag ":tag" er allerede tilknyttet denne transaktion',
    'inspect_transaction'                         => 'Inspicer transaktion ":title" @ Firefly III',
    'inspect_rule'                                => 'Inspicer regel ":title" @ Firefly III',
    'journal_other_user'                          => 'Denne transaktion tilhører ikke brugeren',
    'no_such_journal'                             => 'Denne transaktion findes ikke',
    'journal_already_no_budget'                   => 'Denne transaktion har intet budget, så den kan ikke fjernes',
    'journal_already_no_category'                 => 'Denne transaktion havde ingen kategori, så den kan ikke fjernes',
    'journal_already_no_notes'                    => 'Denne transaktion har ingen noter, så de kan ikke fjernes',
    'journal_not_found'                           => 'Firefly III kan ikke finde den ønskede transaktion',
    'split_group'                                 => 'Firefly III kan ikke udføre denne handling på en transaktion med flere opdelinger',
    'is_already_withdrawal'                       => 'Denne transaktion er allerede en hævning',
    'is_already_deposit'                          => 'Denne transaktion er allerede en deponering',
    'is_already_transfer'                         => 'Denne transaktion er allerede en overførsel',
    'is_not_transfer'                             => 'Denne transaktion er ikke en overførsel',
    'complex_error'                               => 'Noget kompliceret gik galt. Beklager over det. Tjek logs af Firefly III',
    'no_valid_opposing'                           => 'Konvertering mislykkedes fordi der ikke er en gyldig konto med navnet ":account"',
    'new_notes_empty'                             => 'De noter, der skal indstilles, er tomme',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III kan ikke konvertere en ":type" til en udbetaling',
    'unsupported_transaction_type_deposit'        => 'Firefly III kan ikke konvertere en ":type" til en deponering',
    'unsupported_transaction_type_transfer'       => 'Firefly III kan ikke konvertere en ":type" til en overførsel',
    'already_has_source_asset'                    => 'Denne transaktion har allerede ":name" som kildeaktivkonto',
    'already_has_destination_asset'               => 'Denne transaktion har allerede ":name" som destinationsaktivkonto',
    'already_has_destination'                     => 'Denne transaktion har allerede ":name" som destinationskonto',
    'already_has_source'                          => 'Denne transaktion har allerede ":name" som kildekonto',
    'already_linked_to_subscription'              => 'Denne transaktion er allerede knyttet til abonnement ":name"',
    'already_linked_to_category'                  => 'Denne transaktion er allerede knyttet til kategori ":name"',
    'already_linked_to_budget'                    => 'Denne transaktion er allerede knyttet til budget ":name"',
    'cannot_find_subscription'                    => 'Firefly III kan ikke finde abonnement ":name"',
    'no_notes_to_move'                            => 'Transaktionen har ingen noter at flytte til beskrivelsesfeltet',
    'no_tags_to_remove'                           => 'Transaktionen har ingen tags at fjerne',
    'not_withdrawal'                              => 'Denne transaktion er ikke en hævning',
    'not_deposit'                                 => 'Denne transaktion er ikke en deponering',
    'cannot_find_tag'                             => 'Firefly III kan ikke finde tag ":tag"',
    'cannot_find_asset'                           => 'Firefly III kan ikke finde aktivkonto ":name"',
    'cannot_find_accounts'                        => 'Firefly III kan ikke finde kilde- eller destinationkontoen',
    'cannot_find_source_transaction'              => 'Firefly III kan ikke finde kildetransaktionen',
    'cannot_find_destination_transaction'         => 'Firefly III kan ikke finde destinationstransaktionen',
    'cannot_find_source_transaction_account'      => 'Firefly III kan ikke finde kildetransaktionskontoen',
    'cannot_find_destination_transaction_account' => 'Firefly III kan ikke finde destinationstransaktionskontoen',
    'cannot_find_piggy'                           => 'Firefly III kan ikke finde en sparegris med navnet ":name"',
    'no_link_piggy'                               => 'Denne transaktions konti er ikke knyttet til sparegrisen, så ingen handling vil tage sted',
    'cannot_unlink_tag'                           => 'Tag ":tag" er ikke tilknyttet denne transaktion',
    'cannot_find_budget'                          => 'Firefly III kan ikke finde budget ":name"',
    'cannot_find_category'                        => 'Firefly III kan ikke finde kategori ":name"',
    'cannot_set_budget'                           => 'Firefly III kan ikke indstille budget ":name" til en transaktion af typen ":type"',
];
