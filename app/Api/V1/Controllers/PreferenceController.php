<?php
/**
 * PreferencesController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\PreferenceRequest;
use FireflyIII\Models\Preference;
use FireflyIII\Transformers\PreferenceTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use Preferences;

/**
 *
 * Class PreferenceController
 */
class PreferenceController extends Controller
{
    /**
     * List all of them.
     *
     * @param Request $request
     *
     * @return JsonResponse]
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user        = auth()->user();
        $available   = [
            'language', 'customFiscalYear', 'fiscalYearStart', 'currencyPreference',
            'transaction_journal_optional_fields', 'frontPageAccounts', 'viewRange',
            'listPageSize, twoFactorAuthEnabled',
        ];
        $preferences = new Collection;
        foreach ($available as $name) {
            $pref = Preferences::getForUser($user, $name);
            if (null !== $pref) {
                $preferences->push($pref);
            }
        }

        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new FractalCollection($preferences, new PreferenceTransformer($this->parameters), 'preferences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');


    }

    /**
     * List single resource.
     *
     * @param Request    $request
     * @param Preference $preference
     *
     * @return JsonResponse
     */
    public function show(Request $request, Preference $preference): JsonResponse
    {
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new Item($preference, new PreferenceTransformer($this->parameters), 'preferences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @param PreferenceRequest $request
     * @param Preference        $preference
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function update(PreferenceRequest $request, Preference $preference): JsonResponse
    {

        $data     = $request->getAll();
        $newValue = $data['data'];
        switch ($preference->name) {
            default:
                break;
            case 'transaction_journal_optional_fields':
            case 'frontPageAccounts':
                $newValue = explode(',', $data['data']);
                break;
            case 'listPageSize':
                $newValue = (int)$data['data'];
                break;
            case 'customFiscalYear':
            case 'twoFactorAuthEnabled':
                $newValue = (int)$data['data'] === 1;
                break;
        }
        $result = Preferences::set($preference->name, $newValue);

        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new Item($result, new PreferenceTransformer($this->parameters), 'preferences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}