<?php
declare(strict_types=1);


namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use Log;

/**
 * Class ParseDateString
 */
class ParseDateString
{
    private $keywords
        = [
            'today',
            'yesterday',
            'tomorrow',
            'start of this week',
            'end of this week',
            'start of this month',
            'end of this month',
            'start of this quarter',
            'end of this quarter',
            'start of this year',
            'end of this year',
        ];

    /**
     * @param string $date
     *
     * @return Carbon
     */
    public function parseDate(string $date): Carbon
    {
        // parse keywords:
        if (in_array($date, $this->keywords, true)) {
            return $this->parseKeyword($date);
        }

        // if regex for YYYY-MM-DD:
        $pattern = '/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][\d]|3[01])$/';
        if (preg_match($pattern, $date)) {
            return $this->parseDefaultDate($date);
        }

        // if + or -:
        if (0 === strpos($date, '+') || 0 === strpos($date, '-')) {
            return $this->parseRelativeDate($date);
        }

        throw new FireflyException('Not recognised.');
    }

    /**
     * @param string $date
     *
     * @return Carbon
     */
    private function parseDefaultDate(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date);
    }

    /**
     * @param string $keyword
     *
     * @return Carbon
     */
    private function parseKeyword(string $keyword): Carbon
    {
        $today = Carbon::today()->startOfDay();
        switch ($keyword) {
            default:
            case 'today':
                return $today;
            case 'yesterday':
                return $today->subDay();
            case 'tomorrow':
                return $today->addDay();
            case 'start of this week':
                return $today->startOfWeek();
            case 'end of this week':
                return $today->endOfWeek();
            case 'start of this month':
                return $today->startOfMonth();
            case 'end of this month':
                return $today->endOfMonth();
            case 'start of this quarter':
                return $today->startOfQuarter();
            case 'end of this quarter':
                return $today->endOfQuarter();
            case 'start of this year':
                return $today->startOfYear();
            case 'end of this year':
                return $today->endOfYear();
        }
    }

    /**
     * @param string $date
     *
     * @return Carbon
     */
    private function parseRelativeDate(string $date): Carbon
    {
        Log::debug(sprintf('Now in parseRelativeDate("%s")', $date));
        $parts     = explode(' ', $date);
        $today     = Carbon::today()->startOfDay();
        $functions = [
            [
                'd' => 'subDays',
                'w' => 'subWeeks',
                'm' => 'subMonths',
                'q' => 'subQuarters',
                'y' => 'subYears',
            ], [
                'd' => 'addDays',
                'w' => 'addWeeks',
                'm' => 'addMonths',
                'q' => 'addQuarters',
                'y' => 'addYears',
            ],
        ];

        /** @var string $part */
        foreach ($parts as $part) {
            Log::debug(sprintf('Now parsing part "%s"', $part));
            $part = trim($part);

            // verify if correct
            $pattern = '/[+-]\d+[wqmdy]/';
            $res     = preg_match($pattern, $part);
            if (0 === $res || false === $res) {
                Log::error(sprintf('Part "%s" does not match regular expression. Will be skipped.', $part));
                continue;
            }
            $direction = 0 === strpos($part, '+') ? 1 : 0;
            $period    = $part[strlen($part) - 1];
            $number    = (int) substr($part, 1, -1);
            if (!isset($functions[$direction][$period])) {
                Log::error(sprintf('No method for direction %d and period "%s".', $direction, $period));
                continue;
            }
            $func = $functions[$direction][$period];
            Log::debug(sprintf('Will now do %s(%d) on %s', $func, $number, $today->format('Y-m-d')));
            $today->$func($number);
            Log::debug(sprintf('Resulting date is %s', $today->format('Y-m-d')));

        }

        return $today;
    }

}
