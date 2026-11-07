<?php
/****************
 * {$articleTitle|regex_replace:"/[\r\t\n]/":" "}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileRegex_replace
{
    public static function call(array $args)
    {
        if (count($args) < 2) return '';
    
        $subject = $args[0];
        $pattern = trim($args[1], "'\""); // <-- FIX
        $replace = isset($args[2]) ? $args[2]:null;
        if (strpos($pattern, '\\\\') !== false) {
            $pattern = stripslashes($pattern);
        }
        // Add delimiters only if missing
        if ($pattern[0] !== '/' || substr($pattern, -1) !== '/') {
            $pattern = '/' . trim($pattern, '/') . '/';
        }
        if($replace === null)
        {
            $values = preg_replace($pattern,"", $subject);
        }else{
            $values = preg_replace($pattern, $replace, $subject);
        }
        return $values;
    }
}
