<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Country;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IndexController extends Controller
{
    /**
     * GET /api/v1/countries
     *
     * Query params:
     *   - with_provider=1  → return only countries that have a registered
     *                        national-bank provider. Used by the
     *                        administration country selector.
     */
    public function index(Request $request): JsonResponse
    {
        $query        = Country::query()->orderBy('name');
        $withProvider = $request->boolean('with_provider', false);
        if ($withProvider) {
            $query->withProvider();
        }

        return response()->json($query->get());
    }
}
