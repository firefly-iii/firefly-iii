<?php

declare(strict_types=1);

namespace FireflyIII\Support\CreditCard;

use Carbon\Carbon;

class StatementPeriod
{
    public readonly Carbon $start;
    public readonly Carbon $end;
    public readonly ?Carbon $dueDate;
    private readonly int $closingDay;
    private readonly ?int $dueDay;

    private function __construct(Carbon $start, Carbon $end, int $closingDay, ?int $dueDay = null)
    {
        $this->start      = $start->startOfDay();
        $this->end        = $end->endOfDay();
        $this->closingDay = $closingDay;
        $this->dueDay     = $dueDay;
        $this->dueDate    = $this->calculateDueDate();
    }

    public static function forDate(int $closingDay, Carbon $date, ?int $dueDay = null): self
    {
        $closingDay = self::clampDay($closingDay);
        $ref        = $date->copy();

        $closingThisMonth = self::resolveDay($ref->year, $ref->month, $closingDay);

        if ($ref->day <= $closingThisMonth) {
            $endMonth  = $ref->copy();
            $endDay    = $closingThisMonth;
        } else {
            $endMonth  = $ref->copy()->addMonthNoOverflow();
            $endDay    = self::resolveDay($endMonth->year, $endMonth->month, $closingDay);
        }

        $end   = $endMonth->copy()->day($endDay);

        $startMonth    = $end->copy()->subMonthNoOverflow();
        $startClosing  = self::resolveDay($startMonth->year, $startMonth->month, $closingDay);
        $start         = $startMonth->copy()->day($startClosing)->addDay();

        return new self($start, $end, $closingDay, $dueDay);
    }

    public static function current(int $closingDay, ?int $dueDay = null): self
    {
        return self::forDate($closingDay, today(config('app.timezone')), $dueDay);
    }

    public function previous(): self
    {
        return self::forDate($this->closingDay, $this->start->copy()->subDay(), $this->dueDay);
    }

    public function next(): self
    {
        return self::forDate($this->closingDay, $this->end->copy()->addDay(), $this->dueDay);
    }

    public function closingDay(): int
    {
        return $this->closingDay;
    }

    private function calculateDueDate(): ?Carbon
    {
        if (null === $this->dueDay) {
            return null;
        }

        $afterClose = $this->end->copy()->addDay();
        $dueDay     = self::resolveDay($afterClose->year, $afterClose->month, $this->dueDay);

        if ($dueDay >= $afterClose->day) {
            return $afterClose->copy()->day($dueDay)->startOfDay();
        }

        $nextMonth = $afterClose->copy()->addMonthNoOverflow();
        $dueDay    = self::resolveDay($nextMonth->year, $nextMonth->month, $this->dueDay);

        return $nextMonth->copy()->day($dueDay)->startOfDay();
    }

    private static function clampDay(int $day): int
    {
        return max(1, min(31, $day));
    }

    private static function resolveDay(int $year, int $month, int $day): int
    {
        $lastDay = Carbon::create($year, $month)->endOfMonth()->day;

        return min($day, $lastDay);
    }
}
