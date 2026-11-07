<?php
/****************
 * Tplix noprint modifier plugin
 * Type:     modifier
 * Name:     noprint
 * Purpose:  return an empty string
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;

class TplixCompileNoprint
{
    public function call(array $args = []): string
    {
        return "";
    }
}