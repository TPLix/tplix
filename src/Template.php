<?php
namespace Tplix;
class Template
{
    static int $includeCount = 0;
    static $compiledOutput = "";
    static $loops = ['foreach'=>1,'for'=>1,'loop'=>1,'while'=>1];
    /* html plugins */
    static $htmlPlugins = [
        'config_load'=>1,
        'html_options'=>1,
        'html_select_time'=>1,
        'html_select_date'=>1,
        'html_table'=>1,
        'html_checkboxes'=>1,
        'html_radios'=>1
    ];    
    static function handleBlocksExtends($templateContent="")
    {
        // --- 1️⃣ Check for extends ---
        if (preg_match('/\{extends\s+"([^"]+)"\}/', $templateContent, $m))
        {
            $parentPath = self::resolveTemplate($m[1]);
            // 2️⃣ Parse child blocks
            $childBlocks = self::parseBlocks($templateContent);
            // 3️⃣ Read parent template
            $parentContent = file_get_contents($parentPath);
            // 4️⃣ Merge parent + child blocks
            $templateContent = self::mergeWithParent($parentContent, $childBlocks);
        }
        return $templateContent;
    }
    static function resolveTemplate(string $tplName): string {
        return $tplName;
        // Check if the template path is already absolute
        if (file_exists($tplName)) return $tplName;
        // Otherwise, look in your templates directory
        $tplDir = __DIR__ . '/templates/';
        $fullPath = $tplDir . $tplName;
        if (!file_exists($fullPath)) {
            throw new TplixTemplateNotFoundException($tplName,"Tplix: Template file not found: $tplName");
        }
        return $fullPath;
    }
    static function parseBlocks(string $tpl): array {
        $blocks = [];
        $offset = 0;
        while (preg_match('/\{block\s+name="([^"]+)"\}/', $tpl, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $blockName = $m[1][0];
            $startPos = $m[0][1] + strlen($m[0][0]);
            $offset = $startPos;
    
            // Find matching {/block}
            $endPos = strpos($tpl, '{/block}', $offset);
            if ($endPos === false) break;
    
            $blocks[$blockName] = Plugins::call('substr',[$tpl, $startPos, $endPos - $startPos]);
    
            $offset = $endPos + 8; // move past {/block}
        }
        return $blocks;
    }
    static function mergeWithParent(string $parentTpl, array $childBlocks): string {
        $result = $parentTpl;
    
        // Replace each parent block with child block if exists
        foreach ($childBlocks as $name => $content) {
            // Replace {$parent} in child with parent content
            $pattern = '/\{block\s+name="' . preg_quote($name, '/') . '"\}(.*?)\{\/block\}/s';
            $result = preg_replace_callback($pattern, function($m) use ($content) {
                $parentContent = $m[1];
                return Plugins::call('replace',['{$parent}', $parentContent, $content]);
            }, $result);
        }
        return $result;
    }
    static function resolveIncludes(string $tpl): string {
        // Match {include "file.tpl" param1=val1 param2=val2}
        return preg_replace_callback('/\{include\s+"([^"]+)"(.*?)\}/', function($m) {
            $includePath = self::resolveTemplate($m[1]);
            // Extract parameters
            $paramsStr = Plugins::call('trim',[$m[2]]);
            $params = [];
            if ($paramsStr) {
                preg_match_all('/(\w+)\s*=\s*(["\']?)(.*?)\2/', $paramsStr, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $params[$match[1]] = $match[3];
                }
            }
            // Load include content
            $includeContent = file_get_contents($includePath);
            // Inject parameters as temporary variables
            foreach ($params as $k => $v)
            {
                $includeContent = Plugins::call('replace',['{$'.$k.'}', $v, $includeContent]);
            }
            // Recursive resolve includes inside included template
            return self::resolveIncludes($includeContent);
        }, $tpl);
    }
    static function fetchTpl(string $template, bool $useCache = true): string
    {
        static $cache = [];
        // Normalize path
        $tplFile = realpath($template);
        if (!$template || !file_exists($template))
        {
            throw new TplixTemplateNotFoundException($tplFile,"Template file not found: {".$template."}");
        }
        // Return cached content if available
        if ($useCache && isset($cache[$template])) {
            return $cache[$template];
        }
        // Read file content
        $content = file_get_contents($template);
        if ($content === false) {
            throw new TplixTemplateNotFoundException($template,"Failed to read template file: {".$template."}");
        }
        // Remove UTF-8 BOM if exists
        if (Plugins::call('substr',[$content, 0, 3]) === "\xEF\xBB\xBF") {
            $content = Plugins::call('substr',[$content, 3]);
        }
        // Normalize line endings to \n
        $content = Plugins::call('replace',[$content,["\r\n", "\r","|@count"], ["\n","\n","|count"]]);
        // Cache the content if requested
        if ($useCache) $cache[$template] = $content;
        return $content;
    }
    public static function compile($tokens=[],$is_include=false)
    {
        $output = "";
        $capture_stack = []; // Stack to handle nested captures
        $in_capture = false; // Whether we're currently inside a capture block
        $capture_buffer = ""; // Temporary buffer for capture content
        $current_capture_var = '';
        foreach ($tokens as $token)
        {
            $name = isset($token["name"]) ? $token["name"] : null;
            $type = isset($token["type"]) ? $token["type"] : null;
            // Handle capture start
            if ($type == "CAPTURE_START")
            {
                // Push current state if nested
                if ($in_capture) {
                    $capture_stack[] = [
                        'buffer' => $capture_buffer,
                        'var_name' => $current_capture_var
                    ];
                }
                // Start new capture
                $in_capture = true;
                $current_capture_var = $token['params']['name'] ?? 'default'; // Get capture name
                $capture_buffer = "";
                continue; // Don't process this token as regular output
            }
            // Handle capture end
            if ($type == "CAPTURE_END") {
                // Store the captured content as an executable closure
                // Store as a callable in global scope with current template's scope
                $output .= "<?php
                    // Create a closure that will execute with the current user's scope when called
                        ob_start();?>
                    {$capture_buffer}
                    <?php \$captured_content = ob_get_contents(); // Get the content first
                    ob_end_clean();
                    \$this->scope['captures']['{$current_capture_var}']=\$captured_content;
                ?>\n";
                // Restore previous capture state if nested
                if (!empty($capture_stack)) {
                    $prev = array_pop($capture_stack);
                    $capture_buffer = $prev['buffer'];
                    $current_capture_var = $prev['var_name'];
                } else {
                    $in_capture = false;
                    $capture_buffer = "";
                }
                continue; // Don't output anything for CAPTURE_END
            }
            // If inside capture, add to buffer instead of output
            if ($in_capture) {
                // Recursively compile nested tokens to PHP
                $nested_php = self::compileTokenToPhp($token);
                $capture_buffer .= $nested_php;
                continue;
            }            
            // Store tokens for dynamic content (not compiled PHP)
            if ($type == "DYNAMIC_START")
            {
                $in_dynamic = true;
                $dynamic_tokens = [];
                continue;
            }
            if ($type == "DYNAMIC_END")
            {
                $output .= "<?=\$this->PluginCall('dynamic',".var_export($dynamic_tokens, true).");?>";
                $in_dynamic = false;
                continue;
            }
            if ($in_dynamic) {
                $dynamic_tokens[] = $token; // Store raw tokens
                continue;
            }
            if($type == "INCLUDE")
            {
                static $tempVarCounter = 0;
                $compiledParams = [];

                // Use a different variable name to avoid overwriting the outer $token
                foreach ($token["params"] as $key => $value) {
                    // Skip control params
                    if (in_array($key, ['file', 'scope', 'modifiers'])) {
                        $compiledParams[$key] = $value;
                        continue;
                    }

                    // Tokenize parameter value
                    $tokensParam = AstLexer::tokenizeString($value);
                

                    if (empty($tokensParam)) {
                        $compiledParams[$key] = "''";
                        continue;
                    }
                    // Detect if we need a buffer
                    $needsBuffer = false;
                    foreach ($tokensParam as $t) {
                        if (in_array($t['name'], ['if', 'foreach', 'for', 'block', 'include'])) {
                            $needsBuffer = true;
                            break;
                        }
                    }

                    $tempVarName = '$_includeParam_' . self::$includeCount . '_' . $tempVarCounter++;

                    if (!$needsBuffer) {
                        // Safe concatenation
                        $parts = [];

                        foreach ($tokensParam as $t) {
                            if ($t['type'] === 'TEXT') {
                                $text = addslashes($t['value']);
                                if ($text !== '') $parts[] = "'{$text}'";
                            } else {
                                $code = self::compileTokenToPhp($t);
                                /* Remove <?= ... ?> wrapper if any*/
                                $code = preg_replace('/^\s*<\?=\s*/', '', $code);
                                $code = preg_replace('/;?\s*\?>\s*$/', '', $code);
                                $parts[] = "{$code}";
                            }
                        }
                        $expr = implode(' . ', $parts);
                        $compiledParams[$key] = $expr;
                    } else {
                        // Use buffer only for complex blocks
                        $output .= "<?php ob_start();?>\n";
                        foreach ($tokensParam as $t) {
                            $output .= self::compileTokenToPhp($t);
                        }
                        $output .= "\n<?php {$tempVarName} = ob_get_clean();?>\n";
                        $compiledParams[$key] = $tempVarName;
                    }
                }


                // Process 'file' param safely
                $iVal = Plugins::call('replace', [$compiledParams["file"], ["{", "}"], ""]);
                $isTplExist = false;
                if (str_ends_with($iVal, '.tpl'))
                {
                    $iVal = substr($iVal, 0, -4);
                    $isTplExist = true;
                }
                $fileName = Plugins::call('variable',["name"=>$iVal]);
                preg_match_all('/(\$[a-zA-Z_][a-zA-Z0-9_]*(?:\[[^\]]+\]|\->[a-zA-Z_][a-zA-Z0-9_]*)*|[^$]+)/',$fileName,$matches);
                $fileParams = [];
                foreach ($matches[0] as $v) {
                    if ($v[0] === "$") {
                        $fileParams[] = $v;
                    } else {
                        $fileParams[] = "'" . addslashes($v) . "'";
                    }
                }
                if($isTplExist){
                    $fileParams[] = "'" . addslashes('.tpl') . "'";
                }
                $compiledParams["file"] = implode(' . ', $fileParams);
                if($compiledParams["scope"] === null) unset($compiledParams["scope"]);
                if(isset($compiledParams["modifiers"])){
                    if(empty($compiledParams["modifiers"])) unset($compiledParams["modifiers"]);
                }
                // Convert array to PHP code

                $arrayToCode = Custom::arrayToPhpCode($compiledParams);
                $arrayToCode = str_replace(["'true'","'false'"],["true","false"],$arrayToCode);
                // Generate final PHP code for include plugin call
            
                $output .= "<?=\$this->PluginCall('include', {$arrayToCode}); ?>";

                self::$includeCount++;
                continue; // Skip original token processing                
            }
            $output .= self::compileTokenToPhp($token);
        }
        if($is_include){
        // Wrap in closure
        $wrapped = "<?php
    return static function(array \$scope)
    {
    ?>\n".$output."\n
    <?php
    };";
        return $wrapped;
        }else{
        // Wrap in closure
        $wrapped = "<?php
    return function(array \$scope)
    {
        extract(\$scope, EXTR_SKIP);
    ?>\n".$output."\n
    <?php
    };";
        return $wrapped;
        }
    }
    // Helper to convert a single token to PHP code (for capture buffer)
    public static function compileTokenToPhp($token = [])
    {
        $name   = $token['name'] ?? "None";
        $type   = $token['type'] ?? null;
        $params = $token['params'] ?? null;
        /* Most common tokens first */
        if ($name === "TEXT") return $token["value"];
        if ($type === "VARIABLE_AST") return "<?=" . Plugins::call('variable', $token["ast"]) . ";?>";
        if ($type === "VARIABLE") {
            $var = Plugins::call('variable', $token);
            if (str_ends_with($name, '--')) return "<?php {$var};?>";
            return "<?={$var};?>";
        }
        /* delimiters */
        if ($name === "ldelim") return "{";
        if ($name === "rdelim") return "}";
        /* loops */
        if (isset(self::$loops[$name])) {
            return "<?php\n" . Plugins::call($name, $token, $token["loopNo"] ?? 0) . "?>";
        }
        if (isset(self::$htmlPlugins[$name])) {
            $assign = isset($params["assign"])
                ? Plugins::call('ltrim', [$params["assign"], "$"])
                : null;
            $paramsCode = Custom::arrayToPhpCode($params);

            if ($assign === null) {
                return "<?=\$this->PluginCall('{$name}',{$paramsCode});?>";
            }
            return "<?=\$this->PluginCall('{$name}',{$paramsCode});
                \${$assign}=\$this->scope['{$assign}'];
            ?>";
        }
        switch ($name)
        {
            case "html_image":
                return Plugins::call('html_image', $params);
            case "tif":
                if (is_array($params)) {
                    $condition = self::compileVariableExpression($params["condition"]);
                    $true      = self::compileVariableExpression($params["true"]);
                    $false     = self::compileVariableExpression($params["false"]);
                    return "<?={$condition} ? {$true}:{$false};?>";
                }
                return "<?=" . self::compileVariableExpression($params) . ";?>";
            case "assign":
            case "math":
                return "<?php " . Plugins::call($name, $token) . ";?>";
            case "dump":
            case "debug":
            case "cycle":
            case "a":
                $arrayCode = Custom::arrayToPhpCode($params ?? $token);
                return "<?=\$this->PluginCall('{$name}',{$arrayCode});?>";
            case "comment":
                return Plugins::call('comments', $token);
            case "if":
            case "elseif":
            case "else":
                return "<?php " . Plugins::call('condition', $token) . "?>";
        }
        /* function call */
        if ($type === "FUNCTION") {
            $fParams = [];
            foreach ($params as $k => $v)
            {
                $v = Plugins::call('ltrim', [$v, "'"]);
                $v = Plugins::call('rtrim', [$v, "'"]);
                $v2 = Plugins::call('variable', ["name" => $v]);
                $v2 = Plugins::call('ltrim', [$v2, "'"]);
                $v2 = Plugins::call('rtrim', [$v2, "'"]);
                $fParams[$k] = $v2;
            }
            return "<?=\$this->PluginCall('{$name}'," . Custom::arrayToPhpCode($fParams) . ");?>";
        }
        if (is_numeric($name)) return $name;
        return "";
    }
    public static function compileVariableExpression(string $input): string {
        return Variables::call($input);
    }
}