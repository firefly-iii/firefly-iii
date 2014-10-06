<?php

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;
use Exception;
use Illuminate\Support\MessageBag;

class Recurring implements RecurringInterface
{
    /**
     * Returns messages about the validation.
     *
     * @param array $data
     *
     * @return array
     */
    public function validate(array $data)
    {
        $errors = new MessageBag;
        $warnings = new MessageBag;
        $successes = new MessageBag;

        /*
         * Name:
         */
        if (strlen($data['name']) == 0) {
            $errors->add('name', 'The name should not be this short.');
        }
        if (strlen($data['name']) > 250) {
            $errors->add('name', 'The name should not be this long.');
        }
        if (!   isset($data['id'])) {
            $count = \Auth::user()->recurringtransactions()->whereName($data['name'])->count();
        } else {
            $count = \Auth::user()->recurringtransactions()->whereName($data['name'])->where('id', '!=', $data['id'])->count();
        }
        if ($count > 0) {
            $errors->add('name', 'A recurring transaction with this name already exists.');
        }
        if (count($errors->get('name')) == 0) {
            $successes->add('name', 'OK!');
        }

        /*
         * Match
         */
        if (count(explode(',', $data['match'])) > 10) {
            $warnings->add('match', 'This many matches is pretty pointless');
        }
        if (strlen($data['match']) == 0) {
            $errors->add('match', 'Cannot match on nothing.');
        }
        if (count($errors->get('match')) == 0) {
            $successes->add('match', 'OK!');
        }

        /*
         * Amount
         */
        if (floatval($data['amount_max']) == 0 && floatval($data['amount_min']) == 0) {
            $errors->add('amount_min', 'Amount max and min cannot both be zero.');
            $errors->add('amount_max', 'Amount max and min cannot both be zero.');
        }

        if (floatval($data['amount_max']) < floatval($data['amount_min'])) {
            $errors->add('amount_max', 'Amount max must be more than amount min.');
        }

        if (floatval($data['amount_min']) > floatval($data['amount_max'])) {
            $errors->add('amount_max', 'Amount min must be less than amount max.');
        }
        if (count($errors->get('amount_min')) == 0) {
            $successes->add('amount_min', 'OK!');
        }
        if (count($errors->get('amount_max')) == 0) {
            $successes->add('amount_max', 'OK!');
        }


        /*
         * Date
         */
        try {
            $date = new Carbon($data['date']);
        } catch (Exception $e) {
            $errors->add('date', 'The date entered was invalid');
        }
        if (strlen($data['date']) == 0) {
            $errors->add('date', 'The date entered was invalid');
        }
        if (!$errors->has('date')) {
            $successes->add('date', 'OK!');
        }

        $successes->add('active', 'OK!');
        $successes->add('automatch', 'OK!');

        if (intval($data['skip']) < 0) {
            $errors->add('skip', 'Cannot be below zero.');
        } else if (intval($data['skip']) > 31) {
            $errors->add('skip', 'Cannot be above 31.');
        }
        if (count($errors->get('skip')) == 0) {
            $successes->add('skip', 'OK!');
        }

        $set = \Config::get('firefly.budget_periods');
        if (!in_array($data['repeat_freq'], $set)) {
            $errors->add('repeat_freq', 'Invalid value.');
        }
        if (count($errors->get('repeat_freq')) == 0) {
            $successes->add('repeat_freq', 'OK!');
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];

    }
} 