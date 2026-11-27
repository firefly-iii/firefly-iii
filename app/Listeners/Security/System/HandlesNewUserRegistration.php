<?php

declare(strict_types=1);

namespace FireflyIII\Listeners\Security\System;

use Database\Seeders\ExchangeRateSeeder;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Events\Security\System\NewUserRegistered;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\Notifications\Admin\UserRegistration as AdminRegistrationNotification;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\UserRegistration as UserRegistrationNotification;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Authentication\HandlersUserRegistrationTrait;
use FireflyIII\Support\Facades\AppConfiguration;
use FireflyIII\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandlesNewUserRegistration implements ShouldQueue
{
    use HandlersUserRegistrationTrait;
    public function handle(NewUserRegistered $event): void
    {
        $this->sendRegistrationMail($event->user);
        $this->sendAdminRegistrationNotification($event->user, $event->owner);
        $this->attachUserRole($event->user);
        $this->createGroupMembership($event->user);
        $this->createExchangeRates();
    }

    private function attachUserRole(User $user): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        // first user ever?
        if (1 === $repository->count()) {
            Log::debug('User count is one, attach role.');
            $repository->attachRole($user, 'owner');
        }
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    private function createExchangeRates(): void
    {
        $seeder = new ExchangeRateSeeder();
        $seeder->run();
    }

    private function sendAdminRegistrationNotification(User $user, OwnerNotifiable $owner): void
    {
        $sendMail = (bool) AppConfiguration::get('notification_admin_new_reg', true)->data;
        if (!$sendMail) {
            return;
        }
        NotificationSender::send($owner, new AdminRegistrationNotification($user));
    }

    private function sendRegistrationMail(User $user): void
    {
        $sendMail = (bool) AppConfiguration::get('notification_user_new_reg', true)->data;
        if (!$sendMail) {
            return;
        }

        NotificationSender::send($user, new UserRegistrationNotification());
    }
}
