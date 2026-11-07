<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileVariable
{
    public function call(array $args = []): string
    {
        $name      = $args["name"] ?? null;       // e.g. "$val.id", "$foo_{$x+$y}"
        $line      = $args["line"] ?? 1;
        $modifiers = $args["modifiers"] ?? [];    // list of modifier plugins
    
        if (!$name) {
            return '';
        }
    
        // Step 1: Compile variable to a raw PHP expression (now handles complex names)
        $compiled = Tplix::compileVariableExpression($name);
        // $compiled should now be something like $scope['foo_' . ($scope['x'] + $scope['y'])]
    
        // Handle capture variables if necessary (adjust regex if $compiled format changes significantly)
        if (strpos($compiled, '$tplVar["capture"]') !== false) {
            // Extract the capture name from: $tplVar["capture"]["default"]
            preg_match('/\$tplVar\["capture"\]\["([^"]+)"\]/', $name, $matches); // Use $name or adjust based on how capture variables are structured
            $capture_name = $matches[1] ?? 'default';
            return "\$this->scope['captures']['$capture_name']";
        }
        if (strpos($compiled, '$tplVar["config"]') !== false)
        {
            $compiled = str_replace('$tplVar["config"]','$this->scope["tplVar"]["config"]',$compiled);
        }
        // Step 2: Apply modifiers if any
        if (!empty($modifiers)) {
            // Check if $compiled is already an expression (like $scope['...'])
            // If it starts with $scope[ or similar, it's an expression
            $is_compiled_expression = (strpos($compiled, '$scope[') === 0); // Adjust based on your actual scope access pattern
    
            if ($is_compiled_expression) {
                // Pass the already compiled expression as the first argument to the modifier pipeline
                $compiled = $this->compileVariablePipeline($compiled, $modifiers);
            } else {
                // It might still be a simple variable name like $foo, or something unexpected
                // The original check might still apply for truly simple names
                if ($compiled !== '' && !in_array($compiled[0],["'","$",'"',"("])) {
                    $compiled = "'" . $compiled . "'"; // Treat as string literal if not variable or expression
                }
                $compiled = $this->compileVariablePipeline($compiled, $modifiers);
            }
        }
    
        // Step 3: Return the compiled PHP expression (no echo here, caller does that)
        return $compiled;
    }
    private function compileVariablePipeline(string $compiled, array $modifiers): string
    {
        foreach ($modifiers as $modifier)
        {
            $pluginName = $modifier['name'];      // e.g. "upper"
            $pluginArgs = $modifier['args'] ?? [];
            // Convert args into PHP code
            $compiledArgs = [];
            foreach ($pluginArgs as $arg)
            {
                
                // If argument is already an expression (variable, function, number), keep raw
                if (is_string($arg) && preg_match('/^\$|^[0-9]+$|^\(.*\)$/', $arg))
                {
                    $var = Tplix::compileVariableExpression($arg);
                    $compiledArgs[] = $var;
                } else {
                    if(isset($arg["type"]))
                    {
                        $var = $this->call(["name"=>$arg["name"],"modifiers"=>$arg["modifiers"],"line"=>$arg["line"]]);
                        $compiledArgs[] = $var;
                    }else{
                        $compiledArgs[] = var_export($arg, true);
                    }
                }
            }
            // prepend the pipeline variable/expression
            array_unshift($compiledArgs, $compiled);
            // join into function call
            $compiled = "\$this->PluginCall('{$pluginName}', [" . implode(",", $compiledArgs) . "])";
        }
        return $compiled;
    }    
}