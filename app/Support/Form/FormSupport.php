<?php
/**
 * FormSupport.php
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

namespace FireflyIII\Support\Form;

use Carbon\Carbon;
use Exception;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;
use RuntimeException;
use Throwable;

/**
 * Trait FormSupport
 */
trait FormSupport
{
    /**
     * @param string     $name
     * @param array|null $list
     * @param mixed      $selected
     * @param array|null $options
     *
     * @return string
     */
    public function select(string $name, array $list = null, $selected = null, array $options = null): string
    {
        $list     = $list ?? [];
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);
        unset($options['autocomplete'], $options['placeholder']);
        try {
            $html = prefixView('form.select', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();
        } catch (Throwable $e) { // @phpstan-ignore-line
            Log::debug(sprintf('Could not render select(): %s', $e->getMessage()));
            $html = 'Could not render select.';
        }

        return $html;
    }

    /**
     * @param string     $name
     * @param array|null $options
     *
     * @return string
     */
    protected function label(string $name, array $options = null): string
    {
        $options = $options ?? [];
        if (array_key_exists('label', $options)) {
            return $options['label'];
        }
        $name = str_replace('[]', '', $name);

        return (string)trans('form.' . $name);
    }

    /**
     * @param string     $name
     * @param mixed      $label
     * @param array|null $options
     *
     * @return array
     */
    protected function expandOptionArray(string $name, $label, array $options = null): array
    {
        $options                 = $options ?? [];
        $name                    = str_replace('[]', '', $name);
        $options['class']        = 'form-control';
        $options['id']           = 'ffInput_' . $name;
        $options['autocomplete'] = 'off';
        $options['placeholder']  = ucfirst($label);

        return $options;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getHolderClasses(string $name): string
    {
        // Get errors from session:
        /** @var MessageBag $errors */
        $errors  = session('errors');
        $classes = 'form-group';

        if (null !== $errors && $errors->has($name)) {
            $classes = 'form-group has-error has-feedback';
        }

        return $classes;
    }

    /**
     * @param string     $name
     * @param mixed|null $value
     *
     * @return mixed
     */
    protected function fillFieldValue(string $name, $value = null)
    {
        if (app('session')->has('preFilled')) {
            $preFilled = session('preFilled');
            $value     = array_key_exists($name, $preFilled) && null === $value ? $preFilled[$name] : $value;
        }

        try {
            if (null !== request()->old($name)) {
                $value = request()->old($name);
            }
        } catch (RuntimeException $e) { // @phpstan-ignore-line
            // don't care about session errors.
            Log::debug(sprintf('Run time: %s', $e->getMessage()));
        }

        if ($value instanceof Carbon) {
            $value = $value->format('Y-m-d');
        }

        return $value;
    }

    /**
     * @return AccountRepositoryInterface
     */
    protected function getAccountRepository(): AccountRepositoryInterface
    {
        return app(AccountRepositoryInterface::class);
    }

    /**
     * @return Carbon
     */
    protected function getDate(): Carbon
    {
        /** @var Carbon $date */
        $date = null;
        try {
            $date = today(config('app.timezone'));
        } catch (Exception $e) { // @phpstan-ignore-line
            // @ignoreException
        }

        return $date;
    }
}
