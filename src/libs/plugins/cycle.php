<?php
/*******************
 * Initialize:{cycle values="1red,2blue,3green" name="color"}<br>
 * Val:{cycle}<br>
 * Advance:{cycle advance=false}<br>
 * Val:{cycle}<br>
 * Initialize New Cycle:{cycle values="odd,even" name="row"}<br>
 * Val:{cycle}<br>
 * Val:{cycle}<br>
 * Reset:{cycle reset=true}<br>
 ******************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCycle
{
    // Remember last used cycle name (for {cycle} without name)
    private static $lastCycleName = 'default';

    public function call(array $args = []): ?string
    {
        $tplix = Tplix::getInstance();
        $name      = $args["name"] ?? self::$lastCycleName;
        $print     = $this->boolVal($args["print"] ?? true);
        $advance   = $this->boolVal($args["advance"] ?? true);
        $reset     = $this->boolVal($args["reset"] ?? false);
        $delimiter = $args["delimiter"] ?? ',';
        $assign    = $args["assign"] ?? null;
        $valuesArg = $args["values"] ?? null;

        // Remember this name for next {cycle} calls
        self::$lastCycleName = $name;

        // Initialize or reuse values
        if ($valuesArg !== null) {
            if (is_string($valuesArg)) {
                $values = explode($delimiter, $valuesArg);
            } else {
                $values = array_values($valuesArg);
            }
            if (!isset($tplix->cyclesScope[$name]) ||
                $tplix->cyclesScope[$name]['values'] !== $values) {
                $tplix->cyclesScope[$name]['index'] = 0;
            }

            $tplix->cyclesScope[$name]['values'] = $values;
        } elseif (isset($tplix->cyclesScope[$name]['values'])) {
            $values = $tplix->cyclesScope[$name]['values'];
        } else {
            return ''; // prevent null (important for PHP return type)
        }

        if ($reset) $tplix->cyclesScope[$name]['index'] = 0;

        $index = $tplix->cyclesScope[$name]['index'];

        // Handle advance=false repeating previous value
        if (!$advance && $index > 0) {
            $out = $values[$index - 1];
        } else {
            $out = $values[$index] ?? '';
            if ($advance) {
                $tplix->cyclesScope[$name]['index'] = ($index + 1) % count($values);
            }
        }

        if ($assign !== null) {
            $tplix->assignInScope($out, $assign);
            return '';
        }

        return $print ? $out : '';
    }

    private function boolVal($v): bool
    {
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return (bool)$v;
        $v = strtolower(trim((string)$v));
        return in_array($v, ['1','true','yes','on'], true);
    }
}
