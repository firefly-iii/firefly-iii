<?php

declare(strict_types=1);

namespace Tests\unit\Support\CreditCard;

use Carbon\Carbon;
use FireflyIII\Support\CreditCard\StatementPeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\integration\TestCase;

/**
 * @group unit-test
 * @group support
 * @group credit-card
 *
 * @internal
 *
 * @coversNothing
 */
final class StatementPeriodTest extends TestCase
{
    public static function closingDay31Provider(): array
    {
        return [
            'mid-month feb' => [
                '2026-02-14',
                '2026-02-01', '2026-02-28',
            ],
            'on closing day jan 31' => [
                '2026-01-31',
                '2026-01-01', '2026-01-31',
            ],
            'day after closing jan' => [
                '2026-02-01',
                '2026-02-01', '2026-02-28',
            ],
            'first day of march' => [
                '2026-03-01',
                '2026-03-01', '2026-03-31',
            ],
            'mid-month april' => [
                '2026-04-15',
                '2026-04-01', '2026-04-30',
            ],
            'leap year feb' => [
                '2024-02-15',
                '2024-02-01', '2024-02-29',
            ],
            'december' => [
                '2026-12-25',
                '2026-12-01', '2026-12-31',
            ],
        ];
    }

    #[DataProvider('closingDay31Provider')]
    public function testClosingDay31(string $refDate, string $expectedStart, string $expectedEnd): void
    {
        $period = StatementPeriod::forDate(31, Carbon::parse($refDate));

        $this->assertSame($expectedStart, $period->start->format('Y-m-d'));
        $this->assertSame($expectedEnd, $period->end->format('Y-m-d'));
    }

    public static function closingDay15Provider(): array
    {
        return [
            'before closing' => [
                '2026-02-10',
                '2026-01-16', '2026-02-15',
            ],
            'on closing day' => [
                '2026-02-15',
                '2026-01-16', '2026-02-15',
            ],
            'after closing' => [
                '2026-02-20',
                '2026-02-16', '2026-03-15',
            ],
            'jan cycle' => [
                '2026-01-10',
                '2025-12-16', '2026-01-15',
            ],
        ];
    }

    #[DataProvider('closingDay15Provider')]
    public function testClosingDay15(string $refDate, string $expectedStart, string $expectedEnd): void
    {
        $period = StatementPeriod::forDate(15, Carbon::parse($refDate));

        $this->assertSame($expectedStart, $period->start->format('Y-m-d'));
        $this->assertSame($expectedEnd, $period->end->format('Y-m-d'));
    }

    public function testClosingDay1(): void
    {
        $period = StatementPeriod::forDate(1, Carbon::parse('2026-02-15'));

        $this->assertSame('2026-02-02', $period->start->format('Y-m-d'));
        $this->assertSame('2026-03-01', $period->end->format('Y-m-d'));
    }

    public function testClosingDay1OnDay1(): void
    {
        $period = StatementPeriod::forDate(1, Carbon::parse('2026-03-01'));

        $this->assertSame('2026-02-02', $period->start->format('Y-m-d'));
        $this->assertSame('2026-03-01', $period->end->format('Y-m-d'));
    }

    public function testPreviousNavigation(): void
    {
        $current  = StatementPeriod::forDate(31, Carbon::parse('2026-02-14'));
        $previous = $current->previous();

        $this->assertSame('2026-01-01', $previous->start->format('Y-m-d'));
        $this->assertSame('2026-01-31', $previous->end->format('Y-m-d'));
    }

    public function testNextNavigation(): void
    {
        $current = StatementPeriod::forDate(31, Carbon::parse('2026-02-14'));
        $next    = $current->next();

        $this->assertSame('2026-03-01', $next->start->format('Y-m-d'));
        $this->assertSame('2026-03-31', $next->end->format('Y-m-d'));
    }

    public function testPreviousNavigationMidMonth(): void
    {
        $current  = StatementPeriod::forDate(15, Carbon::parse('2026-02-10'));
        $previous = $current->previous();

        $this->assertSame('2025-12-16', $previous->start->format('Y-m-d'));
        $this->assertSame('2026-01-15', $previous->end->format('Y-m-d'));
    }

    public function testNextNavigationMidMonth(): void
    {
        $current = StatementPeriod::forDate(15, Carbon::parse('2026-02-10'));
        $next    = $current->next();

        $this->assertSame('2026-02-16', $next->start->format('Y-m-d'));
        $this->assertSame('2026-03-15', $next->end->format('Y-m-d'));
    }

    public function testDueDateCalculation(): void
    {
        $period = StatementPeriod::forDate(31, Carbon::parse('2026-02-14'), 8);

        $this->assertNotNull($period->dueDate);
        $this->assertSame('2026-03-08', $period->dueDate->format('Y-m-d'));
    }

    public function testDueDateMidMonthClosing(): void
    {
        $period = StatementPeriod::forDate(15, Carbon::parse('2026-02-10'), 5);

        $this->assertNotNull($period->dueDate);
        $this->assertSame('2026-03-05', $period->dueDate->format('Y-m-d'));
    }

    public function testDueDateNullWhenNoDueDay(): void
    {
        $period = StatementPeriod::forDate(31, Carbon::parse('2026-02-14'));

        $this->assertNull($period->dueDate);
    }

    public function testClosingDayClamped(): void
    {
        $period = StatementPeriod::forDate(99, Carbon::parse('2026-02-14'));

        $this->assertSame('2026-02-01', $period->start->format('Y-m-d'));
        $this->assertSame('2026-02-28', $period->end->format('Y-m-d'));
    }

    public function testClosingDayAccessor(): void
    {
        $period = StatementPeriod::forDate(31, Carbon::parse('2026-02-14'));

        $this->assertSame(31, $period->closingDay());
    }

    public function testChainedNavigation(): void
    {
        $period = StatementPeriod::forDate(31, Carbon::parse('2026-06-15'));
        $threeBack = $period->previous()->previous()->previous();

        $this->assertSame('2026-03-01', $threeBack->start->format('Y-m-d'));
        $this->assertSame('2026-03-31', $threeBack->end->format('Y-m-d'));
    }
}
