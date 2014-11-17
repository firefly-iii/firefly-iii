<?php
use FireflyIII\Exception\NotImplementedException;

/**
 * Class ReminderController
 *
 */
class ReminderController extends BaseController
{

    public function __construct() {
        View::share('title','Reminders');
        View::share('mainTitleIcon','fa-lightbulb-o');
    }

    /**
     * @param Reminder $reminder
     */
    public function show(Reminder $reminder)
    {
        throw new NotImplementedException;
//        $subTitle = $reminder->title;
//        $model = null; // related model.
//
//        if(isset($reminder->data->model) && isset($reminder->data->type)) {
//            switch($reminder->data->type) {
//                case 'Test':
//                    break;
//                case 'Piggybank':
//                    break;
//                default:
//                    throw new FireflyException('Cannot handle model of type '.$reminder->data->model);
//                    break;
//            }
//        } else {
//
//        }
//
//        return View::make('reminders.show',compact('reminder','title','subTitle'));
    }
}