<?php
/****************
 * {"Tplix"|substr:2} # renders: ix<br>
 * {"Tplix"|substr:1:3} # renders: pli<br>
 * {"Tplix"|substr:-2} # renders: ix<br>
 * {"Tplix"|substr:-2:1} # renders: i<br>
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileSubstr
{
    public static function call(array $args)
    {
        $encoding = "UTF-8";
        $string = isset($args[0]) ? (string)$args[0] : "";
        $start  = isset($args[1]) ? (int)$args[1] : 0;
        $length = isset($args[2]) ? (int)$args[2] : null;
        if ($length === null)
        {
            return mb_substr($string, $start, null, $encoding);
        }
        return mb_substr($string, $start, $length, $encoding);
    }
}
