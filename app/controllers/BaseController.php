<?php

use Illuminate\Routing\Controller;

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
