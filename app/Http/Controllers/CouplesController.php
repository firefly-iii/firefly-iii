<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Illuminate\View\View;

class CouplesController extends Controller
{
    public function index(): View
    {
        return view('couples.index');
    }
}
