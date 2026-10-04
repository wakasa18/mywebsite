<?php

namespace App\Libraries;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/** Presentation only: never use these labels as stored dates or filter values. */
final class DisplayDate
{
    public static function date(mixed $value): string
    {
        return self::parse($value)?->format('F j, Y') ?? '—';
    }

    public static function dateTime(mixed $value): string
    {
        return self::parse($value)?->format('F j, Y \\a\\t g:i A') ?? '—';
    }

    public static function time(mixed $value): string
    {
        return self::parse($value)?->format('g:i A') ?? '—';
    }

    public static function month(mixed $value): string
    {
        return self::parse($value)?->format('F Y') ?? '—';
    }

    public static function period(mixed $value): string
    {
        if (is_string($value) && preg_match('/^(\d{4})-W(\d{2})$/D', $value, $parts)) {
            $date = (new DateTimeImmutable('now', self::timezone()))->setISODate((int) $parts[1], (int) $parts[2], 1);
            return $date->format('o-\\WW') === $value ? 'Week of ' . self::date($date) : '—';
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/D', $value)) {
            return self::parse($value . '-01')?->format('F Y') ?? '—';
        }
        return self::date($value);
    }

    private static function timezone(): DateTimeZone
    {
        return new DateTimeZone(config('App')->appTimezone);
    }

    private static function parse(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTimezone(self::timezone());
        }
        if (is_int($value)) {
            return (new DateTimeImmutable('@' . $value))->setTimezone(self::timezone());
        }
        if (!is_string($value) || trim($value) === '' || str_contains($value, "\0")) {
            return null;
        }
        // Validate SQL values strictly: null/zero/invalid dates must not become 1970 or today.
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value, self::timezone());
            if ($date !== false && $date->format($format) === $value) {
                return $date;
            }
        }
        return null;
    }
}
