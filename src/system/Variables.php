<?php
namespace Tplix;
use Tplix\Plugins;
/**
 * Compile a Tplix variable expression into valid PHP syntax.
 * Supports:
 *  - Dot access ($user.name.first)
 *  - Arrow access ($obj->prop)
 *  - Mixed ($user.profile->email)
 *  - Array indexes ($arr[0], $arr.$key)
 *  - Internal vars ($.config.title)
 */
class Variables
{
    public static function call(string $input): string
    {
        static $cache = [];
        if (isset($cache[$input])) return $cache[$input];

        $origInput = $input;

        // Trim braces
        $input = ltrim($input, "{");
        $input = rtrim($input, "}");

        // Early exits
        if ($input === '' || strpos($input, '$') === false) return $cache[$origInput] = $input;
        if (!preg_match('/[\.\{\[]/', $input)) return $cache[$origInput] = $input;

        // Quick replacements
        $input = strtr($input, [".{" => ".[", "}." => "]."]);
        if (strpos($input, '$.') === 0)
        {
            $input = str_replace('$.', '', $input);
            $input = '$tplVar.' . $input;
        }

        // Fast operator check
        if (strpbrk($input, '()+-*/')) {
            $result = preg_replace_callback('/\$[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z0-9_]+)+/', function ($m) {
                $parts = explode('.', $m[0]);
                $base = array_shift($parts);
                foreach ($parts as $p) $base .= '["'.$p.'"]';
                return $base;
            }, $input);
            return $cache[$origInput] = $result;
        }

        // Extract {$...} placeholders
        $exprStore = [];
        $inputWithPH = preg_replace_callback('/\{\$[^{}]*\}/', function($m) use (&$exprStore) {
            $k = "TPLIX_EXPR_" . count($exprStore);
            $exprStore[$k] = $m[0];
            return $k;
        }, $input);

        // Tokenize manually
        $tokens = [];
        $buf = '';
        $brDepth = 0;
        $inSingle = $inDouble = false;
        $len = strlen($inputWithPH);
        for ($i = 0; $i < $len; $i++) {
            $ch = $inputWithPH[$i];
            if ($ch === "'" && !$inDouble) { $inSingle = !$inSingle; $buf .= $ch; continue; }
            if ($ch === '"' && !$inSingle) { $inDouble = !$inDouble; $buf .= $ch; continue; }
            if ($inSingle || $inDouble) { $buf .= $ch; continue; }
            if ($ch === '[') { $brDepth++; $buf .= $ch; continue; }
            if ($ch === ']') { $brDepth = max(0, $brDepth-1); $buf .= $ch; continue; }
            if ($ch === '.' && $brDepth === 0) { if ($buf !== '') { $tokens[] = $buf; $buf=''; } continue; }
            $buf .= $ch;
        }
        if ($buf !== '') $tokens[] = $buf;

        // Linear compile loop
        $result = array_shift($tokens);
        if (!isset($result[0]) || $result[0] !== '$') {
            $result = preg_replace_callback('/TPLIX_EXPR_\d+/', fn($m) => $exprStore[$m[0]] ?? $m[0], $inputWithPH);
            return $cache[$origInput] = $result;
        }

        $allowConvertAfterArrow = true;
        foreach ($tokens as $tok) {
            // Placeholder
            if (isset($exprStore[$tok])) {
                $orig = $exprStore[$tok];
                $result .= strpos($orig, '.') !== false ? '['.$orig.']' : '['.substr($orig,1,-1).']';
                $allowConvertAfterArrow = true;
                continue;
            }

            // Arrow handling
            $arrowPos = strpos($tok,'->');
            if ($arrowPos !== false) {
                $left = substr($tok,0,$arrowPos);
                $right = substr($tok,$arrowPos+2);
                $result .= $left !== '' ? self::compileLinearToken($left) : '';
                $result .= '->'.$right;
                $allowConvertAfterArrow = strpos($right,'(') === false;
                continue;
            }

            // Brackets or normal token
            $result .= self::compileLinearToken($tok, $allowConvertAfterArrow);
            $allowConvertAfterArrow = true;
        }

        // Restore placeholders in result
        $result = preg_replace_callback('/TPLIX_EXPR_\d+/', fn($m) => $exprStore[$m[0]] ?? $m[0], $result);

        return $cache[$origInput] = $result;
    }

    // Fully linear token compiler
    private static function compileLinearToken(string $tok, bool $allowConvertAfterArrow=true): string
    {
        // Numeric
        if (ctype_digit($tok)) return '['.$tok.']';
        // Variable
        if ($tok[0]==='$') {
            $parts = explode('.', $tok);
            $base = array_shift($parts);
            foreach ($parts as $p) $base .= '["'.$p.'"]';
            return $base;
        }
        // Bracket handling
        if (strpos($tok,'[')!==false) {
            $out=''; $pos=0; $len=strlen($tok);
            while ($pos<$len) {
                if ($tok[$pos]==='[') {
                    $level=0; $buf=''; $i=$pos;
                    while ($i<$len) {
                        $c=$tok[$i]; $buf.=$c;
                        if ($c==='[') $level++; if ($c===']') { $level--; if($level===0){$i++;break;} }
                        $i++;
                    }
                    $inner=substr($buf,1,-1);
                    $out.='['.self::compileLinearToken($inner).']';
                    $pos=$i; continue;
                }
                $out.=$tok[$pos]; $pos++;
            }
            return $out;
        }
        // Plain property
        return $allowConvertAfterArrow ? '["'.addcslashes($tok,'"\\').'"]' : '.'.$tok;
    }
}