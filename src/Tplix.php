<?php
namespace Tplix;
// Include exceptions manually if not loaded via autoloader
if (!class_exists('Tplix\\TplixException'))
{
    require_once __DIR__ . '/exceptions/TplixException.php';
    require_once __DIR__ . '/exceptions/TplixTemplateNotFoundException.php';
    require_once __DIR__ . '/exceptions/TplixCompilationException.php';
    require_once __DIR__ . '/exceptions/TplixExecutionException.php';
    require_once __DIR__ . '/exceptions/TplixConfigException.php';
    require_once __DIR__ . '/exceptions/TplixPluginNotFoundException.php';
    require_once __DIR__ . '/exceptions/TplixTokensException.php';
}
require_once __DIR__ . '/system/Custom.php';
require_once __DIR__ . '/system/Variables.php';
require_once __DIR__ . '/system/Tags.php';
require_once __DIR__ . '/system/Loops.php';
require_once __DIR__ . '/system/ExpressionLexer.php';
require_once __DIR__ . '/system/ExpressionParser.php';

use Tplix\Parsers;
use Tplix\Plugins;
use Tplix\Security;
use Tplix\Template;
use Tplix\AstLexer;
use Tplix\Cache;
use Tplix\Tags;
use Tplix\Variables;
use Tplix\Custom;

if (!defined('TPLIX_MBSTRING')) {
    define('TPLIX_MBSTRING', function_exists('mb_get_info'));
}
if (!defined('TPLIX_RESOURCE_CHAR_SET')) {
    // UTF-8 can only be done properly when mbstring is available!
    /**
     * @deprecated in favor of Tplix::$_charset
     */
    define('TPLIX_RESOURCE_CHAR_SET', TPLIX_MBSTRING ? 'UTF-8' : 'ISO-8859-1');
}

class Tplix {
    protected Cache $cache;
    const TPLIX_VERSION = '1.0.0';
    private $template = null;
    public string $charset = TPLIX_RESOURCE_CHAR_SET; // charset encoding
    /**
     * Flag denoting if Multibyte String functions are available
     */
    public static $_MBSTRING = TPLIX_MBSTRING;

    /**
     * The character set to adhere to (e.g. "UTF-8")
     */
    public static $_charset = TPLIX_RESOURCE_CHAR_SET;
    
    public bool $debugging = false; // false or true
    
    private $includedTemplates = [];
    public $startTime;
    public string $debugging_ctrl = "NONE"; // Options: 'NONE', 'URL'
    public string $debug_tpl = "src/debug.tpl"; // Options: 'NONE', 'URL'

    protected array $globals = [];
    protected array $scope = [];
    public array $cyclesScope = [];

    public string $compileDir  = 'templates_c/';
    public string $cacheDir = '';
    public string $templateDir = 'templates/';

    protected int $cacheTime = 0;
    protected $securityPolicy = null;
    protected array $pluginsList = [];
    private bool $mergeIncludes = true;
    protected array $filters = [];

    private static ?Tplix $instance = null;
    protected string $mode = "PROD"; // DEV or PROD

    protected static array $closureCache = [];
    private static array $compileLogsCache = [];
    private static array $tokenCache = [];
    public int $dynamicFileCount = 0;
    public int $variableCount = 0;
    private array $tplMetaCache = []; // add this to your class
    private array $compiledCache = []; // add this to your class
    
    private static $scriptStyleBuffers = [];
    private static $placeholderCounter = ['script' => 0, 'style' => 0];

    public function __construct()
    {
        self::$instance = $this;
        $this->startTime = microtime(true);
        if ($this->debugging_ctrl === 'URL' && isset($_GET['TPLIX_DEBUG'])) $this->debugging = true;
        // Default: hide notices/warnings in PROD, show in DEV
        if ($this->mode === 'DEV')
        {
            error_reporting(E_ALL);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        }
        // Auto-select best cache backend (Redis → APCu → File)
        /*$this->cache = new Cache([
            'driver' => 'file',//redis,apcu,file
            'redis_host' => '127.0.0.1',
            'redis_port' => 6379,
            'redis_password' => 'mypassword',
            'redis_db' => 1
        ],$this->compileDir);*/
        // assign base tpl variables (use empty arrays if superglobals missing)
        $this->assign([
            'tplVar.now'     => time(),
            'tplVar.get'     => isset($_GET) ? $_GET : [],
            'tplVar.post'    => isset($_POST) ? $_POST : [],
            'tplVar.request' => isset($_REQUEST) ? $_REQUEST : [],
            'tplVar.server'  => isset($_SERVER) ? $_SERVER : [],
            'tplVar.session' => isset($_SESSION) ? $_SESSION : [],
            'tplVar.cookies' => isset($_COOKIE) ? $_COOKIE : [],
            'tplVar.env'     => isset($_ENV) ? $_ENV : [],
        ]);
    }
    public function setTemplateDir($dir){
        $this->templateDir = $dir;
    }
    public static function getInstance(): Tplix
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setMergeIncludes(bool $merge): static {
        $this->mergeIncludes = $merge;
        return $this;
    }

