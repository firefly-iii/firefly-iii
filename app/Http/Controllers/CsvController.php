<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/15
 * Time: 10:37
 */

namespace FireflyIII\Http\Controllers;

use Auth;
use Crypt;
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
     * @return View
     */
    public function index()
    {
        $subTitle = trans('firefly.csv_import');

        // can actually upload?
        $uploadPossible = is_writable(storage_path('upload'));
        $path           = storage_path('upload');


        return view('csv.index', compact('subTitle', 'uploadPossible', 'path'));
    }

    /**
     *
     */
    public function upload(Request $request)
    {
        // possible column roles:
        $roles = [
            '(ignore this column)',
            'Asset account name',
            'Expense or revenue account name',
            'Amount',
            'Date',
            'Currency',
            'Description',
            'Category',
            'Budget',

        ];


        if (!$request->hasFile('csv')) {
            Session::flash('warning', 'No file uploaded.');


            return Redirect::route('csv.index');
        }
        $hasHeaders = intval(Input::get('has_headers')) === 1;
        $reader     = Reader::createFromPath($request->file('csv')->getRealPath());
        $data       = $reader->query();
        $data->next(); // go to first row:

        $count   = count($data->current());
        $headers = [];
        for ($i = 1; $i <= $count; $i++) {
            $headers[] = trans('firefly.csv_row') . ' #' . $i;
        }
        if ($hasHeaders) {
            $headers = $data->current();
        }

        // example data is always the second row:
        $data->next();
        $example = $data->current();
        // store file somewhere temporary (encrypted)?
        $time     = str_replace(' ', '-', microtime());
        $fileName = 'csv-upload-' . Auth::user()->id . '-' . $time . '.csv.encrypted';
        $fullPath = storage_path('upload') . DIRECTORY_SEPARATOR . $fileName;
        $content  = file_get_contents($request->file('csv')->getRealPath());
        $content  = Crypt::encrypt($content);
        file_put_contents($fullPath, $content);
        Session::put('latestCSVUpload', $fullPath);

        $subTitle = trans('firefly.csv_process');

        return view('csv.upload', compact('headers', 'example', 'roles', 'subTitle'));

    }
}