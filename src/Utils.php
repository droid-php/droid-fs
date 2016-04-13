<?php

namespace Droid\Plugin\Fs;

class Utils
{
    public static function normalizePath($path)
    {
        switch ($path[0]) {
            case '/':
                break;
            case '~':
                $home = getenv("HOME");
                $path = $home . '/' . $path;
                break;
            default:
                $path = getcwd() . '/' . $path;
                break;
        }
        return $path;
    }
}
