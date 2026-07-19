<?php

namespace App\Services;

use Carbon\Carbon;

class EthiopianCalendarService
{
    // Ethiopian month names
    private $ethiopianMonths = [
        1 => 'Meskerem',
        2 => 'Tikimt',
        3 => 'Hidar',
        4 => 'Tahsas',
        5 => 'Tir',
        6 => 'Yekatit',
        7 => 'Megabit',
        8 => 'Miazia',
        9 => 'Ginbot',
        10 => 'Sene',
        11 => 'Hamle',
        12 => 'Nehase',
        13 => 'Pagume',
    ];

    // Ethiopian month names in Amharic
    private $ethiopianMonthsAmharic = [
        1 => 'መስከረም',
        2 => 'ጥቅምት',
        3 => 'ህዳር',
        4 => 'ታህሳስ',
        5 => 'ጥር',
        6 => 'የካቲት',
        7 => 'መጋቢት',
        8 => 'ሚያዝያ',
        9 => 'ግንቦት',
        10 => 'ሰኔ',
        11 => 'ሐምሌ',
        12 => 'ነሐሴ',
        13 => 'ጳጉሜ',
    ];

    /**
     * Convert Gregorian date to Ethiopian date
     * Safely handles leap year alignments and ignores time components
     */
    public function gregorianToEthiopian($gregorianDate)
    {
        // Parse and reset time to 00:00:00 to prevent partial-day float bugs
        $date = Carbon::parse($gregorianDate)->startOfDay();
        $gYear = $date->year;
        
        // Ethiopian New Year falls on Sept 12 in the Gregorian year PRECEDING a Gregorian leap year.
        // E.g., 2023 % 4 == 3 (Sept 12, 2023). 2024 % 4 == 0 (Sept 11, 2024).
        $newYearDayThisYear = ($gYear % 4 == 3) ? 12 : 11;
        $newYearDateThisYear = Carbon::create($gYear, 9, $newYearDayThisYear)->startOfDay();
        
        if ($date->lt($newYearDateThisYear)) {
            // Date is before Sept 11/12, belongs to the previous Ethiopian year
            $ethYear = $gYear - 8;
            $newYearDayLastYear = (($gYear - 1) % 4 == 3) ? 12 : 11;
            $newYearDate = Carbon::create($gYear - 1, 9, $newYearDayLastYear)->startOfDay();
        } else {
            // Date is on or after Sept 11/12, belongs to the current Ethiopian year
            $ethYear = $gYear - 7;
            $newYearDate = $newYearDateThisYear;
        }
        
        // Calculate exact days passed since the Ethiopian New Year
        $diffInDays = $newYearDate->diffInDays($date);
        
        $ethMonth = (int) floor($diffInDays / 30) + 1;
        $ethDay = ($diffInDays % 30) + 1;
        
        // Cap the month at 13 for Pagume leap days (days 361-365/366)
        if ($ethMonth > 13) {
            $ethMonth = 13;
        }
        
        return [
            'year' => $ethYear,
            'month' => $ethMonth,
            'day' => $ethDay,
            'day_of_week' => $date->dayOfWeek, // 0 = Sunday
            'month_name' => $this->ethiopianMonths[$ethMonth] ?? '',
            'month_name_amharic' => $this->ethiopianMonthsAmharic[$ethMonth] ?? '',
        ];
    }

    /**
     * Convert Ethiopian date to Gregorian date
     */
    public function ethiopianToGregorian($ethYear, $ethMonth, $ethDay)
    {
        // Calculate the Gregorian year when this Ethiopian year started
        $gregYear = $ethYear + 7; 
        
        $newYearDay = ($gregYear % 4 == 3) ? 12 : 11;
        $newYearDate = Carbon::create($gregYear, 9, $newYearDay)->startOfDay();
        
        // Calculate days to add based on Ethiopian 30-day months
        $daysToAdd = (($ethMonth - 1) * 30) + ($ethDay - 1);
        
        return $newYearDate->copy()->addDays($daysToAdd);
    }

    /**
     * Format Ethiopian date as string
     */
    public function formatEthiopian($gregorianDate, $format = 'long')
    {
        $eth = $this->gregorianToEthiopian($gregorianDate);
        
        switch ($format) {
            case 'short':
                return sprintf('%02d/%02d/%04d', $eth['day'], $eth['month'], $eth['year']);
            case 'long':
                return $eth['day'] . ' ' . $eth['month_name'] . ' ' . $eth['year'];
            case 'amharic':
                return $eth['day'] . ' ' . $eth['month_name_amharic'] . ' ' . $eth['year'];
            default:
                return $eth['day'] . ' ' . $eth['month_name'] . ' ' . $eth['year'];
        }
    }

    /**
     * Get current Ethiopian date
     */
    public function getCurrentEthiopianDate()
    {
        return $this->gregorianToEthiopian(Carbon::now());
    }

    /**
     * Get Ethiopian month name
     */
    public function getMonthName($monthNumber, $language = 'english')
    {
        if ($language === 'amharic') {
            return $this->ethiopianMonthsAmharic[$monthNumber] ?? '';
        }
        return $this->ethiopianMonths[$monthNumber] ?? '';
    }

    /**
     * Get all Ethiopian months
     */
    public function getMonths()
    {
        $months = [];
        $currentEthYear = $this->getCurrentEthiopianDate()['year'];

        for ($i = 1; $i <= 13; $i++) {
            $months[$i] = [
                'number' => $i,
                'name' => $this->ethiopianMonths[$i],
                'name_amharic' => $this->ethiopianMonthsAmharic[$i],
                'days' => $i == 13 ? $this->getPagumeDays($currentEthYear) : 30,
            ];
        }
        return $months;
    }

    /**
     * Get number of days in Pagume (13th month)
     */
    public function getPagumeDays($ethiopianYear = null)
    {
        $ethiopianYear = $ethiopianYear ?? $this->getCurrentEthiopianDate()['year'];
        
        // Pagume has 5 days normally, 6 days in a leap year
        return $this->isEthiopianLeapYear($ethiopianYear) ? 6 : 5;
    }

    /**
     * Check if Ethiopian year is a leap year
     */
    public function isEthiopianLeapYear($ethiopianYear)
    {
        return ($ethiopianYear % 4 == 3);
    }

    /**
     * Get Ethiopian New Year date in Gregorian
     */
    public function getEthiopianNewYear($gregorianYear = null)
    {
        $year = $gregorianYear ?? Carbon::now()->year;
        $day = ($year % 4 == 3) ? 12 : 11;
        
        return Carbon::create($year, 9, $day)->startOfDay();
    }
}