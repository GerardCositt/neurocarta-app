<?php

namespace App\Services;

use App\Support\ColorMath;
use App\Support\LogoColorSampler;

/**
 * Builds CSS variable palettes (dark/light themes) from a logo or a hex accent color.
 * Color math delegated to ColorMath; image sampling delegated to LogoColorSampler.
 */
class MenuBrandPaletteService
{
    private const SETTING_KEY = 'menu_brand_palette';

    public function __construct(private readonly LogoColorSampler $sampler) {}

    public static function settingKey(): string
    {
        return self::SETTING_KEY;
    }

    /**
     * Re-derives --nav-active-fg and --scroll-top-fg from --gold for contrast legibility.
     *
     * @param array<string, mixed> $palette
     * @return array<string, mixed>
     */
    public function refreshAccentForegrounds(array $palette): array
    {
        $gd = $palette['vars_dark']['--gold']  ?? null;
        $gl = $palette['vars_light']['--gold'] ?? null;

        if (! is_string($gd) || $gd === '' || ! is_string($gl) || $gl === '') {
            return $palette;
        }

        $fgD = ColorMath::foregroundOnAccent($gd, '#0a0e17');
        $fgL = ColorMath::foregroundOnAccent($gl, '#1c1917');

        $palette['vars_dark']['--nav-active-fg']  = $fgD;
        $palette['vars_dark']['--scroll-top-fg']  = $fgD;
        $palette['vars_light']['--nav-active-fg'] = $fgL;
        $palette['vars_light']['--scroll-top-fg'] = $fgL;

        return $palette;
    }

    /**
     * Extracts the brand palette from a storage/public raster image.
     * Returns null when the image cannot be loaded or yields no usable accent.
     */
    public function extractFromStoragePublicPath(string $relativePath): ?array
    {
        $rgb = $this->sampler->dominantAccentRgb($relativePath);
        if ($rgb === null) {
            return null;
        }

        [$r, $g, $b] = $rgb;
        [$hHue, $hSat, $hLight] = ColorMath::rgbToHsl($r, $g, $b);
        [$hHue, $hSat]          = $this->normalizeAccentForTheme($hHue, $hSat, $hLight, $r, $g, $b);

        return [
            'accent_hex' => ColorMath::rgbToHex($r, $g, $b),
            'vars_dark'  => $this->buildDarkVars($hHue, $hSat, $hLight),
            'vars_light' => $this->buildLightVars($hHue, $hSat, $hLight),
        ];
    }

    /**
     * Visually distinct color swatches from the logo for the color picker in the admin panel.
     *
     * @return string[]
     */
    public function extractDistinctSwatchesFromStoragePublicPath(string $relativePath, int $maxSwatches = 16): array
    {
        return $this->sampler->distinctSwatches($relativePath, $maxSwatches);
    }

    /**
     * Builds the full palette from a user-supplied hex accent color.
     */
    public function paletteFromAccentHex(string $hex): ?array
    {
        $hex = strtolower(ltrim(trim($hex), '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return null;
        }

        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));

        [$hHue, $hSat, $hLight] = ColorMath::rgbToHsl($r, $g, $b);
        [$hHue, $hSat]          = $this->normalizeAccentForTheme($hHue, $hSat, $hLight, $r, $g, $b);

        $palette = [
            'accent_hex' => ColorMath::rgbToHex($r, $g, $b),
            'vars_dark'  => $this->buildDarkVars($hHue, $hSat, $hLight),
            'vars_light' => $this->buildLightVars($hHue, $hSat, $hLight),
        ];

