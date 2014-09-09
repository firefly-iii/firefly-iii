<?php

use Carbon\Carbon;
use Firefly\Storage\Reminder\ReminderRepositoryInterface as RRI;

/**
 * Class ReminderController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class ReminderController extends BaseController
{

    protected $_repository;

    /**
     * @param RRI $repository
     */
    public function __construct(RRI $repository)
    {
        $this->_repository = $repository;
    }

    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismiss(\Reminder $reminder)
    {
        $reminder = $this->_repository->deactivate($reminder);

        return Response::json($reminder->id);
    }

    /**
     * Returns the reminders currently active for the modal dialog.
     */
    public function modalDialog()
    {
        $today     = new Carbon;
        $reminders = $this->_repository->getPiggybankReminders();

        /** @var \Reminder $reminder */
        foreach ($reminders as $index => $reminder) {
            if (\Session::has('dismissal-' . $reminder->id)) {
                $time = \Session::get('dismissal-' . $reminder->id);
                if ($time >= $today) {
                    unset($reminders[$index]);
                }

            }
        }

        return View::make('reminders.popup')->with('reminders', $reminders);
    }

    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postpone(\Reminder $reminder)
    {
        $now = new Carbon;
        $now->addDay();
        Session::put('dismissal-' . $reminder->id, $now);

        return Response::json($reminder->id);
    }

    /**
     * @param Reminder $reminder
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function redirect(\Reminder $reminder)
    {
        if ($reminder instanceof PiggybankReminder) {
            // fields to prefill:
            $parameters = [
                'account_to_id' => $reminder->piggybank->account->id,
                'amount'        => round($reminder->amountToSave(), 2),
                'description'   => 'Money for ' . $reminder->piggybank->name,
                'piggybank_id'  => $reminder->piggybank->id,
                'reminder_id'   => $reminder->id
            ];

            return Redirect::to(
                route('transactions.create', ['what' => 'transfer']) . '?' . http_build_query($parameters)
            );
        }
        return View::make('error')->with('message', 'No such reminder.');

    }

} 