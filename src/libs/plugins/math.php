<?php
/****************
 * {$.now|date_format}
 * {$.now|date_format:"%D"}
 * {$yesterday|date_format:"%A, %B %e, %Y"}
 * {$yesterday|date_format:$config.time}
 * {$yesterday|date_format:$config.date}
 * *************/
 use Tplix\Tplix;

class TplixCompileMath
{
    public function call(array $args = []): string
    {
        $params = $args["params"] ?? [];
        $equation = $args["params"]["equation"] ?? '';
        $assign   = $args["params"]["assign"]   ?? '';
        $format   = '';

        // be sure equation parameter is present
        if (empty($equation)) {
            throw new \Exception("math: missing equation parameter");
        }
        // Remove whitespaces
        $equation = preg_replace('/\s+/', '', $equation);
        // normalize constants
        $equation = str_ireplace(
            ['pi', 'M_PI()', 'inf', ' e '],
            ['M_PI', 'M_PI', 'INF', ' M_E '],
            trim($equation)
        );

        // allowed functions
        $_allowed_funcs = [
            'int'=>true,
            'abs'=>true,
            'ceil'=>true,
            'acos'=>true,
            'acosh'=>true,
            'cos'=>true,
            'cosh'=>true,
            'deg2rad'=>true,
            'rad2deg'=>true,
            'exp'=>true,
            'floor'=>true,
            'log'=>true,
            'log10'=>true,
            'max'=>true,
            'min'=>true,
            'pi'=>true,
            'pow'=>true,
            'rand'=>true,
            'round'=>true,
            'asin'=>true,
            'asinh'=>true,
            'sin'=>true,
            'sinh'=>true,
            'sqrt'=>true,
            'srand'=>true,
            'atan'=>true,
            'atanh'=>true,
            'tan'=>true,
            'tanh'=>true
        ];

        // Adapted from https://www.php.net/manual/en/function.eval.php#107377
        $number = '-?(?:\d+(?:[,.]\d+)?|pi|π)'; // What is a number
        $functionsOrVars = '((?:0x[a-fA-F0-9]+)|([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*))';
        $operators = '[,+\/*\^%-]'; // Allowed math operators
        $regexp = '/^((' . $number . '|' . $functionsOrVars . '|(' . $functionsOrVars . '\s*\((?1)*\)|\((?1)*\)))(?:' . $operators . '(?1))?)+$/';

        if (!preg_match($regexp, $equation)) {
            throw new \Exception("Math: illegal characters in equation: ".$equation);
        }

        // make sure parenthesis are balanced
        if (substr_count($equation, '(') !== substr_count($equation, ')')) {
            throw new \Exception("Math: unbalanced parenthesis");
        }

        // disallow backticks
        if (strpos($equation, '`') !== false) {
            throw new \Exception("Math: backtick character not allowed in equation");
        }

        // also disallow dollar signs
        if (strpos($equation, '$') !== false) {
            throw new \Exception("Math: dollar signs not allowed in equation");
        }

        // match all vars in equation, make sure all are passed
        preg_match_all('!(?:0x[a-fA-F0-9]+)|([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)!', $equation, $match);
        foreach ($match[1] as $curr_var)
        {
            if ($curr_var && !isset($params[$curr_var]) && !isset($_allowed_funcs[$curr_var])) {
                throw new \Exception("Math: function call '{$curr_var}' not allowed, or missing parameter '{$curr_var}'");
            }
        }
        // evaluate in a safe scope
        $out = $this->evaluate($equation);
        if ($format !== '') {
            $out = sprintf($format, $out);
        }
        if ($assign !==null)
        {
            Tplix::getInstance()->assign($assign,$out);
            if($assign[0] != "$") $assign = '$'.$assign;
            $assign = Tplix::compileVariableExpression($assign);
            return ''.$assign .'='.$out.'';
        }
        return $out;
    }

    private function evaluate(string $equation)
    {
        set_error_handler(function($errno, $errstr){
            throw new \Exception("Math error: $errstr");
        });

        try {
            $result = eval("return ($equation);");
            return $result;
        } finally {
            restore_error_handler();
        }
    }
}