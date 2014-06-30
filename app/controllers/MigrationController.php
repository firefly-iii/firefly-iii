<?php
class MigrationController extends BaseController {
    public function index() {
        // check if database connection is present.
        $configValue = Config::get('database.connections.old-firefly');
        if(is_null($configValue)) {
            return View::make('migrate.index');
        }

        // try to connect to it:
        try {
        DB::connection('old-firefly')->select('SELECT * from `users`;');
        } catch(PDOException $e) {
            return View::make('migrate.index');
        }

        return Redirect::route('migrate.select-user');


    }

    public function selectUser() {
        // select a user to import data from.
    }

    public function migrate($userID) {
        // import the data.
    }
}