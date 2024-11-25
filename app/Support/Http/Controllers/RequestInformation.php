<?php

/**
 * RequestInformation.php
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

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Http\Requests\RuleFormRequest;
use FireflyIII\Http\Requests\TestRuleFormRequest;
use FireflyIII\Support\Binder\AccountList;
use FireflyIII\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Route as RouteFacade;

/**
 * Trait RequestInformation
 */
trait RequestInformation
{
    /**
     * Get the domain of FF system.
     */
    final protected function getDomain(): string // get request info
    {
        $url   = url()->to('/');
        $parts = parse_url($url);

        return $parts['host'] ?? '';
    }

    /**
     * Get a list of triggers.
     */
    final protected function getValidTriggerList(TestRuleFormRequest $request): array // process input
    {
        $triggers = [];
        $data     = $request->get('triggers');
        if (is_array($data)) {
            foreach ($data as $triggerInfo) {
                $current    = [
                    'type'            => $triggerInfo['type'] ?? '',
                    'value'           => $triggerInfo['value'] ?? '',
                    'prohibited'      => $triggerInfo['prohibited'] ?? false,
                    'stop_processing' => 1 === (int)($triggerInfo['stop_processing'] ?? '0'),
                ];
                $current    = RuleFormRequest::replaceAmountTrigger($current);
                $triggers[] = $current;
            }
        }

        return $triggers;
    }

    /**
     * Returns if user has seen demo.
     */
    final protected function hasSeenDemo(): bool // get request info + get preference
    {
        $page         = $this->getPageName();
        $specificPage = $this->getSpecificPageName();
        // indicator if user has seen the help for this page ( + special page):
        $key          = sprintf('shown_demo_%s%s', $page, $specificPage);
        // is there an intro for this route?
        $intro        = config(sprintf('intro.%s', $page)) ?? [];
        $specialIntro = config(sprintf('intro.%s%s', $page, $specificPage)) ?? [];
        // some routes have a "what" parameter, which indicates a special page:

        $shownDemo    = true;
        // both must be array and either must be > 0
        if (count($intro) > 0 || count($specialIntro) > 0) {
            $shownDemo = app('preferences')->get($key, false)->data;
        }
        if (!is_bool($shownDemo)) {
            $shownDemo = true;
        }

        return $shownDemo;
    }

    final protected function getPageName(): string // get request info
    {
        return str_replace('.', '_', RouteFacade::currentRouteName());
    }

    /**
     * Get the specific name of a page for intro.
     */
    final protected function getSpecificPageName(): string // get request info
    {
        /** @var null|string $param */
        $param = RouteFacade::current()->parameter('objectType');

        return null === $param ? '' : sprintf('_%s', $param);
    }

    /**
     * Check if date is outside session range.
     */
    final protected function notInSessionRange(Carbon $date): bool // Validate a preference
    {
        /** @var Carbon $start */
        $start  = session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end    = session('end', today(config('app.timezone'))->endOfMonth());
        $result = false;
        if ($start->greaterThanOrEqualTo($date) && $end->greaterThanOrEqualTo($date)) {
            $result = true;
        }
        // start and end in the past? use $end
        if ($start->lessThanOrEqualTo($date) && $end->lessThanOrEqualTo($date)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Parses attributes from URL
     */
    final protected function parseAttributes(array $attributes): array // parse input + return result
    {
        $attributes['location'] ??= '';
        $attributes['accounts']  = AccountList::routeBinder($attributes['accounts'] ?? '', new Route('get', '', []));
        $date                    = Carbon::createFromFormat('Ymd', $attributes['startDate']);
        if (null === $date) {
            $date = today(config('app.timezone'));
        }
        $date->startOfMonth();
        $attributes['startDate'] = $date;

        $date2                   = Carbon::createFromFormat('Ymd', $attributes['endDate']);
        if (null === $date2) {
            $date2 = today(config('app.timezone'));
        }
        $date2->endOfDay();
        $attributes['endDate']   = $date2;

        return $attributes;
    }

    /**
     * Validate users new password.
     *
     * @throws ValidationException
     */
    final protected function validatePassword(User $user, string $current, string $new): bool // get request info
    {
        if (!\Hash::check($current, $user->password)) {
            throw new ValidationException((string)trans('firefly.invalid_current_password'));
        }

        if ($current === $new) {
            throw new ValidationException((string)trans('firefly.should_change'));
        }

        return true;
    }

    /**
     * Get a validator for an incoming registration request.
     */
    final protected function validator(array $data): ValidatorContract
    {
        return Validator::make(
            $data,
            [
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:16|secure_password|confirmed',
            ]
        );
    }
}
