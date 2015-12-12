<?php

namespace FireflyIII\Http\Requests;

use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AccountFormRequest
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Requests
 */
class ReportFormRequest extends Request
{
    /** @var  Carbon */
    public $startDate;

    /** @var  Carbon */
    public $endDate;

    /** @var  Collection */
    public $accounts;

    /** @var  string */
    public $reportType;

    /** @var  string */
    public $URL;

    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return Auth::check();
    }

    /**
     * This probably really isn't the best way to do this but I have no idea what Laravel
     * thought up to do this. Oh well, let's just see what happens.
     * @return array
     */
    public function rules()
    {
        // URL:
        $this->URL = $this->route()->parameter('url');

        /** @var \FireflyIII\Repositories\Account\AccountRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // split:
        $parts = explode(';', $this->URL);

        // try to make a date out of parts 1 and 2:
        try {
            $this->startDate = new Carbon($parts[1]);
            $this->endDate   = new Carbon($parts[2]);
        } catch (Exception $e) {
            Log::error('Could not parse date "' . $parts[1] . '" or "' . $parts[2] . '" for user #' . Auth::user()->id);
            abort(404);
        }
        if ($this->endDate < $this->startDate) {
            abort(404);
        }

        // get the accounts:
        $count    = count($parts);
        $list = new Collection();
        for ($i = 3; $i < $count; $i++) {
            $account = $repository->find($parts[$i]);
            if ($account) {
                $list->push($account);
            }
        }
        $this->accounts = $list;

        return [];
    }

    /**
     * @return string
     */
    public function getSomething()
    {
        return 'Hoi';
    }


}
