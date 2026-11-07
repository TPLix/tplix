<?php
/****************
 * {$foo|explode:'PHP_EOL'}
 * *************/
use Tplix\Tplix;

class TplixCompileExplode
{
    public function call(array $args = []): array
    {
        $string = isset($args[0]) ? $args[0]:null;
        if($string === null) return [];
        $limit = isset($args[2]) ? $args[2]:null;
        $separator = isset($args[1]) ? $args[1]:'';
        if(strpos($separator,"_EOL") !== false)
        {
            return explode(PHP_EOL, $string ?? '', $limit ?? PHP_INT_MAX);
        }
        return explode($separator, $string ?? '', $limit ?? PHP_INT_MAX);
    }
}