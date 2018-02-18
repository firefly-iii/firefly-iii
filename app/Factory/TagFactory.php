<?php
/**
 * TagFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Models\Tag;
use FireflyIII\User;

/**
 * Class TagFactory
 */
class TagFactory
{
    /** @var User */
    private $user;

    /**
     * TagFactory constructor.
     */
    public function __construct()
    {

    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Tag|null
     */
    public function create(array $data): ?Tag
    {
        return Tag::create(
            [
                'user_id' => $data['user']->id,
                'tag' => $data['tag'],
                'tagMode' => 'nothing',
                'date' => $data['date'],
                'description'=> $data['description'],

            ]
        );
    }

}