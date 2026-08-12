<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserLoggedInFromNewIpAddress;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\UserLogin;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifiesUserAboutNewIpAddress implements ShouldQueue
{
    public function handle(UserLoggedInFromNewIpAddress $event): void
    {
        $user = $event->user;

        if ($user->hasRole('demo')) {
            return; // do not email demo user.
        }

        /** @var null|array $list */
        $list = Preferences::getForUser($user, 'login_ip_history', [])->data;
        if (!is_array($list)) {
            $list = [];
        }

        /** @var array $entry */
        foreach ($list as $index => $entry) {
            if (false === $entry['notified']) {
                NotificationSender::send($user, new UserLogin());
            }
            $list[$index]['notified'] = true;
        }

        Preferences::setForUser($user, 'login_ip_history', $list);
    }
}
