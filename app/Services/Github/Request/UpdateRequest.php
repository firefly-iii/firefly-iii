<?php
/**
 * UpdateRequest.php
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

namespace FireflyIII\Services\Github\Request;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Github\Object\Release;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;
use RuntimeException;
use SimpleXMLElement;

/**
 * Class UpdateRequest
 *
 * @codeCoverageIgnore
 * @deprecated
 */
class UpdateRequest implements GithubRequest
{
    /** @var array */
    private $releases = [];

    /**
     *
     * @throws FireflyException
     */
    public function call(): void
    {
        $uri = 'https://github.com/firefly-iii/firefly-iii/releases.atom';
        Log::debug(sprintf('Going to call %s', $uri));
        try {
            $client = new Client();
            $res    = $client->request('GET', $uri);
        } catch (GuzzleException|Exception $e) {
            throw new FireflyException(sprintf('Response error from Github: %s', $e->getMessage()));
        }

        if (200 !== $res->getStatusCode()) {
            throw new FireflyException(sprintf('Returned code %d.', $res->getStatusCode()));
        }
        try {
            $releaseXml = new SimpleXMLElement($res->getBody()->getContents(), LIBXML_NOCDATA);
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not get body from github updat result: %s', $e->getMessage()));
            throw new FireflyException(sprintf('Could not get body from github updat result: %s', $e->getMessage()));
        }

        //fetch the products for each category
        if (isset($releaseXml->entry)) {
            Log::debug(sprintf('Count of entries is: %d', count($releaseXml->entry)));
            foreach ($releaseXml->entry as $entry) {
                $array = [
                    'id'      => (string)$entry->id,
                    'updated' => (string)$entry->updated,
                    'title'   => (string)$entry->title,
                    'content' => (string)$entry->content,
                ];
                Log::debug(sprintf('Found version %s', $entry->title));
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
