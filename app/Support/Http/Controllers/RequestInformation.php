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
use FireflyIII\Helpers\Help\HelpInterface;
use FireflyIII\Http\Requests\TestRuleFormRequest;
use FireflyIII\Support\Binder\AccountList;
use FireflyIII\User;
use Hash;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Log;
use Route as RouteFacade;

/**
 * Trait RequestInformation
 *
 */
trait RequestInformation
{


    /**
     * Get the domain of FF system.
     *
     * @return string
     */
    protected function getDomain(): string // get request info
    {
        $url   = url()->to('/');
        $parts = parse_url($url);

        return $parts['host'];
    }

    /**
     * Gets the help text.
     *
     * @param string $route
     * @param string $language
     *
     * @return string
     *
     */
    protected function getHelpText(string $route, string $language): string // get from internet.
    {
        $help = app(HelpInterface::class);
        // get language and default variables.
        $content = '<p>' . trans('firefly.route_has_no_help') . '</p>';

        // if no such route, log error and return default text.
        if (!$help->hasRoute($route)) {
            Log::error('No such route: ' . $route);

            return $content;
        }

        // help content may be cached:
        if ($help->inCache($route, $language)) {
            $content = $help->getFromCache($route, $language);
            Log::debug(sprintf('Help text %s was in cache.', $language));

            return $content;
        }

        // get help content from Github:
        $content          = $help->getFromGitHub($route, $language);
        $originalLanguage = $language;
        // content will have 0 length when Github failed. Try en_US when it does:
        if ('' === $content) {
            $language = 'en_US';

            // also check cache first:
            if ($help->inCache($route, $language)) {
                Log::debug(sprintf('Help text %s was in cache.', $language));
                $content = $help->getFromCache($route, $language);

                return $content;
            }
            $baseHref   = route('index');
            $helpString = sprintf(
                '<p><em><img alt="" src="%s/v1/images/flags/%s.png" /> %s</em></p>', $baseHref, $originalLanguage, (string)trans('firefly.help_translating')
            );
            $content    = $helpString . $help->getFromGitHub($route, $language);
        }

        // help still empty?
        if ('' !== $content) {
            $help->putInCache($route, $language, $content);

            return $content;
        }

        return '<p>' . trans('firefly.route_has_no_help') . '</p>'; // @codeCoverageIgnore
    }

    /**
     * Get user's language.
     *
     * @return string
     */
    protected function getLanguage(): string // get preference
    {
        /** @var string $language */
        $language = app('preferences')->get('language', config('firefly.default_language', 'en_US'))->data;

        return $language;
    }

    /**
     * Get a list of triggers.
     *
     * @param TestRuleFormRequest $request
     *
     * @return array
     */
    protected function getValidTriggerList(TestRuleFormRequest $request): array // process input
    {
        $triggers = [];
        $data     = $request->get('triggers');
        if (is_array($data)) {
            foreach ($data as $index => $triggerInfo) {
                $triggers[] = [
                    'type'            => $triggerInfo['type'] ?? '',
                    'value'           => $triggerInfo['value'] ?? '',
                    'stop_processing' => 1 === (int)($triggerInfo['stop_processing'] ?? '0'),
                ];
            }
        }

        return $triggers;
    }

    /**
     * Returns if user has seen demo.
     *
     * @return bool
     */
    protected function hasSeenDemo(): bool // get request info + get preference
    {
        $page         = $this->getPageName();
        $specificPage = $this->getSpecificPageName();



        // indicator if user has seen the help for this page ( + special page):
        $key = sprintf('shown_demo_%s%s', $page, $specificPage);
        // is there an intro for this route?
        $intro        = config(sprintf('intro.%s', $page)) ?? [];
        $specialIntro = config(sprintf('intro.%s%s', $page, $specificPage)) ?? [];
        // some routes have a "what" parameter, which indicates a special page:

        $shownDemo = true;
        // both must be array and either must be > 0
        if (count($intro) > 0 || count($specialIntro) > 0) {
            $shownDemo = app('preferences')->get($key, false)->data;
            //Log::debug(sprintf('Check if user has already seen intro with key "%s". Result is %s', $key, var_export($shownDemo, true)));
        }
        if (!is_bool($shownDemo)) {
            $shownDemo = true; // @codeCoverageIgnore
        }

        return $shownDemo;
    }

    /**
     * @return string
     */
    protected function getPageName(): string // get request info
    {
        return str_replace('.', '_', RouteFacade::currentRouteName());
    }

    /**
     * Get the specific name of a page for intro.
     *
     * @return string
     */
    protected function getSpecificPageName(): string // get request info
    {
        return null === RouteFacade::current()->parameter('objectType') ? '' : '_' . RouteFacade::current()->parameter('objectType');
    }

    /**
     * Check if date is outside session range.
     *
     * @param Carbon $date
     *
     * @return bool
     *
     */
    protected function notInSessionRange(Carbon $date): bool // Validate a preference
    {
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end    = session('end', Carbon::now()->endOfMonth());
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
     * Parses attributes from URI.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function parseAttributes(array $attributes): array // parse input + return result
    {
        $attributes['location'] = $attributes['location'] ?? '';
        $attributes['accounts'] = AccountList::routeBinder($attributes['accounts'] ?? '', new Route('get', '', []));
        try {
            $attributes['startDate'] = Carbon::createFromFormat('Ymd', $attributes['startDate'])->startOfDay();
        } catch (InvalidArgumentException $e) {
            Log::debug(sprintf('Not important error message: %s', $e->getMessage()));
            $date                    = Carbon::now()->startOfMonth();
            $attributes['startDate'] = $date;
        }

        try {
            $attributes['endDate'] = Carbon::createFromFormat('Ymd', $attributes['endDate'])->endOfDay();
        } catch (InvalidArgumentException $e) {
            Log::debug(sprintf('Not important error message: %s', $e->getMessage()));
            $date                  = Carbon::now()->startOfMonth();
            $attributes['endDate'] = $date;
        }

        return $attributes;
    }

    /**
     * Validate users new password.
     *
     * @param User $user
     * @param string $current
     * @param string $new
     *
     * @return bool
     *
     * @throws ValidationException
     */
    protected function validatePassword(User $user, string $current, string $new): bool //get request info
    {
        if (!Hash::check($current, $user->password)) {
            throw new ValidationException((string)trans('firefly.invalid_current_password'));
        }

        if ($current === $new) {
            throw new ValidationException((string)trans('firefly.should_change'));
        }

        return true;
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
     * @return ValidatorContract
     * @codeCoverageIgnore
     */
    protected function validator(array $data): ValidatorContract
    {
        return Validator::make(
            $data,
            [
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|secure_password|confirmed',
            ]
        );
    }

}
