<?php

namespace Tests\Unit;

use App\Helpers\BusColorHelper;
use Tests\TestCase;

class BusColorHelperTest extends TestCase
{
    public function test_returns_red_when_action_happens_after_departure(): void
    {
        $color = BusColorHelper::getColor('16-05-2026 10:00 AM', '16-05-2026 10:01 AM');

        $this->assertSame(BusColorHelper::COLOR_RED, $color);
    }

    public function test_returns_yellow_when_difference_is_thirty_minutes_or_less(): void
    {
        $color = BusColorHelper::getColor('16-05-2026 10:00 AM', '16-05-2026 09:30 AM');

        $this->assertSame(BusColorHelper::COLOR_YELLOW, $color);
    }

    public function test_returns_green_when_difference_is_two_hours_or_less(): void
    {
        $color = BusColorHelper::getColor('16-05-2026 10:00 AM', '16-05-2026 08:15 AM');

        $this->assertSame(BusColorHelper::COLOR_GREEN, $color);
    }

    public function test_returns_white_when_difference_is_six_hours_or_less(): void
    {
        $color = BusColorHelper::getColor('16-05-2026 10:00 AM', '16-05-2026 04:30 AM');

        $this->assertSame(BusColorHelper::COLOR_WHITE, $color);
    }

    public function test_returns_white_when_difference_is_more_than_six_hours(): void
    {
        $color = BusColorHelper::getColor('16-05-2026 10:00 AM', '16-05-2026 01:00 AM');

        $this->assertSame(BusColorHelper::COLOR_WHITE, $color);
    }

    public function test_handles_cross_date_values_correctly(): void
    {
        $color = BusColorHelper::getColor('17-05-2026 12:15 AM', '16-05-2026 11:55 PM');

        $this->assertSame(BusColorHelper::COLOR_YELLOW, $color);
    }

    public function test_handles_am_pm_values_correctly(): void
    {
        $color = BusColorHelper::getColor('16-05-2026 12:15 PM', '16-05-2026 11:00 AM');

        $this->assertSame(BusColorHelper::COLOR_GREEN, $color);
    }

    public function test_returns_white_for_invalid_values(): void
    {
        $color = BusColorHelper::getColor('invalid-date', '16-05-2026 09:00 AM');

        $this->assertSame(BusColorHelper::COLOR_WHITE, $color);
    }
}
