<?php
/**
 * IndexController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\Response as LaravelResponse;
use Log;
use Response;
use View;


/**
 * Class FileController.
 */
class IndexController extends Controller
{
    /** @var ImportJobRepositoryInterface */
    public $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-archive');
                app('view')->share('title', trans('firefly.import_index_title'));
                $this->repository = app(ImportJobRepositoryInterface::class);

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index']);
    }

    /**
     * Creates a new import job for $bank with the default (global) job configuration.
     *
     * @param string $bank
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function create(string $bank)
    {
        if (true === !(config(sprintf('import.enabled.%s', $bank)))) {
            throw new FireflyException(sprintf('Cannot import from "%s" at this time.', $bank)); // @codeCoverageIgnore
        }

        $importJob = $this->repository->create($bank);

        // from here, always go to configure step.
        return redirect(route('import.configure', [$importJob->key]));
    }

    /**
     * Generate a JSON file of the job's configuration and send it to the user.
     *
     * @param ImportJob $job
     *
     * @return LaravelResponse
     */
    public function download(ImportJob $job)
    {
        Log::debug('Now in download()', ['job' => $job->key]);
        $config = $job->configuration;

        // This is CSV import specific:
        $config['column-roles-complete']   = false;
        $config['column-mapping-complete'] = false;
        $config['initial-config-complete'] = false;
        $config['has-file-upload']         = false;
        $config['delimiter']               = "\t" === $config['delimiter'] ? 'tab' : $config['delimiter'];
        unset($config['stage']);

        $result = json_encode($config, JSON_PRETTY_PRINT);
        $name   = sprintf('"%s"', addcslashes('import-configuration-' . date('Y-m-d') . '.json', '"\\'));

        /** @var LaravelResponse $response */
        $response = response($result, 200);
        $response->header('Content-disposition', 'attachment; filename=' . $name)
                 ->header('Content-Type', 'application/json')
                 ->header('Content-Description', 'File Transfer')
                 ->header('Connection', 'Keep-Alive')
                 ->header('Expires', '0')
                 ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                 ->header('Pragma', 'public')
                 ->header('Content-Length', strlen($result));

        return $response;
    }

    /**
     * General import index.
     *
     * @return View
     */
    public function index()
    {
        $subTitle     = trans('firefly.import_index_sub_title');
        $subTitleIcon = 'fa-home';
        $routines     = config('import.enabled');

        return view('import.index', compact('subTitle', 'subTitleIcon', 'routines'));
    }

    /**
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws FireflyException
     */
    public function start(ImportJob $job)
    {
        $type      = $job->file_type;
        $key       = sprintf('import.routine.%s', $type);
        $className = config($key);
        if (null === $className || !class_exists($className)) {
            throw new FireflyException(sprintf('Cannot find import routine class for job of type "%s".', $type)); // @codeCoverageIgnore
        }

        /** @var RoutineInterface $routine */
        $routine = app($className);
        $routine->setJob($job);
        $result = $routine->run();

        if ($result) {
            return Response::json(['run' => 'ok']);
        }

        throw new FireflyException('Job did not complete successfully. Please review the log files.');
    }
}
