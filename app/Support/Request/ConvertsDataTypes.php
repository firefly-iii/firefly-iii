<?php
/**
 * ConvertsDataTypes.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\Request;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Repositories\Administration\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Trait ConvertsDataTypes
 */
trait ConvertsDataTypes
{
    /**
     * Return integer value.
     *
     * @param string $field
     *
     * @return int
     */
    public function convertInteger(string $field): int
    {
        return (int)$this->get($field);
    }

    /**
     * Abstract method that always exists in the Request classes that use this
     * trait, OR a stub needs to be added by any other class that uses this train.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * Return string value.
     *
     * @param string $field
     *
     * @return string
     */
    public function convertString(string $field): string
    {
        $entry = $this->get($field);
        if (!is_scalar($entry)) {
            return '';
        }
        return $this->clearString((string)$entry, false);
    }

    /**
     * @param string|null $string
     * @param bool        $keepNewlines
     *
     * @return string|null
     */
    public function clearString(?string $string, bool $keepNewlines = true): ?string
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return '';
        }
        $search  = [
            "\0", // NUL
            "\f", // form feed
            "\v", // vertical tab
            "\u{0001}", // start of heading
            "\u{0002}", // start of text
            "\u{0003}", // end of text
            "\u{0004}", // end of transmission
            "\u{0005}", // enquiry
            "\u{0006}", // ACK
            "\u{0007}", // BEL
            "\u{0008}", // backspace
            "\u{000E}", // shift out
            "\u{000F}", // shift in
            "\u{0010}", // data link escape
            "\u{0011}", // DC1
            "\u{0012}", // DC2
            "\u{0013}", // DC3
            "\u{0014}", // DC4
            "\u{0015}", // NAK
            "\u{0016}", // SYN
            "\u{0017}", // ETB
            "\u{0018}", // CAN
            "\u{0019}", // EM
            "\u{001A}", // SUB
            "\u{001B}", // escape
            "\u{001C}", // file separator
            "\u{001D}", // group separator
            "\u{001E}", // record separator
            "\u{001F}", // unit separator
            "\u{007F}", // DEL
            "\u{00A0}", // non-breaking space
            "\u{1680}", // ogham space mark
            "\u{180E}", // mongolian vowel separator
            "\u{2000}", // en quad
            "\u{2001}", // em quad
            "\u{2002}", // en space
            "\u{2003}", // em space
            "\u{2004}", // three-per-em space
            "\u{2005}", // four-per-em space
            "\u{2006}", // six-per-em space
            "\u{2007}", // figure space
            "\u{2008}", // punctuation space
            "\u{2009}", // thin space
            "\u{200A}", // hair space
            "\u{200B}", // zero width space
            "\u{202F}", // narrow no-break space
            "\u{3000}", // ideographic space
            "\u{FEFF}", // zero width no -break space
        ];
        $replace = "\x20"; // plain old normal space
        $string  = str_replace($search, $replace, $string);

        $secondSearch = $keepNewlines ? ["\r"] : ["\r", "\n", "\t", "\036", "\025"];
        $string       = str_replace($secondSearch, '', $string);

        // clear zalgo text (TODO also in API v2)
        $string = preg_replace('/(\pM{2})\pM+/u', '\1', $string);
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return '';
        }
        return trim($string);
    }

    /**
     * TODO duplicate, see SelectTransactionsRequest
     *
     * Validate list of accounts. This one is for V2 endpoints, so it searches for groups, not users.
     *
     * @return Collection
     */
    public function getAccountList(): Collection
    {
        // fixed
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        // set administration ID
        // group ID
        $administrationId = auth()->user()->getAdministrationId();
        $repository->setAdministrationId($administrationId);

        $set        = $this->get('accounts');
        $collection = new Collection();
        if (is_array($set)) {
            foreach ($set as $accountId) {
                $account = $repository->find((int)$accountId);
                if (null !== $account) {
                    $collection->push($account);
                }
            }
        }

        return $collection;
    }

    /**
     * Return string value with newlines.
     *
     * @param string $field
     *
     * @return string
     */
    public function stringWithNewlines(string $field): string
    {
        return $this->clearString((string)($this->get($field) ?? ''));
    }

    /**
     * @param mixed $array
     *
     * @return array|null
     */
    protected function arrayFromValue($array): ?array
    {
        if (is_array($array)) {
            return $array;
        }
        if (null === $array) {
            return null;
        }
        if (is_string($array)) {
            return explode(',', $array);
        }

        return null;
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    protected function convertBoolean(?string $value): bool
    {
        if (null === $value) {
            return false;
        }
        if ('' === $value) {
            return false;
        }
        if ('true' === $value) {
            return true;
        }
        if ('yes' === $value) {
            return true;
        }
        if ('1' === $value) {
            return true;
        }

        return false;
    }

    /**
     * @param string|null $string
     *
     * @return Carbon|null
     */
    protected function convertDateTime(?string $string): ?Carbon
    {
        $value = $this->get($string);
        if (null === $value) {
            return null;
        }
        if ('' === $value) {
            return null;
        }
        if (10 === strlen($value)) {
            // probably a date format.
            try {
                $carbon = Carbon::createFromFormat('Y-m-d', $value);
            } catch (InvalidDateException $e) {
                Log::error(sprintf('[1] "%s" is not a valid date: %s', $value, $e->getMessage()));
                return null;
            } catch (InvalidFormatException $e) {
                Log::error(sprintf('[2] "%s" is of an invalid format: %s', $value, $e->getMessage()));

                return null;
            }
            return $carbon;
        }
        // is an atom string, I hope?
        try {
            $carbon = Carbon::parse($value);
        } catch (InvalidDateException $e) {
            Log::error(sprintf('[3] "%s" is not a valid date or time: %s', $value, $e->getMessage()));

            return null;
        } catch (InvalidFormatException $e) {
            Log::error(sprintf('[4] "%s" is of an invalid format: %s', $value, $e->getMessage()));

            return null;
        }
        return $carbon;
    }

    /**
     * Return floating value.
     *
     * @param string $field
     *
     * @return float|null
     */
    protected function convertFloat(string $field): ?float
    {
        $res = $this->get($field);
        if (null === $res) {
            return null;
        }

        return (float)$res;
    }

    /**
     * @param string|null $string
     *
     * @return Carbon|null
     */
    protected function dateFromValue(?string $string): ?Carbon
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return null;
        }
        $carbon = null;
        try {
            $carbon = new Carbon($string, config('app.timezone'));
        } catch (InvalidFormatException $e) {
            // @ignoreException
        }
        if (null === $carbon) {
            Log::debug(sprintf('Invalid date: %s', $string));

            return null;
        }
        Log::debug(sprintf('Date object: %s (%s)', $carbon->toW3cString(), $carbon->getTimezone()));

        return $carbon;
    }

    /**
     * Returns all data in the request, or omits the field if not set,
     * according to the config from the request. This is the way.
     *
     * @param array $fields
     *
     * @return array
     */
    protected function getAllData(array $fields): array
    {
        $return = [];
        foreach ($fields as $field => $info) {
            if ($this->has($info[0])) {
                $method         = $info[1];
                $return[$field] = $this->$method($info[0]);
            }
        }

        return $return;
    }

    /**
     * Abstract method that always exists in the Request classes that use this
     * trait, OR a stub needs to be added by any other class that uses this train.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    abstract public function has($key);

    /**
     * Return date or NULL.
     *
     * @param string $field
     *
     * @return Carbon|null
     */
    protected function getCarbonDate(string $field): ?Carbon
    {
        $result = null;
        try {
            $result = $this->get($field) ? new Carbon($this->get($field), config('app.timezone')) : null;
        } catch (InvalidFormatException $e) {
            // @ignoreException
        }
        if (null === $result) {
            Log::debug(sprintf('Exception when parsing date "%s".', $this->get($field)));
        }

        return $result;
    }

    /**
     * Parse to integer
     *
     * @param string|null $string
     *
     * @return int|null
     */
    protected function integerFromValue(?string $string): ?int
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return null;
        }

        return (int)$string;
    }

    /**
     * Return integer value, or NULL when it's not set.
     *
     * @param string $field
     *
     * @return int|null
     */
    protected function nullableInteger(string $field): ?int
    {
        if (!$this->has($field)) {
            return null;
        }

        $value = (string)$this->get($field);
        if ('' === $value) {
            return null;
        }

        return (int)$value;
    }
}
