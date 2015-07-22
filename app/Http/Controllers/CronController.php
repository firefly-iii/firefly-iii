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

    /**
     *
     */
    public function sendgrid()
    {

        $URL        = 'https://api.sendgrid.com/api/bounces.get.json';
        $parameters = [
            'api_user' => env('SENDGRID_USERNAME'),
            'api_key'  => env('SENDGRID_PASSWORD'),
            'date'     => 1,
            'days'     => 7
        ];
        $fullURL    = $URL . '?' . http_build_query($parameters);
        $data       = json_decode(file_get_contents($fullURL));
        $users      = [];
        // loop the result, if any.
        if (is_array($data)) {
            foreach ($data as $entry) {
                $address = $entry->email;
                $users[] = User::where('email', $address);


            }
        }

        /** @var User $user */
        foreach($users as $user) {
            if($user) {
                // block because bounce.
            }
        }

    }

}