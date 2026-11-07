<?php
/****************
 * {$article|nl2br}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileNl2br
{
    public function call(array $args = []): mixed
    {
        $value = isset($args[0]) ? $args[0]:0;
        $xhtml = isset($args[1]) ? $args[1]:null;
        if($xhtml === null) return nl2br($value);
        return nl2br($value,$xhtml);
    }
}