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
    'main_message'                                => 'Handling ":action", tilstede i regel ":rule", kunne ikke brukes på transaksjon #:group: :error',
    'find_or_create_tag_failed'                   => 'Kunne ikke finne eller opprette merke ":tag"',
    'tag_already_added'                           => 'Merke ":tag" er allerede knyttet til denne transaksjonen',
    'inspect_transaction'                         => 'Inspeksjon av transaksjon ":title" @ Firefly III',
    'inspect_rule'                                => 'Inspeksjon av regel ":title" @ Firefly III',
    'journal_other_user'                          => 'Denne transaksjonen tilhører ikke brukeren',
    'no_such_journal'                             => 'Denne transaksjonen eksisterer ikke',
    'journal_already_no_budget'                   => 'Denne transaksjonen har ingen budsjett, så den kan ikke fjernes',
    'journal_already_no_category'                 => 'Denne transaksjonen hadde ingen kategori, så den kan ikke fjernes',
    'journal_already_no_notes'                    => 'Denne transaksjonen hadde ingen notater, så de kan ikke fjernes',
    'journal_not_found'                           => 'Firefly III kan ikke finne den forespurte transaksjonen',
    'split_group'                                 => 'Firefly III kan ikke utføre denne handlingen på en transaksjon med flere splittelser',
    'is_already_withdrawal'                       => 'Denne transaksjonen er allerede en uttak',
    'is_already_deposit'                          => 'Denne transaksjonen er allerede en innskudd',
    'is_already_transfer'                         => 'Denne transaksjonen er allerede en overføring',
    'is_not_transfer'                             => 'Denne transaksjonen er ikke en overføring',
    'complex_error'                               => 'Noe komplisert gikk galt. Beklager det. Vennligst inspiser loggene til Firefly III',
    'no_valid_opposing'                           => 'Konvertering mislyktes fordi det ikke finnes en gyldig konto med navnet ":account"',
    'new_notes_empty'                             => 'Notatene som skal settes, er tomme',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III kan ikke konvertere ":type" til et uttak',
    'unsupported_transaction_type_deposit'        => 'Firefly III kan ikke konvertere ":type" til et innskudd',
    'unsupported_transaction_type_transfer'       => 'Firefly III kan ikke konvertere ":type" til en overføring',
    'already_has_source_asset'                    => 'Denne transaksjonen har allerede ":name" som kilde for eiendelskonto',
    'already_has_destination_asset'               => 'Denne transaksjonen har allerede ":name" som målkonto for eiendeler',
    'already_has_destination'                     => 'Denne transaksjonen har allerede ":name" som destinasjonskonto',
    'already_has_source'                          => 'Denne transaksjonen har allerede ":name" som kildekonto',
    'already_linked_to_subscription'              => 'Transaksjonen er allerede knyttet til abonnementet ":name"',
    'already_linked_to_category'                  => 'Transaksjonen er allerede knyttet til kategorien ":name"',
    'already_linked_to_budget'                    => 'Transaksjonen er allerede knyttet til budsjettet ":name"',
    'cannot_find_subscription'                    => 'Firefly III kan ikke finne abonnementet ":name"',
    'no_notes_to_move'                            => 'Transaksjonen har ingen notater å flytte til beskrivelsesfeltet',
    'no_tags_to_remove'                           => 'Transaksjonen har ingen merker å fjerne',
    'not_withdrawal'                              => 'The transaction is not a withdrawal',
    'not_deposit'                                 => 'The transaction is not a deposit',
    'cannot_find_tag'                             => 'Firefly III kan ikke finne merket ":tag"',
    'cannot_find_asset'                           => 'Firefly III kan ikke finne eiendelskontoen ":name"',
    'cannot_find_accounts'                        => 'Firefly III kan ikke finne kilde- eller destinasjonskontoen',
    'cannot_find_source_transaction'              => 'Firefly III kan ikke finne kilde transaksjonen',
    'cannot_find_destination_transaction'         => 'Firefly III kan ikke finne destinasjonstransaksjonen',
    'cannot_find_source_transaction_account'      => 'Firefly III kan ikke finne kilde transaksjonskontoen',
    'cannot_find_destination_transaction_account' => 'Firefly III kan ikke finne destinasjonstransaksjonskontoen',
    'cannot_find_piggy'                           => 'Firefly III kan ikke finne en sparegris med navnet ":name"',
    'no_link_piggy'                               => 'Kontoene til denne transaksjonen er ikke knyttet til sparegrisen, så ingen handling vil bli utført',
    'cannot_unlink_tag'                           => 'Merke ":tag" er ikke knyttet til denne transaksjonen',
    'cannot_find_budget'                          => 'Firefly III kan ikke finne budsjettet ":name"',
    'cannot_find_category'                        => 'Firefly III kan ikke finne kategorien ":name"',
    'cannot_set_budget'                           => 'Firefly III kan ikke sette budsjettet ":name" til en transaksjon av typen ":type"',
];