        return $this->refreshAccentForegrounds($palette);
    }

    // ── Palette construction ──────────────────────────────────────────────────

    private function buildDarkVars(float $hue, float $sat, float $accentLight): array
    {
        $neutral   = $sat < 0.02;
        $bias      = $neutral ? 0.0 : 0.08;
        $biasSurf  = $neutral ? 0.0 : 0.1;

        $gold        = ColorMath::hslToHex($hue, min(0.88, $sat), 0.54);
        $goldLight   = ColorMath::hslToHex($hue, $sat * 0.92, 0.66);
        $goldDim     = ColorMath::hslToHex($hue, $neutral ? 0.0 : min(0.78, $sat + 0.06), 0.36);
        $bg          = ColorMath::hslToHex($hue, min(0.22, $sat * 0.35 + $bias), 0.055);
        $surface     = ColorMath::hslToHex($hue, min(0.26, $sat * 0.4  + $biasSurf), 0.09);
        $surfaceEl   = ColorMath::hslToHex($hue, min(0.3,  $sat * 0.45 + $biasSurf), 0.125);
        $text        = ColorMath::hslToHex($hue, $neutral ? 0.0 : 0.04, 0.93);
        $textMuted   = ColorMath::hslToHex($hue, $neutral ? 0.0 : 0.05, 0.62);
        $red         = $neutral
            ? '#dc2626'
            : ColorMath::hslToHex(fmod($hue + 118, 360), 0.72, 0.52);
        $navActiveFg = ColorMath::foregroundOnAccent($gold, '#0a0e17');
        $bgRgb       = ColorMath::hexToRgb($bg);

        return array_merge($this->brandInteractionVars($gold), [
            '--bg'             => $bg,
            '--surface'        => $surface,
            '--surface-el'     => $surfaceEl,
            '--gold'           => $gold,
            '--gold-light'     => $goldLight,
            '--gold-dim'       => $goldDim,
            '--text'           => $text,
            '--text-muted'     => $textMuted,
            '--red'            => $red,
            '--nav-bg'         => sprintf('rgba(%d,%d,%d,0.94)', $bgRgb[0], $bgRgb[1], $bgRgb[2]),
            '--nav-border'     => ColorMath::hexToRgba($gold, 0.16),
            '--hero-grad-from' => $surface,
            '--nav-active-fg'  => $navActiveFg,
            '--modal-scrim'    => sprintf('rgba(%d,%d,%d,0.48)', $bgRgb[0], $bgRgb[1], $bgRgb[2]),
            '--prod-border'    => ColorMath::hexToRgba($text, 0.09),
            '--divider'        => ColorMath::hexToRgba($text, 0.1),
            '--chip-bg'        => ColorMath::hexToRgba($text, 0.06),
            '--modal-shadow'   => '0 20px 50px rgba(0,0,0,0.48)',
            '--scroll-top-fg'  => $navActiveFg,
        ]);
    }

    private function buildLightVars(float $hue, float $sat, float $accentLight): array
    {
        $neutral         = $sat < 0.02;
        $darkNeutralBrand = $neutral && $accentLight < 0.38;

        if ($darkNeutralBrand) {
            $strength  = max(0.0, min(1.0, (0.38 - $accentLight) / 0.38));
            $bg        = ColorMath::hslToHex(0, 0, 0.945 - 0.095 * $strength);
            $surfaceEl = ColorMath::hslToHex(0, 0, 0.895 - 0.075 * $strength);
            $surface   = ColorMath::hslToHex(0, 0, 0.982 - 0.065 * $strength);
            $gold        = '#141414';
            $goldLight   = '#2a2a2a';
            $goldDim     = '#0a0a0a';
            $text        = '#0c0c0c';
            $textMuted   = ColorMath::hslToHex(0, 0, 0.43);
            $red         = '#b91c1c';
            $navActiveFg = ColorMath::foregroundOnAccent($gold, '#1c1917');
            $bgRgb       = ColorMath::hexToRgb($bg);
            $textRgb     = ColorMath::hexToRgb($text);

            return array_merge($this->brandInteractionVars($gold), [
                '--bg'             => $bg,
                '--surface'        => $surface,
                '--surface-el'     => $surfaceEl,
                '--gold'           => $gold,
                '--gold-light'     => $goldLight,
                '--gold-dim'       => $goldDim,
                '--text'           => $text,
                '--text-muted'     => $textMuted,
                '--red'            => $red,
                '--nav-bg'         => sprintf('rgba(%d,%d,%d,0.94)', $bgRgb[0], $bgRgb[1], $bgRgb[2]),
                '--nav-border'     => ColorMath::hexToRgba($gold, 0.2),
                '--hero-grad-from' => $surfaceEl,
                '--nav-active-fg'  => $navActiveFg,
                '--modal-scrim'    => sprintf('rgba(%d,%d,%d,0.4)', $textRgb[0], $textRgb[1], $textRgb[2]),
                '--prod-border'    => ColorMath::hexToRgba($text, 0.13),
                '--divider'        => ColorMath::hexToRgba($text, 0.11),
                '--chip-bg'        => ColorMath::hexToRgba($text, 0.06),
                '--modal-shadow'   => '0 20px 50px rgba(0,0,0,0.14)',
                '--scroll-top-fg'  => $navActiveFg,
            ]);
        }

        $gold        = ColorMath::hslToHex($hue, min(0.82, $sat * 0.95), 0.34);
        $goldLight   = ColorMath::hslToHex($hue, $sat * 0.85, 0.42);
        $goldDim     = ColorMath::hslToHex($hue, $neutral ? 0.0 : min(0.75, $sat + 0.05), 0.28);
        $bg          = ColorMath::hslToHex($hue, min(0.08, $sat * 0.2), 0.97);
        $surface     = '#ffffff';
        $surfaceEl   = ColorMath::hslToHex($hue, min(0.12, $sat * 0.25), 0.94);
        $text        = ColorMath::hslToHex($hue, $neutral ? 0.0 : min(0.12, $sat * 0.2), 0.12);
        $textMuted   = ColorMath::hslToHex($hue, $neutral ? 0.0 : 0.06, 0.42);
        $red         = $neutral
            ? '#b91c1c'
            : ColorMath::hslToHex(fmod($hue + 115, 360), 0.7, 0.38);
        $navActiveFg = ColorMath::foregroundOnAccent($gold, '#1c1917');
        $bgRgb       = ColorMath::hexToRgb($bg);
        $textRgb     = ColorMath::hexToRgb($text);

        return array_merge($this->brandInteractionVars($gold), [
            '--bg'             => $bg,
            '--surface'        => $surface,
            '--surface-el'     => $surfaceEl,
            '--gold'           => $gold,
            '--gold-light'     => $goldLight,
            '--gold-dim'       => $goldDim,
            '--text'           => $text,
            '--text-muted'     => $textMuted,
            '--red'            => $red,
            '--nav-bg'         => sprintf('rgba(%d,%d,%d,0.94)', $bgRgb[0], $bgRgb[1], $bgRgb[2]),
            '--nav-border'     => ColorMath::hexToRgba($gold, 0.22),
            '--hero-grad-from' => $surfaceEl,
            '--nav-active-fg'  => $navActiveFg,
            '--modal-scrim'    => sprintf('rgba(%d,%d,%d,0.4)', $textRgb[0], $textRgb[1], $textRgb[2]),
            '--prod-border'    => ColorMath::hexToRgba($text, 0.11),
            '--divider'        => ColorMath::hexToRgba($text, 0.12),
            '--chip-bg'        => ColorMath::hexToRgba($text, 0.05),
            '--modal-shadow'   => '0 20px 50px rgba(0,0,0,0.14)',
            '--scroll-top-fg'  => $navActiveFg,
        ]);
    }

    /** Hover/glow CSS vars tied to the accent color. */
    private function brandInteractionVars(string $goldHex): array
    {
        return [
            '--hero-glow'         => ColorMath::hexToRgba($goldHex, 0.14),
            '--gold-border-soft'  => ColorMath::hexToRgba($goldHex, 0.35),
            '--gold-border-hover' => ColorMath::hexToRgba($goldHex, 0.52),
            '--gold-focus-ring'   => ColorMath::hexToRgba($goldHex, 0.24),
        ];
    }

    /**
     * Neutrals (gray/black/white logos) must not get false saturation boosts.
     *
     * @return array{0:float,1:float}
     */
    private function normalizeAccentForTheme(float $hue, float $sat, float $light, int $r, int $g, int $b): array
    {
        $delta     = max($r, $g, $b) - min($r, $g, $b);
        $isNeutral = $sat < 0.07 || $delta <= 14;

        if ($isNeutral) {
            return [$hue, 0.0];
        }

        return [$hue, max(0.28, min(0.92, $sat * 1.08))];
    }
}
