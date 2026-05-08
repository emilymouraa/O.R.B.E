<?php
namespace App\Helpers;

class Toast
{
    public static function set(
        string $type,
        string $title,
        string $desc = '',
        int    $duration = 3000
    ): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['toast'] = compact('type', 'title', 'desc', 'duration');
    }

    public static function success(string $title, string $desc = ''): void
    {
        self::set('success', $title, $desc, 3000);
    }

    public static function error(string $title, string $desc = ''): void
    {
        self::set('error', $title, $desc, 5000);
    }

    public static function warning(string $title, string $desc = ''): void
    {
        self::set('warning', $title, $desc, 4000);
    }

    public static function info(string $title, string $desc = ''): void
    {
        self::set('info', $title, $desc, 3000);
    }
}