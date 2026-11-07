<?php
/****************
 * {$articleTitle|count_words}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileIsset
{
    public function call(array $args = []): string
    {
        $value = isset($args[0]) ? $args[0]:"";
        return $value;
    }
}