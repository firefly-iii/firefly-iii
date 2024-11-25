<?php

/*
 * IndexController.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\UserGroup;

use FireflyIII\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    /**
     * Show all administrations.
     *
     * @return Factory|View
     */
    public function index(Request $request)
    {
        $title         = (string)trans('firefly.administrations_page_title');
        $subTitle      = (string)trans('firefly.administrations_page_sub_title');
        $mainTitleIcon = 'fa-book';
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        return view('administrations.index')->with(compact('title', 'subTitle', 'mainTitleIcon'));
    }
}
