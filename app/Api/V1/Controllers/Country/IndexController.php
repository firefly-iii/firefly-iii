<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Country;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Country;
use Illuminate\Http\JsonResponse;

final class IndexController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Country::query()
                ->orderBy('name')
                ->get()
        );
    }
}
