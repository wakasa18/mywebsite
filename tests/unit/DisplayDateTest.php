<?php

use App\Libraries\DisplayDate;
use CodeIgniter\Test\CIUnitTestCase;

final class DisplayDateTest extends CIUnitTestCase
{
    public function testReadableDatesAndPeriods(): void
    {
        $this->assertSame('September 30, 2026', DisplayDate::date('2026-09-30'));
        $this->assertSame('September 30, 2026 at 9:05 AM', DisplayDate::dateTime('2026-09-30 09:05:03'));
        $this->assertSame('12:00 AM', DisplayDate::time('2026-09-30 00:00:00'));
        $this->assertSame('12:00 PM', DisplayDate::time('2026-09-30 12:00:00'));
        $this->assertSame('September 2026', DisplayDate::period('2026-09'));
        $this->assertSame('September 2026', DisplayDate::month('2026-09-30'));
        $this->assertSame('—', DisplayDate::month(null));
        $this->assertSame('Week of September 28, 2026', DisplayDate::period('2026-W40'));
        $this->assertSame('Week of December 30, 2024', DisplayDate::period('2025-W01'));
        $this->assertSame('February 29, 2024', DisplayDate::date('2024-02-29'));
    }

    public function testMissingAndInvalidValuesNeverBecomeTodayOrTheEpoch(): void
    {
        foreach ([null, '', false, 'not a date', '0000-00-00', '2026-02-30', '2026-09-30 25:00:00', '2026-09-30 extra'] as $value) {
            $this->assertSame('—', DisplayDate::dateTime($value));
        }
        $this->assertSame('—', DisplayDate::period('2026-13'));
        $this->assertSame('—', DisplayDate::period('2025-W53'));
        $this->assertSame('—', DisplayDate::period('2026-W00'));
    }

    public function testExplicitInstantsUseTheApplicationTimezone(): void
    {
        $utc = new DateTimeImmutable('2026-09-29 18:00:00', new DateTimeZone('UTC'));
        $this->assertSame('September 30, 2026 at 2:00 AM', DisplayDate::dateTime($utc));
        $this->assertSame(DisplayDate::dateTime($utc), DisplayDate::dateTime($utc->getTimestamp()));
    }
}
