<?php
declare(strict_types=1);
namespace Tplix;
class Security
{
    // -------------------------------
    // --- Template Engine Policies ---
    // -------------------------------

    protected static array $allowedTags = [
        'if', 'elseif', 'else', 'foreach', 'for', 'loop', 'include', 'var'
    ];

    protected static array $allowedPhpFunctions = [
        'strlen', 'count', 'in_array', 'is_array',
        'round', 'floor', 'ceil', 'number_format','isset','time'
    ];

    protected static array $allowedModifiers = [
        'upper', 'lower', 'escape', 'trim', 'substr', 'join','isset'
    ];

    protected static array $allowedMethods = [];      // e.g. ['User' => ['getName']]
    protected static array $allowedProperties = [];   // e.g. ['User' => ['name']]

    protected static array $compiledCodeBlacklist = [
        'eval(', 'system\\s*\\(', 'exec\\s*\\(', 'passthru\\s*\\(',
        'shell_exec\\s*\\(', 'popen\\s*\\(', 'proc_open\\s*\\(',
        '`', '\\bassert\\s*\\(', '\\bcreate_function\\s*\\(',
        '\\binclude\\s*(_once)?\\s*\\(', '\\brequire\\s*(_once)?\\s*\\(',
        '\\bbase64_decode\\s*\\(', '\\bgzuncompress\\s*\\('
    ];

    protected static array $disallowedFunctions = [
        'eval(', 'exec', 'system', 'shell_exec', 'passthru','include', 'require',
        'popen', 'proc_open', 'assert', 'file_put_contents','rmdir',
        'file_get_contents', 'unlink', 'copy', 'move_uploaded_file','create_function'
    ];
    protected static bool $throwOnViolation = true;
    protected static $violationCallback = null;

    protected static bool $allowAllPropertyAccess = true;
    protected static bool $allowSuperGlobals = false;
    protected static bool $allowConstants = false;

    protected static array $allowedConstants = ['true', 'false', 'null'];


    public function __construct(array $policy = [])
    {
        foreach ($policy as $k => $v) {
            if (property_exists(self::class, $k)) {
                if (is_array(self::${$k}) && is_array($v)) {
                    self::${$k} = $v;
                } elseif ($k === 'throwOnViolation') {
                    self::$throwOnViolation = (bool)$v;
                } elseif ($k === 'violationCallback' && is_callable($v)) {
                    self::$violationCallback = $v;
                }
            }
        }
    }

    // -------------------------
    // Policy Setters
    // -------------------------

    public static function allowTag(string $tag): void
    {
        self::$allowedTags[] = $tag;
    }

    public static function denyTag(string $tag): void
    {
        self::$allowedTags = array_diff(self::$allowedTags, [$tag]);
    }

    public static function allowPhpFunction(string $fn): void
    {
        self::$allowedPhpFunctions[] = $fn;
    }
    public static function isAllowedFuncs(string $fn): bool
    {
        $funs = self::$allowedPhpFunctions;
        $mods = self::$allowedModifiers;
        $funs = array_merge($funs, $mods);
        return in_array($fn,$funs);
    }

    public static function denyPhpFunction(string $fn): void
    {
        self::$allowedPhpFunctions = array_diff(self::$allowedPhpFunctions, [$fn]);
    }

    public static function setAllowedModifiers(array $mods): void
    {
        self::$allowedModifiers = $mods;
    }

    public static function setAllowedMethods(array $map): void
    {
        self::$allowedMethods = $map;
    }

    public static function setAllowedProperties(array $map): void
    {
        self::$allowedProperties = $map;
    }

    public static function setThrowOnViolation(bool $v): void
    {
        self::$throwOnViolation = $v;
    }

    public static function setViolationCallback(callable $cb): void
    {
        self::$violationCallback = $cb;
    }

    public static function setAllowAllPropertyAccess(bool $allow): void
    {
        self::$allowAllPropertyAccess = $allow;
    }

    public static function setAllowSuperGlobals(bool $allow): void
    {
        self::$allowSuperGlobals = $allow;
    }

    public static function setAllowConstants(bool $allow): void
    {
        self::$allowConstants = $allow;
    }

    public static function setAllowedConstants(array $consts): void
    {
        self::$allowedConstants = array_map('strtolower', $consts);
    }

    // -------------------------
    // Validation Logic
    // -------------------------

