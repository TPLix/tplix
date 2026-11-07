<?php
namespace Tplix;

/**
 * Optimized AST lexer for Tplix template engine.
 * Focuses on performance while maintaining robust parsing.
 */
use Tplix\LoopsLexer;
use Tplix\ExpressionParser;
class AstLexer
{
    private static $template;
    private static $tokens = [];
    private static $state = 'TEXT';
    private static bool $stripActive = false;
    private static bool $preserveWhitespace = true;
    private static $nestingLevel = 0;
    private static $currentLine = 1;
    private static $errors = [];
    protected static bool $includePositions = true;
    protected static bool $includeWhitespace = false;
    protected static string $ldel = '{';
    protected static string $rdel = '}';
    protected static int $pos;
    protected static int $len;
    protected static int $LOOP_ID = 0;
    private static $insideScriptOrStyle = false;

    /*********************** Start CSS/JS Tokenizers (Optimized) ***************/
    // ... (Keep the CSS/JS tokenizers as they were, or apply similar optimizations if needed) ...
    static function tokenizeCSS($cssContent)
    {
        self::resetCompileState();
        self::$template = $cssContent;
        self::$len = strlen(self::$template);
        while (self::$pos < self::$len) {
            if (self::$state === 'TPL_TAG') {
                self::scanTemplateTag();
            } elseif (self::$state === 'TPL_VAR') {
                self::scanTemplateVariable();
            } else {
                self::scanCSS();
            }
        }
        return self::$tokens;
    }

    static function scanCSS()
    {
        $start = self::$pos;
        $tagStart = false;
        $i = self::$pos;

        while ($i < self::$len) {
            $char = self::$template[$i];
            if ($char === '{' && $i + 1 < self::$len) {
                $nextChar = self::$template[$i + 1];
                if ($nextChar === '$' || $nextChar === '*' || (ctype_alpha($nextChar) || $nextChar === '_' || $nextChar === '/')) {
                    $tagStart = $i;
                    break;
                }
            }
            if ($char === "\n") {
                self::$currentLine++;
            }
            $i++;
        }

        if ($tagStart === false) {
            $tagStart = self::$len;
        }

        $css = substr(self::$template, $start, $tagStart - $start);

        if ($css !== '') {
            self::$tokens[] = [
                'type' => 'TEXT',
                'name' => 'TEXT',
                'value' => $css,
                'line' => self::$currentLine
            ];
        }

        self::$pos = $tagStart;

        if (self::$pos < self::$len) {
            if (self::$template[self::$pos] === '{' && self::$template[self::$pos + 1] === '*') {
                self::$state = 'TPL_TAG';
                self::scanTemplateTag();
            } elseif (self::$template[self::$pos] === '{' && self::$template[self::$pos + 1] === '$') {
                self::$state = 'TPL_VAR';
                self::$pos += 2;
            } else {
                self::$state = 'TPL_TAG';
                self::$pos++; // Skip '{'
            }
        }
    }

    static function scanTemplateVariable()
    {
        $start = self::$pos-1;
        $braceCount = 0;
        $i = $start;

        while ($i < self::$len) {
            $char = self::$template[$i];
            if ($char === '{') {
                $braceCount++;
            } elseif ($char === '}') {
                if ($braceCount === 0) {
                    break; // Found closing brace for this variable
                } else {
                    $braceCount--;
                }
            }
            $i++;
        }

        if ($i >= self::$len) {
            self::$tokens[] = [
                'type' => 'ERROR',
                'name' => 'ERROR',
                'value' => 'Unclosed variable: {' . substr(self::$template, $start),
                'line' => self::$currentLine
            ];
            self::$pos = self::$len;
            self::$state = 'TEXT';
            return;
        }
        $varContent = substr(self::$template, $start, $i - $start);

        self::$pos = $i + 1; // Skip '}'
        $lineCount = substr_count($varContent, "\n");
        self::$currentLine += $lineCount;
        // Use optimized expression parser
        $ast = ExpressionParser::parseExpression($varContent, self::$currentLine);

        self::$tokens[] = [
            'type' => 'VARIABLE_AST',
            'ast' => $ast,
            'line' => self::$currentLine
        ];

        self::$state = 'TEXT';
    }

