<?php

namespace FireflyIII\Http\Controllers;

use FireflyIII\User;

/**
 * Class WebhookController
 *
 * @package FireflyIII\Http\Controllers
 */
class CronController extends Controller
{
    /** @var array */
    protected $set = [];

    /** @var array */
    protected $parameters = [];


    /**
     * CronController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->set        = [
            'blocks'   => 'https://api.sendgrid.com/api/blocks.get.json',
            'bounces'  => 'https://api.sendgrid.com/api/bounces.get.json',
            'invalids' => 'https://api.sendgrid.com/api/invalidemails.get.json',

        ];
        $this->parameters = [
            'api_user' => env('SENDGRID_USERNAME'),
            'api_key'  => env('SENDGRID_PASSWORD'),
            'date'     => 1,
            'days'     => 7
        ];

    }


    /**
     * Firefly doesn't have anything that should be in the a cron job, except maybe this one, and it's fairly exceptional.
     *
     * If you use SendGrid like I do, you can detect bounces and thereby check if users gave an invalid address. If they did,
     * it's easy to block them and change their password. Optionally, you could notify yourself about it and send them a message.
     *
     * But thats something not supported right now.
     */
    public function sendgrid()
    {

        if (strlen(env('SENDGRID_USERNAME')) > 0 && strlen(env('SENDGRID_PASSWORD')) > 0) {

            echo '<pre>';
            foreach ($this->set as $name => $url) {
                $data = json_decode(file_get_contents($url . '?' . http_build_query($this->parameters)));
                $this->processResult($name, $data);

            }
            echo 'Done!' . "\n";
        } else {
            echo 'Please fill in SendGrid details.';
        }

    }

    /**
     * @param string $name
     * @param array  $data
     */
    protected function processResult($name, array $data)
    {
        if (is_array($data)) {
            echo 'Found ' . count($data) . ' entries in the SendGrid ' . $name . ' list.' . "\n";
            foreach ($data as $entry) {
                $address = $entry->email;
                $user    = User::where('email', $address)->where('blocked', 0)->first();
                if (!is_null($user)) {
                    echo 'Found a user: ' . $address . ', who is now blocked.' . "\n";
                    $user->blocked      = 1;
                    $user->blocked_code = 'bounced';
                    $user->password     = 'bounced';
                    $user->save();
                } else {
                    echo 'Found no user: ' . $address . ', did nothing.' . "\n";
                }
            }

        }
    }

}
