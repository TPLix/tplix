<?php
class TplixParseTif
{
    function parse(string $input): array {
        return $this->generate($input);
    } 
    function generate($tagContent)
    { 
        $params = [
            'condition' => null,
            'true'      => null,
            'false'     => null,
        ];
    
        // Remove inline comment temporarily
        $tagContent = preg_replace('/\{\*.*?\*\}/', '', $tagContent);
    
        // Full ternary: {tif $foo == "bar" ? "sadd" : "false"}
        if (preg_match('/^(.*?)\s*\?\s*(.*?)\s*:\s*(.*)$/', $tagContent, $m)) {
            $params['condition'] = trim($m[1]);
            $params['true']      = trim($m[2]);
            $params['false']     = trim($m[3]);
        }
        // Short ternary: {tif $foo ?: "false"} or {tif $foo ? "true"}
        elseif (preg_match('/^(.*?)\s*\?\s*(.*)$/', $tagContent, $m)) {
            $params['condition'] = trim($m[1]);
            $params['true']      = trim($m[2]);
            $params['false']     = null;
        }
        // Condition only: {tif $foo}
        else {
            $params['condition'] = trim($tagContent);
            $params['true']      = trim($tagContent);
            $params['false']     = null;
        }
    
        // Auto-fill short ternary true value if empty
        if ($params['true'] === '' || $params['true'] === null) {
            $params['true'] = $params['condition'];
        }
    
        // Ensure false key exists
        if (!isset($params['false'])) {
            $params['false'] = null;
        }
    
        // Optional: strip surrounding quotes from true/false
        foreach (['true', 'false'] as $key) {
            if (isset($params[$key]) && preg_match('/^["\'](.*)["\']$/', $params[$key], $m)) {
                $params[$key] = $m[1];
            }
        }
        return $params;
    }
}