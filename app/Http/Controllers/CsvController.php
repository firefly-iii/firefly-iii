<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/15
 * Time: 10:37
 */

namespace FireflyIII\Http\Controllers;

use App;
use Carbon\Carbon;
use Config;
use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Csv\Data;
use FireflyIII\Helpers\Csv\Importer;
use FireflyIII\Helpers\Csv\WizardInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Http\Request;
use Input;
use League\Csv\Reader;
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

    /** @var  Data */
    protected $data;
    /** @var  WizardInterface */
    protected $wizard;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.csv'));
        View::share('mainTitleIcon', 'fa-file-text-o');

        $this->wizard = App::make('FireflyIII\Helpers\Csv\WizardInterface');
        $this->data   = App::make('FireflyIII\Helpers\Csv\Data');

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
        if (!$this->wizard->sessionHasValues($fields)) {
            Session::flash('warning', 'Could not recover upload.');

            return Redirect::route('csv.index');
        }

        $subTitle       = trans('firefly.csv_process');
        $firstRow       = $this->data->getReader()->fetchOne();
        $count          = count($firstRow);
        $headers        = [];
        $example        = $this->data->getReader()->fetchOne();
        $availableRoles = [];
        $roles          = $this->data->getRoles();
        $map            = $this->data->getMap();

        for ($i = 1; $i <= $count; $i++) {
            $headers[] = trans('firefly.csv_row') . ' #' . $i;
        }
        if ($this->data->getHasHeaders()) {
            $headers = $firstRow;
        }

        foreach (Config::get('csv.roles') as $name => $role) {
            $availableRoles[$name] = $role['name'];
        }
        ksort($availableRoles);

        return view('csv.column-roles', compact('availableRoles', 'map', 'roles', 'headers', 'example', 'subTitle'));
    }

    /**
     * Optional download of mapping.
     *
     * STEP FOUR THREE-A
     */
    public function downloadConfig()
    {
        $fields = ['csv-date-format', 'csv-has-headers'];
        if (!$this->wizard->sessionHasValues($fields)) {
            Session::flash('warning', 'Could not recover upload.');

            return Redirect::route('csv.index');
        }
        $data = [
            'date-format' => Session::get('date-format'),
            'has-headers' => Session::get('csv-has-headers')
        ];
        //        $fields = ['csv-file', 'csv-date-format', 'csv-has-headers', 'csv-map', 'csv-roles', 'csv-mapped'];
        if (Session::has('csv-map')) {
            $data['map'] = Session::get('csv-map');
        }
        if (Session::has('csv-roles')) {
            $data['roles'] = Session::get('csv-roles');
        }
        if (Session::has('csv-mapped')) {
            $data['mapped'] = Session::get('csv-mapped');
        }

        $result = json_encode($data, JSON_PRETTY_PRINT);
        $name   = 'csv-configuration-' . date('Y-m-d') . '.json';

        header('Content-disposition: attachment; filename=' . $name);
        header('Content-type: application/json');
        echo $result;
        exit;
    }

    /**
     * @return View
     */
    public function downloadConfigPage()
    {
        return view('csv.download-config');
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
        Session::forget('csv-map');
        Session::forget('csv-roles');
        Session::forget('csv-mapped');


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
        if (!$this->wizard->sessionHasValues($fields)) {
            Session::flash('warning', 'Could not recover upload.');

            return Redirect::route('csv.index');
        }


        // process given roles and mapping:
        $roles = $this->wizard->processSelectedRoles(Input::get('role'));
        $maps  = $this->wizard->processSelectedMapping($roles, Input::get('map'));

        Session::put('csv-map', $maps);
        Session::put('csv-roles', $roles);

        /*
         * Go back when no roles defined:
         */
        if (count($roles) === 0) {
            Session::flash('warning', 'Please select some roles.');

            return Redirect::route('csv.column-roles');
        }

        /*
         * Continue with map specification when necessary.
         */
        if (count($maps) > 0) {
            return Redirect::route('csv.map');
        }

        /*
         * Or simply start processing.
         */

        return Redirect::route('csv.process');

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
        if (!$this->wizard->sessionHasValues($fields)) {
            Session::flash('warning', 'Could not recover upload.');

            return Redirect::route('csv.index');
        }

        /*
         * The "options" array contains all options the user has
         * per column, where the key represents the column.
         *
         * For each key there is an array which in turn represents
         * all the options available: grouped by ID.
         *
         * Aka:
         *
         * options[column index] = [
         * field id => field identifier.
         * ]
         */
        try {
            $options = $this->wizard->showOptions($this->data->getMap());
        } catch (FireflyException $e) {
            return view('error', ['message' => $e->getMessage()]);
        }

        /*
         * After these values are prepped, read the actual CSV file
         */
        $reader     = $this->data->getReader();
        $map        = $this->data->getMap();
        $hasHeaders = $this->data->getHasHeaders();
        $values     = $this->wizard->getMappableValues($reader, $map, $hasHeaders);
        $map        = $this->data->getMap();
        $mapped     = $this->data->getMapped();

        return view('csv.map', compact('map', 'options', 'values', 'mapped'));
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
        if (!$this->wizard->sessionHasValues($fields)) {
            Session::flash('warning', 'Could not recover upload.');

            return Redirect::route('csv.index');
        }

        //
        $importer = new Importer;
        $importer->setData($this->data);
        try {
            $importer->run();
        } catch (FireflyException $e) {
            return view('error', ['message' => $e->getMessage()]);
        }


        exit;

        // loop the original file again:
        $content    = file_get_contents(Session::get('csv-file'));
        $hasHeaders = Session::get('csv-has-headers');
        $reader     = Reader::createFromString(Crypt::decrypt($content));

        // dump stuff
        $dateFormat = Session::get('csv-date-format');
        $roles      = Session::get('csv-roles');
        $mapped     = Session::get('csv-mapped');

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
        if (!$this->wizard->sessionHasValues($fields)) {
            Session::flash('warning', 'Could not recover upload.');

            return Redirect::route('csv.index');
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
        return Redirect::route('csv.download-config-page');

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

        /*
         * Store CSV and put in session.
         */
        $fullPath   = $this->wizard->storeCsvFile($request->file('csv')->getRealPath());
        $dateFormat = Input::get('date_format');
        $hasHeaders = intval(Input::get('has_headers')) === 1;
        $map        = [];
        $roles      = [];
        $mapped     = [];


        /*
         * Process config file if present.
         */
        if ($request->hasFile('csv_config')) {

            $data = file_get_contents($request->file('csv_config')->getRealPath());
            $json = json_decode($data, true);

            if (!is_null($json)) {
                $dateFormat = isset($json['date-format']) ? $json['date-format'] : $dateFormat;
                $hasHeaders = isset($json['has-headers']) ? $json['has-headers'] : $hasHeaders;
                $map        = isset($json['map']) && is_array($json['map']) ? $json['map'] : [];
                $mapped     = isset($json['mapped']) && is_array($json['mapped']) ? $json['mapped'] : [];
                $roles      = isset($json['roles']) && is_array($json['roles']) ? $json['roles'] : [];
            }
        }

        $this->data->setCsvFileLocation($fullPath);
        $this->data->setDateFormat($dateFormat);
        $this->data->setHasHeaders($hasHeaders);
        $this->data->setMap($map);
        $this->data->setMapped($mapped);
        $this->data->setRoles($roles);


        return Redirect::route('csv.column-roles');

    }
}