    static function scanTemplateTag()
    {
        $start = self::$pos;
        $tagEnd = self::findClosingBrace($start);
        if ($tagEnd === false) {
            self::$tokens[] = [
                'type' => 'ERROR',
                'name' => 'ERROR',
                'value' => 'Unclosed tag at position ' . $start,
                'line' => self::$currentLine
            ];
            self::$pos = self::$len;
            return;
        }

        $tagContent = trim(substr(self::$template, $start, $tagEnd - $start));
        self::$pos = $tagEnd + 1;
        $lineCount = substr_count($tagContent, "\n");
        self::$currentLine += $lineCount;

        if ($tagContent[0] === '/') {
            $tagName = substr($tagContent, 1);
            if($tagName == "nocache") $tagName = "dynamic";
            $endType = strtoupper($tagName) . '_END';
            self::$tokens[] = [
                'type' => $endType,
                'name' => $tagName,
                'line' => self::$currentLine
            ];
            self::$state = 'TEXT';
            self::$nestingLevel = max(0, self::$nestingLevel - 1);
            return;
        }

        // Extract function name and argument string safely
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/s', $tagContent, $m)) {
            $tagName = $m[1];
            $attrString = trim($m[2]);
        } else {
            $parts = preg_split('/\s+/', $tagContent, 2, PREG_SPLIT_NO_EMPTY);
            $tagName = array_shift($parts);
            $attrString = implode(' ', $parts);
        }

        $utagName = strtoupper($tagName);
        $token = ['type' => $utagName, 'line' => self::$currentLine];

        if (in_array($tagName, ['if', 'elseif', 'forelseif'])) {
            $token['type'] = strtoupper($tagName) . '_START';
            $token['name'] = $tagName;
            // Use optimized expression parser for conditions
            $conditionAst = ExpressionParser::parseExpression($attrString, self::$currentLine);
            $token['condition_ast'] = $conditionAst;
            //$token['variables'] = self::extractVariablesFromAst($conditionAst);
            $token['variables'] = "";
            $token['condition'] = $attrString;
        } elseif (in_array($tagName, ['else', 'foreachelse','loopelse','forelse'])) {
            $token['name'] = 'else';
            $token['type'] = 'ELSE';
        } else {
            if ($tagName === '*') {
                $commentEnd = strpos(self::$template, '*}', $start);
                if ($commentEnd !== false) {
                    $commentContent = substr(self::$template, $start + 1, $commentEnd - $start - 1);
                    self::$tokens[] = [
                        'type' => 'COMMENT',
                        'name' => 'comment',
                        'value' => $commentContent,
                        'line' => self::$currentLine
                    ];
                    self::$pos = $commentEnd + 2;
                    self::$state = 'TEXT';
                    return;
                }
            } else {
                $token['type'] = 'FUNCTION';
                $token['name'] = $tagName;
                // Use optimized expression parser for function arguments
                $parsedArgs = ExpressionParser::parseFunctionArguments($attrString, self::$currentLine);
                $token['params'] = $parsedArgs;
            }
        }

        self::$tokens[] = $token;
        self::$state = 'TEXT';
    }
    /*********************** End CSS/JS Tokenizers ***************/

    static function resetCompileState(): void
    {
        self::$pos = 0;
        self::$tokens = [];
        self::$template = '';
        self::$len = 0;
        self::$nestingLevel = 0;
    }

    public static function tokenizeJsContent($jsContent)
    {
        self::resetCompileState();
        return AstLexer::tokenize($jsContent);
        //return self::tokenizeCSS($jsContent);
    }

    static function tokenizeString($templateContent = "")
    {
        self::resetCompileState();
        return self::tokenize($templateContent);
    }
    static function tokenize($templateContent = "")
    {
        self::$template = $templateContent;
        self::$len = strlen(self::$template);
        while (self::$pos < self::$len) {
            if (self::$state === 'LITERAL') {
                self::scanLiteral();
            } elseif (self::$state === 'COMMENT') {
                self::scanComment();
            } elseif (self::$state === 'TAG') {
                self::scanTag();
            } else {
                self::scanText();
            }
        }
        if (self::$LOOP_ID > 0)
        {
            self::$tokens = LoopsLexer::variables(self::$tokens);
            self::$tokens = LoopsLexer::elses(self::$tokens);
            self::$tokens = LoopsLexer::reorder(self::$tokens);
        }
        return self::$tokens;
    }
  
