<?php
use Tplix\Tplix;
class TplixParseCondition
{
    private $data = [];
    private $variables = [];
    
    public function setData(array $data): void
    {
        $this->data = $data;
    }
    
    public function parse(string $expr): mixed
    {
        $expr = rtrim($expr,"{");
        $expr = ltrim($expr,"{");
        $findAndReplace = [
            "|empty:'!'"=>' _isNotEmpty_',
            "|empty"=>' _isEmpty_',
            " is empty"=>' _isEmpty_',
            " is not empty"=>' _isNotEmpty_',
            "|is_array:'!'"=>' _isNotArray_',
            "|is_array"=>' _isArray_',
            " is array"=>' _isArray_',
            " is not array"=>' _isNotArray_',
            "|isset:'!'"=>' _isNotSet_',
            "|isset"=>' _isSet_',
        ];
        foreach ($findAndReplace as $k=>$v)
        {
            $expr = str_replace($k,$v, $expr);
        }
        //empty or not 
        $expr = preg_replace('/\$(\w+(?:->\w+|\.\w+|\[\s*[\'"]?\w+[\'"]?\s*\])*)\s*_isNotEmpty_\b/', '!empty($$1)', $expr);
        $expr = preg_replace('/\$(\w+(?:->\w+|\.\w+|\[\s*[\'"]?\w+[\'"]?\s*\])*)\s*_isEmpty_\b/', 'empty($$1)', $expr);
        //is_array or not
        $expr = preg_replace('/\$(\w+(?:->\w+|\.\w+|\[\s*[\'"]?\w+[\'"]?\s*\])*)\s*_isNotArray_\b/', '!is_array($$1)', $expr);
        $expr = preg_replace('/\$(\w+(?:->\w+|\.\w+|\[\s*[\'"]?\w+[\'"]?\s*\])*)\s*_isArray_\b/', 'is_array($$1)', $expr);

        $expr = preg_replace('/\$(\w+(?:->\w+|\.\w+|\[\s*[\'"]?\w+[\'"]?\s*\])*)\s*_isSet_\b/', 'isset($$1)', $expr);
        $expr = preg_replace('/\$(\w+(?:->\w+|\.\w+|\[\s*[\'"]?\w+[\'"]?\s*\])*)\s*_isNotSet_\b/', '!isset($$1)', $expr);

        // Handle "in" operator
        $expr = preg_replace('/(.+?)\s+is not in\s+(.+)/', '!in_array($1, $2)', $expr);
        $expr = preg_replace('/(.+?)\s+is in\s+(.+)/', 'in_array($1, $2)', $expr);

        // Handle modifiers first
        $expr = $this->processModifiers($expr);
        
        // Replace variables in the expression
        $expr = $this->replaceVariables($expr);
        // Clean up any remaining special syntax
        $expr = $this->cleanExpression($expr);
        return ["variables"=>$this->variables,"condition"=>$expr];
    }
    
    private function processModifiers(string $expr): string
    {
        // Handle variable modifiers like $var|modifier:param
        $expr = preg_replace_callback(
            '/\$(\w+(?:\.\w+)*)\|(\w+)(?::((?:\'[^\']*\'|"[^"]*"|[^ )]+)))?/',
            function ($m) {
                $var = '$' . $m[1];
                $modifier = $m[2];
                $param = $m[3] ?? null;
                
                switch ($modifier) {
                    case 'default':
                        return "isset($var) && $var !== '' ? $var : " . ($param ?? "''");
                    case 'upper':
                        return "strtoupper($var)";
                    case 'lower':
                        return "strtolower($var)";
                    case 'length':
                        return "strlen($var)";
                    case 'iset':
                        return "\$this->PluginCall('iset',[$var,$param])";
                    default:
                        // For other modifiers, you can implement as needed
                        return $var;
                }
            },
            $expr
        );
        
        return $expr;
    }
    
    private function replaceVariables(string $expr): string
    {
        // Replace variable references like $var.property or $var[index] with PHP syntax
        return preg_replace_callback(
            '/\$(\w+(?:\.\w+|->\w+|\[[^\]]+\])*)/',
            function ($matches) {
                $varPath = "$".$matches[1];
                $tplix = Tplix::getInstance();
                $tplix->variableCount++;
                $varName = "__VAR_REPLACEABLE__".$tplix->variableCount;
                $this->variables[$varName] = $varPath;
                // Convert dot notation to array/object access
                return $varName;
            },
            $expr
        );
    }
    
    private function cleanExpression(string $expr): string
    {
        // Handle text operators
        $operators = [
            'eq' => '==',
            'ne' => '!=',
            'neq' => '!=',
            'gt' => '>',
            'lt' => '<',
            'gte' => '>=',
            'lte' => '<=',
            'ge' => '>=',
            'le' => '<=',
        ];
        foreach ($operators as $text => $op) {
            $expr = preg_replace("/\s+$text\s+/", " $op ", $expr);
        }
        // Handle "and" and "or"
        $expr = preg_replace("/\s+and\s+/", " && ", $expr);
        $expr = preg_replace("/\s+or\s+/", " || ", $expr);
        $expr = preg_replace("/\s+not\s+/", " ! ", $expr);
        return $expr;
    }
}