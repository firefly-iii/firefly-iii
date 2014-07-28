<?php

use Firefly\Helper\Migration\MigrationHelperInterface as MHI;

/**
 * Class MigrationController
 */
class MigrationController extends BaseController
{
    protected $_migration;

    /**
     * @param MHI $migration
     */
    public function __construct(MHI $migration)
    {
        $this->_migration = $migration;
        View::share('menu', 'home');

    }

    /**
     * Dev method
     */
    public function dev()
    {
        $file = Config::get('dev.import');
        if (file_exists($file)) {
            $user = User::find(1);

            /** @noinspection PhpParamsInspection */
            Auth::login($user);
            /** @var Firefly\Helper\Migration\MigrationHelperInterface $migration */
            $migration = App::make('Firefly\Helper\Migration\MigrationHelperInterface');
            $migration->loadFile($file);
            if ($migration->validFile()) {
                $migration->migrate();
            } else {
                throw new \Firefly\Exception\FireflyException('Invalid file.');
            }
        }

        return '<a href="' . route('index') . '">home</a>';
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return View::make('migrate.index');
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postIndex()
    {
        if (Input::hasFile('exportFile')) {

            // get content:
            $file = Input::file('exportFile');
            $path = $file->getRealPath();

            $this->_migration->loadFile($path);

            if (!$this->_migration->validFile()) {
                return View::make('error')->with('message', 'Invalid JSON content.');
            }
            $this->_migration->migrate();

            return Redirect::route('index');
        } else {
            return View::make('error')->with('message', 'No file selected');
        }
    }
}