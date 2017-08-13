<?php
/**
 * ImportController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use View;

/**
 * Class ImportController.
 *
 * @package FireflyIII\Http\Controllers
 */
class ImportController extends Controller
{
    /** @var  ImportJobRepositoryInterface */
    public $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                View::share('mainTitleIcon', 'fa-archive');
                View::share('title', trans('firefly.import_index_title'));
                $this->repository = app(ImportJobRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * General import index
     *
     * @return View
     */
    public function index()
    {
        $subTitle          = trans('firefly.import_index_sub_title');
        $subTitleIcon      = 'fa-home';
        $importFileTypes   = [];
        $defaultImportType = config('firefly.default_import_format');

        foreach (array_keys(config('firefly.import_formats')) as $type) {
            $importFileTypes[$type] = trans('firefly.import_file_type_' . $type);
        }

        return view('import.index', compact('subTitle', 'subTitleIcon', 'importFileTypes', 'defaultImportType'));
    }

}