    public static function setAllowAllPropertyAccess($trueFalse=false): void
    {
        Security::setAllowAllPropertyAccess($trueFalse);
    }
    public function assign(array|string $key, mixed $value = null): void
    {
        if (is_array($key)) {
            // Assign each key-value pair if $key is an array
            foreach ($key as $k => $v) {
                $this->assignInScope($v, $k);
            }
            return;
        }
        // If $key is a string, assign the single value
        $this->assignInScope($value, $key);
    }
    public function assignInScope(mixed $value, string $scope): void
    {
        if (!is_string($scope))
        {
            throw new TplixExecutionException(
                "Assignments must be into strings, got " . gettype($scope)
            );
        }
        if (strpos($scope, '.') === false && strpos($scope, '->') === false && strpos($scope, '[') === false) {
            $this->scope[$scope] = $value;
            return;
        }

        preg_match_all('#(\[|->|\.)?([^.[\]-]+)\]?#i', $scope, $m);

        $cur  = &$this->scope;
        $lastSep = array_pop($m[1]);
        $lastKey = array_pop($m[2]);

        foreach ($m[1] as $k => $sep) {
            if ($sep === '.' || $sep === '[' || $sep === '' || $sep === null) {
                if (!is_array($cur)) {
                    $cur = [];
                }
                $cur = &$cur[$m[2][$k]];
            } elseif ($sep === '->') {
                if (!is_object($cur)) $cur = new \stdClass();
                $cur = &$cur->{$m[2][$k]};
            } else {
                return;
            }
        }
        if ($lastSep === '.' || $lastSep === '[' || $lastSep === '' || $lastSep === null) {
            if (!is_array($cur)) $cur = [];
            $cur[$lastKey] = $value;
        } elseif ($lastSep === '->') {
            if (!is_object($cur)) $cur = new \stdClass();
            $cur->{$lastKey} = $value;
        }
    }

