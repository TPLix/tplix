<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileForeach
{
    function call(array $args = [], int $FOREACH_ID = 0): string
    {
        $type = $args['type'] ?? '';
        $hasElse = $args['hasElse'] ?? 0;
        $loopNo = $args['loopNo'] ?? 0;
        $params = $args['params'] ?? [];
        $extra = array_flip($args['extra_variables'] ?? []);

        // Determine which loop variables to generate
        $isIterationAllowed = isset($extra["iteration"]);
        $isFirstAllowed     = isset($extra["first"]);
        $isLastAllowed      = isset($extra["last"]);
        $isIndexAllowed     = isset($extra["index"]);
        $isTotalAllowed     = isset($extra["total"]);
        $isShowAllowed      = isset($extra["show"]);
        $isKeyAllowed       = isset($extra["key"]);


        // Extract loop parameters
        $originalFrom = $params['from'] ?? null;
        $item = isset($params['item']) ? preg_replace('/^\$/', '', $params['item']) : null;
        $key  = isset($params['key']) ? preg_replace('/^\$/', '', $params['key']) : null;
        $loopName = trim($params['name'] ?? 'default');

        $start = isset($params['start']) ? (int)$params['start'] : null;
        $max   = isset($params['max']) ? (int)$params['max'] : null;
        $step  = isset($params['step']) ? (int)$params['step'] : 1;

        $output = '';

        if ($type === 'FOREACH_START') {
            if (!$originalFrom) throw new Exception("Foreach <em>from</em> parameter missing");
            if (!$item) throw new Exception("Foreach <em>item</em> parameter missing");

            // Compile variable expression if it's not an array literal
            if (!is_array($originalFrom)) {
                if ($originalFrom[0] !== '$' && $originalFrom[0] !== '[') {
                    $originalFrom = '$' . $originalFrom;
                }
                $originalFrom = Tplix::compileVariableExpression($originalFrom);
                $originalFrom = $this->replaceVariables($originalFrom);
                $output .= '$foreach_' . $loopName . '_' . $FOREACH_ID . ' = ' . $originalFrom . ';' . "\n";
            } else {
                $output .= '$foreach_' . $loopName . '_' . $FOREACH_ID . ' = ' . var_export($originalFrom, true) . ';' . "\n";
            }

            // Slice array if start/max
            if ($start !== null || $max !== null) {
                $output .= '$foreach_' . $loopName . '_' . $FOREACH_ID . ' = array_slice($foreach_' . $loopName . '_' . $FOREACH_ID . ', ' 
                         . ($start ?? 0) . ', ' . ($max ?? null) . ', false);' . "\n";
            }

            // Apply step
            if ($step > 1) {
                $output .= '$foreach_' . $loopName . '_' . $FOREACH_ID . ' = array_filter($foreach_' . $loopName . '_' . $FOREACH_ID
                         . ', fn($k) => $k % ' . $step . ' === 0, ARRAY_FILTER_USE_KEY);' . "\n";
            }

            // Handle hasElse
            if ($hasElse == 1) {
                $output .= Plugins::call('condition', [
                    "type" => "IF_START",
                    "condition" => '!empty($foreach_' . $loopName . '_' . $FOREACH_ID . ')'
                ]);
            }

            // Auto-enable iteration/total for last/first calculations
            if ($isLastAllowed) { $isTotalAllowed = $isIterationAllowed = true; }
            if ($isFirstAllowed) $isIterationAllowed = true;

            // Initialize loop variables
            if ($isIterationAllowed) $output .= '$fl'.$loopNo.'_iteration = 0;' . "\n";
            if ($isFirstAllowed)     $output .= '$fl'.$loopNo.'_first = 0;' . "\n";
            if ($isLastAllowed)      $output .= '$fl'.$loopNo.'_last = 0;' . "\n";
            if ($isTotalAllowed)     $output .= '$fl'.$loopNo.'_total = count($foreach_' . $loopName . '_' . $FOREACH_ID . ');' . "\n";
            if ($isIndexAllowed)     $output .= '$fl'.$loopNo.'_index = -1;' . "\n";
            if ($isShowAllowed)      $output .= '$fl'.$loopNo.'_show = !empty($fl'.$loopNo.'_total);' . "\n";

            // Build foreach
            $output .= $key === null
                     ? 'foreach($foreach_' . $loopName . '_' . $FOREACH_ID . ' as $' . $item . '):' . "\n"
                     : 'foreach($foreach_' . $loopName . '_' . $FOREACH_ID . ' as $' . $key . ' => $' . $item . '):' . "\n";

            if ($isKeyAllowed) $output .= '$fl'.$loopNo.'_key = $'.$key.';' . "\n";
            if ($isIterationAllowed) $output .= '$fl'.$loopNo.'_iteration++;' . "\n";
            if ($isFirstAllowed)     $output .= '$fl'.$loopNo.'_first = ($fl'.$loopNo.'_iteration === 1 ? 1 : null);' . "\n";
            if ($isLastAllowed)      $output .= '$fl'.$loopNo.'_last = ($fl'.$loopNo.'_iteration === $fl'.$loopNo.'_total);' . "\n";
            if ($isIndexAllowed)     $output .= '$fl'.$loopNo.'_index++;' . "\n";

            return $output;
        }

        if ($type === 'FOREACH_END') {
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