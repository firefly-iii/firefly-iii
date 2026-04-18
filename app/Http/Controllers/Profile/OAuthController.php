<?php
/*
 * OAuthController.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\Profile;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token;

final class OAuthController extends Controller
{
    protected bool $internalAuth;

    public function __construct(protected ClientRepository $clients, protected ValidationFactory $validation)
    {
        parent::__construct();

        $this->middleware(static function ($request, $next) {
            app('view')->share('title', (string)trans('firefly.oauth_tokens'));
            app('view')->share('mainTitleIcon', 'fa-user');

            return $next($request);
        });
        $authGuard          = config('firefly.authentication_guard');
        $this->internalAuth = 'web' === $authGuard;
        Log::debug(sprintf('ProfileController::__construct(). Authentication guard is "%s"', $authGuard));

        $this->middleware(IsDemoUser::class)->except(['index']);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        $count = DB::table('oauth_clients')->where('grant_types', '["personal_access"]')->whereNull('owner_id')->count();

        if (0 === $count) {
            /** @var ClientRepository $repository */
            $repository = app(ClientRepository::class);
            $repository->createPersonalAccessGrantClient('Firefly III Personal Access Grant Client', null);
        }
        $link = route('index');
        return view('profile.oauth.index', compact('link'));
    }

    public function listClients(): JsonResponse
    {
        // Retrieving all the OAuth app clients that belong to the user...
        $clients = auth()->user()->oauthApps()->where('revoked', false)->get();
        return response()->json($clients);
    }

    public function storeClient(Request $request): JsonResponse
    {

        $this->validation->make($request->only(['name','redirect_uris','confidential']), [
            'name'          => ['required', 'string', 'max:255'],
            'redirect_uris' => ['required', 'url'],
            'confidential'  => 'boolean',
        ])->validate();

        // Creating an OAuth app client that belongs to the given user...
        $client             = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            name        : $request->input('name'),
            redirectUris: [$request->input('redirect_uris')],
            confidential: $request->input('confidential'),
            user        : auth()->user()
        );
        $arr                = $client->toArray();
        $arr['plainSecret'] = $client->plainSecret;
        return response()->json($arr);
    }

    public function regenerateClientSecret(Request $request, string $clientId): JsonResponse | Response | Client
    {


        $client = auth()->user()->oauthApps()->where('revoked', false)->find($clientId);
        if (null === $client) {
            return new Response('', 404);
        }
        //$client->
        $this->clients->regenerateSecret($client);
        $arr                = $client->toArray();
        $arr['plainSecret'] = $client->plainSecret;
        return response()->json($arr);

    }

    public function updateClient(Request $request, string $clientId): Response | Client
    {
        $client = auth()->user()->oauthApps()->where('revoked', false)->find($clientId);

        if (null === $client) {
            return new Response('', 404);
        }

        $this->validation->make($request->only(['name','redirect_uris']), [
            'name'          => ['required', 'string', 'max:255'],
            'redirect_uris' => ['required', 'url'],
        ])->validate();

        $this->clients->update(
            $client, $request->input('name'), explode(',', $request->input('redirect_uris'))
        ); // FIXME replace

        return $client;
    }

    public function destroyClient(Request $request, string $clientId): Response
    {
        /** @var Client|null $client */
        $client = auth()->user()->oauthApps()->where('revoked', false)->find($clientId);

        if (null === $client) {
            return new Response('', 404);
        }

        $client->tokens()->with('refreshToken')->each(function (#[\SensitiveParameter] Token $token): void {
            $token->refreshToken?->revoke();
            $token->revoke();
        });

        $client->forceFill(['revoked' => true])->save();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function storePersonalAccessToken(Request $request): JsonResponse
    {
        $this->validation->make($request->only(['name']), [
            'name' => ['required', 'max:255']])->validate();

        return response()->json($request->user()->createToken($request->name));
    }

    public function destroyPersonalAccessToken(Request $request, string $tokenId): Response
    {
        $token = auth()->user()->tokens()->where('revoked', false)->find($tokenId);

        if (is_null($token)) {
            return new Response('', 404);
        }

        $token->revoke();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function listPersonalAccessTokens(): JsonResponse
    {
        // Retrieving all the OAuth app clients that belong to the user...
        $tokens = auth()->user()->tokens()
                        ->with('client')
                        ->where('revoked', false)
                        ->where('expires_at', '>', Date::now())
                        ->get()
                        ->filter(fn(#[\SensitiveParameter] Token $token) => $token->client->hasGrantType('personal_access'));
        return response()->json($tokens);
    }


}
