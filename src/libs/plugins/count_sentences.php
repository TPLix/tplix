<?php
/****************
 * {$articleTitle|count_sentences}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCount_sentences
{
    public function call(array $args = []): string
    {
        $value = isset($args[0]) ? $args[0]:null;
        //$charset  = Tplix::getInstance()->charset;
        return preg_match_all('#[\\w\\pL]\\.(?![\\w\\pL])#u', $value,$tmp);
    }
}