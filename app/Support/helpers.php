<?php

if (! function_exists('ga')) {
    /** A fact from config/allinson.php: ga('phone'), ga('depots'). */
    function ga(string $key, mixed $default = null): mixed
    {
        return config('allinson.'.$key, $default);
    }
}

if (! function_exists('uk_digits')) {
    /** A UK number written any common way (01325 461241, +44 1325 461241, 0044…) as national digits "01325461241", or null. */
    function uk_digits(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if (str_starts_with($digits, '0044')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '44') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 2);
        }

        return preg_match('/^0\d{9,10}$/', $digits) ? $digits : null;
    }
}

if (! function_exists('tel_href')) {
    /** tel:+441325461241 */
    function tel_href(?string $number): string
    {
        $digits = uk_digits($number);

        return $digits ? 'tel:+44'.substr($digits, 1) : 'tel:'.preg_replace('/[^\d+]/', '', (string) $number);
    }
}

if (! function_exists('format_phone')) {
    /** UK style: 01325 461241, 07700 900123, 020 7946 0018. Anything else is returned unchanged. */
    function format_phone(?string $number): string
    {
        $d = uk_digits($number);
        if (! $d) {
            return (string) $number;
        }

        return match (true) {
            str_starts_with($d, '02') && strlen($d) === 11 => substr($d, 0, 3).' '.substr($d, 3, 4).' '.substr($d, 7),
            str_starts_with($d, '011') || preg_match('/^01\d1/', $d) === 1 => substr($d, 0, 4).' '.substr($d, 4, 3).' '.substr($d, 7),
            default => substr($d, 0, 5).' '.substr($d, 5),
        };
    }
}

if (! function_exists('input_string')) {
    /** A request value as a string. Arrays (?type[]=x) and other junk become ''. */
    function input_string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * Textarea with one item per line, bullets optional, to a clean list.
     *
     * @return list<string>
     */
    function lines_to_array(?string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
        $clean = array_map(fn (string $l) => trim((string) preg_replace('/^(?:[-*]|•)\s*/u', '', $l)), $lines);

        return array_values(array_filter($clean, fn (string $l) => $l !== ''));
    }
}

if (! function_exists('compact_number')) {
    /** 1,284 → 1,284; 12,800 → 13K, used on stat tiles. */
    function compact_number(int|float $n): string
    {
        return match (true) {
            abs($n) >= 1_000_000 => round($n / 1_000_000, 1).'M',
            abs($n) >= 10_000 => round($n / 1_000).'K',
            default => number_format($n),
        };
    }
}

if (! function_exists('gbp')) {
    /** £1,250.00, or £1,250 when whole. Null stays null. */
    function gbp(int|float|string|null $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }
        $n = (float) $amount;

        return '£'.number_format($n, fmod($n, 1.0) === 0.0 ? 0 : 2);
    }
}

if (! function_exists('photo')) {
    /**
     * Responsive <img> attributes for a photo in public/img ({key}-{width}.webp). The widths come
     * from config('allinson.photos'), so the firm's small originals are never upscaled.
     *
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    function photo(string $key): array
    {
        $meta = config('allinson.photos.'.$key) ?? ['w' => 1920, 'h' => 1280, 'widths' => [480, 960, 1920]];
        $widths = array_map('intval', (array) ($meta['widths'] ?? [480, 960, 1920]));
        $url = fn (int $w) => asset("img/{$key}-{$w}.webp");
        $mid = collect($widths)->first(fn (int $w) => $w >= 900) ?? end($widths);

        return [
            'src' => $url((int) $mid),
            'srcset' => implode(', ', array_map(fn (int $w) => $url($w).' '.$w.'w', $widths)),
            'width' => (int) $meta['w'],
            'height' => (int) $meta['h'],
        ];
    }
}

if (! function_exists('is_own_photo')) {
    /** Whether a photo shows the firm itself (no "Illustrative photo" note). */
    function is_own_photo(?string $key): bool
    {
        return $key !== null && in_array($key, (array) ga('own_photos'), true);
    }
}

if (! function_exists('file_size')) {
    /** 2.4 MB, 380 KB */
    function file_size(?int $bytes): string
    {
        $bytes = (int) $bytes;

        return $bytes >= 1_048_576 ? round($bytes / 1_048_576, 1).' MB' : max(1, (int) round($bytes / 1024)).' KB';
    }
}

if (! function_exists('uk_postcode')) {
    /** "dl30ph" → "DL3 0PH"; null when it is not a full UK postcode. */
    function uk_postcode(?string $value): ?string
    {
        $v = strtoupper((string) preg_replace('/\s+/', '', (string) $value));
        if (! preg_match('/^([A-Z]{1,2}\d[A-Z\d]?)(\d[A-Z]{2})$/', $v, $m)) {
            return null;
        }

        return $m[1].' '.$m[2];
    }
}

if (! function_exists('postcode_area')) {
    /** The letters a postcode starts with: "DL3 0PH" → "DL", "L1 8JQ" → "L". */
    function postcode_area(?string $postcode): ?string
    {
        return preg_match('/^([A-Z]{1,2})\d/', strtoupper(trim((string) $postcode)), $m) ? $m[1] : null;
    }
}

if (! function_exists('plain_number')) {
    /** 9.50 → "9.5", 9.00 → "9": a figure without trailing zeros. */
    function plain_number(float $n, int $decimals = 2): string
    {
        return rtrim(rtrim(number_format($n, $decimals, '.', ''), '0'), '.');
    }
}
