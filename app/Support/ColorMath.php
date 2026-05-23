<?php

namespace App\Support;

/**
 * Pure color math helpers (HSL ↔ RGB, hex conversions, WCAG luminance/contrast).
 * All methods are static — no state, no side effects.
 */
final class ColorMath
{
    /** @return array{0:float,1:float,2:float}  [hue°, saturation 0-1, lightness 0-1] */
    public static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l   = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match (true) {
            $max === $r => (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6,
            $max === $g => (($b - $r) / $d + 2) / 6,
            default     => (($r - $g) / $d + 4) / 6,
        };

        return [$h * 360, $s, $l];
    }

    /** @return array{0:int,1:int,2:int}  [r, g, b] each 0-255 */
    public static function hslToRgb(float $h, float $s, float $l): array
    {
        $h = fmod($h, 360) / 360;
        $s = max(0, min(1, $s));
        $l = max(0, min(1, $l));

        if ($s == 0.0) {
            $v = (int) round($l * 255);

            return [$v, $v, $v];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        return [
            (int) round(255 * self::hue2rgb($p, $q, $h + 1 / 3)),
            (int) round(255 * self::hue2rgb($p, $q, $h)),
            (int) round(255 * self::hue2rgb($p, $q, $h - 1 / 3)),
        ];
    }

    public static function hslToHex(float $h, float $s, float $l): string
    {
        [$r, $g, $b] = self::hslToRgb($h, $s, $l);

        return self::rgbToHex($r, $g, $b);
    }

    public static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }

    /** @return array{0:int,1:int,2:int} */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return [10, 14, 23];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function hexToRgba(string $hex, float $alpha): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $a = max(0, min(1, $alpha));

        return sprintf('rgba(%d,%d,%d,%.2f)', $r, $g, $b, $a);
    }

    // ── WCAG contrast helpers ─────────────────────────────────────────────────

    /**
     * Returns '#ffffff' or $darkTextHex depending on which has better contrast
     * against $accentHex (WCAG relative luminance).
     */
    public static function foregroundOnAccent(string $accentHex, string $darkTextHex): string
    {
        $bgL      = self::relativeLuminanceFromHex($accentHex);
        $whiteL   = self::relativeLuminanceFromHex('#ffffff');
        $darkL    = self::relativeLuminanceFromHex($darkTextHex);

        $cWhite = self::luminanceContrast($bgL, $whiteL);
        $cDark  = self::luminanceContrast($bgL, $darkL);

        return $cWhite >= $cDark ? '#ffffff' : $darkTextHex;
    }

    public static function luminanceContrast(float $lum1, float $lum2): float
    {
        $hi = max($lum1, $lum2);
        $lo = min($lum1, $lum2);

        return ($hi + 0.05) / ($lo + 0.05);
    }

    public static function relativeLuminanceFromHex(string $hex): float
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        return self::relativeLuminanceSrgb($r / 255, $g / 255, $b / 255);
    }

    public static function relativeLuminanceSrgb(float $r, float $g, float $b): float
    {
        return 0.2126 * self::linearizeSrgbChannel($r)
             + 0.7152 * self::linearizeSrgbChannel($g)
             + 0.0722 * self::linearizeSrgbChannel($b);
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private static function hue2rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;

        return $p;
    }

    private static function linearizeSrgbChannel(float $c): float
    {
        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }
}
