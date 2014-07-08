<?php

namespace Firefly\Helper\Toolkit;


class Toolkit implements ToolkitInterface
{

    /**
     * Based on the preference 'viewRange' and other variables I have not yet thought of,
     * this method will return a date range that defines the 'current' period of time.
     *
     * ie. the current week or month.
     *
     * $start is always the past, $end is 'now' or at least later.
     */
    public static function getDateRange()
    {
        $preferences = \App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $viewRange = $preferences->get('viewRange', 'week');

        // as you can see, this method now only supports "right now":
        $now = new \Carbon\Carbon;
        $start = clone $now;
        $end = clone $now;


        switch ($viewRange->data) {
            case 'week':
                $start->startOfWeek();
                $end->endOfWeek();
                break;
            default:
            case 'month':
                $start->startOfMonth();
                $end->endOfMonth();
                break;
        }
        return [$start, $end];


    }

} 