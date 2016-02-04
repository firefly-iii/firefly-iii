<?php
/**
 * ExportJobRepository.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Repositories\ExportJob;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\ExportJob;
use Illuminate\Support\Str;

/**
 * Class ExportJobRepository
 *
 * @package FireflyIII\Repositories\ExportJob
 */
class ExportJobRepository implements ExportJobRepositoryInterface
{

    /**
     * @return bool
     */
    public function cleanup()
    {
        $dayAgo = Carbon::create()->subDay();
        ExportJob::where('created_at', '<', $dayAgo->format('Y-m-d H:i:s'))
                 ->where('status', 'never_started')
            // TODO also delete others.
                 ->delete();

        return true;
    }

    /**
     * @return ExportJob
     */
    public function create()
    {
        $exportJob = new ExportJob;
        $exportJob->user()->associate(Auth::user());
        /*
         * In theory this random string could give db error.
         */
        $exportJob->key    = Str::random(12);
        $exportJob->status = 'export_status_never_started';
        $exportJob->save();

        return $exportJob;
    }

    /**
     * @param $key
     *
     * @return ExportJob|null
     */
    public function findByKey($key)
    {
        return Auth::user()->exportJobs()->where('key', $key)->first();
    }

}