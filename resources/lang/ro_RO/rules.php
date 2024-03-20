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
    'main_message'                                => 'Acțiunea ":action", prezent în regula ":rule", nu a putut fi aplicată tranzacției #:group: :error',
    'find_or_create_tag_failed'                   => 'Nu s-a putut găsi sau crea eticheta ":tag"',
    'tag_already_added'                           => 'Eticheta ":tag" este deja legată de această tranzacție',
    'inspect_transaction'                         => 'Inspectează tranzacția ":title" @ Firefly III',
    'inspect_rule'                                => 'Inspectează regula ":title" @ Firefly III',
    'journal_other_user'                          => 'Această tranzacție nu aparține utilizatorului',
    'no_such_journal'                             => 'Această tranzacție nu există',
    'journal_already_no_budget'                   => 'Această tranzacție nu are buget, deci nu poate fi eliminată',
    'journal_already_no_category'                 => 'Această tranzacție nu a avut nicio categorie, deci nu a putut fi ștearsă',
    'journal_already_no_notes'                    => 'Această tranzacție nu a avut notițe, deci nu a putut fi eliminată',
    'journal_not_found'                           => 'Firefly III nu a găsit tranzacția solicitată',
    'split_group'                                 => 'Firefly III nu poate executa această acțiune pentru o tranzacție cu mai multe scindări',
    'is_already_withdrawal'                       => 'Această tranzacție este deja o retragere',
    'is_already_deposit'                          => 'Această tranzacție este deja un depozit',
    'is_already_transfer'                         => 'Această tranzacție este deja un transfer',
    'is_not_transfer'                             => 'Această tranzacție nu este un transfer',
    'complex_error'                               => 'Ceva complicat a mers prost. Ne pare rău pentru asta. Te rugăm să verifici jurnalele lui Firefly III',
    'no_valid_opposing'                           => 'Conversia a eșuat deoarece nu există un cont valid numit ":account"',
    'new_notes_empty'                             => 'Notele care trebuie setate sunt goale',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III nu poate converti un ":type" la o retragere',
    'unsupported_transaction_type_deposit'        => 'Firefly III nu poate converti un ":type" într-un depozit',
    'unsupported_transaction_type_transfer'       => 'Firefly III nu poate converti un ":type" într-un transfer',
    'already_has_source_asset'                    => 'Această tranzacție deja are ":name" ca cont de active sursă',
    'already_has_destination_asset'               => 'Această tranzacție deja are ":name" ca cont de active destinație',
    'already_has_destination'                     => 'Această tranzacție deja are ":name" ca cont de destinație',
    'already_has_source'                          => 'Această tranzacție deja are ":name" ca cont sursă',
    'already_linked_to_subscription'              => 'Tranzacția este deja legată de abonamentul ":name"',
    'already_linked_to_category'                  => 'Tranzacția este deja legată de categoria ":name"',
    'already_linked_to_budget'                    => 'Tranzacția este deja legată de bugetul ":name"',
    'cannot_find_subscription'                    => 'Firefly III nu poate găsi abonamentul ":name"',
    'no_notes_to_move'                            => 'Tranzacția nu are note de mutat în câmpul de descriere',
    'no_tags_to_remove'                           => 'Tranzacția nu are etichete de eliminat',
    'not_withdrawal'                              => 'Tranzacția nu este o retragere',
    'not_deposit'                                 => 'Tranzacția nu este un depozit',
    'cannot_find_tag'                             => 'Firefly III nu poate găsi tag-ul ":tag"',
    'cannot_find_asset'                           => 'Firefly III nu poate găsi contul de active ":name"',
    'cannot_find_accounts'                        => 'Firefly III nu poate găsi contul sursă sau destinație',
    'cannot_find_source_transaction'              => 'Firefly III nu a găsit tranzacția sursă',
    'cannot_find_destination_transaction'         => 'Firefly III nu a găsit tranzacția de destinație',
    'cannot_find_source_transaction_account'      => 'Firefly III nu a găsit contul sursă de tranzacție',
    'cannot_find_destination_transaction_account' => 'Firefly III nu a găsit contul de destinație al tranzacției',
    'cannot_find_piggy'                           => 'Firefly III nu poate găsi o pușculiță numită ":name"',
    'no_link_piggy'                               => 'Conturile acestei tranzacții nu sunt legate de pușculiță, deci nu se va lua nicio acțiune',
    'cannot_unlink_tag'                           => 'Eticheta ":tag" nu este legată de această tranzacție',
    'cannot_find_budget'                          => 'Firefly III nu poate găsi bugetul ":name"',
    'cannot_find_category'                        => 'Firefly III nu a găsit categoria ":name"',
    'cannot_set_budget'                           => 'Firefly III nu poate seta bugetul ":name" la o tranzacție de tipul ":type"',
    'journal_invalid_amount'                      => 'Firefly III nu poate seta suma ":amount" deoarece nu este un număr valid.',
];
