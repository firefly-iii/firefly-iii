<?php
/**
 * UpdateRequest.php
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

namespace FireflyIII\Services\Github\Request;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Github\Object\Release;
use Requests;
use SimpleXMLElement;

/**
 * Class UpdateRequest
 */
class UpdateRequest implements GithubRequest
{
    /** @var array */
    private $releases = [];

    /**
     *
     * @throws FireflyException
     */
    public function call()
    {
        $uri = 'https://github.com/firefly-iii/firefly-iii/releases.atom';
        try {
            $response = Requests::get($uri);
        } catch (Exception $e) {
            throw new FireflyException(sprintf('Response error from Github: %s', $e->getMessage()));
        }

        if ($response->status_code !== 200) {
            throw new FireflyException(sprintf('Returned code %d, error: %s', $response->status_code, $response->body));
        }

        $releaseXml = new SimpleXMLElement($response->body, LIBXML_NOCDATA);

        //fetch the products for each category
        if (isset($releaseXml->entry)) {
            foreach ($releaseXml->entry as $entry) {
                $array            = [
                    'id'      => strval($entry->id),
                    'updated' => strval($entry->updated),
                    'title'   => strval($entry->title),
                    'content' => strval($entry->content),
                ];
                $this->releases[] = new Release($array);
            }
        }
    }

    /**
     * @return array
     */
    public function getReleases(): array
    {
        return $this->releases;
    }


}