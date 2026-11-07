<?php
use Tplix\Tplix;

class TplixCompileConfig_load
{
    public function call(array $args = []): ?string
    {
        $file  = $args['file'] ?? null;
        $section  = $args['section'] ?? null;
        if (!$file || !is_readable($file)) {
            return null;
        }
        $contents = $this->parse($file,$section);
        if (!empty($contents['data'])) {
            $this->assign($contents['data']);
        }

        return null;
    }

    private function assign(array $data, string $prefix = 'ConfigFile.')
    {
        $tplix = Tplix::getInstance();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->assign($value, $key);
            } else {
                $tplix->assign($key, $value);
            }
        }
    }
    private function parse(string $filePath, string $isection = null): array
    {
        if (!is_readable($filePath)) {
            return ['comments' => [], 'data' => []];
        }
    
        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        $comments = [];
        $globals = [];
        $sections = [];
        $currentSection = null;
    
        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
    
            // skip empty lines
            if ($line === '') continue;
    
            // comments (support # and ;)
            if ($line[0] === '#' || $line[0] === ';') {
                $comments[] = $line;
                continue;
            }
    
            // section header
            if (preg_match('/^\s*\[([^\]]+)\]\s*$/', $line, $m)) {
                $currentSection = trim($m[1]);
                continue;
            }
    
            // key = value (allow = in value)
            if (strpos($line, '=') !== false) {
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                $value = trim($value);
                // strip surrounding quotes (single or double)
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
    
                if ($currentSection === null) {
                    // global key
                    $globals[$key] = $value;
                } else {
                    // section key
                    if (!isset($sections[$currentSection])) {
                        $sections[$currentSection] = [];
                    }
                    $sections[$currentSection][$key] = $value;
                }
            }
        }
    
        // Build config array with both global keys and section-scoped keys
        $config = [];
    
        // Put global keys in tplVar.ConfigFile.*
        foreach ($globals as $k => $v) {
            $config["tplVar.config.$k"] = $v;
        }
    
        // Put section-scoped keys in tplVar.config.Section.key
        foreach ($sections as $sec => $pairs) {
            foreach ($pairs as $k => $v) {
                $config["tplVar.config.$sec.$k"] = $v;
            }
        }
    
        // If a section requested, overwrite global keys with that section's keys (Smarty behavior)
        if ($isection !== null && isset($sections[$isection])) {
            foreach ($sections[$isection] as $k => $v) {
                $config["tplVar.config.$k"] = $v; // overwrite or create global key with section value
            }
        }
    
        return ['comments' => $comments, 'data' => $config];
    }

}