<?php
use Tplix\Tplix;
use Tplix\Plugins;

class TplixCompileHtml_select_time
{
    public function call(array $params = []): string
    {
        // Default parameters matching Smarty
        $defaults = [
            'prefix'                => 'Time_',
            'time'                  => null,
            'display_hours'         => true,
            'display_minutes'       => true,
            'display_seconds'       => true,
            'display_meridian'      => true, // Note: Smarty defaults to true
            'use_24_hours'          => true,
            'minute_interval'       => 1,
            'second_interval'       => 1,
            'field_array'           => null,
            'all_extra'             => '',
            'hour_extra'            => '',
            'minute_extra'          => '',
            'second_extra'          => '',
            'meridian_extra'        => '',
            'all_id'                => null,
            'hour_id'               => null,
            'minute_id'             => null,
            'second_id'             => null,
            'meridian_id'           => null,
            'field_separator'       => "\n",
            'option_separator'      => "\n",
            'all_empty'             => null,
            'hour_empty'            => null,
            'minute_empty'          => null,
            'second_empty'          => null,
            'meridian_empty'        => null,
            'hour_format'           => '%02d',
            'hour_value_format'     => '%02d',
            'minute_format'         => '%02d',
            'minute_value_format'   => '%02d',
            'second_format'         => '%02d',
            'second_value_format'   => '%02d',
        ];
        $p = array_merge($defaults, $params);
        $booleanParams = [
            'display_hours',
            'display_minutes', 
            'display_seconds',
            'display_meridian',
            'use_24_hours'
        ];
        foreach ($booleanParams as $key) {
            if (isset($p[$key])) {
                $p[$key] = filter_var($p[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }
        // Resolve time value
        $hour = $minute = $second = $meridian = null;

        if (is_array($p['time']) && $p['field_array']) {
            // Extract from array using field_array + prefix logic
            $fa = $p['field_array'];
            $pre = $p['prefix'];
            $hour_val   = $p['time'][$fa][$pre . 'Hour']   ?? $p['time'][$pre . 'Hour']   ?? null;
            $minute_val = $p['time'][$fa][$pre . 'Minute'] ?? $p['time'][$pre . 'Minute'] ?? null;
            $second_val = $p['time'][$fa][$pre . 'Second'] ?? $p['time'][$pre . 'Second'] ?? null;
            $merid_val  = $p['time'][$fa][$pre . 'Meridian'] ?? $p['time'][$pre . 'Meridian'] ?? null;

            $hour   = is_numeric($hour_val) ? (int)$hour_val : null;
            $minute = is_numeric($minute_val) ? (int)$minute_val : null;
            $second = is_numeric($second_val) ? (int)$second_val : null;
            $meridian = is_string($merid_val) ? strtoupper(trim($merid_val)) : null;
        } else {
            // Parse scalar time
            if ($p['time'] === null) {
                $timestamp = time();
            } elseif (is_numeric($p['time'])) {
                $timestamp = (int)$p['time'];
            } elseif ($p['time'] instanceof DateTime) {
                $timestamp = $p['time']->getTimestamp();
            } else {
                $timestamp = strtotime($p['time']);
                if ($timestamp === false) {
                    $timestamp = time();
                }
            }

            $hour   = (int)date('H', $timestamp);
            $minute = (int)date('i', $timestamp);
            $second = (int)date('s', $timestamp);
            $meridian = ($hour < 12) ? 'AM' : 'PM';
        }

        // Helper to build select
        $buildSelect = function (
            string $name,
            array $options,
            $selectedValue,
            string $extra,
            ?string $emptyLabel = null,
            ?string $id = null
        ) use ($p): string {
            $attrs = 'name="' . htmlspecialchars($name) . '"';
            if ($id !== null) {
                $attrs .= ' id="' . htmlspecialchars($id) . '"';
            }
            if ($extra) $attrs .= ' ' . $extra;
            if ($p['all_extra']) $attrs .= ' ' . $p['all_extra'];

            $html = "<select $attrs>" . $p['option_separator'];
            if ($emptyLabel !== null) {
                $html .= '<option value="">' . htmlspecialchars($emptyLabel) . '</option>' . $p['option_separator'];
            }
            foreach ($options as $value => $label) {
                $sel = ($value == $selectedValue) ? ' selected="selected"' : '';
                $html .= '<option value="' . htmlspecialchars($value) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>' . $p['option_separator'];
            }
            $html .= "</select>";

            return $html;
        };

        $name_prefix = $p['field_array']
            ? $p['field_array'] . '['
            : $p['prefix'];
        $name_suffix = $p['field_array'] ? ']' : '';

        $fields = [];

        // --- Hours ---
        if ($p['display_hours']) {
            $hourOptions = [];
            $hourRange = $p['use_24_hours'] ? range(0, 23) : range(1, 12);
            foreach ($hourRange as $h) {
                $displayLabel = sprintf($p['hour_format'], $h);
                $actualValue  = sprintf($p['hour_value_format'], $h);
                // In 12-hour mode, value 0 = 12 AM, but we show 12
                $hourOptions[$actualValue] = $displayLabel;
            }

            $selectedHour = $hour;
            if (!$p['use_24_hours'] && $hour !== null) {
                if ($hour == 0 || $hour == 12) {
                    $selectedHour = '12';
                } else {
                    $selectedHour = (string)($hour > 12 ? $hour - 12 : $hour);
                }
            }

            $hourName = $name_prefix . 'Hour' . $name_suffix;
            $hourId   = $p['hour_id'] ?? ($p['all_id'] ? $p['all_id'] . '_Hour' : null);
            $empty    = $p['hour_empty'] ?? $p['all_empty'];

            $fields[] = $buildSelect(
                $hourName,
                $hourOptions,
                $selectedHour,
                $p['hour_extra'],
                $empty,
                $hourId
            );
        }

        // --- Minutes ---
        if ($p['display_minutes']) {
            $minOptions = [];
            for ($m = 0; $m < 60; $m += $p['minute_interval']) {
                $label = sprintf($p['minute_format'], $m);
                $value = sprintf($p['minute_value_format'], $m);
                $minOptions[$value] = $label;
            }

            $minuteName = $name_prefix . 'Minute' . $name_suffix;
            $minuteId   = $p['minute_id'] ?? ($p['all_id'] ? $p['all_id'] . '_Minute' : null);
            $empty      = $p['minute_empty'] ?? $p['all_empty'];

            $fields[] = $buildSelect(
                $minuteName,
                $minOptions,
                $minute !== null ? sprintf($p['minute_value_format'], $minute) : null,
                $p['minute_extra'],
                $empty,
                $minuteId
            );
        }

        // --- Seconds ---
        if ($p['display_seconds']) {
            $secOptions = [];
            for ($s = 0; $s < 60; $s += $p['second_interval']) {
                $label = sprintf($p['second_format'], $s);
                $value = sprintf($p['second_value_format'], $s);
                $secOptions[$value] = $label;
            }

            $secondName = $name_prefix . 'Second' . $name_suffix;
            $secondId   = $p['second_id'] ?? ($p['all_id'] ? $p['all_id'] . '_Second' : null);
            $empty      = $p['second_empty'] ?? $p['all_empty'];

            $fields[] = $buildSelect(
                $secondName,
                $secOptions,
                $second !== null ? sprintf($p['second_value_format'], $second) : null,
                $p['second_extra'],
                $empty,
                $secondId
            );
        }

        // --- Meridian ---
        if ($p['display_meridian'] && !$p['use_24_hours']) {
            $meridOptions = ['AM' => 'AM', 'PM' => 'PM'];
            $meridName = $name_prefix . 'Meridian' . $name_suffix;
            $meridId   = $p['meridian_id'] ?? ($p['all_id'] ? $p['all_id'] . '_Meridian' : null);
            $empty     = $p['meridian_empty'] ?? $p['all_empty'];

            $fields[] = $buildSelect(
                $meridName,
                $meridOptions,
                $meridian,
                $p['meridian_extra'],
                $empty,
                $meridId
            );
        }

        return implode($p['field_separator'], $fields);
    }
}