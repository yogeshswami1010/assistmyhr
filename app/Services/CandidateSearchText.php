<?php

namespace App\Services;

class CandidateSearchText
{
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace(["\u{00A0}", "\u{2010}", "\u{2011}", "\u{2013}", "\u{2014}"], [' ', '-', '-', '-', '-'], $text);
        // Qualification codes: 310 J, 310-J and 310J mean the same thing.
        $text = preg_replace_callback('/\b(\d{2,5})[\s-]+([a-z]{1,3})\b/u', fn ($m) => in_array($m[2], ['yr', 'yrs', 'hr', 'hrs', 'km', 'kg', 'lbs']) ? $m[1].' '.$m[2] : $m[1].$m[2], $text);
        $text = preg_replace('/(?<=[\pL\pN])\.(?=\s|$)/u', '', $text);
        $text = preg_replace('/[^\pL\pN+#.]+/u', ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    public static function terms(array $terms): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($term) => is_string($term) ? self::normalize($term) : '',
            $terms
        ), fn ($term) => $term !== '')));
    }

    public static function codes(string $query): array
    {
        preg_match_all('/\b\d{2,5}[a-z]{1,3}\b/u', self::normalize($query), $matches);
        return array_values(array_unique(array_filter($matches[0], fn ($code) => !preg_match('/(?:yrs?|hrs?|km|kg|lbs)$/', $code))));
    }

    /** Strength: exact token/phrase = 1, joined words = .9, conservative typo = .7. */
    public static function strength(string $text, string $term): float
    {
        return self::normalizedStrength(self::normalize($text), self::normalize($term));
    }

    public static function normalizedStrength(string $text, string $term): float
    {
        if ($text === '' || $term === '') return 0;
        if (str_contains(' '.$text.' ', ' '.$term.' ')) return 1;

        $words = explode(' ', $text);
        $needles = explode(' ', $term);
        $size = count($needles);
        // Compare adjacent words only: don't join unrelated parts of a CV.
        $compact = str_replace(' ', '', $term);
        if (mb_strlen($compact) >= 4 && !preg_match('/\d/u', $compact)) {
            for ($width = max(1, $size - 1); $width <= min($size + 1, 6); $width++) {
                for ($start = 0, $count = count($words); $start <= $count - $width; $start++) {
                    if (implode('', array_slice($words, $start, $width)) === $compact) return .9;
                }
            }
        }
        // Never guess codes, numbers or short abbreviations.
        if (preg_match('/[\d+#.]/u', $term)) return 0;
        for ($start = 0, $count = count($words); $start <= $count - $size; $start++) {
            $matched = true;
            foreach ($needles as $offset => $needle) {
                $word = $words[$start + $offset];
                if ($word === $needle) continue;
                $length = strlen($needle);
                $limit = $length >= 8 ? 2 : 1;
                if ($length < 4 || !preg_match('/^[a-z]+$/', $needle.$word)
                    || abs(strlen($word) - $length) > $limit
                    || self::distance($word, $needle) > $limit) {
                    $matched = false;
                    break;
                }
            }
            if ($matched) return .7;
        }
        return 0;
    }

    private static function distance(string $a, string $b): int
    {
        $distance = levenshtein($a, $b);
        if ($distance === 2 && strlen($a) === strlen($b)) {
            for ($i = 0; $i < strlen($a) - 1; $i++) {
                $swapped = $a;
                $swapped[$i] = $a[$i + 1];
                $swapped[$i + 1] = $a[$i];
                if ($swapped === $b) return 1;
            }
        }
        return $distance;
    }
}
