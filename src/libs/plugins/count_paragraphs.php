<?php
/****************
 * {$articleTitle|count_paragraphs}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCount_paragraphs
{
    public function call(array $args = []): string
    {
        $value = isset($args[0]) ? $args[0]:null;
        //$charset  = Tplix::getInstance()->charset;
        return (preg_match_all('#[\r\n]+#',$value, $tmp)+1);
    }
}