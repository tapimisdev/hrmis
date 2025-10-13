<?php

use Carbon\Carbon;

if (!function_exists('ordinal')) {
    function ordinal($num) {
        $num = (int)$num;
        $suffix = 'th';

        if (($num % 100) >= 11 && ($num % 100) <= 13) {
            $suffix = 'th';
        } else {
            switch ($num % 10) {
                case 1:
                    $suffix = 'st';
                    break;
                case 2:
                    $suffix = 'nd';
                    break;
                case 3:
                    $suffix = 'rd';
                    break;
            }
        }

        return $num . $suffix;
    }
}

if (!function_exists('formatDateRanges')) {
    /**
     * Format an array or comma-separated string of dates into a compact string.
     *
     * Rules:
     * - If dates in same month/year and sequential: Oct 1-3 2025
     * - If dates in same month/year but not sequential: Oct 1, 14, 15 2025
     * - If multiple months/years: Oct 1 2025, Nov 14 2025, Dec 15 2025
     *
     * @param array|string $dates
     * @return string
     */
    function formatDateRanges($dates): string
    {
        if (is_string($dates)) {
            $dates = explode(',', $dates);
        }

        if (empty($dates) || !is_array($dates)) {
            return '';
        }

        // Parse and sort dates
        $carbonDates = array_map(fn($d) => Carbon::parse(trim($d)), $dates);
        usort($carbonDates, fn($a, $b) => $a->timestamp <=> $b->timestamp);

        // Group by year-month (e.g. '2025-10')
        $groups = [];
        foreach ($carbonDates as $date) {
            $key = $date->format('Y-m');
            $groups[$key][] = $date;
        }

        // Format each group
        $resultParts = [];

        $formatGroup = function($datesGroup) {
            $days = array_map(fn($d) => (int)$d->format('d'), $datesGroup);
            sort($days);

            // Check if days are sequential
            $sequential = true;
            for ($i = 1; $i < count($days); $i++) {
                if ($days[$i] !== $days[$i - 1] + 1) {
                    $sequential = false;
                    break;
                }
            }

            $monthYear = $datesGroup[0]->format('M Y');

            if (count($days) === 1) {
                return $datesGroup[0]->format('M d, Y');
            }

            if ($sequential) {
                return $datesGroup[0]->format('M ') . $days[0] . '-' . end($days) . ' ' . $datesGroup[0]->format('Y');
            }

            $daysList = implode(', ', $days);
            return $datesGroup[0]->format('M ') . $daysList . ' ' . $datesGroup[0]->format('Y');
        };

        foreach ($groups as $groupDates) {
            $resultParts[] = $formatGroup($groupDates);
        }

        return implode(', ', $resultParts);
    }
}