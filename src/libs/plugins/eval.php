<?php
/****************
 * {eval var=$foo}
 * {eval var=#title#}
 * {eval var=#ErrorCity#}
 * {eval var=#ErrorState# assign='state_error'}
 * *************/
use Tplix\Tplix;

class TplixCompileEval
{
    public function call(array $args = []): string
    {
        print_r($args);
        die();
        $value = isset($args["value"]) ? $args["value"]:null;
        return '<!--'.$value.'-->';
    }
}