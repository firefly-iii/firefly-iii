<?php

namespace Firefly\Helper\Controllers;


interface RecurringInterface {
    /**
     * Returns messages about the validation.
     *
     * @param array $data
     *
     * @return array
     */
    public function validate(array $data);
} 