    public function getIncludedTemplates(): array
    {
        return $this->includedTemplates;
    }
    public function getScope(): array
    {
        return $this->scope;
    }
    public function getTemplateVars(): array
    {
        return $this->scope;
    }
    public function getCompileInfo($compilePath): array
    {
        $fp = fopen($compilePath, 'r');
        $header = '';

        while (!feof($fp)) {
            $header .= fgets($fp);
            if (strpos($header, '*/') !== false) {
                break;
            }
        }

        fclose($fp);

        $start = strpos($header, '/*');
        $end   = strpos($header, '*/');

        if ($start !== false && $end !== false) {
            $json = substr($header, $start + 2, $end - $start - 2);
            return json_decode($json, true) ?: [];
        }

        return [];
    }
    public static function fastHash(string $filePath): string
    {
        // Get just the filename (e.g. layout.tpl → layout)
        $base = basename($filePath, '.tpl');
        // Compute ultra-fast 32-bit hash with CRC32
        $hash = dechex(crc32($filePath));
    
        // Combine readable + hashed form for easy debugging
        return $base . '_' . $hash;
    }
    public function getDir(){
        return $this->compileDir;
    }
    public function getSessionId(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return session_id();        
    }
    function handlePostScriptStyles($compiledContent="")
    {
        $finalContent=[];
        if (!empty(self::$scriptStyleBuffers))
        {
            foreach (self::$scriptStyleBuffers as $placeholder => $originalScriptContent)
            {
                if (strpos($placeholder, '[SCRIPT') !== false)
                {
                    // Tokenize only the inner content where template variables exist
                        $maps = [];
                        $originalScriptContent = preg_replace_callback(
                            '/\{([^\s][^}]*[^\s])\}/',
                            function ($m) use (&$maps) {
                                // $m[0] = full match including {}
                                // $m[1] = inner content

                                $key = '__JS_' . count($maps) . '__';

                                $maps[$key] = $m[0]; // remember original text

                                return $key; // temporary replace
                            },
                            $originalScriptContent
                        );
                    // Compile the tokens to get processed content
                    if(!empty($maps))
                    {
                        foreach($maps as $k=>$v)
                        {
                            $tokens = AstLexer::tokenizeJsContent($v);
                            foreach($tokens as $token)
                            {
                                $compiled = Template::compileTokenToPhp($token);
                                $originalScriptContent = str_replace($k,$compiled,$originalScriptContent);
                            }
                        }
                    }
                    $finalContent[$placeholder]= $originalScriptContent;
                    //echo "<textarea>".print_r($compiledInnerContent,true)."</textarea>";
                    //die();
                    // Replace the placeholder in the main content
                }else{
                    $tokens = AstLexer::tokenizeCSS($originalScriptContent);
                    // Compile the tokens to get processed content
                    $compiledInnerContent = '';
                    foreach($tokens as $token)
                    {
                        $compiledInnerContent .= Template::compileTokenToPhp($token);
                    }
                    $finalContent[$placeholder]= $compiledInnerContent;
                }
            }
        }        
        $compiledContent = str_replace(array_keys($finalContent), array_values($finalContent), $compiledContent);
        return $compiledContent;
    }
    function handlePreScriptStyles($templateContent="")
    {
        $preprocessCallback = function ($matches) {
            $fullMatch = $matches[0];
            $openTag = $matches[1]; // The opening tag with attributes
            $tagType = strtolower($matches[2]); // 'script' or 'style'
            $content = $matches[3]; // Content inside the tags
            
            // Check if the tag has a src attribute (for script tags)
            if ($tagType === 'script' && preg_match('/\bsrc\s*=\s*["\'][^"\']*["\']/i', $openTag)) {
                // Has src attribute, return original content unchanged
                return $fullMatch;
            }
            
            // For style tags, check if they have a src-like attribute or href
            if ($tagType === 'style' && preg_match('/\b(href|src)\s*=\s*["\'][^"\']*["\']/i', $openTag)) {
                // Has src/href attribute, return original content unchanged
                return $fullMatch;
            }
            
            // No src attribute found, process normally
            self::$placeholderCounter[$tagType]++;
            $placeholder = '[' . strtoupper($tagType) . self::$placeholderCounter[$tagType] . ']';
            self::$scriptStyleBuffers[$placeholder] = $fullMatch; // Store content
            return $placeholder; // Return placeholder
        };
        
        // Use a regex to find <script>...</script> and <style>...</style> blocks
        $pattern = '/(<(script|style)\b[^>]*>)(.*?)<\/\2>/is'; 
        $templateContent = preg_replace_callback($pattern, $preprocessCallback, $templateContent);
        return $templateContent;
    }
    function handlePreHashConfigs($templateContent = "")
    {
        return preg_replace_callback(
            '/\{#([^#]+)#\}/',
            function ($matches) {
                return '$.config.' . $matches[1];
            },
            $templateContent
        );
    }
    function fileHasHtml($file) {
        $content = file_get_contents($file);
        // Remove PHP code blocks
        $clean = preg_replace('/<\?php.*?\?>/s', '', $content);
        $clean = trim($clean);
        // Detect HTML tags
        return preg_match('/<(div|a|body|section|span|p|ul|li|html|head|meta|img|script|style)\b/i', $clean) === 1;
    }

    // -------------------- Fetch / compile flow --------------------
    public function fetch($tplFile = "", bool $returnString = true, ?array $customScope = null, $is_include = false): mixed
    {
        if (empty($tplFile)) return "";

        // Ensure compile directory exists
        $dir = rtrim($this->compileDir, '/') . '/';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create compile directory: $dir");
        }

        $isStringTpl = str_starts_with($tplFile, "string:");
        $tplKey = $isStringTpl
            ? self::fastHash("string-" . microtime(true))
            : self::fastHash($tplFile);
        // Use in-memory metadata cache to skip repeated filesystem calls
        if (isset($this->tplMetaCache[$tplKey]) && !$this->debugging) {
            $tplMtime = $this->tplMetaCache[$tplKey]['tplMtime'];
            $compilePath = $this->tplMetaCache[$tplKey]['compilePath'];
            $recompile = $this->tplMetaCache[$tplKey]['recompile'];
        } else {
            $tplPath = $tplFile;
            $tplMtime = @filemtime($tplPath) ?: 0;
            $compilePath = $dir . $tplKey . '.php';
            $tplCmtime = @filemtime($compilePath) ?: 0;

            // Determine if recompile is needed
            $recompile = false;
            if ($tplCmtime) {
                $compileFilesLogs = $this->getCompileInfo($compilePath) ?: [];
                $log = $compileFilesLogs ?? null;
                if ($log !== null) {
                    $tplTime = $log['TplTime'] ?? 0;
                    $tplLastCompileTime = $log['compileTime'] ?? 0;
                    if ($tplMtime != $tplTime || $tplTime === 0 || $tplLastCompileTime === 0 || $tplLastCompileTime != $tplCmtime) {
                        $recompile = true;
                    } else {
                        $isHasHtml = $this->fileHasHtml($compilePath);
                        $recompile = !$isHasHtml;
                    }
                }
            } else {
                $recompile = true;
            }
            // Save meta in memory for future fetch calls
            $this->tplMetaCache[$tplKey] = [
                'tplMtime' => $tplMtime,
                'compilePath' => $compilePath,
                'recompile' => $recompile,
            ];
        }

