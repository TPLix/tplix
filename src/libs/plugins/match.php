<?php
/****************
 * {$articleTitle|count_words}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileMatch
{
    public function call(array $args = []): mixed
    {
        $string = $args[0] ?? "";
        $string = trim($string);
        $search = isset($args[1]) ? trim($args[1]):null;
        $text = isset($args[2]) ? trim($args[2]):null;
        $text2 = isset($args[3]) ? trim($args[3]):null;
        if($text !== null)
        {
            if($string == $search) return $text;
            return $text2;
        } 
        if($string == $search) return true;
        return false;
    }
}