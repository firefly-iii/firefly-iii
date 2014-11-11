<?php
namespace FireflyIII;

use FireflyIII\Shared\Validation\FireflyValidator;
use Illuminate\Support\ServiceProvider;

/**
 * Class FF3ServiceProvider
 *
 * @package FireflyIII
 */
class FF3ServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->app->validator->resolver(
            function ($translator, $data, $rules, $messages) {
                return new FireflyValidator($translator, $data, $rules, $messages);
            }
        );
    }

    /**
     * Triggered automatically by Laravel
     */
    public function register()
    {
        // FORMAT:
        #$this->app->bind('Interface', 'Class');

        // preferences:
        $this->app->bind('FireflyIII\Shared\Preferences\PreferencesInterface', 'FireflyIII\Shared\Preferences\Preferences');

        // registration and user mail:
        $this->app->bind('FireflyIII\Shared\Mail\RegistrationInterface', 'FireflyIII\Shared\Mail\Registration');

    }

}