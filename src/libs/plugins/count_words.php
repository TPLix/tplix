<?php
/****************
 * {$articleTitle|count_words}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCount_words
{
    public function call(array $args = []): string
    {
        $value = isset($args[0]) ? $args[0]:null;
        $charset  = Tplix::getInstance()->charset;
        return preg_match_all(strcasecmp($charset,'utf-8')===0 ? '#[\w\pL]+#u' : '#\w+#',$value, $tmp);
    }
}