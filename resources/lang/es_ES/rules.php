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
    'main_message'                                => 'Acción ":action", presente en la regla ":rule", no se pudo aplicar a la transacción #:group: :error',
    'find_or_create_tag_failed'                   => 'No se pudo encontrar o crear la etiqueta ":tag"',
    'tag_already_added'                           => 'La etiqueta ":tag" ya está vinculada a esta transacción',
    'inspect_transaction'                         => 'Inspeccionar transacción ":title" @ Firefly III',
    'inspect_rule'                                => 'Inspeccionar regla ":title" @ Firefly III',
    'journal_other_user'                          => 'Esta transacción no pertenece al usuario',
    'no_such_journal'                             => 'Esta transacción no existe',
    'journal_already_no_budget'                   => 'Esta transacción no tiene presupuesto, por lo que no se puede eliminar',
    'journal_already_no_category'                 => 'Esta transacción no tenía categoría, por lo que no se puede eliminar',
    'journal_already_no_notes'                    => 'Esta transacción no tenía notas, por lo que no se pueden eliminar',
    'journal_not_found'                           => 'Firefly III no puede encontrar la transacción solicitada',
    'split_group'                                 => 'Firefly III no puede ejecutar esta acción en una transacción con múltiples divisiones',
    'is_already_withdrawal'                       => 'Esta transferencia ya es un gasto',
    'is_already_deposit'                          => 'Esta transacción ya es un ingreso',
    'is_already_transfer'                         => 'Esta transacción ya es una transferencia',
    'is_not_transfer'                             => 'Esta transacción no es una transferencia',
    'complex_error'                               => 'Algo complicado salió mal. Lo sentimos. Por favor inspeccione los registros de Firefly III',
    'no_valid_opposing'                           => 'La conversión falló porque no hay una cuenta válida llamada ":account"',
    'new_notes_empty'                             => 'Las notas a establecer están vacías',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III no puede convertir un ":type" en un gasto',
    'unsupported_transaction_type_deposit'        => 'Firefly III no puede convertir un ":type" en un ingreso',
    'unsupported_transaction_type_transfer'       => 'Firefly III no puede convertir un ":type" en una transferencia',
    'already_has_source_asset'                    => 'Esta transacción ya tiene ":name" como la cuenta de activo origen',
    'already_has_destination_asset'               => 'Esta transacción ya tiene ":name" como cuenta de activo de destino',
    'already_has_destination'                     => 'Esta transacción ya tiene ":name" como cuenta de destino',
    'already_has_source'                          => 'Esta transacción ya tiene ":name" como cuenta de origen',
    'already_linked_to_subscription'              => 'La transacción ya está vinculada a la suscripción ":name"',
    'already_linked_to_category'                  => 'La transacción ya está vinculada a la categoría ":name"',
    'already_linked_to_budget'                    => 'La transacción ya está vinculada al presupuesto ":name"',
    'cannot_find_subscription'                    => 'Firefly III no puede encontrar la suscripción ":name"',
    'no_notes_to_move'                            => 'La transacción no tiene notas para mover al campo de descripción',
    'no_tags_to_remove'                           => 'La transacción no tiene etiquetas que eliminar',
    'not_withdrawal'                              => 'The transaction is not a withdrawal',
    'not_deposit'                                 => 'The transaction is not a deposit',
    'cannot_find_tag'                             => 'Firefly III no puede encontrar la etiqueta ":tag"',
    'cannot_find_asset'                           => 'Firefly III no puede encontrar la cuenta de activo ":name"',
    'cannot_find_accounts'                        => 'Firefly III no puede encontrar la cuenta de origen o destino',
    'cannot_find_source_transaction'              => 'Firefly III no puede encontrar la transacción de origen',
    'cannot_find_destination_transaction'         => 'Firefly III no puede encontrar la transacción de destino',
    'cannot_find_source_transaction_account'      => 'Firefly III no puede encontrar la cuenta de transacción origen',
    'cannot_find_destination_transaction_account' => 'Firefly III no puede encontrar la cuenta de transacción de destino',
    'cannot_find_piggy'                           => 'Firefly III no puede encontrar la hucha llamada ":name"',
    'no_link_piggy'                               => 'Las cuentas de esta transacción no están vinculadas a la hucha, por lo que no se realizará ninguna acción',
    'cannot_unlink_tag'                           => 'La etiqueta ":tag" no está vinculada a esta transacción',
    'cannot_find_budget'                          => 'Firefly III no puede encontrar el presupuesto ":name"',
    'cannot_find_category'                        => 'Firefly III no puede encontrar la categoría ":name"',
    'cannot_set_budget'                           => 'Firefly III no puede establecer el presupuesto ":name" a una transacción de tipo ":type"',
];
