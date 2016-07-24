<?php
/**
 * UserController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Admin;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\User;
use Illuminate\Http\Request;
use Preferences;
use Session;

/**
 * Class UserController
 *
 * @package FireflyIII\Http\Controllers\Admin
 */
class UserController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function domains()
    {

        $title         = strval(trans('firefly.administration'));
        $mainTitleIcon = 'fa-hand-spock-o';
        $subTitle      = strval(trans('firefly.blocked_domains'));
        $subTitleIcon  = 'fa-users';
        $domains       = FireflyConfig::get('blocked-domains', [])->data;

        // known domains
        $knownDomains = $this->getKnownDomains();

        return view('admin.users.domains', compact('title', 'mainTitleIcon', 'knownDomains', 'subTitle', 'subTitleIcon', 'domains'));
    }

    /**
     * @param UserRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(UserRepositoryInterface $repository)
    {
        $title          = strval(trans('firefly.administration'));
        $mainTitleIcon  = 'fa-hand-spock-o';
        $subTitle       = strval(trans('firefly.user_administration'));
        $subTitleIcon   = 'fa-users';
        $confirmAccount = env('MUST_CONFIRM_ACCOUNT', false);
        $users          = $repository->all();

        // add meta stuff.
        $users->each(
            function (User $user) use ($confirmAccount) {
                // is user activated?
                $isConfirmed     = Preferences::getForUser($user, 'user_confirmed', false)->data;
                $user->activated = true;
                if ($isConfirmed === false && $confirmAccount === true) {
                    $user->activated = false;
                }

                $user->isAdmin = $user->hasRole('owner');
                $is2faEnabled  = Preferences::getForUser($user, 'twoFactorAuthEnabled', false)->data;
                $has2faSecret  = !is_null(Preferences::getForUser($user, 'twoFactorAuthSecret'));
                $user->has2FA  = false;
                if ($is2faEnabled && $has2faSecret) {
                    $user->has2FA = true;
                }
            }
        );


        return view('admin.users.index', compact('title', 'mainTitleIcon', 'subTitle', 'subTitleIcon', 'users'));

    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function manual(Request $request)
    {
        if (strlen($request->get('domain')) === 0) {
            Session::flash('error', trans('firefly.no_domain_filled_in'));

            return redirect(route('admin.users.domains'));
        }

        $domain  = $request->get('domain');
        $blocked = FireflyConfig::get('blocked-domains', [])->data;

        if (in_array($domain, $blocked)) {
            Session::flash('error', trans('firefly.domain_already_blocked', ['domain' => $domain]));

            return redirect(route('admin.users.domains'));
        }
        $blocked[] = $domain;
        FireflyConfig::set('blocked-domains', $blocked);

        Session::flash('success', trans('firefly.domain_is_now_blocked', ['domain' => $domain]));
        return redirect(route('admin.users.domains'));
    }

    /**
     * @param string $domain
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function toggleDomain(string $domain)
    {
        $blocked = FireflyConfig::get('blocked-domains', [])->data;

        if (in_array($domain, $blocked)) {
            $key = array_search($domain, $blocked);
            unset($blocked[$key]);
            sort($blocked);

            FireflyConfig::set('blocked-domains', $blocked);
            Session::flash('message', trans('firefly.domain_now_unblocked', ['domain' => $domain]));


            return redirect(route('admin.users.domains'));

        }

        $blocked[] = $domain;

        FireflyConfig::set('blocked-domains', $blocked);
        Session::flash('message', trans('firefly.domain_now_blocked', ['domain' => $domain]));

        return redirect(route('admin.users.domains'));
    }

    /**
     * @return array
     */
    private function getKnownDomains(): array
    {
        $users    = User::get();
        $set      = [];
        $filtered = [];
        /** @var User $user */
        foreach ($users as $user) {
            $email  = $user->email;
            $parts  = explode('@', $email);
            $domain = $parts[1];
            $set[]  = $domain;
        }
        $set = array_unique($set);
        // filter for already banned domains:
        $blocked = FireflyConfig::get('blocked-domains', [])->data;

        foreach ($set as $domain) {
            // in the block array? ignore it.
            if (!in_array($domain, $blocked)) {
                $filtered[] = $domain;
            }
        }
        asort($filtered);

        return $filtered;
    }

}
