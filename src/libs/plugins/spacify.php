<?php
/****************
 * {"Something Went Wrong in Jet Crash, Experts Say."|spacify}
 * {"Something Went Wrong in Jet Crash, Experts Say."|spacify:"^^"}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileSpacify
{
    public static function call(array $args)
    {
        $string = isset($args[0]) ? (string)$args[0] : "";
        $spaces  = isset($args[1]) ? $args[1] : " ";
        // Run replacement safely
        return implode($spaces, preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY));
    }
}
