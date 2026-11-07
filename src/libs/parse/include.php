<?php

/**
 * Parse the content of an {include ...} tag into an associative array.
 *
    {include file='page_header.tpl'}
    {include file="$tpl_name.tpl"}
    {include 'links.tpl' title='Newest links' links=$link_array}
    {include 'sub_template.tpl' scope=parent}
 *
 * @param string $tagContent The string inside the include tag (without {include and })
 * @return array Parsed assignment with file and additional named parameters
 */
class TplixParseInclude
{
    function parse(string $input): array {
        return $this->generate($input);
    } 
    
    function generate($tagContent)
    { 
        $params = [
            'file'  => null,
            'scope' => null,
            'modifiers' => [],
        ];

        // Use the character-by-character approach for the entire tag content to handle complex cases
        $allAttributes = $this->parseAttributes($tagContent);
        
        // Extract file parameter first
        if (isset($allAttributes['file'])) {
            $fileValue = $allAttributes['file'];
            unset($allAttributes['file']);
            
            // Check if file value has modifiers
            if (strpos($fileValue, '|') !== false && preg_match('/^(.*?)\|(.+)$/', $fileValue, $m)) {
                $params['file'] = $m[1];
                $mods = explode('|', trim($m[2], '|'));
                foreach ($mods as $mod) {
                    $parts = explode(':', $mod, 2);
                    $params['modifiers'][] = [
                        'name' => trim($parts[0]),
                        'args' => isset($parts[1]) ? trim($parts[1], '"\'') : null
                    ];
                }
            } else {
                $params['file'] = $fileValue;
            }
        }
        
        // Set other parameters
        foreach ($allAttributes as $key => $value) {
            $params[$key] = $value;
        }
        
        return $params;
    }
    
    /**
     * Parse attributes using a character-by-character approach to handle nested quotes
     */
    private function parseAttributes($tagContent) {
        $attributes = [];
        $len = strlen($tagContent);
        $i = 0;
        
        while ($i < $len) {
            // Skip whitespace
            while ($i < $len && ctype_space($tagContent[$i])) {
                $i++;
            }
            
            if ($i >= $len) break;
            
            // Read attribute name
            $attrName = '';
            while ($i < $len && $tagContent[$i] !== '=' && !ctype_space($tagContent[$i]) && $tagContent[$i] !== '>') {
                $attrName .= $tagContent[$i];
                $i++;
            }
            
            if (empty($attrName)) {
                $i++;
                continue;
            }
            
            // Skip whitespace after attribute name
            while ($i < $len && ctype_space($tagContent[$i])) {
                $i++;
            }
            
            // Check if it's a boolean attribute (no equals sign)
            if ($i >= $len || ($tagContent[$i] !== '=' && ctype_space($tagContent[$i]))) {
                $attributes[$attrName] = true;
                continue;
            }
            
            // Expect equals sign
            if ($i < $len && $tagContent[$i] === '=') {
                $i++; // skip '='
                
                // Skip whitespace after equals
                while ($i < $len && ctype_space($tagContent[$i])) {
                    $i++;
                }
                
                // Get the value
                $attrValue = '';
                if ($i < $len) {
                    $quote = $tagContent[$i];
                    if ($quote === '"' || $quote === "'") {
                        // Quoted value - need to handle template syntax properly
                        $i++; // skip opening quote
                        $startQuote = $quote;
                        
                        // Track nested braces for template expressions
                        $braceDepth = 0;
                        
                        while ($i < $len) {
                            // Check for template opening brace {
                            if ($tagContent[$i] === '{') {
                                $braceDepth++;
                                $attrValue .= $tagContent[$i];
                                $i++;
                                continue;
                            }
                            
                            // Check for template closing brace }
                            if ($tagContent[$i] === '}' && $braceDepth > 0) {
                                $braceDepth--;
                                $attrValue .= $tagContent[$i];
                                $i++;
                                continue;
                            }
                            
                            // Only treat quote as closing if we're not inside a template expression
                            if ($tagContent[$i] === $startQuote && $braceDepth === 0) {
                                // Closing quote found outside of template expression
                                $i++;
                                break;
                            }
                            
                            // Handle escaped characters
                            if ($tagContent[$i] === '\\') {
                                $attrValue .= $tagContent[$i];
                                $i++;
                                if ($i < $len) {
                                    $attrValue .= $tagContent[$i];
                                    $i++;
                                }
                                continue;
                            }
                            
                            $attrValue .= $tagContent[$i];
                            $i++;
                        }
                    } else {
                        // Unquoted value - read until whitespace or end
                        while ($i < $len && !ctype_space($tagContent[$i]) && $tagContent[$i] !== '>') {
                            $attrValue .= $tagContent[$i];
                            $i++;
                        }
                    }
                }
                
                $attributes[$attrName] = $attrValue;
            } else {
                // Boolean attribute (no value)
                $attributes[$attrName] = true;
            }
        }
        
        return $attributes;
    }
}