<?php
/****************
 * {$articleTitle|count_words}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileIset
{
    public function call(array $args = []): mixed
    {
        $from = $args[0];
        $check = isset($args[1]) ? $args[1]:null;
        $equal = isset($args[2]) ? $args[2]:null;

        if($check === null && $equal === null) return isset($from);
        if($check !== null && $equal === null)
        {
            return isset($from[$check]) ? $from[$check]:"";
        }
        return $from[$check] == $equal;
    }
}