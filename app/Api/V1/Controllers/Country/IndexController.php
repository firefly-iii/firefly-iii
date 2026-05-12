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
        $countries = Country::query()
            ->orderBy('name')
            ->get()
            ->map(function (Country $country): Country {
                $flag = $country->flag_src;

                if (null === $flag || '' === $flag) {
                    $flag = 'default.png';
                }

                // сразу отдаём готовый путь (лучше для фронта)
                $country->flag_src = sprintf('/v1/images/flags/%s', $flag);

                return $country;
            });

        return response()->json($countries);
    }
}
