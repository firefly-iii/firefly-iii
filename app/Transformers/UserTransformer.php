<?php

/**
 * UserTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;

/**
 * Class UserTransformer
 */
class UserTransformer extends AbstractTransformer
{
    private UserRepositoryInterface $repository;

    /**
     * Transform user.
     *
     * @throws FireflyException
     */
    public function transform(User $user): array
    {
        $this->repository ??= app(UserRepositoryInterface::class);

        return [
            'id'           => (int)$user->id,
            'created_at'   => $user->created_at->toAtomString(),
            'updated_at'   => $user->updated_at->toAtomString(),
            'email'        => $user->email,
            'blocked'      => 1 === (int)$user->blocked,
            'blocked_code' => '' === $user->blocked_code ? null : $user->blocked_code,
            'role'         => $this->repository->getRoleByUser($user),
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => '/users/'.$user->id,
                ],
            ],
        ];
    }
}
