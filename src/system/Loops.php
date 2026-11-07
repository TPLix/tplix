<?php
namespace Tplix;
/**
 *  LoopsLexer: handle the loops
 */
class LoopsLexer
{
    //mark loop variables
    static function variables(array $tokens): array
    {
        $allowed = ['index', 'iteration', 'first', 'last', 'show', 'total','key'];
        $loopStack = [];
        $loopStackKeys = [];
        $loopCounter = 0;
        foreach ($tokens as $i => &$token)
        {
            $type = $token['type'] ?? null;
            // start of a loop (supports loop, for, foreach)
            if (in_array($type, ['LOOP_START', 'FOR_START', 'FOREACH_START'], true))
            {
                $loopCounter = $token['loopNo'] ?? 0;
                $token['loopNo'] = $loopCounter;
                $params = $token['params'] ?? [];
                $loopStack[] = [
                    'params' => $params,
                    'loopNo' => $loopCounter,
                    'type'   => $type,
                    'loopIndexId'   => $i,
                ];
                continue;
            }
    
            // end of a loop
            if (in_array($type, ['LOOP_END', 'FOR_END', 'FOREACH_END'], true)) {
                array_pop($loopStack);
                continue;
            }
    
            // variable inside a loop
            if(!empty($loopStack))
            {
                $current = end($loopStack);                
                $params  = $current['params'] ?? [];
                $loopNo  = $current['loopNo'];
                $loopIndex  = $current['loopIndexId'];
                if (in_array($type, ['IF_START','ELSEIF_START','FORELSEIF_START'])) {
                    $tokenItemCondition = $token["condition"];
                    if (strpos($tokenItemCondition, '@') !== false){
                        $tokenItemCondition = str_replace(array_keys($token["variables"]),array_values($token["variables"]),$token["condition"]);
                        // Replace any $something@key with $flX_key
                        $tokenItemCondition = preg_replace_callback(
                            '/\$[a-zA-Z_][\w]*@([a-zA-Z_][\w]*)/',
                            function($matches) use ($loopNo,$loopIndex,&$loopStackKeys)
                            {
                                $key = $matches[1]; 
                                $loopStackKeys[$loopIndex][$key]=$key;
                                return '$fl' . $loopNo . '_' . $key;
                            },
                            $tokenItemCondition
                        );                
                        $token['condition'] = $tokenItemCondition;
                        continue;
                    }
                } else {
                    $tokenItemName = $token["name"];
                    
                    if (strpos($tokenItemName, '@') !== false) {
                        $tokenItemName = preg_replace_callback(
                            '/\$[a-zA-Z_][\w]*@([a-zA-Z_][\w]*)/',
                            function($matches) use ($loopNo,$loopIndex,&$loopStackKeys) {
                                $key = $matches[1]; 
                                $loopStackKeys[$loopIndex][$key]=$key;
                                return '$fl' . $loopNo . '_' . $key;
                            },
                            $tokenItemName
                        );
                        $token['name'] = $tokenItemName;
                        continue;
                    }
                }
            }
        }
        unset($token);
        if(!empty($loopStackKeys)){
            foreach($loopStackKeys as $i=>$keys){
                $tokens[$i]["extra_variables"] = $keys;
            }
        }
        return $tokens;
    }
    //else blocks markings
    static function elses(array $tokens): array
    {
        $loopStack = []; // stack of indices for active loops (LOOP_START, FOREACH_START, FOR_START)
        $ifStack   = []; // stack of indices for active IF/ELSEIF blocks
    
        foreach ($tokens as $i => &$token) {
            $type = $token['type'];
    
            // -------------------------
            // Handle loop starts
            // -------------------------
            if (in_array($type, ['FOR_START', 'FOREACH_START', 'LOOP_START'], true)) {
                $loopStack[] = $i;
            }
    
            // -------------------------
            // Handle IF/ELSEIF starts
            // -------------------------
            elseif ($type === 'IF_START' || $type === 'ELSEIF') {
                $ifStack[] = $i;
            }
    
            // -------------------------
            // Handle IF_END: close IF block
            // -------------------------
            elseif ($type === 'IF_END') {
                if (!empty($ifStack)) {
                    array_pop($ifStack);
                }
                // Note: We don't touch loopStack here
            }
    
            // -------------------------
            // Handle generic {else}
            // Only consider as loop-else if NOT inside any IF block
            // -------------------------
            elseif ($type === 'ELSE') {
                // If we are inside an IF/ELSEIF block, this {else} belongs to it → skip
                if (!empty($ifStack)) {
                    // This is an IF-level else — do nothing for loop processing
                    continue;
                }
    
                // If we are NOT in an IF, but inside a loop, treat as loop else
                if (!empty($loopStack)) {
                    $loopStartIndex = end($loopStack);
                    $loopStartType = $tokens[$loopStartIndex]['type'];
                    if (in_array($loopStartType, ['FOR_START', 'FOREACH_START', 'LOOP_START'], true)) {
                        // Mark the loop as having an else block
                        $tokens[$loopStartIndex]['hasElse'] = true;
                        $tokens[$loopStartIndex]['elseIndex'] = $i;
                        $token['linkedTo'] = $loopStartIndex; // optional: back-reference
                    }
                }
                // If not in loop either → orphaned {else}; ignore or log warning
            }
    
            // -------------------------
            // Handle explicit loop else tags: {foreachelse}, {forelse}, {loopelse}
            // -------------------------
            elseif (in_array($type, ['FOREACHELSE', 'FORELSE', 'LOOPELSE'], true)) {
                if (!empty($loopStack)) {
                    $loopStartIndex = end($loopStack);
                    $tokens[$loopStartIndex]['hasElse'] = true;
                    $tokens[$loopStartIndex]['elseIndex'] = $i;
                    $token['linkedTo'] = $loopStartIndex;
                }
            }
    
            // -------------------------
            // Handle loop ends
            // -------------------------
            elseif (in_array($type, ['FOR_END', 'FOREACH_END', 'LOOP_END'], true)) {
                if (!empty($loopStack)) {
                    $loopStartIndex = array_pop($loopStack);
                    // Optional: link end to start
                    $tokens[$loopStartIndex]['pairedEnd'] = $i;
                }
            }
    
            // All other tokens: do nothing
        }
    
        unset($token); // break reference
        return $tokens;
    }
    //reorder else blocks of loops
    static function reorder(array $tokens): array
    {
        $result = [];
        $i = 0;
        $count = count($tokens);
    
        // Stack to track loop starts (index and token)
        $loopStack = [];
    
        while ($i < $count) {
            $token = $tokens[$i];
            $type  = $token['type'];
    
            // Track loop starts
            if (in_array($type, ['FOR_START', 'FOREACH_START', 'LOOP_START'], true)) {
                $loopStack[] = $i;
            }
    
            // Process loop with ELSE
            if (
                in_array($type, ['FOR_START', 'FOREACH_START', 'LOOP_START'], true)
                && isset($token['hasElse'], $token['elseIndex'], $token['pairedEnd'])
            ) {
                $elseIndex = $token['elseIndex'];
                $endIndex  = $token['pairedEnd'];
    
                // Collect loop main block (up to ELSE)
                for ($j = $i; $j < $elseIndex; $j++) {
                    $result[] = $tokens[$j];
                }
    
                // Collect ELSE content (after ELSE up to loop END)
                $elseContent = [];
                for ($j = $elseIndex + 1; $j < $endIndex; $j++) {
                    $elseContent[] = $tokens[$j];
                }
    
                // Append loop END token
                $result[] = $tokens[$endIndex];
    
                // Add ELSE node after loop END
                $result[] = $tokens[$elseIndex];
    
                // Append ELSE content
                foreach ($elseContent as $t) {
                    $result[] = $t;
                }
    
                // Add IF_END node automatically to mark end of loop ELSE
                $result[] = [
                    'type' => 'IF_END',
                    'name' => 'if',
                    'autoAdded' => true,
                    'linkedTo' => $i
                ];
    
                // Jump past the end token
                $i = $endIndex + 1;
                array_pop($loopStack); // loop is done
                continue;
            }
            $result[] = $token;
            $i++;
        }
        return array_values($result);
    }    
}