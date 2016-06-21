<?php

namespace Droid\Plugin\Fs;

use RuntimeException;

class Utils
{
    public static function normalizePath($path)
    {
        if (preg_match('@^\w+://@', $path)) {
            return $path; // has a scheme, e.g. "file://"
        }
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

    public static function getContents($filename)
    {
        if (substr($filename, 0, 5) == 'data:') {
            // parse as data-uri
            $content = file_get_contents($filename);
        } else {
            $filename = Utils::normalizePath($filename);
            if (!file_exists($filename)) {
                throw new RuntimeException("File not found: " . $filename);
            }
            $content = file_get_contents($filename);
        }
        return $content;
    }
}
