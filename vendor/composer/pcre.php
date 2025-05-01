<?php

namespace Composer\Pcre;

class Preg
{
    public static function replace(string $pattern, string $replacement, string $subject, int $limit = -1): string
    {
        return preg_replace($pattern, $replacement, $subject, $limit);
    }

    public static function match(string $pattern, string $subject, array &$matches = null, int $flags = 0, int $offset = 0): int|false
    {
        return preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    public static function matchAll(string $pattern, string $subject, array &$matches = null, int $flags = 0, int $offset = 0): int|false
    {
        return preg_match_all($pattern, $subject, $matches, $flags, $offset);
    }

    public static function quote(string $str, string $delimiter = null): string
    {
        return preg_quote($str, $delimiter);
    }
}
