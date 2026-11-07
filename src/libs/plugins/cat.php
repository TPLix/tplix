<?php
/****************
 * {$name|cat:' World!'}
 * {$name|cat:' yesterday':' but any other day'}
 * {"When will you come— "|cat:' tomorrow':' or':' some':' other day?'}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCat
{
    public function call(array $args = []): string
    {
        $value = isset($args[0]) ? $args[0]:null;
        //$charset  = Tplix::getInstance()->charset;
        return implode("", $args);
    }
}