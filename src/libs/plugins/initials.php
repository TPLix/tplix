<?php
/****************
 * {$articleTitle}<br>
 * {$articleTitle|initials:2}<br>
 * *************/
/**
 * Initials a string to a given number of characters
 *
 * @param string  $string      Input string
 * @param integer $limit       Max character from string (default: 2)
 * @return string
 */
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileInitials
{
    public static function call(array $args)
    {
        $string = trim($args[0] ?? '');
        $limit  = (int)($args[1] ?? 2);

        if ($string === '') {
            return '';
        }

        $words = preg_split('/\s+/', $string);
        $initials = '';
        $count = 0;

        foreach ($words as $word) {
            if ($word === '') continue;

            $initials .= strtoupper(mb_substr($word, 0, 1));
            $count++;

            if ($count >= $limit) {
                break;
            }
        }

        return $initials;
    }
}