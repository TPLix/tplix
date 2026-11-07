<?php
/****************
 * {"Tplix"|substring:2} # renders: ix<br>
 * {"Tplix"|substring:1:3} # renders: pli<br>
 * {"Tplix"|substring:-2} # renders: ix<br>
 * {"Tplix"|substring:-2:1} # renders: i<br>
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;

class TplixCompileSubstring
{
    public static function call(array $args)
    {
        $string = isset($args[0]) ? (string)$args[0] : "";
        $start  = isset($args[1]) ? (int)$args[1] : 0;
        $length = isset($args[2]) ? (int)$args[2] : null;

        if ($length === null) {
            return substr($string, $start);
        }
        return substr($string, $start, $length);
    }
}