static function scanText() {
    $start = self::$pos;
    $tagStart = strpos(self::$template, '{', self::$pos);
    if ($tagStart === false) $tagStart = self::$len;

    // Grab the text before the next '{'
    $text = substr(self::$template, $start, $tagStart - $start);
    $lineCount = substr_count($text, "\n");
    self::$currentLine += $lineCount;

    // Optional whitespace handling
    if (self::$stripActive) {
        $text = preg_replace('/\s+/', '', $text);
    } elseif (!self::$preserveWhitespace) {
        if ($text === '') {
            self::$pos = $tagStart;
            return;
        }
    }

    // Add TEXT token if text exists
    if ($text !== '') {
        $lastIndex = count(self::$tokens) - 1;
        if ($lastIndex >= 0 && self::$tokens[$lastIndex]['type'] === 'TEXT') {
            // Merge consecutive text tokens
            self::$tokens[$lastIndex]['value'] .= $text;
            self::$tokens[$lastIndex]['line'] = self::$currentLine;
        } else {
            self::$tokens[] = [
                'type' => 'TEXT',
                'name' => 'TEXT',
                'value' => $text,
                'line' => self::$currentLine
            ];
        }
    }

    self::$pos = $tagStart;

    // Check if next '{' is actually a Tplix tag
    if (self::$pos < self::$len && self::$template[self::$pos] === '{') {
        $nextChar = self::$template[self::$pos + 1] ?? '';

        // Only consider it a Tplix tag if nextChar is valid: letter, $, /, *
        if (preg_match('/[a-zA-Z0-9_$\/\*\'"\(]/', $nextChar)) {
            if ($nextChar === '*') {
                // Comment tag
                self::$state = 'COMMENT';
                self::$pos += 2; // Skip '{*'
            } else {
                // Valid Tplix tag like {$var} or {if ...}
                self::$state = 'TAG';
                self::$pos++; // Skip '{'
            }
        } else {
            // Literal '{' (space, number, quote, etc.) → treat as text
            self::$tokens[] = [
                'type' => 'TEXT',
                'name' => 'TEXT',
                'value' => '{',
                'line' => self::$currentLine
            ];
            self::$pos++;
        }
    }
}
    static function assign($token,$tagContent){
        $token['type'] = 'FUNCTION';
        $token['name'] = 'assign';
        $assignParse = Parsers::call("assign",$tagContent);
        $assignValue = self::processModifiers($assignParse["value"]);
        $token['params'] = [
            "var"=>$assignParse["var"],
            "value"=>$assignValue["name"],
            "modifiers"=>$assignValue["modifiers"],
        ];
        return $token;
    }
    static function scanTag()
    {
        $loopCounter = 0;
        $start = self::$pos;
        $tagEnd = self::findClosingBrace($start);
        if ($tagEnd === false)
        {
            self::$tokens[] = ['type' => 'ERROR', 'value' => 'Unclosed tag at position ' . $start, 'line' => self::$currentLine];
            self::$pos = self::$len;
            return;
        }
    
        $tagContent = trim(substr(self::$template, $start, $tagEnd - $start));
       self::$pos = $tagEnd + 1;
    
        self::$currentLine += substr_count($tagContent, "\n");
    
        if (strpos($tagContent, '/') === 0)
        {
            $tagName = substr($tagContent, 1);
            if($tagName == "nocache") $tagName = "dynamic";
            $endType = strtoupper($tagName) . '_END';
            self::$tokens[] = ['type' => $endType, 'name' => $tagName, 'line' => self::$currentLine];
            if ($tagName === 'strip') self::$stripActive = false;
            self::$state = 'TEXT';
            self::$nestingLevel = max(0, self::$nestingLevel - 1);
            return;
        }
    
        // Extract function name and argument string safely
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/s', trim($tagContent), $m)) {
            $tagName = $m[1];         // e.g. system
            $attrString = trim($m[2]); // e.g. "echo 1;"
        } else {
            // fallback for non-function tags like {foreach from=...}
            $parts = preg_split('/\s+/', $tagContent, 2, PREG_SPLIT_NO_EMPTY);
            $tagName = array_shift($parts);
            $attrString = implode(' ', $parts);
        }
        $utagName = strtoupper($tagName);
        $token = ['type' =>$utagName, 'line' => self::$currentLine];

        //if in start $ or single or double quote
        $firstChar = $tagContent[0];
        $secondChar = isset($tagContent[1]) ? $tagContent[1]:null;
        $secondChar = isset($secondChar) ? trim($secondChar):null;
        if($firstChar == "$" || $firstChar == "'" || $firstChar == '"' && ($secondChar !=="{" || $secondChar!==null))
        {
            //Assign like {$abc="test"}
            if (strpos($tagContent, '=') !== false)
            {
                $token = self::assign($token,$tagContent);
            }else{
                $token = self::processModifiers($tagContent);
            }
        }
        // --- HANDLE SPECIFIC TAGS FIRST ---
        elseif ($tagName === 'assign')
        {
            $token = self::assign($token,$attrString);
        }
        elseif (in_array($tagName, ['else', 'foreachelse','loopelse','forelse']))
        {
            $token['name'] = 'else';
            $token['type'] = 'ELSE';
            // No params
        }elseif(Parsers::exist($tagName))
        {
            if($tagName == "nocache") $tagName = "dynamic";

            $token['name'] = $tagName;
            $isLoop = false;
            $isStart = false;
            $isConditions = false;
            if (in_array($tagName, ['if', 'elseif', 'forelseif']))
            {
                $isConditions = true;
            }
            if(in_array($tagName, ['with', 'foreach','loop','for','while']))
            {
                $isLoop = true;
                $isStart = true;
            }
            if(in_array($tagName, ['with', 'foreach','capture','loop','for','while','block', 'capture', 'dynamic','nocache', 'template', 'textformat','if', 'elseif', 'forelseif']))
            {
                $isStart = true;
            }
            if($isLoop){
                self::$LOOP_ID++;
                $token['loopNo'] = self::$LOOP_ID;                
            }
            if($isStart){
                $token['type'] = strtoupper($tagName) . '_START';                
            }else{
                $token['type'] = strtoupper($tagName);            
            }
            if($tagName == 'config_load') $token['type'] = strtoupper("CONFIG");
            if($isConditions)
            {
                $response = Parsers::call("condition",$attrString);
            }else{
                $response = Parsers::call($tagName,$attrString);                
            }
            if(!empty(self::$errors))
            {
                $token = self::$errors;
                self::$errors = [];
            }else{
                if($isConditions)
                {
                    $token['variables'] = $response["variables"];
                    $token['condition'] = $response["condition"];
                }else{
                    $token['params'] = $response;
                }
            }
            if($isLoop) $loopCounter++;
            self::$nestingLevel++;
        }
        // --- END OF SPECIFIC TAG HANDLING ---
        else {
            // Now, check if this unknown tag is a general expression
            // Check if it's a mathematical expression or complex expression
            $isExpression = false;

            $isPureNumber = preg_match('/^[0-9+\-*\/\.\s]+$/', $tagContent);
            $hasOperators = preg_match('/[+\-*\/%<>!=&|]/', $tagContent);
            $hasVariableOrFunction = preg_match('/[$a-zA-Z_]/', $tagContent);

            if ($isPureNumber || ($hasOperators && $hasVariableOrFunction)) {
                $isExpression = true;
            }
            if ($isExpression) {
                // Handle as an expression
                if (substr_count($tagContent, '|') > 0) {
                    // Has modifiers, use processModifiers
                    $token = self::processModifiers($tagContent);
                } else {
                    // Pure expression without modifiers
                    $token = [
                        'type' => 'VARIABLE',
                        'name' => $tagContent,
                        'value' => $tagContent,
                        'line' => self::$currentLine
                    ];
                }
            } else {
                // It's a function call or unknown tag without operators
                $isPipeExist = substr_count($tagContent, '|');
                if($isPipeExist>0)
                {
                    $token = self::processModifiers($tagName,$tagContent);
                }else{
                    $isTernary = self::isTernary($tagContent);
                    if($isTernary)
                    {
                        $token['type'] = "TIF";
                        $token['name'] = "tif";
                        $token['params'] = $tagContent;
                        self::$nestingLevel++;
                    }else{
                        $endsWithSemicolon = str_ends_with(trim($tagName), ';');
                        if($endsWithSemicolon){
                            $token['type'] = "TEXT";
                            $token['name'] = "TEXT";
                            $token['value'] = $tagName;
                        }else{
                            $endsWithcolon = str_ends_with(trim($tagName), ':');
                            if($endsWithcolon)
                            {
                                $token['type'] = "TEXT";
                                $token['name'] = "TEXT";
                                $token['value'] = $tagName;
                            }else{
                                $token['type'] = 'FUNCTION';
                                $token['name'] = $tagName;
                                $token['params'] = self::scanFunctionArguments($attrString); 
                                // Use the potentially flawed one for now, or the fixed one you implemented earlier                                
                            }
                        }
                    }
                }
            }
        }
        self::$tokens[] = $token;
        self::$state = 'TEXT';
        if ($tagName === 'literal')
        {
            self::$state = 'LITERAL';
            array_pop(self::$tokens);
            self::$tokens[] = ['type' => 'LITERAL_START','name'=>$tagName, 'line' => self::$currentLine];
        } elseif ($tagName === 'strip') {
            self::$stripActive = true;
            array_pop(self::$tokens);
            self::$tokens[] = ['type' => 'STRIP_START','name'=>$tagName, 'line' => self::$currentLine];
        }
    }
    static function isTernary(string $expr): bool {
        $q = strpos($expr, '?');
        $c = strpos($expr, ':');
    
        return ($q !== false && $c !== false && $q < $c);
    } 
    // Helper to extract variable names from an AST (simplified)
    private static function extractVariablesFromAst($ast) {
        $vars = [];
        if (is_array($ast)) {
            if ($ast['type'] === 'VARIABLE') {
                $vars[] = $ast['name'];
            }
            foreach ($ast as $key => $value) {
                if (is_array($value)) {
                    $vars = array_merge($vars, self::extractVariablesFromAst($value));
                }
            }
        }
        return array_unique($vars);
    }

    // --- Other helper methods remain the same ---
   static function scanAttributes($attrString) {
        $attrs = [];
        $positional = [];
        $selfClosing = false;
    
        $offset = 0;
        $length = strlen($attrString);
        
        // Pattern to match attributes one by one
        while ($offset < $length) {
            // Skip whitespace
            if (preg_match('/^\s+/', substr($attrString, $offset), $wsMatch)) {
                $offset += strlen($wsMatch[0]);
                continue;
            }
            
            // Check for self-closing tag marker
            if (substr($attrString, $offset, 1) === '/') {
                $selfClosing = true;
                $offset++;
                continue;
            }
            
            // Check for positional variable
            if (preg_match('/^(\$[a-zA-Z_][a-zA-Z0-9_]*)/', substr($attrString, $offset), $posMatch)) {
                $positional[] = $posMatch[1];
                $offset += strlen($posMatch[0]);
                continue;
            }
            
            // Check for boolean attribute
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_\-]*)/', substr($attrString, $offset), $boolMatch)) {
                $attrName = $boolMatch[1];
                $nextChar = $offset + strlen($boolMatch[0]) < $length ? $attrString[$offset + strlen($boolMatch[0])] : '';
                
                if ($nextChar === '=' || $nextChar === ' ' || $nextChar === '>' || $nextChar === '/') {
                    // It's a boolean attribute if followed by space, =, >, or /
                    $attrs[$attrName] = true;
                    $offset += strlen($boolMatch[0]);
                    continue;
                }
            }
            
            // Check for key="value" or key='value' with proper quote matching
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_\-]*)\s*=\s*("[^"]*"|\'[^\']*\')/', substr($attrString, $offset), $quotedMatch)) {
                $key = $quotedMatch[1];
                $quotedValue = $quotedMatch[2];
                $value = substr($quotedValue, 1, -1); // Remove outer quotes
                
                $attrs[$key] = $value;
                $offset += strlen($quotedMatch[0]);
                continue;
            }
            
            // Check for key=value (unquoted)
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_\-]*)\s*=\s*([^\s\/>]+)/', substr($attrString, $offset), $unquotedMatch)) {
                $key = $unquotedMatch[1];
                $value = trim($unquotedMatch[2]);
                
                $attrs[$key] = $value;
                $offset += strlen($unquotedMatch[0]);
                continue;
            }
            
            // If we can't match anything, advance by 1 to avoid infinite loop
            $offset++;
        }
    
        // Merge positional and named
        $params = [];
        foreach ($positional as $idx => $val) {
            $params[$idx] = $val;
        }
        foreach ($attrs as $k => $v) {
            $params[$k] = $v;
        }
        if ($selfClosing) {
            $params['selfClosing'] = true;
        }
        return $params;
    }
    static function findClosingBrace($start) {
        $pos = $start;
        $nestLevel = 1;
        while ($pos < self::$len) {
            if (self::$template[$pos] === '{') $nestLevel++;
            elseif (self::$template[$pos] === '}') {
                $nestLevel--;
                if ($nestLevel === 0) return $pos;
            }
            $pos++;
        }
        return false;
    }
    private static function scanLiteral()
    {
        $start = self::$pos;
        $literalEnd = strpos(self::$template, '{/literal}', self::$pos);
        if ($literalEnd === false) {
            $literalEnd = self::$len;
        }
        $content = substr(self::$template, $start, $literalEnd - $start);
        self::$tokens[] = ['type' => 'LITERAL','name'=>'literal','value' => $content, 'line' => self::$currentLine];
        self::$pos = $literalEnd + strlen('{/literal}');
        self::$state = 'TEXT';
        self::$tokens[] = ['type' => 'LITERAL_END','name'=>'literal', 'line' => self::$currentLine];
    }

    private  static function scanComment() {
        $start = self::$pos;
        $commentEnd = strpos(self::$template, '*}', self::$pos);
        if ($commentEnd === false) $commentEnd = self::$len;
        $content = substr(self::$template, $start, $commentEnd - $start);
        self::$tokens[] = ['type' => 'COMMENT','name'=>'comment','value' => $content, 'line' => self::$currentLine];
        self::$pos = $commentEnd + 2;
        self::$state = 'TEXT';
    }
    static function processModifiers($tagContent)
    {
        $currentLine = self::$currentLine;
        $tagContent = trim($tagContent);
    
        // Handle assignments ($name="test" or name=abc|cat)
        if (preg_match('/^\$?([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $tagContent, $m))
        {
            return [
                'type'   => 'FUNCTION',
                'name'   => 'assign',
                'params' => self::parsedAssign($m[1], $m[2], $currentLine),
                'line'   => $currentLine
            ];
        }
    
        // Otherwise treat as variable or string with modifiers
        return self::parsedVariable($tagContent, $currentLine);
    }
    
    static function parsedAssign($varName, $valuePart, $currentLine) {
        $value = self::parsedVariable($valuePart, $currentLine);
        return [
            'var'   => $varName,
            'value' => $value
        ];
    }

    static function parsedVariable($tagContent, $currentLine) {
        // --- Helper: recursively parse argument values ---
        $parseArg = function($arg) use (&$parseArg, $currentLine) {
            $arg = trim($arg);
    
            // If looks like a variable or string with modifiers {$...} or {"..."} 
            if (preg_match('/^\{.*\}$/s', $arg)) {
                return self::parsedVariable(substr($arg, 1, -1), $currentLine);
            }
    
            // If looks like an array [ ... ]
            if (preg_match('/^\[.*\]$/s', $arg)) {
                $arr = trim($arg, '[]');
                if ($arr === '') return [];
                
                $items = [];
                $len = strlen($arr);
                $buf = '';
                $depth = 0;
                $inQuote = false;
                $quoteChar = '';
                
                for ($i = 0; $i < $len; $i++) {
                    $c = $arr[$i];
    
                    if ($inQuote) {
                        $buf .= $c;
                        if ($c === $quoteChar) $inQuote = false;
                        elseif ($c === '\\') { $i++; if ($i < $len) $buf .= $arr[$i]; }
                    } else {
                        if ($c === '"' || $c === "'") {
                            $inQuote = true;
                            $quoteChar = $c;
                            $buf .= $c;
                        } elseif ($c === '[') {
                            $depth++;
                            $buf .= $c;
                        } elseif ($c === ']') {
                            $depth--;
                            $buf .= $c;
                        } elseif ($c === ',' && $depth === 0) {
                            $items[] = trim($buf);
                            $buf = '';
                        } else {
                            $buf .= $c;
                        }
                    }
                }
                if ($buf !== '') $items[] = trim($buf);
    
                return array_map($parseArg, $items);
            }
    
            // --- Handle ternary expressions ($a ? $b : $c) ---
            if (preg_match('/^(.+?)\?(.+?):(.+)$/s', $arg, $m)) {
                return [
                    'type' => 'TERNARY',
                    'condition' => $parseArg($m[1]),
                    'if_true' => $parseArg($m[2]),
                    'if_false' => $parseArg($m[3])
                ];
            }
    
            // Simple scalar conversion
            $arg = trim($arg, '\'"');
            if ($arg === 'true') return true;
            if ($arg === 'false') return false;
            if ($arg === '') return null;
            if (is_numeric($arg)) return $arg + 0;
    
            return $arg;
        };
    
        // --- Split modifiers by | but respecting nested braces/quotes ---
        $parts = [];
        $len = strlen($tagContent);
        $buf = '';
        $depth = 0;
        $inQuote = false;
        $quoteChar = '';
        
        for ($i = 0; $i < $len; $i++) {
            $c = $tagContent[$i];
    
            if ($inQuote) {
                $buf .= $c;
                if ($c === $quoteChar) $inQuote = false;
                elseif ($c === '\\') { $i++; if ($i < $len) $buf .= $tagContent[$i]; }
            } else {
                if ($c === '"' || $c === "'") {
                    $inQuote = true;
                    $quoteChar = $c;
                    $buf .= $c;
                } elseif ($c === '{') {
                    $depth++;
                    $buf .= $c;
                } elseif ($c === '}') {
                    $depth--;
                    $buf .= $c;
                } elseif ($c === '|' && $depth === 0) {
                    $parts[] = $buf;
                    $buf = '';
                } else {
                    $buf .= $c;
                }
            }
        }
        if ($buf !== '') $parts[] = $buf;
    
        $base = array_shift($parts);
        $base = trim($base);
        if (preg_match('/^\$/', $base)) $base = ltrim($base);
    
        $modifiers = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*:?\s*(.*)$/s', $p, $mm)) {
                $modName = $mm[1];
                $argsRaw = trim($mm[2]);
                $args = [];
    
                if ($argsRaw !== '') {
                    // Split arguments safely respecting quotes/braces
                    $args = [];
                    $len = strlen($argsRaw);
                    $buf = '';
                    $depth = 0;
                    $inQuote = false;
                    $quoteChar = '';
                    
                    for ($i = 0; $i < $len; $i++) {
                        $c = $argsRaw[$i];
    
                        if ($inQuote) {
                            $buf .= $c;
                            if ($c === $quoteChar) $inQuote = false;
                            elseif ($c === '\\') { $i++; if ($i < $len) $buf .= $argsRaw[$i]; }
                        } else {
                            if ($c === '"' || $c === "'") {
                                $inQuote = true;
                                $quoteChar = $c;
                                $buf .= $c;
                            } elseif ($c === '{') {
                                $depth++;
                                $buf .= $c;
                            } elseif ($c === '}') {
                                $depth--;
                                $buf .= $c;
                            } elseif ($c === ':' && $depth === 0) {
                                $args[] = trim($buf);
                                $buf = '';
                            } else {
                                $buf .= $c;
                            }
                        }
                    }
                    if ($buf !== '') $args[] = trim($buf);
                    $args = array_map($parseArg, $args);
                }    
                $modifiers[] = ['name' => $modName, 'args' => $args];
            }
        }
    
        return [
            'type' => 'VARIABLE',
            'name' => $base,
            'modifiers' => $modifiers ?: null,
            'line' => $currentLine
        ];
    }
    static function scanFunctionArguments($argString) {
        $args = [];
        $len = strlen($argString);
        $buf = '';
        $depth = 0;
        $inQuote = false;
        $quoteChar = '';
        $argIndex = 0;
        for ($i = 0; $i < $len; $i++) {
            $c = $argString[$i];
            if ($inQuote) {
                $buf .= $c;
                if ($c === $quoteChar) {
                    $inQuote = false;
                } elseif ($c === '\\') { 
                    $i++; 
                    if ($i < $len) $buf .= $argString[$i]; 
                }
            } else {
                if ($c === '"' || $c === "'") {
                    $inQuote = true;
                    $quoteChar = $c;
                    $buf .= $c;
                } elseif ($c === '(') {
                    $depth++;
                    $buf .= $c;
                } elseif ($c === ')') {
                    $depth--;
                    $buf .= $c;
                } elseif ($c === ',' && $depth === 0) {
                    $args[$argIndex] = trim($buf);
                    $buf = '';
                    $argIndex++;
                } else {
                    $buf .= $c;
                }
            }
        }
        if ($buf !== '') $args[$argIndex] = trim($buf);
        return $args;
    }
}