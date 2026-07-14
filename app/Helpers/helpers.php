<?php

use Carbon\Carbon;

if (!function_exists('esc')) {
    function esc($value)
    {
        if ($value === null) {
            return '';
        }
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('fmt_num')) {
    function fmt_num($value)
    {
        if ($value === null) {
            return '0';
        }
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }
        try {
            $num = (float) ($value ?? 0);
            return $num == floor($num) ? number_format($num) : number_format($num, 2);
        } catch (\Exception $e) {
            return (string) ($value ?? '0');
        }
    }
}

if (!function_exists('round_headcount')) {
    function round_headcount($value)
    {
        try {
            return (int) round((float) ($value ?? 0));
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('fmt_count')) {
    function fmt_count($value)
    {
        return number_format(round_headcount($value));
    }
}

if (!function_exists('duration_days')) {
    function duration_days($start, $end)
    {
        if (empty($start) || empty($end)) {
            return 0;
        }
        try {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);
            return max(0, $endDate->diffInDays($startDate));
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('duration_from_days')) {
    function duration_from_days($days)
    {
        $days = max(0, (int) $days);
        $years = intdiv($days, 365);
        $rem = $days % 365;
        $months = intdiv($rem, 30);
        $dayPart = $rem % 30;
        
        $parts = [];
        if ($years) {
            $parts[] = $years . ' year' . ($years != 1 ? 's' : '');
        }
        if ($months) {
            $parts[] = $months . ' month' . ($months != 1 ? 's' : '');
        }
        if ($dayPart || empty($parts)) {
            $parts[] = $dayPart . ' day' . ($dayPart != 1 ? 's' : '');
        }
        return implode(' ', $parts);
    }
}

if (!function_exists('duration_text')) {
    function duration_text($start, $end)
    {
        if (empty($start) || empty($end)) {
            return '';
        }
        return duration_from_days(duration_days($start, $end));
    }
}

if (!function_exists('valid_iso_date')) {
    function valid_iso_date($value, $required = false)
    {
        $value = trim($value ?? '');
        if (empty($value) && !$required) {
            return '';
        }
        try {
            Carbon::parse($value);
            return $value;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Enter a valid date in YYYY-MM-DD format.');
        }
    }
}