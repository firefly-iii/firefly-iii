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
    'main_message'                                => 'L\'azione ":action", presente nella regola ":rule", non può essere applicata alla transazione #:group: :error',
    'find_or_create_tag_failed'                   => 'Impossibile trovare o creare il tag ":tag"',
    'tag_already_added'                           => 'L\'etichetta ":tag" è già collegata a questa transazione',
    'inspect_transaction'                         => 'Ispeziona la transazione ":title" @ Firefly III',
    'inspect_rule'                                => 'Ispeziona la regola ":title" @ Firefly III',
    'journal_other_user'                          => 'Questa transazione non appartiene all\'utente',
    'no_such_journal'                             => 'Questa transazione non esiste',
    'journal_already_no_budget'                   => 'Questa transazione non ha un budget, quindi non può essere rimossa',
    'journal_already_no_category'                 => 'Questa transazione non ha una categoria, quindi non può essere rimossa',
    'journal_already_no_notes'                    => 'Questa transazione non ha note, quindi non può essere rimossa',
    'journal_not_found'                           => 'Firefly III non riesce a trovare la transazione richiesta',
    'split_group'                                 => 'Firefly III non può eseguire questa azione su una transazione con più divisioni',
    'is_already_withdrawal'                       => 'Questa transazione è già un prelievo',
    'is_already_deposit'                          => 'Questa transazione è già un deposito',
    'is_already_transfer'                         => 'Questa transazione è già un trasferimento',
    'is_not_transfer'                             => 'Questa transazione non è un trasferimento',
    'complex_error'                               => 'Qualcosa di complicato è andato storto. Scusateci. Controlla i log di Firefly III',
    'no_valid_opposing'                           => 'Conversione non riuscita perché non c\'è un conto valido denominato ":account"',
    'new_notes_empty'                             => 'Le note da impostare sono vuote',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III non può convertire un ":type" in un prelievo',
    'unsupported_transaction_type_deposit'        => 'Firefly III non può convertire un ":type" in un deposito',
    'unsupported_transaction_type_transfer'       => 'Firefly III non può convertire un ":type" in un trasferimento',
    'already_has_source_asset'                    => 'Questa transazione ha già ":name" come conto di origine',
    'already_has_destination_asset'               => 'Questa transazione ha già ":name" come conto di destinazione',
    'already_has_destination'                     => 'Questa transazione ha già ":name" come conto di destinazione',
    'already_has_source'                          => 'Questa transazione ha già ":name" come conto di origine',
    'already_linked_to_subscription'              => 'La transazione è già collegata all\'abbonamento ":name"',
    'already_linked_to_category'                  => 'La transazione è già collegata alla categoria ":name"',
    'already_linked_to_budget'                    => 'La transazione è già collegata al budget ":name"',
    'cannot_find_subscription'                    => 'Firefly III non riesce a trovare l\'abbonamento ":name"',
    'no_notes_to_move'                            => 'La transazione non ha note da spostare nel campo descrizione',
    'no_tags_to_remove'                           => 'La transazione non ha etichette da rimuovere',
    'not_withdrawal'                              => 'The transaction is not a withdrawal',
    'not_deposit'                                 => 'The transaction is not a deposit',
    'cannot_find_tag'                             => 'Firefly III non riesce a trovare l\'etichetta ":tag"',
    'cannot_find_asset'                           => 'Firefly III non riesce a trovare il conto attività ":name"',
    'cannot_find_accounts'                        => 'Firefly III non riesce a trovare il conto di origine o destinazione',
    'cannot_find_source_transaction'              => 'Firefly III non riesce a trovare la transazione di origine',
    'cannot_find_destination_transaction'         => 'Firefly III non riesce a trovare la transazione di destinazione',
    'cannot_find_source_transaction_account'      => 'Firefly III non riesce a trovare il conto di origine della transazione',
    'cannot_find_destination_transaction_account' => 'Firefly III non riesce a trovare il conto di destinazione della transazione',
    'cannot_find_piggy'                           => 'Firefly III non riesce a trovare un salvadanaio ":name"',
    'no_link_piggy'                               => 'I conti di questa transazione non sono collegati alla salvadanaio, quindi non verrà intrapresa alcuna azione',
    'cannot_unlink_tag'                           => 'L\'etichetta ":tag" non è collegata a questa transazione',
    'cannot_find_budget'                          => 'Firefly III non riesce a trovare il budget ":name"',
    'cannot_find_category'                        => 'Firefly III non riesce a trovare la categoria ":name"',
    'cannot_set_budget'                           => 'Firefly III non può impostare il budget ":name" a una transazione di tipo ":type"',
];
