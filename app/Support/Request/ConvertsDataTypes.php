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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Trait ConvertsDataTypes
 */
trait ConvertsDataTypes
{
    private array $characters
        = [
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
            "\r", // carriage return
        ];

    public function clearIban(?string $string): ?string
    {
        $string = $this->clearString($string);

        return Steam::filterSpaces($string);
    }

    public function clearString(?string $string): ?string
    {
        $string = $this->clearStringKeepNewlines($string);

        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return '';
        }

        // then remove newlines too:
        $string = str_replace(["\r", "\n", "\t", "\036", "\025"], '', $string);

        return trim($string);
    }

    public function clearStringKeepNewlines(?string $string): ?string
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return '';
        }
        $string = str_replace($this->characters, "\x20", $string);

        // clear zalgo text (TODO also in API v2)
        $string = \Safe\preg_replace('/(\pM{2})\pM+/u', '\1', $string);

        return trim((string) $string);
    }

    public function convertIban(string $field): string
    {
        return Steam::filterSpaces($this->convertString($field));
    }

    /**
     * Return string value.
     */
    public function convertString(string $field, string $default = ''): string
    {
        $entry = $this->get($field);
        if (!is_scalar($entry)) {
            return $default;
        }

        return (string) $this->clearString((string) $entry);
    }

    /**
     * Abstract method that always exists in the Request classes that use this
     * trait, OR a stub needs to be added by any other class that uses this train.
     */
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * Return integer value.
     */
    public function convertInteger(string $field): int
    {
        return (int) $this->get($field);
    }

    /**
     * TODO duplicate, see SelectTransactionsRequest
     *
     * Validate list of accounts. This one is for V2 endpoints, so it searches for groups, not users.
     */
    public function getAccountList(): Collection
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        if (method_exists($this, 'validateUserGroup')) { // @phpstan-ignore-line
            $userGroup = $this->validateUserGroup($this);
            $repository->setUserGroup($userGroup);
        }

        // set administration ID
        // group ID

        $set        = $this->get('accounts');
        $collection = new Collection();
        if (is_array($set)) {
            foreach ($set as $accountId) {
                $account = $repository->find((int) $accountId);
                if (null !== $account) {
                    $collection->push($account);
                }
            }
        }

        return $collection;
    }

    /**
     * Return string value with newlines.
     */
    public function stringWithNewlines(string $field): string
    {
        return (string) $this->clearStringKeepNewlines((string) ($this->get($field) ?? ''));
    }

    /**
     * @param mixed $array
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

    protected function convertDateTime(?string $string): ?Carbon
    {
        $value = $this->get((string) $string);
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
            } catch (InvalidDateException $e) { // @phpstan-ignore-line
                app('log')->error(sprintf('[1] "%s" is not a valid date: %s', $value, $e->getMessage()));

                return null;
            } catch (InvalidFormatException $e) { // @phpstan-ignore-line
                app('log')->error(sprintf('[2] "%s" is of an invalid format: %s', $value, $e->getMessage()));

                return null;
            }
            if (null === $carbon) {
                app('log')->error(sprintf('[2] "%s" is of an invalid format.', $value));

                return null;
            }

            return $carbon;
        }

        // is an atom string, I hope?
        try {
            $carbon = Carbon::parse($value);
        } catch (InvalidDateException $e) { // @phpstan-ignore-line
            app('log')->error(sprintf('[3] "%s" is not a valid date or time: %s', $value, $e->getMessage()));

            return null;
        } catch (InvalidFormatException $e) {
            app('log')->error(sprintf('[4] "%s" is of an invalid format: %s', $value, $e->getMessage()));

            return null;
        }

        return $carbon;
    }

    /**
     * Return floating value.
     */
    protected function convertFloat(string $field): ?float
    {
        $res = $this->get($field);
        if (null === $res) {
            return null;
        }

        return (float) $res;
    }

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
            app('log')->debug(sprintf('Invalid date: %s', $string));

            return null;
        }
        app('log')->debug(sprintf('Date object: %s (%s)', $carbon->toW3cString(), $carbon->getTimezone()));

        return $carbon;
    }

    /**
     * Returns all data in the request, or omits the field if not set,
     * according to the config from the request. This is the way.
     */
    protected function getAllData(array $fields): array
    {
        $return = [];
        foreach ($fields as $field => $info) {
            if (true === $this->has($info[0])) {
                $method         = $info[1];
                $return[$field] = $this->{$method}($info[0]); // @phpstan-ignore-line
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
     */
    protected function getCarbonDate(string $field): ?Carbon
    {
        $result = null;

        Log::debug(sprintf('Date string is "%s"', (string) $this->get($field)));

        try {
            $result = '' !== (string) $this->get($field) ? new Carbon((string) $this->get($field), config('app.timezone')) : null;
        } catch (InvalidFormatException $e) {
            // @ignoreException
            Log::debug(sprintf('Exception when parsing date "%s".', $this->get($field)));
        }
        if (null === $result) {
            app('log')->debug(sprintf('Exception when parsing date "%s".', $this->get($field)));
        }

        return $result;
    }

    /**
     * Parse to integer
     */
    protected function integerFromValue(?string $string): ?int
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return null;
        }

        return (int) $string;
    }

    protected function parseAccounts(mixed $array): array
    {
        if (!is_array($array)) {
            return [];
        }
        $return = [];
        foreach ($array as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $amount   = null;
            if (array_key_exists('current_amount', $entry)) {
                $amount = $this->clearString((string) ($entry['current_amount'] ?? '0'));
                if (null === $entry['current_amount']) {
                    $amount = null;
                }
            }
            if (!array_key_exists('current_amount', $entry)) {
                $amount = null;
            }
            $return[] = [
                'account_id'     => $this->integerFromValue((string) ($entry['account_id'] ?? '0')),
                'current_amount' => $amount,
            ];
        }

        return $return;
    }

    protected function floatFromValue(?string $string): ?float
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return null;
        }

        return (float) $string;
    }

    /**
     * Return integer value, or NULL when it's not set.
     */
    protected function nullableInteger(string $field): ?int
    {
        if (false === $this->has($field)) {
            return null;
        }

        $value = (string) $this->get($field);
        if ('' === $value) {
            return null;
        }

        return (int) $value;
    }
}
