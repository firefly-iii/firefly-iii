<?php

use Firefly\Helper\Migration\MigrationHelperInterface as MHI;

class MigrationController extends BaseController
{
    protected $migration;

    public function __construct(MHI $migration)
    {
        $this->migration = $migration;

    }

    public function index()
    {
        return View::make('migrate.index');
    }

    public function postIndex()
    {
        // @codeCoverageIgnoreStart
        if (Input::hasFile('exportFile')) {

            // get content:
            $file = Input::file('exportFile');
            $path = $file->getRealPath();

            $this->migration->loadFile($path);

            if (!$this->migration->validFile()) {
                return View::make('error')->with('message', 'Invalid JSON content.');
            }
            $this->migration->migrate();
            return Redirect::route('index');
        } else {
            return View::make('error')->with('message', 'No file selected');
        }
        // @codeCoverageIgnoreEnd
    }
}