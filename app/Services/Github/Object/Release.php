<?php
/**
 * Release.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Github\Object;

use Carbon\Carbon;


/**
 * Class Release
 */
class Release extends GithubObject
{
    /** @var string */
    private $content;
    /** @var string */
    private $id;
    /** @var string */
    private $title;
    /** @var Carbon */
    private $updated;

    /**
     * Release constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id      = $data['id'];
        $this->updated = new Carbon($data['updated']);
        $this->title   = $data['title'];
        $this->content = $data['content'];
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return Carbon
     */
    public function getUpdated(): Carbon
    {
        return $this->updated;
    }


}