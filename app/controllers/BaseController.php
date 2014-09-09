<?php

use \Illuminate\Routing\Controller;

/**
 * Class BaseController
 */
class BaseController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        \Event::fire('limits.check');
        \Event::fire('piggybanks.check');
        \Event::fire('recurring.check');

    }

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (!is_null($this->layout)) {
            $this->layout = View::make($this->layout);
        }
    }

}
