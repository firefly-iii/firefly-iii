<?php
/**
 * EncryptService.php
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

namespace FireflyIII\Services\Internal\File;

use FireflyIII\Exceptions\FireflyException;
use Crypt;

/**
 * Class EncryptService
 */
class EncryptService
{
    /**
     * @param string $file
     * @param string $key
     *
     * @throws FireflyException
     */
    public function encrypt(string $file, string $key): void
    {
        if (!file_exists($file)) {
            throw new FireflyException(sprintf('File "%s" does not seem to exist.', $file));
        }
        $content = file_get_contents($file);
        $content = Crypt::encrypt($content);
        $newName = sprintf('%s.upload', $key);
        $path    = storage_path('upload') . '/' . $newName;

        file_put_contents($path, $content);
    }

}
