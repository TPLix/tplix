<?php
/**
 * Conditional block, the syntax is very similar to the php one, allowing () || && and
 * other php operators. Additional operators and their equivalent php syntax are as follow :.
 * eq -> ==
 * neq or ne -> !=
 * gte or ge -> >=
 * lte or le -> <=
 * gt -> >
 * lt -> <
 * mod -> %
 * not -> !
 * X is [not] div by Y -> (X % Y) == 0
 * X is [not] even [by Y] -> (X % 2) == 0 or ((X/Y) % 2) == 0
 * X is [not] odd [by Y] -> (X % 2) != 0 or ((X/Y) % 2) != 0
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the use of this software.
 */
use Tplix\Tplix;
use Tplix\Plugins;
use Tplix\Template;
class TplixCompileCondition
{
    public function parse($expr): string
    {
        // Replace variables ($key, $user.name, $user->prop)
        $expr = $this->handleExpression($expr);
        return $expr;
    }
    // Function to replace operators **only outside quotes**
    function replaceOperatorsOutsideStrings($expr, $map) {
        // Split by quoted strings, keeping quotes
        $parts = preg_split('/(".*?"|\'.*?\')/', $expr, -1, PREG_SPLIT_DELIM_CAPTURE);
    
        foreach ($parts as &$part) {
            // Only replace if NOT inside quotes
            if (!preg_match('/^["\'].*["\']$/', $part)) {
                $part = preg_replace(array_keys($map), array_values($map), $part);
            }
        }
    
        return implode('', $parts);
    }    
    function handleExpression($expr){
        // Replace common operators
        $map = [
            '/\beq\b/'  => '==',
            '/\bne\b/'  => '!=',
            '/\bneq\b/' => '!=',
            '/\bgte\b/' => '>=',
            '/\bge\b/'  => '>=',
            '/\blte\b/' => '<=',
            '/\ble\b/'  => '<=',
            '/\bgt\b/'  => '>',
            '/\blt\b/'  => '<',
            '/\bmod\b/' => '%',
            '/\band\b/' => '&&',
            '/\bor\b/'  => '||',
            '/\bnot\b/' => '!',
        ];
        $expr = $this->replaceOperatorsOutsideStrings($expr,$map);
        $expr = str_replace(["! =="],["!=="],$expr);
        // Handle "is ..." operators
        $expr = $this->handleIsExpressions($expr);
        //$v = $this->tokenizeExpression('$dtp["Pricing"][0].$dtp["default_registration_period"].USD.Prices.Register>0');
        //$vals = $this->tokenizeExpression('($dtp["Pricing"][0].{$dtp["default_registration_period"]}.USD.Prices.Register)>0 && $item==1');
        //echo $expr;
        //echo "<br>";
        //echo $vvv = Template::parseComplexVariableName($expr);
        $vals = $this->tokenize($expr);
        //$vals = $this->tokenize('($dtp["Pricing"][0].{$dtp["default_registration_period"]}.USD.Prices.Register)>0 && $item=="waleed"');
        $cond = [];
        foreach($vals as $i)
        {
            if($i[0] == "$")
            {
                $cond[] = Plugins::call('variable',["name"=>$i]);
            }else{
                $cond[] = $i;
            }
        }
        $expr = implode("",$cond);
        //$expr = str_replace(["( "," )","! "," , ","[ "," ]","y ("],["(",")","!",",","[","]","y("],$expr);
        //$expr = $this->replaceVariables($expr);
        return $expr;
    }
function tokenize(string $expr): array {
    $pattern = '/
        "(?:\\\\.|[^"])*"                 # double-quoted strings
      | \'(?:\\\\.|[^\'])*\'              # single-quoted strings
      | \s+                               # whitespace (RETURN as token)
      | \$\{?[\w\[\]"]+\}?                # $var or {$var}
        (?:\.(?:\{?\$?[\w\[\]"]+\}?))*    # .something or .{$var}
      | \$[\w\[\]"]+                      # simple $var
      | \d+(?:\.\d+)?                     # numbers
      | &&|\|\|                           # logical ops
      | ==|!=|>=|<=|<>|->                       # comparison ops
      | [><=()!+*\-\/\[\],]               # single operators, parens, brackets, comma
      | [A-Za-z_]\w*(?:\.[A-Za-z_]\w*)*   # constants like USD.Prices.Register
    /x';

    preg_match_all($pattern, $expr, $matches);
    return array_values($matches[0]);
}

   
    protected function handleIsExpressions(string $expr): string
    {
        $expr = preg_replace('/(\$\w+)\s+is\s+div\s+by\s+(\$\w+|\d+)/i', '($1 % $2) == 0', $expr);
        $expr = preg_replace('/(\$\w+)\s+is\s+not\s+div\s+by\s+(\$\w+|\d+)/i', '($1 % $2) != 0', $expr);
        $expr = preg_replace('/(\$\w+)\s+is\s+even\b/i', '($1 % 2) == 0', $expr);
        $expr = preg_replace('/(\$\w+)\s+is\s+even\s+by\s+(\$\w+|\d+)/i', '(($1 / $2) % 2) == 0', $expr);
        $expr = preg_replace('/(\$\w+)\s+is\s+odd\b/i', '($1 % 2) != 0', $expr);
        $expr = preg_replace('/(\$\w+)\s+is\s+odd\s+by\s+(\$\w+|\d+)/i', '(($1 / $2) % 2) != 0', $expr);
        return $expr;
    }
    protected function replaceVariables(string $expr): string
    {
        return preg_replace_callback(
            // Match $variable chains (with . or ->) but NOT numeric literals
            '/\$[\w\[\]"]+(?:\.(?:[\w\[\]"]+))*/',
            function ($matches) {
                $fullExpr = $matches[0]; // e.g. $item.CartItemId
    
                // Split base variable and modifiers
                $parts = explode('|', $fullExpr);
                $vname = array_shift($parts); // e.g. $item.CartItemId
                $modifiers = [];
    
                foreach ($parts as $modifier) {
                    [$name, $args] = array_pad(explode(':', $modifier, 2), 2, null);
                    $argsList = [];
                    if ($args !== null) {
                        $argsList = array_map('trim', explode(':', $args));
                    }
                    $modifiers[] = ["name" => $name, "args" => $argsList];
                }
    
                // Call PluginCall to transform variable, leaving numbers untouched
                return Tplix::getInstance()->PluginCall("variable", [
                    "name" => $vname,
                    "modifiers" => $modifiers
                ]);
            },
            $expr
        );
    }

    public function call(array $args = []): string
    {
        $name = "else";
        if ($args["type"] == "IF_START") $name = "if";
        if ($args["type"] == "IF_END") $name = "endif";
        if ($args["type"] == "ELSEIF_START") $name = "elseif";
        if ($args["type"] == "FORELSEIF_START") $name = "elseif";
        $fun = "compile".ucfirst($name);
        //$condition = $args['condition'];
        //$expr = Plugins::call('variable',["name"=>$expr]);
        if(!empty($args["variables"]))
        {
            $variables = [];
            foreach ($args["variables"] as $k=>$v){
                $v = Plugins::call('variable',["name"=>$v]);
                $variables[$k]=$v;
            }
            $condition = str_replace(array_keys($variables),array_values($variables),$args["condition"]);
        }else{
            $condition = $args["condition"];
        }
        //if($condition !== null) $condition = str_replace(["{","}"],"",$condition);
        return $this->$fun($condition);
    }

    public function compileIf($condition=""): string
    {
        $cond = $this->parse($condition);
        return "if($cond){ ";
    }

    public function compileElseif($condition=""): string
    {
        $cond = $this->parse($condition);
        return "}elseif($cond){ ";
    }

    public function compileElse($condition=""): string
    {
        return "}else{ ";
    }

    public function compileEndif($condition=""): string
    {
        return "} ";
    }
}