        $this->includedTemplates[$tplKey]["name"] = $tplFile;
        $this->includedTemplates[$tplKey]["compile_time"] = null;

        // Compile template if needed
        if ($recompile) {
            AstLexer::resetCompileState();

            $templateContent = $isStringTpl
                ? str_replace("string:", "", $tplFile)
                : $this->fetchTpl($tplFile, true);

            $templateContent = $this->handlePreScriptStyles($templateContent);
            $templateContent = $this->handlePreHashConfigs($templateContent);
            $templateContent = Template::handleBlocksExtends($templateContent);

            if ($this->mergeIncludes) {
                $templateContent = Template::resolveIncludes($templateContent);
            }

            $templateContent = str_replace('$tplix.', '$.', $templateContent);
            // echo "<textarea class='form-control'>".print_r($templateContent,true)."</textarea>";
            $templateContent = str_replace("{else if", "{elseif", $templateContent);
            $templateContent = str_replace(["{{", "}|"], ["{", "|"], $templateContent);

            $this->template = $templateContent;
            Security::validateTplSource($this->template, $tplFile);

            $tokens = AstLexer::tokenize($this->template);
            foreach ($tokens as $item) {
                if ($item["type"] === "ERROR") {
                    throw new TplixTokensException($item["value"], $tplFile, $item["line"]);
                }
            }

            $tagsErrors = Tags::validate($tokens);
            if (!empty($tagsErrors)) {
                foreach ($tagsErrors as $error) {
                    throw new TplixTokensException($error, $tplFile);
                }
            }
            Security::validateTokens($tokens, $tplFile);
            $compiled = $this->compile($tokens, $is_include);
            //echo "<textarea class='form-control'>".print_r($compiled,true)."</textarea>";
            
            $this->includedTemplates[$tplKey]["compile_time"] = microtime(true) - $this->startTime;
            $compiled = $this->handlePostScriptStyles($compiled);
            // Save compiled PHP safely
            $this->storeCompileFile($tplKey,$tplFile,$tplMtime,$compilePath,$compiled);
            // Update in-memory meta cache
            $this->tplMetaCache[$tplKey]['recompile'] = false;
        }

        // Load compiled closure (with compiled cache)
        if (!is_file($compilePath)) {
            throw new TplixTemplateNotFoundException($tplFile, "Compiled template missing: " . $compilePath);
        }

        if (!isset($this->compiledCache[$tplKey])) {
            $tpl = require $compilePath;
            if (!is_callable($tpl)) {
                throw new TplixCompilationException(
                    "Compiled template did not return a valid closure: $compilePath"
                );
            }
            $this->compiledCache[$tplKey] = $tpl;
        }

        $tpl = $this->compiledCache[$tplKey];
        $scope = $customScope ?? $this->scope;

        $this->includedTemplates[$tplKey]["cache_time"] = microtime(true) - $this->startTime;

        ob_start();
        $tpl($scope);

        $html = ob_get_clean();

        $this->includedTemplates[$tplKey]["render_time"] = microtime(true) - $this->startTime;

        if (!$this->debugging) {
            return $html;
        }

        // Keep $html for debugging
        return $html;
    }
    function storeCompileFile($tplKey,$tplFile,$tpltime,$compilePath,$compiledCode){
        $tplHash = self::fastHash($tplFile);
        $compiletime = time();
        $tpltime = filemtime($tplFile);
        $meta = '/*{"Name":"TPLIX","File":"'.$tplFile.'","Hash":"'.$tplHash.'","TplTime": '.$tpltime.',"compileTime":'.$compiletime.'}*/';
        $compiledPhp = "<?php\n{$meta}\n";
        $compiledPhp .= ltrim($compiledCode,"<?php");
        file_put_contents($compilePath, $compiledPhp, LOCK_EX);        
    }
    public function assignScope(array $scope): void {
        $this->scope = $scope;
    }
    public function fetchTpl(string $template, bool $useCache = true): string
    {
        return Template::fetchTpl($template, $useCache);
    }
    public static function arrayToPhpCode($value): string {
        return Custom::arrayToPhpCode($value);
    }
    public function compile($tokens = [],$is_include=false)
    {
        return Template::compile($tokens,$is_include);
    }
    public static function compileVariableExpression(string $name): string
    {
        return Variables::call($name);
    }
    public function PluginCall($name="",$args=[],$loopNo=0)
    {
       return Plugins::call($name,$args,$loopNo);
    }
    public function registerPlugin($type="",$name="",$func="")
    {
       return Plugins::registerPlugin($type,$name,$func);
    }
}