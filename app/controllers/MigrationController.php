<?php

use Carbon\Carbon as Carbon;
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
                echo 'Invalid file.';
                exit();
            }
        }
        echo '<a href="' . route('index') . '">home</a>';
        exit();
    }

    public function limit()
    {
        $user = User::find(1);
        $budgets = [];
        // new budget
        for ($i = 0; $i < 7; $i++) {
            $budget = new Budget();
            $budget->user()->associate($user);
            $budget->name = 'Some budget #' . rand(1, 2000);
            $budget->save();
            $budgets[] = $budget;
        }

        // create a non-repeating limit for this week:
        $today = new Carbon('01-07-2014');

        $limit = new Limit;
        $limit->budget()->associate($budgets[0]);
        $limit->amount = 100;
        $limit->startdate = $today;
        $limit->amount = 100;
        $limit->repeats = 0;
        $limit->repeat_freq = 'weekly';

        var_dump($limit->save());
        var_dump($limit->errors()->all());


        // create a repeating daily limit:
        $day = new Limit;
        $day->budget()->associate($budgets[1]);
        $day->amount = 100;
        $day->startdate = $today;
        $day->amount = 100;
        $day->repeats = 1;
        $day->repeat_freq = 'daily';
        $day->save();

        // repeating weekly limit.
        $week = new Limit;
        $week->budget()->associate($budgets[2]);
        $week->amount = 100;
        $week->startdate = $today;
        $week->amount = 100;
        $week->repeats = 1;
        $week->repeat_freq = 'weekly';
        $week->save();

        // repeating monthly limit
        $month = new Limit;
        $month->budget()->associate($budgets[3]);
        $month->amount = 100;
        $month->startdate = $today;
        $month->amount = 100;
        $month->repeats = 1;
        $month->repeat_freq = 'monthly';
        $month->save();

        // quarter
        $quarter = new Limit;
        $quarter->budget()->associate($budgets[4]);
        $quarter->amount = 100;
        $quarter->startdate = $today;
        $quarter->amount = 100;
        $quarter->repeats = 1;
        $quarter->repeat_freq = 'quarterly';
        $quarter->save();

        // six months
        $six = new Limit;
        $six->budget()->associate($budgets[5]);
        $six->amount = 100;
        $six->startdate = $today;
        $six->amount = 100;
        $six->repeats = 1;
        $six->repeat_freq = 'half-year';
        $six->save();

        // year
        $yearly = new Limit;
        $yearly->budget()->associate($budgets[6]);
        $yearly->amount = 100;
        $yearly->startdate = $today;
        $yearly->amount = 100;
        $yearly->repeats = 1;
        $yearly->repeat_freq = 'yearly';
        $yearly->save();


        // create a repeating weekly limit:
        // create a repeating monthly limit:

        foreach ($budgets as $budget) {

            echo '#' . $budget->id . ': ' . $budget->name . ':<br />';
            foreach ($budget->limits()->get() as $limit) {
                echo '&nbsp;&nbsp;Limit #' . $limit->id . ', amount: ' . $limit->amount . ', start: '
                    . $limit->startdate->format('D d-m-Y') . ', repeats: '
                    . $limit->repeats . ', repeat_freq: ' . $limit->repeat_freq . '<br />';

                foreach ($limit->limitrepetitions()->get() as $rep) {
                    echo '&nbsp;&nbsp;&nbsp;&nbsp;rep: #' . $rep->id . ', from ' . $rep->startdate->format('D d-m-Y')
                        . ' to '
                        . $rep->enddate->format('D d-m-Y') . '<br>';

                }
            }
        }


        return '';
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