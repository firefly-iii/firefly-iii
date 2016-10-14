<?php
/**
 * AttachUserRole.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\UserRegistration;
use FireflyIII\Repositories\User\UserRepositoryInterface;

/**
 * Class AttachUserRole
 *
 * @package FireflyIII\Handlers\Events
 */
class AttachUserRole
{
    /**
     * Create the event listener.
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserRegistration $event
     */
    public function handle(UserRegistration $event)
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        // first user ever?
        if ($repository->count() == 1) {
            $repository->attachRole($event->user, 'owner');
        }
    }

}
