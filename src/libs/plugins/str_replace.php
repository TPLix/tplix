<?php
/****************
 * {$articleTitle|replace:"abc":"world"}
 * {$articleTitle|replace:['a','b']:['c','d']}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileStr_replace
{
    public static function call(array $args)
    {
        // Expected order: subject, search, replace
        if (count($args) < 3) return '';
        [$search, $replace,$subject] = $args;
        // Run replacement safely
        return str_replace($search, $replace, $subject);
    }
}
