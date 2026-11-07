<?php
/****************
 * {* This is a Tplix comment *}
 * {*
 * This is a multi line
 * Tplix comment!
 * *}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileComments
{
    public function call(array $args = []): string
    {
        $value = $args["value"] ?? "";
        return '<!--'.$value.'-->';
    }
}