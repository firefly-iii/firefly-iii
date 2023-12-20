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
    'main_message'                                => 'Dejanja ":action", ki je prisotno v pravilu ":rule", ni bilo mogoče uporabiti za transakcijo #:group: :error',
    'find_or_create_tag_failed'                   => 'Ni bilo mogoče najti ali ustvariti oznake »:tag«',
    'tag_already_added'                           => 'Oznaka ":tag" je že povezana s to transakcijo',
    'inspect_transaction'                         => 'Preglejte transakcijo ":title" @ Firefly III',
    'inspect_rule'                                => 'Preglejte pravilo ":title" @ Firefly III',
    'journal_other_user'                          => 'Ta transakcija ne pripada uporabniku',
    'no_such_journal'                             => 'Ta transakcija ne obstaja',
    'journal_already_no_budget'                   => 'Ta transakcija nima proračuna, zato je ni mogoče odstraniti',
    'journal_already_no_category'                 => 'Ta transakcija ni imela kategorije, zato je ni mogoče odstraniti',
    'journal_already_no_notes'                    => 'Ta transakcija ni imela opomb, zato jih ni mogoče odstraniti',
    'journal_not_found'                           => 'Firefly III ne najde zahtevane transakcije',
    'split_group'                                 => 'Firefly III ne more izvesti tega dejanja na transakciji z več razdelitvami',
    'is_already_withdrawal'                       => 'Ta transakcija je že dvig',
    'is_already_deposit'                          => 'Ta transakcija je že depozit',
    'is_already_transfer'                         => 'Ta transakcija je že prenos',
    'is_not_transfer'                             => 'Ta transakcija ni prenos',
    'complex_error'                               => "Nekaj \u{200b}\u{200b}zapletenega je šlo narobe. Oprostite za to. Prosimo, preglejte dnevnike Firefly III",
    'no_valid_opposing'                           => 'Pretvorba ni uspela, ker ni veljavnega računa z imenom ":account"',
    'new_notes_empty'                             => 'Opombe, ki jih želite nastaviti, so prazne',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III ne more pretvoriti ":type" v dvig',
    'unsupported_transaction_type_deposit'        => 'Firefly III ne more pretvoriti ":type" v depozit',
    'unsupported_transaction_type_transfer'       => 'Firefly III ne more pretvoriti ":type" v prenos',
    'already_has_source_asset'                    => 'Ta transakcija že ima ":name" kot izvorni račun sredstva',
    'already_has_destination_asset'               => 'Ta transakcija že ima »:name« kot ciljni račun sredstev',
    'already_has_destination'                     => 'Ta transakcija že ima ":name" kot ciljni račun',
    'already_has_source'                          => 'Ta transakcija že ima ":name" kot izvorni račun',
    'already_linked_to_subscription'              => 'Transakcija je že povezana z naročnino ":name"',
    'already_linked_to_category'                  => 'Transakcija je že povezana s kategorijo ":name"',
    'already_linked_to_budget'                    => 'Transakcija je že povezana s proračunom ":name"',
    'cannot_find_subscription'                    => 'Firefly III ne najde naročnine ":name"',
    'no_notes_to_move'                            => 'Transakcija nima nobenih opomb, ki bi jih bilo treba premakniti v polje opisa',
    'no_tags_to_remove'                           => 'Transakcija nima oznak za odstranitev',
    'not_withdrawal'                              => 'Transakcija ni dvig',
    'not_deposit'                                 => 'Transakcija ni depozit',
    'cannot_find_tag'                             => 'Firefly III ne najde oznake ":tag"',
    'cannot_find_asset'                           => 'Firefly III ne najde računa sredstev ":name"',
    'cannot_find_accounts'                        => 'Firefly III ne najde izvornega ali ciljnega računa',
    'cannot_find_source_transaction'              => 'Firefly III ne najde izvorne transakcije',
    'cannot_find_destination_transaction'         => 'Firefly III ne najde ciljne transakcije',
    'cannot_find_source_transaction_account'      => 'Firefly III ne najde izvornega transakcijskega računa',
    'cannot_find_destination_transaction_account' => 'Firefly III ne najde ciljnega transakcijskega računa',
    'cannot_find_piggy'                           => 'Firefly III ne najde hranilnika z imenom ":name"',
    'no_link_piggy'                               => 'Računi te transakcije niso povezani s hranilnikom, zato ne bo nobenih ukrepov',
    'cannot_unlink_tag'                           => 'Oznaka ":tag" ni povezana s to transakcijo',
    'cannot_find_budget'                          => 'Firefly III ne najde proračuna ":name"',
    'cannot_find_category'                        => 'Firefly III ne najde kategorije ":name"',
    'cannot_set_budget'                           => 'Firefly III ne more nastaviti proračuna ":name" na transakcijo tipa ":type"',
];
