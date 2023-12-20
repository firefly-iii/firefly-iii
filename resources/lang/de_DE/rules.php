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
    'main_message'                                => 'Aktion ":action", vorhanden in Regel ":rule", konnte nicht auf Transaktion #:group angewendet werden: :error',
    'find_or_create_tag_failed'                   => 'Konnte Schlagwort ":tag" nicht finden oder erstellen',
    'tag_already_added'                           => 'Schlagwort ":tag" ist bereits mit dieser Transaktion verknüpft',
    'inspect_transaction'                         => 'Buchung ":title" untersuchen @ Firefly III',
    'inspect_rule'                                => 'Regel ":title" untersuchen @ Firefly III',
    'journal_other_user'                          => 'Diese Buchung gehört nicht zum Benutzer',
    'no_such_journal'                             => 'Diese Buchung existiert nicht',
    'journal_already_no_budget'                   => 'Diese Buchung hat kein Budget, daher kann sie nicht entfernt werden',
    'journal_already_no_category'                 => 'Diese Buchung hatte keine Kategorie, daher kann sie nicht entfernt werden',
    'journal_already_no_notes'                    => 'Diese Buchung hatte keine Notizen, daher können sie nicht entfernt werden',
    'journal_not_found'                           => 'Firefly III kann die angeforderte Buchung nicht finden',
    'split_group'                                 => 'Firefly III kann diese Aktion bei einer Buchung mit mehreren Aufteilungen nicht ausführen',
    'is_already_withdrawal'                       => 'Diese Buchung ist bereits eine Ausgabe',
    'is_already_deposit'                          => 'Diese Buchung ist bereits eine Einnahme',
    'is_already_transfer'                         => 'Diese Buchung ist bereits eine Umbuchung',
    'is_not_transfer'                             => 'Diese Buchung ist keine Überweisung',
    'complex_error'                               => 'Etwas kompliziertes ist schief gelaufen. Bitte die Protokolle von Firefly III überprüfen',
    'no_valid_opposing'                           => 'Konvertierung fehlgeschlagen, da kein gültiges Konto mit dem Namen ":account " existiert',
    'new_notes_empty'                             => 'Die zu setzenden Notizen sind leer',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III kann eine ":type" nicht in eine Auszahlung konvertieren',
    'unsupported_transaction_type_deposit'        => 'Firefly III kann ein ":type" nicht in eine Einzahlung umwandeln',
    'unsupported_transaction_type_transfer'       => 'Firefly III kann eine ":type" nicht in eine Überweisung umwandeln',
    'already_has_source_asset'                    => 'Diese Transaktion hat bereits ":name" als Quellkonto',
    'already_has_destination_asset'               => 'Diese Transaktion hat bereits ":name" als Zielkonto',
    'already_has_destination'                     => 'Diese Transaktion hat bereits ":name" als Zielkonto',
    'already_has_source'                          => 'Diese Transaktion hat bereits ":name" als Quellkonto',
    'already_linked_to_subscription'              => 'Die Transaktion ist bereits mit dem Abonnement ":name " verknüpft',
    'already_linked_to_category'                  => 'Die Transaktion ist bereits mit der Kategorie ":name" verknüpft',
    'already_linked_to_budget'                    => 'Die Transaktion ist bereits mit Budget ":name" verknüpft',
    'cannot_find_subscription'                    => 'Firefly III kann das Abonnement ":name" nicht finden',
    'no_notes_to_move'                            => 'Diese Transaktion hat keine Notizen für das Beschreibungsfeld',
    'no_tags_to_remove'                           => 'Die Buchung hat keine Schlagworte zum Entfernen',
    'not_withdrawal'                              => 'The transaction is not a withdrawal',
    'not_deposit'                                 => 'The transaction is not a deposit',
    'cannot_find_tag'                             => 'Firefly III kann Schlagwort ":tag" nicht finden',
    'cannot_find_asset'                           => 'Firefly III kann kein Girokonto ":name" finden',
    'cannot_find_accounts'                        => 'Firefly III kann das Quell- oder Zielkonto nicht finden',
    'cannot_find_source_transaction'              => 'Firefly III kann die Quelltransaktion nicht finden',
    'cannot_find_destination_transaction'         => 'Firefly III kann die Zieltransaktion nicht finden',
    'cannot_find_source_transaction_account'      => 'Firefly III konnte das Quellkonto nicht finden',
    'cannot_find_destination_transaction_account' => 'Firefly III kann das Zielkonto nicht finden',
    'cannot_find_piggy'                           => 'Firefly III kann kein Sparschwein mit dem Namen ":name" finden',
    'no_link_piggy'                               => 'Die Konten dieser Buchung sind nicht mit dem Sparschwein verbunden, daher wird nichts gemacht',
    'cannot_unlink_tag'                           => 'Schlagwort ":tag" ist nicht mit dieser Buchung verknüpft',
    'cannot_find_budget'                          => 'Firefly III kann kein Budget ":name" finden',
    'cannot_find_category'                        => 'Firefly III kann die Kategorie ":name" nicht finden',
    'cannot_set_budget'                           => 'Firefly III kann das Budget ":name" nicht auf eine Buchung vom Typ ":type" setzen',
];
