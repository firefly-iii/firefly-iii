<?php
/*
 * ReleaseNotesController.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\System;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

final class ReleaseNotesController extends Controller
{
    /**
     * @throws FireflyException
     */
    public function index(): JsonResponse
    {
        $version = trim(str_replace('/', '-', config('firefly.version')));
        $disk    = Storage::disk('release-notes');
        $file    = sprintf('%s.md', $version);
        $notes   = null;
        $parsed  = null;
        if ($disk->exists($file)) {
            $notes  = trim((string)$disk->get($file));
            $parsed = trim(parse_markdown($notes));
        }

        return response()->json(
            [
                'version' =>config('firefly.version'),
                'release_notes'          => $parsed,
                'release_notes_markdown' => $notes,
            ]
        );
    }

}
