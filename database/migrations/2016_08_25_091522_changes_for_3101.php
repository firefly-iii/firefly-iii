<?php
/**
 * 2016_08_25_091522_changes_for_3101.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesFor3101
 */
class ChangesFor3101 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {
                if (Schema::hasColumn('import_jobs', 'extended_status')) {
                    $table->dropColumn('extended_status');
                }
            }
        );
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(): void
    {
        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {
                if (!Schema::hasColumn('import_jobs', 'extended_status')) {
                    $table->text('extended_status')->nullable();
                }
            }
        );
    }
}
