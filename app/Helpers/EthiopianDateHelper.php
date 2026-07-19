<?php

namespace App\Helpers;

use App\Services\EthiopianCalendarService;

class EthiopianDateHelper
{
    private static $instance = null;
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new EthiopianCalendarService();
        }
        return self::$instance;
    }
    
    public static function dualDate($gregorianDate)
    {
        if (!$gregorianDate) return '-';
        
        $eth = self::getInstance();
        $ethDate = $eth->gregorianToEthiopian($gregorianDate);
        
        $html = '<span style="font-weight:600;">' . \Carbon\Carbon::parse($gregorianDate)->format('M d, Y') . '</span>';
        $html .= ' <span style="font-size:11px; color:#627386;">🇪🇹 ' . $ethDate['day'] . ' ' . $ethDate['month_name'] . ' ' . $ethDate['year'] . '</span>';
        
        return $html;
    }
    
    public static function ethDate($gregorianDate)
    {
        if (!$gregorianDate) return '-';
        
        $eth = self::getInstance();
        $ethDate = $eth->gregorianToEthiopian($gregorianDate);
        return $ethDate['day'] . ' ' . $ethDate['month_name'] . ' ' . $ethDate['year'];
    }
    
    public static function todayEthiopian()
    {
        return self::getInstance()->getCurrentEthiopianDate();
    }
}
