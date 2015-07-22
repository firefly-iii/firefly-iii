<?php

namespace FireflyIII\Http\Controllers;

use FireflyIII\Models\Preference;
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
        echo "<pre>\n";
        // loop the result, if any.
        if (is_array($data)) {
            foreach ($data as $entry) {
                $address = $entry->email;
                $user    = User::where('email', $address)->first();
                if (!is_null($user)) {
                    $users[] = $user;
                    echo "Blocked " . $user->email . " because a message bounced.\n";

                    // create preference:
                    $preference       = Preference::firstOrCreate(['user_id' => $user->id, 'name' => 'bounce']);
                    $preference->data = $entry->reason;
                    $preference->save();
                }
            }
        }
    }

}