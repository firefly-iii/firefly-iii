<?php

/**
 * ExpandedForm.php
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

namespace FireflyIII\Support;

use Eloquent;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Form\FormSupport;
use Illuminate\Support\Collection;

/**
 * Class ExpandedForm.
 */
class ExpandedForm
{
    use FormSupport;

    /**
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function amountNoCurrency(string $name, $value = null, ?array $options = null): string
    {
        $options ??= [];
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        unset($options['currency'], $options['placeholder']);

        // make sure value is formatted nicely:
        // if (null !== $value && '' !== $value) {
        // $value = round((float)$value, 8);
        // }
        try {
            $html = view('form.amount-no-currency', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render amountNoCurrency(): %s', $e->getMessage()));
            $html = 'Could not render amountNoCurrency.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @param mixed $checked
     *
     * @throws FireflyException
     */
    public function checkbox(string $name, ?int $value = null, $checked = null, ?array $options = null): string
    {
        $options ??= [];
        $value   ??= 1;
        $options['checked'] = true === $checked;

        if (app('session')->has('preFilled')) {
            $preFilled          = session('preFilled');
            $options['checked'] = $preFilled[$name] ?? $options['checked'];
        }

        $label              = $this->label($name, $options);
        $options            = $this->expandOptionArray($name, $label, $options);
        $classes            = $this->getHolderClasses($name);
        $value              = $this->fillFieldValue($name, $value);

        unset($options['placeholder'], $options['autocomplete'], $options['class']);

        try {
            $html = view('form.checkbox', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render checkbox(): %s', $e->getMessage()));
            $html = 'Could not render checkbox.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function date(string $name, $value = null, ?array $options = null): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        unset($options['placeholder']);

        try {
            $html = view('form.date', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render date(): %s', $e->getMessage()));
            $html = 'Could not render date.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @throws FireflyException
     */
    public function file(string $name, ?array $options = null): string
    {
        $options ??= [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);

        try {
            $html = view('form.file', compact('classes', 'name', 'label', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render file(): %s', $e->getMessage()));
            $html = 'Could not render file.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function integer(string $name, $value = null, ?array $options = null): string
    {
        $options         ??= [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $options['step'] ??= '1';

        try {
            $html = view('form.integer', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render integer(): %s', $e->getMessage()));
            $html = 'Could not render integer.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function location(string $name, $value = null, ?array $options = null): string
    {
        $options ??= [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);

        try {
            $html = view('form.location', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render location(): %s', $e->getMessage()));
            $html = 'Could not render location.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    public function makeSelectListWithEmpty(Collection $set): array
    {
        $selectList    = [];
        $selectList[0] = '(none)';
        $fields        = ['title', 'name', 'description'];

        /** @var \Eloquent $entry */
        foreach ($set as $entry) {
            // All Eloquent models have an ID
            $entryId              = $entry->id;
            $current              = $entry->toArray();
            $title                = null;
            foreach ($fields as $field) {
                if (array_key_exists($field, $current) && null === $title) {
                    $title = $current[$field];
                }
            }
            $selectList[$entryId] = $title;
        }

        return $selectList;
    }

    /**
     * @param null $value
     *
     * @throws FireflyException
     */
    public function objectGroup($value = null, ?array $options = null): string
    {
        $name            = 'object_group';
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['rows'] = 4;

        if (null === $value) {
            $value = '';
        }

        try {
            $html = view('form.object_group', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render objectGroup(): %s', $e->getMessage()));
            $html = 'Could not render objectGroup.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @throws FireflyException
     */
    public function optionsList(string $type, string $name): string
    {
        try {
            $html = view('form.options', compact('type', 'name'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render select(): %s', $e->getMessage()));
            $html = 'Could not render optionsList.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @throws FireflyException
     */
    public function password(string $name, ?array $options = null): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);

        try {
            $html = view('form.password', compact('classes', 'name', 'label', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render password(): %s', $e->getMessage()));
            $html = 'Could not render password.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @throws FireflyException
     */
    public function passwordWithValue(string $name, string $value, ?array $options = null): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);

        try {
            $html = view('form.password', compact('classes', 'value', 'name', 'label', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render passwordWithValue(): %s', $e->getMessage()));
            $html = 'Could not render passwordWithValue.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * Function to render a percentage.
     *
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function percentage(string $name, $value = null, ?array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        unset($options['placeholder']);

        try {
            $html = view('form.percentage', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render percentage(): %s', $e->getMessage()));
            $html = 'Could not render percentage.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function staticText(string $name, $value, ?array $options = null): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);

        try {
            $html = view('form.static', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render staticText(): %s', $e->getMessage()));
            $html = 'Could not render staticText.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function text(string $name, $value = null, ?array $options = null): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);

        try {
            $html = view('form.text', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render text(): %s', $e->getMessage()));
            $html = 'Could not render text.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function textarea(string $name, $value = null, ?array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['rows'] = 4;

        if (null === $value) {
            $value = '';
        }

        try {
            $html = view('form.textarea', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render textarea(): %s', $e->getMessage()));
            $html = 'Could not render textarea.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }
}
