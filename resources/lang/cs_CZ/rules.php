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
    'main_message'                                => 'Akce ":action", přítomná v pravidle ":rule", nelze použít na transakci #:group: :error',
    'find_or_create_tag_failed'                   => 'Nelze najít nebo vytvořit štítek ":tag"',
    'tag_already_added'                           => 'Štítek ":tag" je již propojen s touto transakcí',
    'inspect_transaction'                         => 'Prozkoumat transakci ":title" @ Firefly III',
    'inspect_rule'                                => 'Prozkoumat pravidlo ":title" @ Firefly III',
    'journal_other_user'                          => 'Tato transakce nepatří uživateli',
    'no_such_journal'                             => 'Tato transakce neexistuje',
    'journal_already_no_budget'                   => 'Tato transakce nemá žádný rozpočet, proto nemůže být odstraněna',
    'journal_already_no_category'                 => 'Tato transakce nemá žádnou kategorii, proto nemůže být odstraněna',
    'journal_already_no_notes'                    => 'Tato transakce neměla žádné poznámky, takže nemůže být odstraněna',
    'journal_not_found'                           => 'Firefly III nemůže najít požadovanou transakci',
    'split_group'                                 => 'Firefly III nemůže provést tuto akci na transakci rozdělenou na více částí',
    'is_already_withdrawal'                       => 'Tato transakce už je výběrem',
    'is_already_deposit'                          => 'Tato transakce už je vkladem',
    'is_already_transfer'                         => 'Tato transakce už je převodem',
    'is_not_transfer'                             => 'Tato transakce není převod',
    'complex_error'                               => 'Něco se pokazilo. Omlouváme se, prosím zkontrolujte logy Firefly III',
    'no_valid_opposing'                           => 'Převod se nezdařil, protože neexistuje žádný platný účet s názvem ":account"',
    'new_notes_empty'                             => 'Poznámky, které mají být nastaveny, jsou prázdné',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III nemůže převést ":type" na výběr',
    'unsupported_transaction_type_deposit'        => 'Firefly III nemůže konvertovat ":type" na vklad',
    'unsupported_transaction_type_transfer'       => 'Firefly III nemůže konvertovat ":type" na převod',
    'already_has_source_asset'                    => 'Tato transakce již má ":name" jako zdrojový majetkový účet',
    'already_has_destination_asset'               => 'Tato transakce již má ":name" jako cílový majetkový účet',
    'already_has_destination'                     => 'Tato transakce již má ":name" jako cílový účet',
    'already_has_source'                          => 'Tato transakce již má ":name" jako zdrojový účet',
    'already_linked_to_subscription'              => 'The transaction is already linked to subscription ":name"',
    'already_linked_to_category'                  => 'Transakce je již propojena s kategorií ":name"',
    'already_linked_to_budget'                    => 'Transakce je již propojena s rozpočtem ":name"',
    'cannot_find_subscription'                    => 'Firefly III can\'t find subscription ":name"',
    'no_notes_to_move'                            => 'Transakce nemá žádné poznámky k přesunutí do pole popisu',
    'no_tags_to_remove'                           => 'Transakce nemá žádné štítky k odstranění',
    'not_withdrawal'                              => 'Transakce není výběrem',
    'not_deposit'                                 => 'Transakce není vklad',
    'cannot_find_tag'                             => 'Firefly III nemůže najít štítek ":tag"',
    'cannot_find_asset'                           => 'Firefly III nemůže najít majetkový účet ":name"',
    'cannot_find_accounts'                        => 'Firefly III nemůže najít zdrojový nebo cílový účet',
    'cannot_find_source_transaction'              => 'Firefly III nemůže najít zdrojovou transakci',
    'cannot_find_destination_transaction'         => 'Firefly III nemůže najít cílovou transakci',
    'cannot_find_source_transaction_account'      => 'Firefly III nemůže najít zdrojový transakční účet',
    'cannot_find_destination_transaction_account' => 'Firefly III nemůže najít cílový účet transakce',
    'cannot_find_piggy'                           => 'Firefly III nemůže najít pokladničku s názvem ":name"',
    'no_link_piggy'                               => 'Účty této transakce nejsou propojeny s pokladničkou, takže žádná akce nebude provedena',
    'cannot_unlink_tag'                           => 'Štítek ":tag" není propojen s touto transakcí',
    'cannot_find_budget'                          => 'Firefly III nemůže najít rozpočet ":name"',
    'cannot_find_category'                        => 'Firefly III nemůže najít kategorii ":name"',
    'cannot_set_budget'                           => 'Firefly III nemůže nastavit rozpočet ":name" na transakci typu ":type"',
    'journal_invalid_amount'                      => 'Firefly III can\'t set amount ":amount" because it is not a valid number.',
];
