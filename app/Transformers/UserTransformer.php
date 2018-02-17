<?php
/**
 * UserTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;


use FireflyIII\Models\Role;
use FireflyIII\User;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class UserTransformer
 */
class UserTransformer extends TransformerAbstract
{
    /** @var ParameterBag */
    protected $parameters;

    /**
     * UserTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Transform user.
     *
     * @param User $user
     *
     * @return array
     */
    public function transform(User $user): array
    {
        /** @var Role $role */
        $role = $user->roles()->first();
        if (!is_null($role)) {
            $role = $role->name;
        }

        return [
            'id'           => (int)$user->id,
            'updated_at'   => $user->updated_at->toAtomString(),
            'created_at'   => $user->created_at->toAtomString(),
            'email'        => $user->email,
            'blocked'      => intval($user->blocked) === 1,
            'blocked_code' => $user->blocked_code,
            'role'         => $role,
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => '/users/' . $user->id,
                ],
            ],
        ];
    }

}