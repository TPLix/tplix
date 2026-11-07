<?php

use Tplix\Tplix;
use Tplix\Plugins;

class TplixCompileHtml_select_date
{
    public function call(array $params = []): string
    {
        $defaults = [
            'prefix'                => 'Date_',
            'time'                  => null,
            'start_year'            => 'today',
            'end_year'              => 'today',
            'display_days'          => true,
            'display_months'        => true,
            'display_years'         => true,
            'month_names'           => null, // if null, use strftime-style formatting
            'month_format'          => '%B',
            'day_format'            => '%02d',
            'day_value_format'      => '%d',
            'year_as_text'          => false,
            'reverse_years'         => false,
            'field_array'           => null,
            'day_size'              => null,
            'month_size'            => null,
            'year_size'             => null,
            'all_extra'             => '',
            'day_extra'             => '',
            'month_extra'           => '',
            'year_extra'            => '',
            'all_id'                => null,
            'day_id'                => null,
            'month_id'              => null,
            'year_id'               => null,
            'field_order'           => 'MDY',
            'field_separator'       => "\n",
            'month_value_format'    => '%m',
            'all_empty'             => null,
            'year_empty'            => null,
            'month_empty'           => null,
            'day_empty'             => null,
            'option_separator'      => "\n",
        ];
        $p = array_merge($defaults, $params);
        $booleanParams = [
            'display_days',
            'display_months', 
            'display_years',
            'year_as_text',
            'reverse_years'
        ];
        foreach ($booleanParams as $key) {
            if (isset($p[$key])) {
                $p[$key] = filter_var($p[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Resolve base timestamp if scalar
        $useArrayInput = is_array($p['time']) && $p['field_array'];
        $selected_year = $selected_month = $selected_day = null;

        if ($useArrayInput) {
            $fa = $p['field_array'];
            $pre = $p['prefix'];
            $getVal = function ($key) use ($p, $fa, $pre) {
                return $p['time'][$fa][$pre . $key] ?? $p['time'][$pre . $key] ?? null;
            };
            $selected_year  = $getVal('Year');
            $selected_month = $getVal('Month');
            $selected_day   = $getVal('Day');
        } else {
            if ($p['time'] === null || $p['time'] === '') {
                $timestamp = time();
            } elseif (is_numeric($p['time'])) {
                $timestamp = (int)$p['time'];
            } elseif ($p['time'] instanceof DateTime) {
                $timestamp = $p['time']->getTimestamp();
            } else {
                // Try parsing; if fails, use current time
                $timestamp = strtotime($p['time']);
                if ($timestamp === false) {
                    $timestamp = time();
                }
            }

            // Handle partial dates like "2025--15" or "2025-04-"
            if (is_string($p['time']) && preg_match('/^(\d{4})-(\d{1,2}|)-(?:\d{1,2}|)$/', $p['time'], $parts)) {
                $selected_year = (int)$parts[1];
                $selected_month = ($parts[2] === '' || $parts[2] === '-') ? null : (int)$parts[2];
                $selected_day = null;
                if (preg_match('/-(\d{1,2})$/', $p['time'], $dayPart)) {
                    $selected_day = (int)$dayPart[1];
                }
            } else {
                $selected_year  = (int)date('Y', $timestamp);
                $selected_month = (int)date('m', $timestamp);
                $selected_day   = (int)date('d', $timestamp);
            }
        }

        // Helper: build <select>
        $buildSelect = function (
            string $name,
            array $options,
            $selectedValue,
            string $extra,
            ?string $emptyLabel = null,
            ?string $id = null,
            ?string $size = null
        ) use ($p): string {
            $attrs = 'name="' . htmlspecialchars($name) . '"';
            if ($id !== null) {
                $attrs .= ' id="' . htmlspecialchars($id) . '"';
            }
            if ($size) {
                $attrs .= ' size="' . (int)$size . '"';
            }
            if ($extra) $attrs .= ' ' . $extra;
            if ($p['all_extra']) $attrs .= ' ' . $p['all_extra'];

            $html = "<select $attrs>" . $p['option_separator'];
            if ($emptyLabel !== null) {
                $html .= '<option value="">' . htmlspecialchars($emptyLabel) . '</option>' . $p['option_separator'];
            }
            foreach ($options as $value => $label) {
                $sel = ((string)$value === (string)$selectedValue) ? ' selected="selected"' : '';
                $html .= '<option value="' . htmlspecialchars($value) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>' . $p['option_separator'];
            }
            $html .= '</select>';
            return $html;
        };

        $name_prefix = $p['field_array'] ? $p['field_array'] . '[' : $p['prefix'];
        $name_suffix = $p['field_array'] ? ']' : '';

        $fields = [];

        // --- Years ---
        if ($p['display_years']) {
            $current_year = (int)date('Y');
            $resolveYear = function ($spec) use ($current_year) {
                if (is_numeric($spec)) return (int)$spec;
                if ($spec === 'today') return $current_year;
                if (preg_match('/^([+-]\d+)$/', trim($spec), $m)) {
                    return $current_year + (int)$m[1];
                }
                return $current_year;
            };

            $start = $resolveYear($p['start_year']);
            $end   = $resolveYear($p['end_year']);
            if ($start > $end) [$start, $end] = [$end, $start];

            $years = $p['reverse_years'] ? range($end, $start) : range($start, $end);

            $yearOptions = [];
            foreach ($years as $y) {
                $label = $p['year_as_text'] ? (string)$y : $y;
                $yearOptions[$y] = $label;
            }

            $yearName = $name_prefix . 'Year' . $name_suffix;
            $yearId   = $p['year_id'] ?? ($p['all_id'] ? $p['all_id'] . '_Year' : null);
            $empty    = $p['year_empty'] ?? $p['all_empty'];

            $fields['Y'] = $buildSelect(
                $yearName,
                $yearOptions,
                $selected_year,
                $p['year_extra'],
                $empty,
                $yearId,
                $p['year_size']
            );
        }

        // --- Months ---
        if ($p['display_months']) {
            $monthOptions = [];
            for ($m = 1; $m <= 12; $m++) {
                if ($p['month_names'] && isset($p['month_names'][$m])) {
                    $label = $p['month_names'][$m];
                } else {
                    // Use strftime-style formatting via helper
                    $label = $this->strftimeToDateTimeFormat($p['month_format'], mktime(0, 0, 0, $m, 1, 2000));
                }
                // Format month value using strftime-style codes
                $value = $this->strftimeToDateTimeFormat($p['month_value_format'], mktime(0, 0, 0, $m, 1, 2000));
                $monthOptions[$value] = $label;
            }

            $monthName = $name_prefix . 'Month' . $name_suffix;
            $monthId   = $p['month_id'] ?? ($p['all_id'] ? $p['all_id'] . '_Month' : null);
            $empty     = $p['month_empty'] ?? $p['all_empty'];

            $fields['M'] = $buildSelect(
                $monthName,
                $monthOptions,
                $selected_month,
                $p['month_extra'],
                $empty,
                $monthId,
                $p['month_size']
            );
        }

        // --- Days ---
        if ($p['display_days']) {
            $dayOptions = [];
            for ($d = 1; $d <= 31; $d++) {
                $label = sprintf($p['day_format'], $d);
                $value = sprintf($p['day_value_format'], $d);
                $dayOptions[$value] = $label;
            }

            $dayName = $name_prefix . 'Day' . $name_suffix;
            $dayId   = $p['day_id'] ?? ($p['all_id'] ? $p['all_id'] . '_Day' : null);
            $empty   = $p['day_empty'] ?? $p['all_empty'];

            $fields['D'] = $buildSelect(
                $dayName,
                $dayOptions,
                $selected_day,
                $p['day_extra'],
                $empty,
                $dayId,
                $p['day_size']
            );
        }

        // Reorder fields based on field_order
        $orderMap = [
            'M' => 'M', 'D' => 'D', 'Y' => 'Y',
            'm' => 'M', 'd' => 'D', 'y' => 'Y'
        ];
        $order = [];
        foreach (str_split(strtoupper($p['field_order'])) as $char) {
            if (isset($orderMap[$char]) && isset($fields[$orderMap[$char]])) {
                $order[] = $fields[$orderMap[$char]];
            }
        }

        return implode($p['field_separator'], $order);
    }

    /**
     * Converts strftime format codes to DateTime::format codes and evaluates them.
     * Falls back to IntlDateFormatter for unsupported codes.
     * @param string $format The strftime format string (e.g., '%B', '%m')
     * @param int $timestamp A timestamp for the date to format
     * @return string Formatted string
     */
    private function strftimeToDateTimeFormat(string $format, int $timestamp): string
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);

        // Map common strftime codes to DateTime codes
        $map = [
            '%a' => 'D', // Abbreviated weekday name
            '%A' => 'l', // Full weekday name
            '%b' => 'M', // Abbreviated month name
            '%B' => 'F', // Full month name
            '%d' => 'd', // Day of the month, 2 digits
            '%e' => 'j', // Day of the month, space-padded
            '%m' => 'm', // Month, 2 digits
            '%n' => "\n", // Newline
            '%t' => "\t", // Tab
            '%y' => 'y', // 2-digit year
            '%Y' => 'Y', // 4-digit year
            '%H' => 'H', // 24-hour format
            '%I' => 'h', // 12-hour format
            '%M' => 'i', // Minutes
            '%S' => 's', // Seconds
            '%p' => 'A', // AM/PM
        ];

        $result = $format;
        foreach ($map as $strftime => $php) {
            $result = str_replace($strftime, $php, $result);
        }

        // If result still contains unmapped strftime codes (e.g. %C, %G), use IntlDateFormatter
        if (preg_match('/%[a-zA-Z]/', $result)) {
            if (class_exists('IntlDateFormatter')) {
                $formatter = new IntlDateFormatter(
                    null, // locale (null = default)
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::FULL,
                    date_default_timezone_get(),
                    null,
                    $format
                );
                return $formatter->format($timestamp);
            } else {
                // Fallback: return timestamp or empty if Intl is not available
                // Or return a static English name if you want locale independence
                $englishMonths = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                ];
                if ($format === '%B') return $englishMonths[date('n', $timestamp)];
                if ($format === '%b') return substr($englishMonths[date('n', $timestamp)], 0, 3);
                // For other unmapped codes, return the timestamp or a default
                return date('F', $timestamp); // Default to full month name using date()
            }
        }

        return $date->format($result);
    }
}