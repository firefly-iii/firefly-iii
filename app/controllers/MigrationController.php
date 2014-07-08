<?php

use Firefly\Helper\Migration\MigrationHelperInterface as MHI;

class MigrationController extends BaseController
{
    protected $migration;

    public function __construct(MHI $migration)
    {
        $this->migration = $migration;
        View::share('menu', 'home');

    }

    public function dev() {
        $file = Config::get('dev.import');
        if(file_exists($file)) {
            $user = User::find(1);
            Auth::login($user);
            /** @var Firefly\Helper\Migration\MigrationHelperInterface $migration */
            $migration = App::make('Firefly\Helper\Migration\MigrationHelperInterface');
            $migration->loadFile($file);
            if ($migration->validFile()) {
                $migration->migrate();
            }
        }
    }

    public function index()
    {
        return View::make('migrate.index');
    }

    public function postIndex()
    {
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
    }
}