<?php
/****************
 * Tplix indent modifier plugin
 * Type:     modifier
 * Name:     indent
 * Purpose:  indent lines of text
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;

class TplixCompileIndent
{
    public function call(array $args = []): string
    {
        $text   = $args[0] ?? "";
        $count  = isset($args[1]) ? (int)$args[1] : 4;
        $indent = isset($args[2]) ? (string)$args[2] : " ";
        return preg_replace('!^!m', str_repeat($indent, $count), $text);
    }
}