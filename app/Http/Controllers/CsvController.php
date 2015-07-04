<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/15
 * Time: 10:37
 */

namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Config;
use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Http\Request;
use Input;
use League\Csv\Reader;
use Log;
use Redirect;
use Session;
use View;

/**
 * Class CsvController
 *
 * @package FireflyIII\Http\Controllers
 */
class CsvController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.csv'));
        View::share('mainTitleIcon', 'fa-file-text-o');

    }

    /**
     * Define column roles and mapping.
     *
     *
     * STEP THREE
     *
     * @return View
     */
    public function columnRoles()
    {
        $fields = ['csv-file', 'csv-date-format', 'csv-has-headers'];
        foreach ($fields as $field) {
            if (!Session::has($field)) {
                Session::flash('warning', 'Could not recover upload (' . $field . ' missing).');

                return Redirect::route('csv.index');
            }
        }

        $subTitle         = trans('firefly.csv_process');
        $fullPath         = Session::get('csv-file');
        $hasHeaders       = Session::get('csv-has-headers');
        $content          = file_get_contents($fullPath);
        $contentDecrypted = Crypt::decrypt($content);
        $reader           = Reader::createFromString($contentDecrypted);


        Log::debug('Get uploaded content from ' . $fullPath);
        Log::debug('Strlen of original content is ' . strlen($contentDecrypted));
        Log::debug('MD5 of original content is ' . md5($contentDecrypted));

        $firstRow = $reader->fetchOne();

        $count   = count($firstRow);
        $headers = [];
        for ($i = 1; $i <= $count; $i++) {
            $headers[] = trans('firefly.csv_row') . ' #' . $i;
        }
        if ($hasHeaders) {
            $headers = $firstRow;
        }

        // example data is always the second row:
        $example = $reader->fetchOne();
        $roles   = [];
        foreach (Config::get('csv.roles') as $name => $role) {
            $roles[$name] = $role['name'];
        }
        ksort($roles);


        return view('csv.column-roles', compact('roles', 'headers', 'example', 'subTitle'));
    }

    /**
     * This method shows the initial upload form.
     *
     * STEP ONE
     *
     * @return View
     */
    public function index()
    {
        $subTitle = trans('firefly.csv_import');

        Session::forget('csv-date-format');
        Session::forget('csv-has-headers');
        Session::forget('csv-file');


        // can actually upload?
        $uploadPossible = is_writable(storage_path('upload'));
        $path           = storage_path('upload');

        return view('csv.index', compact('subTitle', 'uploadPossible', 'path'));
    }

    /**
     * Parse the file.
     *
     * STEP FOUR
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function initialParse()
    {
        $fields = ['csv-file', 'csv-date-format', 'csv-has-headers'];
        foreach ($fields as $field) {
            if (!Session::has($field)) {
                Session::flash('warning', 'Could not recover upload (' . $field . ' missing).');

                return Redirect::route('csv.index');
            }
        }
        $configRoles = Config::get('csv.roles');
        $roles       = [];

        /*
         * Store all rows for each column:
         */
        if (is_array(Input::get('role'))) {
            $roles = [];
            foreach (Input::get('role') as $index => $role) {
                if ($role != '_ignore') {
                    $roles[$index] = $role;
                }

            }
        }
        /*
         * Go back when no roles defined:
         */
        if (count($roles) === 0) {
            Session::flash('warning', 'Please select some roles.');

            return Redirect::route('csv.column-roles');
        }
        Session::put('csv-roles', $roles);

        /*
         * Show user map thing:
         */
        if (is_array(Input::get('map'))) {
            $maps = [];
            foreach (Input::get('map') as $index => $map) {
                $name = $roles[$index];
                if ($configRoles[$name]['mappable']) {
                    $maps[$index] = $name;
                }
            }
            // redirect to map routine.
            Session::put('csv-map', $maps);

            return Redirect::route('csv.map');
        }

        var_dump($roles);
        var_dump($_POST);
        exit;

    }

    /**
     *
     * Map first if necessary,
     *
     * STEP FIVE.
     *
     * @return \Illuminate\Http\RedirectResponse|View
     * @throws FireflyException
     */
    public function map()
    {

        /*
         * Make sure all fields we need are accounted for.
         */
        $fields = ['csv-file', 'csv-date-format', 'csv-has-headers', 'csv-map', 'csv-roles'];
        foreach ($fields as $field) {
            if (!Session::has($field)) {
                Session::flash('warning', 'Could not recover upload (' . $field . ' missing).');

                return Redirect::route('csv.index');
            }
        }

        /*
         * The $map array contains all columns
         * the user wishes to map on to data already in the system.
         */
        $map = Session::get('csv-map');

        /*
         * The "options" array contains all options the user has
         * per column, where the key represents the column.
         *
         * For each key there is an array which in turn represents
         * all the options available: grouped by ID.
         */
        $options = [];

        /*
         * Loop each field the user whishes to map.
         */
        foreach ($map as $index => $columnRole) {

            /*
             * Depending on the column role, get the relevant data from the database.
             * This needs some work to be optimal.
             */
            switch ($columnRole) {
                default:
                    throw new FireflyException('Cannot map field of type "' . $columnRole . '".');
                    break;
                case 'account-iban':
                    // get content for this column.
                    $content = Auth::user()->accounts()->where('account_type_id', 3)->get(['accounts.*']);
                    $list    = [];
                    // make user friendly list:

                    foreach ($content as $account) {
                        $list[$account->id] = $account->name;
                        //if(!is_null($account->iban)) {
                        //$list[$account->id] .= ' ('.$account->iban.')';
                        //}
                    }
                    $options[$index] = $list;
                    break;
                case 'currency-code':
                    $currencies = TransactionCurrency::get();
                    $list       = [];
                    foreach ($currencies as $currency) {
                        $list[$currency->id] = $currency->name . ' (' . $currency->code . ')';
                    }
                    $options[$index] = $list;
                    break;
                case 'opposing-name':
                    // get content for this column.
                    $content = Auth::user()->accounts()->whereIn('account_type_id', [4, 5])->get(['accounts.*']);
                    $list    = [];
                    // make user friendly list:

                    foreach ($content as $account) {
                        $list[$account->id] = $account->name . ' (' . $account->accountType->type . ')';
                    }
                    $options[$index] = $list;
                    break;

            }

        }


        /*
         * After these values are prepped, read the actual CSV file
         */
        $content    = file_get_contents(Session::get('csv-file'));
        $hasHeaders = Session::get('csv-has-headers');
        $reader     = Reader::createFromString(Crypt::decrypt($content));
        $values     = [];

        /*
         * Loop over the CSV and collect mappable data:
         */
        foreach ($reader as $index => $row) {
            if (($hasHeaders && $index > 1) || !$hasHeaders) {
                // collect all map values
                foreach ($map as $column => $irrelevant) {
                    // check if $irrelevant is mappable!
                    $values[$column][] = $row[$column];
                }
            }
        }
        foreach ($values as $column => $found) {
            $values[$column] = array_unique($found);
        }

        return view('csv.map', compact('map', 'options', 'values'));
    }

    /**
     * Finally actually process the CSV file.
     *
     * STEP SEVEN
     */
    public function process()
    {
        /*
         * Make sure all fields we need are accounted for.
         */
        $fields = ['csv-file', 'csv-date-format', 'csv-has-headers', 'csv-map', 'csv-roles', 'csv-mapped'];
        foreach ($fields as $field) {
            if (!Session::has($field)) {
                Session::flash('warning', 'Could not recover upload (' . $field . ' missing).');

                return Redirect::route('csv.index');
            }
        }

        // loop the original file again:
        $content    = file_get_contents(Session::get('csv-file'));
        $hasHeaders = Session::get('csv-has-headers');
        $reader     = Reader::createFromString(Crypt::decrypt($content));

        // dump stuff
        $dateFormat = Session::get('csv-date-format');
        $roles      = Session::get('csv-roles');
        $mapped     = Session::get('csv-mapped');

        var_dump($roles);
        var_dump(Session::get('csv-mapped'));


        /*
         * Loop over the CSV and collect mappable data:
         */
        foreach ($reader as $index => $row) {
            if (($hasHeaders && $index > 1) || !$hasHeaders) {
                // this is the data we need to store the new transaction:
                $amount          = 0;
                $amountModifier  = 1;
                $description     = '';
                $assetAccount    = null;
                $opposingAccount = null;
                $currency        = null;
                $date            = null;

                foreach ($row as $index => $value) {
                    if (isset($roles[$index])) {
                        switch ($roles[$index]) {
                            default:
                                throw new FireflyException('Cannot process role "' . $roles[$index] . '"');
                                break;
                            case 'account-iban':
                                // find ID in "mapped" (if present).
                                if (isset($mapped[$index])) {
                                    $searchID     = $mapped[$index][$value];
                                    $assetAccount = Account::find($searchID);
                                } else {
                                    // create account
                                }
                                break;
                            case 'opposing-name':
                                // don't know yet if its going to be a
                                // revenue or expense account.
                                $opposingAccount = $value;
                                break;
                            case 'currency-code':
                                // find ID in "mapped" (if present).
                                if (isset($mapped[$index])) {
                                    $searchValue = $mapped[$index][$value];
                                    $currency    = TransactionCurrency::whereCode($searchValue);
                                } else {
                                    // create account
                                }
                                break;
                            case 'date-transaction':
                                // unmappable:
                                $date = Carbon::createFromFormat($dateFormat, $value);

                                break;
                            case 'rabo-debet-credet':
                                if ($value == 'D') {
                                    $amountModifier = -1;
                                }
                                break;
                            case 'amount':
                                $amount = $value;
                                break;
                            case 'description':
                                $description .= ' ' . $value;
                                break;
                            case 'sepa-ct-id':
                                $description .= ' ' . $value;
                                break;

                        }
                    }
                }
                // do something with all this data:


                // do something.
                var_dump($row);

            }
        }


    }

    /**
     * Store the mapping the user has made. This is
     *
     * STEP SIX
     */
    public function saveMapping()
    {
        /*
         * Make sure all fields we need are accounted for.
         */
        $fields = ['csv-file', 'csv-date-format', 'csv-has-headers', 'csv-map', 'csv-roles'];
        foreach ($fields as $field) {
            if (!Session::has($field)) {
                Session::flash('warning', 'Could not recover upload (' . $field . ' missing).');

                return Redirect::route('csv.index');
            }
        }
        // save mapping to session.
        $mapped = [];
        if (!is_array(Input::get('mapping'))) {
            Session::flash('warning', 'Invalid mapping.');

            return Redirect::route('csv.map');
        }

        foreach (Input::get('mapping') as $index => $data) {
            $mapped[$index] = [];
            foreach ($data as $value => $mapping) {
                $mapped[$index][$value] = $mapping;
            }
        }
        Session::put('csv-mapped', $mapped);

        // proceed to process.
        return Redirect::route('csv.process');

    }

    /**
     *
     * This method processes the file, puts it away somewhere safe
     * and sends you onwards.
     *
     * STEP TWO
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        if (!$request->hasFile('csv')) {
            Session::flash('warning', 'No file uploaded.');


            return Redirect::route('csv.index');
        }


        $dateFormat = Input::get('date_format');
        $hasHeaders = intval(Input::get('has_headers')) === 1;
        // store file somewhere temporary (encrypted)?
        $time     = str_replace(' ', '-', microtime());
        $fileName = 'csv-upload-' . Auth::user()->id . '-' . $time . '.csv.encrypted';
        $fullPath = storage_path('upload') . DIRECTORY_SEPARATOR . $fileName;
        $content  = file_get_contents($request->file('csv')->getRealPath());

        Log::debug('Stored uploaded content in ' . $fullPath);
        Log::debug('Strlen of uploaded content is ' . strlen($content));
        Log::debug('MD5 of uploaded content is ' . md5($content));

        $content = Crypt::encrypt($content);
        file_put_contents($fullPath, $content);


        Session::put('csv-date-format', $dateFormat);
        Session::put('csv-has-headers', $hasHeaders);
        Session::put('csv-file', $fullPath);

        return Redirect::route('csv.column-roles');


        //
        //
        //

        //
        //        return view('csv.upload', compact('headers', 'example', 'roles', 'subTitle'));

    }
}