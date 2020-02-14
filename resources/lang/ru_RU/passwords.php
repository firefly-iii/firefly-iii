<?php

/**
 * passwords.php
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
    'password' => 'Пароль должен содержать не менее 6 символов. Пароль и его подтверждение должны совпадать.',
    'user'     => 'Мы не можем найти пользователя с таким e-mail.',
    'token'    => 'Это неправильный ключ для сброса пароля.',
    'sent'     => 'Мы отправили ссылку для сброса пароля на ваш e-mail!',
    'reset'    => 'Ваш пароль был успешно сброшен!',
    'blocked'  => 'Это была хорошая попытка.',
];
