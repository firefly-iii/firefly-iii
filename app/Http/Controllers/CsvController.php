<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/15
 * Time: 10:37
 */

namespace FireflyIII\Http\Controllers;

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
        $uploadPossible = !is_writable(storage_path('upload'));
        $path           = storage_path('upload');


        return view('csv.index', compact('subTitle', 'uploadPossible', 'path'));
    }

    /**
     *
     */
    public function upload(Request $request)
    {
        if (!$request->hasFile('csv')) {
            Session::flash('warning', 'No file uploaded.');


            return Redirect::route('csv.index');
        }
        $hasHeaders = intval(Input::get('has_headers')) === 1;
        $reader     = Reader::createFromPath($request->file('csv')->getRealPath());
        $data       = $reader->query();
        $data->next(); // go to first row:
        if ($hasHeaders) {

            // first row = headers.
            $headers = $data->current();
        } else {
            $count   = count($data->current());
            $headers = [];
            for ($i = 1; $i <= $count; $i++) {
                $headers[] = trans('firefly.csv_row') . ' #' . $i;
            }
        }

        // example data is always the second row:
        $data->next();
        $example = $data->current();

        var_dump($headers);
        var_dump($example);

        // store file somewhere temporary?


        exit;

    }
}