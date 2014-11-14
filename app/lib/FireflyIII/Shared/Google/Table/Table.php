<?php

namespace FireflyIII\Shared\Google\Table;


use Illuminate\Support\Collection;

interface Table {

    public function generate();

    public function addData(Collection $data);
} 