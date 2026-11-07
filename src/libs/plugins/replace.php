<?php
/****************
 * {$articleTitle|replace:"abc":"world"}
 * {$articleTitle|replace:['a','b']:['c','d']}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileReplace
{
    public static function call(array $args)
    {
        // Expected order: subject, search, replace
        if (count($args) < 3) return '';
        [$subject, $search, $replace] = $args;
        // Run replacement safely
        return str_replace($search, $replace, $subject);
    }
}
