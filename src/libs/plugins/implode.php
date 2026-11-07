<?php
/****************
 * {$foo|implode:'PHP_EOL'}
 * *************/
use Tplix\Tplix;

class TplixCompileImplode
{
    public function call(array $args = []): string
    {
        $separate = isset($args[0]) ? $args[0]:null;
        $limit = isset($args[2]) ? $args[2]:null;
        $text = isset($args[1]) ? $args[1]:'';
        if(is_array($separate))
        {
            $separator = $text;
            $string = $separate;
        }else{
            $separator = $separate;
            $string = $text;            
        }

        if($string === null) return "";
        if(strpos($separator,"_EOL") !== false)
        {
            if($limit === null) return implode(PHP_EOL, $string ?? '');
            return implode(PHP_EOL, $string ?? '', $limit ?? PHP_INT_MAX);
        }
        if($limit === null) return implode($separator, $string ?? '');
        return implode($separator, $string ?? '', $limit ?? PHP_INT_MAX);
    }
}