    /**
     * Validate parsed template tokens before compilation.
     */
    public static  function validateTokens(array $tokens, string $tplFile = ''): bool
    {
        foreach ($tokens as $token) {
            $type = $token['type'] ?? null;

            // --- Tag validation ---
            if ($type === 'tag') {
                $name = $token['name'] ?? '';
                if (!in_array($name, self::$allowedTags, true)) {
                    return self::violation("Tag not allowed: {$name}", ['token' => $token, 'file' => $tplFile]);
                }
            }

            // --- PHP function validation ---
            if (in_array($type, ['function_call', 'phpfunc', 'FUNCTION'], true)) {
                $fn = strtolower(trim($token['name'] ?? ''));
                $fn = str_contains($fn, '(') ? strstr($fn, '(', true) : $fn;

                if (in_array($fn, self::$disallowedFunctions, true)) {
                    throw new TplixExecutionException("Tplix Security Violation: Function '$fn' is not allowed in templates.");
                }
                if (!in_array($fn, self::$allowedPhpFunctions, true)) {
                    return self::violation("PHP function not allowed: {$fn}", ['token' => $token, 'file' => $tplFile]);
                }
            }

            // --- Modifier validation ---
            if ($type === 'modifier') {
                $m = $token['name'] ?? '';
                if (!in_array($m, self::$allowedModifiers, true)) {
                    return self::violation("Modifier not allowed: {$m}", ['token' => $token, 'file' => $tplFile]);
                }
            }

            // --- Variable validation ---
            if ($type === 'VARIABLE') {
                $name = $token['name'] ?? '';

                if (!self::$allowSuperGlobals &&
                    preg_match('/^\$(?:_GET|_POST|_REQUEST|_SESSION|_SERVER|_COOKIE|_ENV)\b/', $name)) {
                    return self::violation("Access to PHP superglobal not allowed: {$name}", ['token' => $token, 'file' => $tplFile]);
                }

                // Object access
                if (preg_match('/^\$([A-Za-z_]\w*)->([A-Za-z_]\w*)(\(\))?$/', $name, $m)) {
                    $var = '$' . $m[1];
                    $attr = $m[2];
                    $isMethod = !empty($m[3]);

                    if ($isMethod && !self::isMethodAllowed($var, $attr)) {
                        return self::violation("Method call not allowed: {$var}->{$attr}()", ['token' => $token, 'file' => $tplFile]);
                    }
                    if (!$isMethod && !self::isPropertyAllowed($var, $attr)) {
                        return self::violation("Property access not allowed: {$var}->{$attr}", ['token' => $token, 'file' => $tplFile]);
                    }
                }

                // Modifiers on variables
                if (!empty($token['modifiers'])) {
                    foreach ($token['modifiers'] as $mod) {
                        $mName = $mod['name'] ?? '';
                        if (!in_array($mName, self::$allowedModifiers, true)) {
                            return self::violation("Modifier not allowed in variable: {$mName}", ['token' => $token, 'file' => $tplFile]);
                        }
                    }
                }
            }

            // --- Constant access ---
            if ($type === 'CONSTANT') {
                $name = strtolower($token['name'] ?? '');
                if (!self::$allowConstants &&
                    !in_array($name, self::$allowedConstants, true)) {
                    return self::violation("Access to PHP constant not allowed: {$name}", ['token' => $token, 'file' => $tplFile]);
                }
            }

            // --- Explicit method call token ---
            if ($type === 'method_call') {
                $classOrVar = $token['target'] ?? '';
                $method = $token['name'] ?? '';
                if (!self::isMethodAllowed($classOrVar, $method)) {
                    return self::violation("Method call not allowed: {$classOrVar}::{$method}", ['token' => $token, 'file' => $tplFile]);
                }
            }

            // --- Explicit property access token ---
            if ($type === 'property_access') {
                $classOrVar = $token['target'] ?? '';
                $prop = $token['name'] ?? '';
                if (!self::isPropertyAllowed($classOrVar, $prop)) {
                    return self::violation("Property access not allowed: {$classOrVar}->{$prop}", ['token' => $token, 'file' => $tplFile]);
                }
            }
        }

        return true;
    }

    // -------------------------
    // Property & Method Checks
    // -------------------------

    protected static function isMethodAllowed(string $classOrVar, string $method): bool
    {
        if (empty(self::$allowedMethods)) return false;

        if (str_starts_with($classOrVar, '$')) {
            return isset(self::$allowedMethods['*']) &&
                   in_array($method, self::$allowedMethods['*'], true);
        }

        return isset(self::$allowedMethods[$classOrVar]) &&
               in_array($method, self::$allowedMethods[$classOrVar], true);
    }

    protected static function isPropertyAllowed(string $classOrVar, string $prop): bool
    {
        if (self::$allowAllPropertyAccess) return true;
        if (empty(self::$allowedProperties)) return false;

        if (str_starts_with($classOrVar, '$')) {
            return isset(self::$allowedProperties['*']) &&
                   in_array($prop, self::$allowedProperties['*'], true);
        }

        return isset(self::$allowedProperties[$classOrVar]) &&
               in_array($prop, self::$allowedProperties[$classOrVar], true);
    }

    // -------------------------
    // Compiled & Source Checks
    // -------------------------

    public static  function validateCompiledCode(string $phpCode, string $compilePath = ''): bool
    {
        foreach (self::$compiledCodeBlacklist as $pattern) {
            if (preg_match('/' . $pattern . '/i', $phpCode)) {
                return self::violation("Compiled code contains blacklisted construct: {$pattern}", ['path' => $compilePath]);
            }
        }
        return true;
    }

    public static function validateTplSource(string $tplContent, string $tplPath): bool
    {
        if (preg_match('/<\?(php|=)?|<%|%>/i', $tplContent)) {
            return self::violation("Raw PHP code not allowed in template: $tplPath", ['path' => $tplPath]);
        }

        if (preg_match('/\{include[^}]*https?:\/\//i', $tplContent)) {
            return self::violation("Remote includes not allowed: $tplPath", ['path' => $tplPath]);
        }

        return true;
    }

    // -------------------------
    // Runtime Safety
    // -------------------------

    public function callPhpFunction(string $fn, array $args = [])
    {
        if (!in_array($fn, self::$allowedPhpFunctions, true)) {
            return self::violation("Runtime call to disallowed PHP function: {$fn}", ['fn' => $fn]);
        }
        if (!is_callable($fn)) {
            return self::violation("Attempted call to non-callable function: {$fn}", ['fn' => $fn]);
        }

        return $fn(...$args);
    }

    // -------------------------
    // Violation Handler
    // -------------------------

    protected static function violation(string $message, array $context = [])
    {
        if(self::$violationCallback === null) return false;
        if (is_callable(self::$violationCallback)) {
            (self::$violationCallback)($message, $context);
        }

        if (self::$throwOnViolation) {
            throw new TplixExecutionException("Tplix Security Violation: " . $message);
        }

        return false;
    }
}
