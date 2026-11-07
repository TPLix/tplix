<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileLoop
{
    function call(array $args = [], int $LOOP_ID = 0): string
    {
        // Safety: Ensure args
        $type = $args['type'] ?? '';
        $hasElse = $args['hasElse'] ?? 0;
        $loopNo = $args['loopNo'] ?? 0;
        $params = isset($args['params']) && is_array($args['params']) ? $args['params'] : [];
    
        // Extract variables safely
        $originalFrom = isset($params['array']) && is_string($params['array']) ? $params['array'] : null;
        $originalFrom !== null ? preg_replace('/^\$/', '', $originalFrom) : null;
        $item = isset($params['var']) && is_string($params['var']) ? preg_replace('/^\$/', '', $params['var']) : null;
    
        $loopName = (isset($params['name']) && is_string($params['name']) && trim($params['name']) !== '') 
                    ? trim($params['name']) 
                    : 'default';
    
        // Optional params
        $start = isset($params['start']) ? (int)$params['start'] : 0;
        $max   = isset($params['max']) ? (int)$params['max'] : null;
        $step  = isset($params['step']) ? (int)$params['step'] : 1;
        $show  = isset($params['show']) ? (int)$params['show'] : 0;
        $totalRecords  = isset($params['loop']) ? (int)$params['loop'] : 0;
        $max = $max ?? 0;
        // Prepare output
        $output = '';
    
        if ($type === 'LOOP_START') {
            if (!$originalFrom) throw new Exception("Foreach <em>from</em> parameter missing");
            if (!$item) throw new Exception("Foreach <em>item</em> parameter missing");
            if($originalFrom[0] != "$" && $originalFrom[0] !== '[') $originalFrom = "$".$originalFrom;
            $originalFrom = Tplix::compileVariableExpression($originalFrom);
            $originalFrom = $this->replaceVariables($originalFrom);
            //$output .= TPLIX_PHP_OPEN . "\n";
            $output .= '$loop_' . $loopName . '[' . $LOOP_ID . '] = ' . $originalFrom . ';' . "\n";
            // Slice array if start/max are provided
            if ($start>=0 && $max>0) {
                $sliceCode = '$loop_' . $loopName . '[' . $LOOP_ID . '] = array_slice('
                           . '$loop_' . $loopName . '[' . $LOOP_ID . '], '
                           . ($start ?? 0) . ', ' .$max. ', false);';
                $output .= $sliceCode . "\n";
            }
            // Apply step
            if ($step > 1)
            {
                $output .= '$loop_' . $loopName . '[' . $LOOP_ID . '] = array_filter('
                         . '$loop_' . $loopName . '[' . $LOOP_ID . '], function($k){ return $k % ' . $step . ' === 0; }, ARRAY_FILTER_USE_KEY);' 
                         . "\n";
            }
            $key  = isset($params['key']) && is_string($params['key']) ? preg_replace('/^\$/', '', $params['key']) : "key";
            // Build foreach
            if($hasElse == 1)
            {
                $output .= Plugins::call('condition',["type"=>"IF_START","condition"=>'!empty($loop_' . $loopName . '[' . $LOOP_ID . '])']);
                $output .="\n";
            }
            $output .='$fl'.$loopNo.'_i = $fl'.$loopNo.'_iteration = $fl'.$loopNo.'_first = $fl'.$loopNo.'_last = $fl'.$loopNo.'_key = $fl'.$loopNo.'_total=0;'."\n";
            $output .='$fl'.$loopNo.'_total=count($loop_' . $loopName . '[' . $LOOP_ID . ']);'."\n";
            $output .='$fl'.$loopNo.'_index =-1;'."\n";
            $output .='$fl'.$loopNo.'_show = empty($fl'.$loopNo.'_total) ? false:true;'."\n";

            $output .='$fl'.$loopNo.'_keys = array_keys('.$originalFrom.'[0]);'."\n";
            if ($key === null) {
                $output .= 'foreach($loop_' . $loopName . '[' . $LOOP_ID . '] as $' . $key . ' => $' . $item . '): ';
            } else {
                $output .= 'foreach($loop_' . $loopName . '[' . $LOOP_ID . '] as $' . $key . ' => $' . $item . '): ';
            }
            $output .='$fl'.$loopNo.'_key=$'.$key.';'."\n";
            $output .='$fl'.$loopNo.'_iteration+=1;'."\n";
            $output .='$fl'.$loopNo.'_i++;'."\n";
            $output .='if($fl'.$loopNo.'_i == 1){$fl'.$loopNo.'_first=1;}else{$fl'.$loopNo.'_first=null;}'."\n";
            $output .='$fl'.$loopNo.'_last = ($fl'.$loopNo.'_iteration === $fl'.$loopNo.'_total);'."\n";
            $output .='$fl'.$loopNo.'_index++;'."\n";
            // Export each key as a variable automatically
            $output .= 'foreach ($fl' . $loopNo . '_keys as $fl' . $loopNo . '_k) {' . "\n";
            $output .= '    ${$fl' . $loopNo . '_k} = $' . $item . '[$fl' . $loopNo . '_k] ?? null;' . "\n";
            $output .= '}' . "\n";
            return $output;
        }
    
        if ($type === 'LOOP_END') {
            return "\nendforeach;\n";
        }
        return '';
    }
    protected function replaceVariables(string $expr): string
    {
        return preg_replace_callback(
            '/\$(\w+(?:\[[^\]]+\])*)(\|[a-zA-Z_]\w*(?::[^|]+)*)*/',
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
                return Plugins::call("variable",["name"=>$vname,"modifiers"=>$modifiers]);
            },
            $expr
        );
    }     
}