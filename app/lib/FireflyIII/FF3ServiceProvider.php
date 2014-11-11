<?php
namespace FireflyIII;

use Illuminate\Support\ServiceProvider;

/**
 * Class FF3ServiceProvider
 *
 * @package FireflyIII
 */
class FF3ServiceProvider extends ServiceProvider
{


    /**
     * Triggered automatically by Laravel
     */
    public function register()
    {
        // FORMAT:
        #$this->app->bind('Interface', 'Class');

        // preferences:
        $this->app->bind('FireflyIII\Shared\Preferences\PreferencesInterface', 'FireflyIII\Shared\Preferences\Preferences');

    }

}