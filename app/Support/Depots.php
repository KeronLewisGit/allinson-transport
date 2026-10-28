<?php

namespace App\Support;

/**
 * The depots in config('allinson.depots'), and which one prices a collection postcode.
 * Routing is by postcode area (the letters at the start: DL, LS, L…). Anything not listed,
 * or not a UK postcode, goes to the Darlington head office.
 */
final class Depots
{
    public const string HEAD_OFFICE = 'darlington';

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return (array) ga('depots', []);
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, string> key => "Darlington", for selects */
    public static function options(): array
    {
        return array_map(fn (array $d) => (string) $d['name'], self::all());
    }

    public static function name(?string $key): string
    {
        return (string) (self::all()[$key]['name'] ?? ($key ?? '—'));
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    /** The depot whose 'areas' include this postcode's area; the head office otherwise. */
    public static function forPostcode(?string $postcode): string
    {
        $area = postcode_area($postcode);
        if ($area !== null) {
            foreach (self::all() as $key => $depot) {
                if (in_array($area, (array) ($depot['areas'] ?? []), true)) {
                    return $key;
                }
            }
        }

        return self::HEAD_OFFICE;
    }
}
