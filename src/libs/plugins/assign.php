<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileAssign
{
    function call(array $params = [])
    {
        $name = $params["params"]["var"];
        $value = "";
        $modifiers = [];
        if(isset($params["params"]["value"]["type"]))
        {
            $value = $params["params"]["value"]["name"];
            $modifiers = $params["params"]["value"]["modifiers"];
        }else{
            $value = $params["params"]["value"];
            if(isset($params["params"]["modifiers"]))
            {
                $modifiers = $params["params"]["modifiers"];
            }
        }

        //Tplix::getInstance()->assign($name,$value);
        // If it's already a variable/expression ($, array, function call), don't quote
        $name = $this->replaceVariables("$".$name);
        $name = ltrim($name,"$");
        if (is_numeric($value)) {
            return "$".ltrim($name,"$")."={$value}";
        }
        if (is_string($value))
        {
            // If it's a variable/expression (starts with $, [, {, or ( ), leave raw
            if (preg_match('/^(\$|\[|\{|\()/',$value))
            {
                $value = Tplix::getInstance()->PluginCall("variable",["name"=>$value,"modifiers"=>$modifiers]);
                return "$".$name."={$value}";
            }
            if ($value[0] != "'" && $value[0] != '"') return "$".$name."=" . var_export($value, true);
        }
        if (is_array($value))
        {
            // arrays need export to proper PHP
            return "$".$name."=" . var_export($value, true);
        }
        if($value[1] == "{")
        {
            $value = str_replace(["{","}","'",'"'],[""],$value);
        }
        // Fallback
        return "$".$name."=".Tplix::getInstance()->PluginCall("variable",["name"=>$value,"modifiers"=>$modifiers]);
        
        //$expr = $this->replaceVariables($expr);
    }
    protected function replaceVariables(string $expr): string
    {
        return preg_replace_callback(
            '/\$(\w+(?:[.\w\[\]\->]*)?)(\|[a-zA-Z_]\w*(?::[^|]+)*)*/',
            function ($m) {
                $fullExpr = $m[0]; // e.g. $key|upper or $user.name|truncate:10

                // split base variable and modifiers
                $parts = explode('|', $fullExpr);
                $vname = array_shift($parts); // e.g. "$key" or "$user.name"
                $modifiers = [];
                foreach ($parts as $modifier) {
                    [$name, $args] = array_pad(explode(':', $modifier, 2), 2, null);
                    $argsList = [];
                    if ($args !== null) {
                        $argsList = array_map('trim', explode(':', $args));
                    }
                    $modifiers[] = ["name"=>$name,"args"=>$argsList];
                }
                return Tplix::getInstance()->PluginCall("variable",["name"=>$vname,"modifiers"=>$modifiers]);
            },
            $expr
        );
    }    
}