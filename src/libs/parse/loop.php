<?php

/**
 * Parse the content of an {loop ...} tag into an associative array.
 * {loop $contacts}
 * {loop $contacts start=1 max=5 step=2}
 * {loop $contacts start=0 show=false}
 * {loop $contacts as $c}
 * {loop $contacts as $c name="myloop"}
 * {loop $contacts as $k=>$c name="myloop"}
 * $_key for key accessing during loop
 *
 * @param string $tagContent The string inside the loop tag (without {loop and })
 * @return array Parsed assignment with keys 'var','start','end','max','show','loop','name' and 'step' or additional named parameters
 */
class TplixParseLoop
{
    function parse(string $input): array {
        return $this->generate($input);
    } 
    function generate($tagContent)
    { 
        $params = [
            'array' => null,   // array to loop over
            'var'   => 'item', // loop variable name
            'key'   => 'key', // loop variable name
            'start' => 0,
            'max'   => null,
            'step'  => 1,
            'show'  => true,
            'loop'  => null,   // total iterations (calculated)
            'name'  => null,   // loop name
        ];
        $tagContent = str_replace(["$"," as "],[""," "],$tagContent);
        
        // Support var with dot notation: $arr.child.sub
        $varPattern = '\\w+(?:\.\w+)*';
        $identPattern = '\w+'; // allow bare identifiers like "contact"    
        // 1) {loop arr k => v}
        if (preg_match('/^\s*(' . $varPattern . ')\s+(' . $varPattern . ')\s*=>\s*(' . $varPattern . ')/i', $tagContent, $m)) {
            $params['array'] = $m[1];
            $params['key']  = ltrim($m[2], '$');
            $params['var'] = ltrim($m[3], '$');
            $tagContent = trim(substr($tagContent, strlen($m[0])));
        }
        // 2) {loop arr val}
        elseif (preg_match('/^\s*(' . $varPattern . ')\s+(' . $varPattern . ')/i', $tagContent, $m)) {
            $params['array'] = $m[1];
            $params['var'] = ltrim($m[2], '$');
            $params['key'] = "key";
            $tagContent = trim(substr($tagContent, strlen($m[0])));
        }
        // 3) {loop arr}
        elseif (preg_match('/^\s*(' . $varPattern . ')/', $tagContent, $m)) {
            $params['array'] = $m[1];
            $params['var'] = substr($m[1],0,-1);
            $params['key'] = "key";
            $tagContent = trim(substr($tagContent, strlen($m[0])));
        }
        // 2) Parse extra named parameters (start, max, step, name, show)
        if (preg_match_all('/(\w+)\s*=\s*(?:(["\'])(.*?)\2|(\$\w+)|(\w+))/i', $tagContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $key = $m[1];
                if (!empty($m[3])) $value = $m[3];       // quoted string
                elseif (!empty($m[4])) $value = $m[4];   // variable
                elseif (!empty($m[5])) $value = $m[5];   // plain word
                else $value = '';
    
                // Convert numeric strings to integer where appropriate
                if (in_array($key, ['start', 'max', 'step']) && is_numeric($value)) {
                    $value = (int)$value;
                }
                // Convert show to boolean
                if ($key === 'show') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                $params[$key] = $value;
            }
        }
        // 3) Calculate loop count if array is known (optional, can be evaluated at runtime)
        /*if ($params['array'] && is_null($params['loop'])) {
            $params['loop'] = 'count(' . $params['array'] . ')';
            if (isset($params['max'])) {
                $params['loop'] = 'min(' . $params['loop'] . ',' . $params['max'] . ')';
            }
        }*/
        return $params;
    }
}