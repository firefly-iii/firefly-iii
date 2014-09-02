<?php

/**
 * Class MigrateController
 */
class MigrateController extends BaseController
{

    /**
     * @return $this
     */
    public function index()
    {
        return View::make('migrate.index')->with('index', 'Migration');
    }

    /**
     *
     */
    public function upload()
    {
        if (Input::hasFile('file') && Input::file('file')->isValid()) {
            // move file to storage:
            // ->move($destinationPath, $fileName);
            $path     = storage_path();
            $fileName = 'firefly-iii-import-' . date('Y-m-d-H-i') . '.json';
            $fullName = $path . DIRECTORY_SEPARATOR . $fileName;
            if (is_writable($path)) {
                Input::file('file')->move($path, $fileName);
                // so now we push something in a queue and do something with it! Yay!
                Queue::push('Firefly\Queue\Import@start', ['file' => $fullName,'user' => \Auth::user()->id]);
                exit;
            }
            Session::flash('error', 'Could not save file to storage.');
            return Redirect::route('migrate.index');

        } else {
            Session::flash('error', 'Please upload a file.');
            return Redirect::route('migrate.index');
        }

    }

} 