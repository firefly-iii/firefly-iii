<?php declare(strict_types=1);

use FireflyIII\Models\Configuration;
use Illuminate\Database\Seeder;

/**
 * Class ConfigSeeder
 */
class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $entry = Configuration::where('name', 'db_version')->first();
        if (is_null($entry)) {
            Log::warning('No database version entry is present. Database is assumed to be OLD (version 1).');
            // FF old or no version present. Put at 1:
            Configuration::create(
                [
                    'name' => 'db_version',
                    'data' => 1,
                ]
            );
        }
        if (!is_null($entry)) {
            $version     = intval(config('firefly.db_version'));
            $entry->data = $version;
            $entry->save();

            Log::warning(sprintf('Database entry exists. Update to latest version (%d)', $version));
        }
    }
}
