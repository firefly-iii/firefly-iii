<?php

/*
 * Adds the `provider_class` column to the `countries` table.
 *
 * The value is the fully-qualified class name of a
 * FireflyIII\Services\ExchangeRate\Providers\NationalRateProviderInterface
 * implementation, or NULL when the country has no national-bank
 * provider yet. Countries without a provider are silently hidden from
 * the administration country selector.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('countries', static function (Blueprint $table): void {
            $table->string('provider_class', 255)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('countries', static function (Blueprint $table): void {
            $table->dropColumn('provider_class');
        });
    }
};
