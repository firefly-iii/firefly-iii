<?php
/*
 * SafeDecrypter.php
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

namespace FireflyIII\Support\System;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use function Safe\unserialize;

/**
 * Decrypts values that Firefly III stored with Crypt::encrypt() / encrypt() (attachments before 4.8.1, encrypted
 * preferences and configuration values) without letting unserialize() create objects.
 *
 * The stored bytes are not always written by Firefly III itself: API attachment uploads are stored as sent, and
 * the preferences API stores any string. Whoever knows APP_KEY can make those bytes a valid Laravel payload, and
 * Crypt::decrypt() with its default unserialize=true would then instantiate arbitrary classes (PHP object injection).
 */
class SafeDecrypter
{
    /**
     * @throws DecryptException
     */
    public static function decrypt(mixed $payload): mixed
    {
        if (!is_string($payload)) {
            throw new DecryptException('The payload is not a string.');
        }
        $plain = Crypt::decrypt($payload, false);
        if ('b:0;' === $plain) {
            return false;
        }
        try {
            $value = unserialize($plain, ['allowed_classes' => false]);
        } catch(\ErrorException $e) {
            throw new DecryptException('The decrypted payload could not be unserialized.', 0, $e);
        }
        if (false === $value || is_object($value)) {
            throw new DecryptException('The decrypted payload is not a serialized scalar or array.');
        }
        return $value;
    }
    /**
     * @throws DecryptException
     */
    public static function decryptString(mixed $payload): string
    {
        $value = self::decrypt($payload);
        if (!is_string($value)) {
            throw new DecryptException('The decrypted payload is not a string.');
        }
        return $value;
    }
}
