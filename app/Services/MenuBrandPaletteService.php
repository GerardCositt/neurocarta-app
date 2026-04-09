<?php

namespace App\Services;

/**
 * Extrae un color de acento del logo y genera variables CSS para la carta pública
 * (tema claro / oscuro) alineadas con la marca.
 */
class MenuBrandPaletteService
{
    private const SETTING_KEY = 'menu_brand_palette';

    public static function settingKey(): string
    {
        return self::SETTING_KEY;
    }

    /**
     * Recalcula --nav-active-fg y --scroll-top-fg a partir de --gold (contraste legible).
     *
     * @param array<string, mixed> $palette
     * @return array<string, mixed>
     */
    public function refreshAccentForegrounds(array $palette): array
    {
        $gd = $palette['vars_dark']['--gold'] ?? null;
        $gl = $palette['vars_light']['--gold'] ?? null;
        if (! is_string($gd) || $gd === '' || ! is_string($gl) || $gl === '') {
            return $palette;
        }
        $fgD = $this->foregroundOnAccent($gd, '#0a0e17');
        $fgL = $this->foregroundOnAccent($gl, '#1c1917');
        $palette['vars_dark']['--nav-active-fg'] = $fgD;
        $palette['vars_dark']['--scroll-top-fg'] = $fgD;
        $palette['vars_light']['--nav-active-fg'] = $fgL;
        $palette['vars_light']['--scroll-top-fg'] = $fgL;

        return $palette;
    }

    /**
     * Lee la ruta relativa en storage/public (p. ej. branding/xxx.webp) y devuelve
     * arrays vars_dark / vars_light listos para volcar a :root y html[data-theme="light"].
     */
    public function extractFromStoragePublicPath(string $relativePath): ?array
    {
        $buf = $this->loadAndResizeRasterForSampling($relativePath, 56);
        if ($buf === null) {
            return null;
        }

        [$im, $w, $h] = $buf;

        try {
            $accentRgb = $this->dominantSaturatedRgb($im, $w, $h);
            if ($accentRgb === null) {
                return null;
            }

            [$hr, $hg, $hb] = $accentRgb;
            [$hHue, $hSat, $hLight] = $this->rgbToHsl($hr, $hg, $hb);
            [$hHue, $hSat] = $this->normalizeAccentForTheme($hHue, $hSat, $hLight, $hr, $hg, $hb);

            return [
                'accent_hex' => $this->rgbToHex($hr, $hg, $hb),
                'vars_dark'  => $this->buildDarkVars($hHue, $hSat, $hLight),
                'vars_light' => $this->buildLightVars($hHue, $hSat, $hLight),
            ];
        } finally {
            if (isset($im) && (is_object($im) || is_resource($im))) {
                imagedestroy($im);
            }
        }
    }

    /**
     * Colores representativos del logo para chips en el panel (frecuencia + separación mínima en RGB).
     *
     * @return string[]
     */
    public function extractDistinctSwatchesFromStoragePublicPath(string $relativePath, int $maxSwatches = 16): array
    {
        $buf = $this->loadAndResizeRasterForSampling($relativePath, 72);
        if ($buf === null) {
            return [];
        }

        [$im, $w, $h] = $buf;

        try {
            $counts = [];
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $c = imagecolorat($im, $x, $y);
                    $r = ($c >> 16) & 255;
                    $g = ($c >> 8) & 255;
                    $b = $c & 255;
                    // Cuantizar para agrupar tonos similares del mismo logo
                    $rq = ($r >> 3) << 3;
                    $gq = ($g >> 3) << 3;
                    $bq = ($b >> 3) << 3;
                    $key = ($rq << 16) | ($gq << 8) | $bq;
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            }

            if ($counts === []) {
                return [];
            }

            arsort($counts);
            $pickedHex = [];
            $pickedRgb = [];

            foreach ($counts as $key => $_n) {
                $rq = ($key >> 16) & 255;
                $gq = ($key >> 8) & 255;
                $bq = $key & 255;
                $tooClose = false;
                foreach ($pickedRgb as $prgb) {
                    $pr = $prgb[0];
                    $pg = $prgb[1];
                    $pb = $prgb[2];
                    $dist = ($rq - $pr) * ($rq - $pr) + ($gq - $pg) * ($gq - $pg) + ($bq - $pb) * ($bq - $pb);
                    if ($dist < 42 * 42) {
                        $tooClose = true;
                        break;
                    }
                }
                if ($tooClose) {
                    continue;
                }

                $pickedHex[] = $this->rgbToHex($rq, $gq, $bq);
                $pickedRgb[] = [$rq, $gq, $bq];
                if (count($pickedHex) >= $maxSwatches) {
                    break;
                }
            }

            return $pickedHex;
        } finally {
            if (isset($im) && (is_object($im) || is_resource($im))) {
                imagedestroy($im);
            }
        }
    }

    /**
     * @param int $maxDim lado máximo tras reescalar (rápido para muestreo)
     *
     * @return array|null [image resource, width, height]
     */
    private function loadAndResizeRasterForSampling(string $relativePath, int $maxDim): ?array
    {
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '' || ! is_file(storage_path('app/public/' . $relativePath))) {
            return null;
        }

        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            return null;
        }

        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $binary = @file_get_contents(storage_path('app/public/' . $relativePath));
        if ($binary === false || $binary === '') {
            return null;
        }

        $im = @imagecreatefromstring($binary);
        if ($im === false) {
            return null;
        }

        try {
            if (! imageistruecolor($im)) {
                $tc = imagecreatetruecolor(imagesx($im), imagesy($im));
                if ($tc) {
                    imagecopy($tc, $im, 0, 0, 0, 0, imagesx($im), imagesy($im));
                    imagedestroy($im);
                    $im = $tc;
                }
            }

            $w = imagesx($im);
            $h = imagesy($im);
            if ($w < 1 || $h < 1) {
                imagedestroy($im);

                return null;
            }

            if ($w > $maxDim || $h > $maxDim) {
                $scale = min($maxDim / $w, $maxDim / $h);
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $resized = imagecreatetruecolor($nw, $nh);
                if ($resized === false) {
                    imagedestroy($im);

                    return null;
                }
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, $nw, $nh, $transparent);
                imagealphablending($resized, true);
                imagecopyresampled($resized, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($im);
                $im = $resized;
                $w = $nw;
                $h = $nh;
            }

            return [$im, $w, $h];
        } catch (\Throwable $e) {
            if (isset($im) && (is_object($im) || is_resource($im))) {
                @imagedestroy($im);
            }

            return null;
        }
    }

    /**
     * Misma estructura que la extracción del logo, pero a partir de un color (#RGB o #RRGGBB).
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
        [$hHue, $hSat, $hLight] = $this->rgbToHsl($r, $g, $b);
        [$hHue, $hSat] = $this->normalizeAccentForTheme($hHue, $hSat, $hLight, $r, $g, $b);

        $palette = [
            'accent_hex' => $this->rgbToHex($r, $g, $b),
            'vars_dark'  => $this->buildDarkVars($hHue, $hSat, $hLight),
            'vars_light' => $this->buildLightVars($hHue, $hSat, $hLight),
        ];

        return $this->refreshAccentForegrounds($palette);
    }

    /**
     * Acento dominante para la carta: prioriza colores vivos (flor, tipografía) frente a
     * fondos papel/crema (suelen tener L alto pero cuentan muchos píxeles) y frente a
     * masas grandes poco relevantes; pondera el centro del logo (símbolo habitual).
     */
    private function dominantSaturatedRgb($im, int $w, int $h): ?array
    {
        $buckets = array_fill(0, 36, ['score' => 0.0, 'r' => 0.0, 'g' => 0.0, 'b' => 0.0]);
        $fallbackR = 0.0;
        $fallbackG = 0.0;
        $fallbackB = 0.0;
        $fallbackW = 0.0;

        $sigma = 0.26;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($im, $x, $y);
                $r = ($c >> 16) & 255;
                $g = ($c >> 8) & 255;
                $b = $c & 255;
                [$hh, $ss, $ll] = $this->rgbToHsl($r, $g, $b);

                $nx = ($x + 0.5) / $w - 0.5;
                $ny = ($y + 0.5) / $h - 0.5;
                $centerW = 0.45 + 1.55 * exp(-(($nx * $nx + $ny * $ny) / (2 * $sigma * $sigma)));

                // Fallback: mismo criterio que cubetas (evita que el crema del fondo arrastre el acento).
                if ($ll <= 0.82 && $ss >= 0.08) {
                    $fb = $ss * $ss * $centerW * (1.0 - 0.85 * max(0.0, $ll - 0.72));
                    if ($fb > 0) {
                        $fallbackR += $r * $fb;
                        $fallbackG += $g * $fb;
                        $fallbackB += $b * $fb;
                        $fallbackW += $fb;
                    }
                }

                // Descarta casi blancos/cremas (L alto) y grises; el crema típico del logo entra aquí.
                if ($ss < 0.14 || $ll < 0.05 || $ll > 0.82) {
                    continue;
                }

                $chromW = $ss * $ss;
                $wPixel = $centerW * $chromW;

                $bi = (int) floor(fmod($hh, 360) / 10);
                if ($bi < 0 || $bi > 35) {
                    $bi = 0;
                }
                $buckets[$bi]['score'] += $wPixel;
                $buckets[$bi]['r'] += $r * $wPixel;
                $buckets[$bi]['g'] += $g * $wPixel;
                $buckets[$bi]['b'] += $b * $wPixel;
            }
        }

        // Suavizado circular en el círculo cromático: rojos/granates suelen repartirse
        // entre la cubeta 0 y la 35 (0° ↔ 360°).
        $bestI = -1;
        $bestScore = 0.0;
        for ($i = 0; $i < 36; $i++) {
            $s0 = $buckets[$i]['score'];
            $sPrev = $buckets[($i + 35) % 36]['score'];
            $sNext = $buckets[($i + 1) % 36]['score'];
            $smooth = $s0 + 0.35 * ($sPrev + $sNext);
            if ($smooth > $bestScore) {
                $bestScore = $smooth;
                $bestI = $i;
            }
        }

        if ($bestI >= 0 && $bestScore >= 1e-4) {
            $b = $buckets[$bestI];
            $den = max(1e-6, $b['score']);

            return [
                (int) round($b['r'] / $den),
                (int) round($b['g'] / $den),
                (int) round($b['b'] / $den),
            ];
        }

        if ($fallbackW < 1e-6) {
            return [201, 168, 76];
        }

        return [
            (int) round($fallbackR / $fallbackW),
            (int) round($fallbackG / $fallbackW),
            (int) round($fallbackB / $fallbackW),
        ];
    }

    /**
     * Negro / blanco / gris: sin “subir” saturación (evita granates falsos) ni verde complementary en ofertas.
     *
     * @return array{0: float, 1: float}
     */
    private function normalizeAccentForTheme(float $hue, float $sat, float $light, int $r, int $g, int $b): array
    {
        $delta = max($r, $g, $b) - min($r, $g, $b);
        $isNeutral = $sat < 0.07 || $delta <= 14;

        if ($isNeutral) {
            return [$hue, 0.0];
        }

        return [$hue, max(0.28, min(0.92, $sat * 1.08))];
    }

    private function buildDarkVars(float $hue, float $sat, float $accentLight): array
    {
        $neutral = $sat < 0.02;
        $bias = $neutral ? 0.0 : 0.08;
        $biasSurf = $neutral ? 0.0 : 0.1;

        $gold = $this->hslToHex($hue, min(0.88, $sat), 0.54);
        $goldLight = $this->hslToHex($hue, $sat * 0.92, 0.66);
        $goldDim = $this->hslToHex($hue, $neutral ? 0.0 : min(0.78, $sat + 0.06), 0.36);
        $bg = $this->hslToHex($hue, min(0.22, $sat * 0.35 + $bias), 0.055);
        $surface = $this->hslToHex($hue, min(0.26, $sat * 0.4 + $biasSurf), 0.09);
        $surfaceEl = $this->hslToHex($hue, min(0.3, $sat * 0.45 + $biasSurf), 0.125);
        $text = $this->hslToHex($hue, $neutral ? 0.0 : 0.04, 0.93);
        $textMuted = $this->hslToHex($hue, $neutral ? 0.0 : 0.05, 0.62);
        $red = $neutral
            ? '#dc2626'
            : $this->hslToHex(fmod($hue + 118, 360), 0.72, 0.52);
        $navActiveFg = $this->foregroundOnAccent($gold, '#0a0e17');

        $bgRgb = $this->hexToRgb($bg);

        return array_merge($this->brandInteractionVars($gold), [
            '--bg'            => $bg,
            '--surface'       => $surface,
            '--surface-el'    => $surfaceEl,
            '--gold'          => $gold,
            '--gold-light'    => $goldLight,
            '--gold-dim'      => $goldDim,
            '--text'          => $text,
            '--text-muted'    => $textMuted,
            '--red'           => $red,
            '--nav-bg'        => sprintf('rgba(%d,%d,%d,0.94)', $bgRgb[0], $bgRgb[1], $bgRgb[2]),
            '--nav-border'    => $this->hexToRgba($gold, 0.16),
            '--hero-grad-from'=> $surface,
            '--nav-active-fg' => $navActiveFg,
            '--modal-scrim'   => sprintf('rgba(%d,%d,%d,0.48)', $bgRgb[0], $bgRgb[1], $bgRgb[2]),
            '--prod-border'   => $this->hexToRgba($text, 0.09),
            '--divider'       => $this->hexToRgba($text, 0.1),
            '--chip-bg'       => $this->hexToRgba($text, 0.06),
            '--modal-shadow'  => '0 20px 50px rgba(0,0,0,0.48)',
            '--scroll-top-fg' => $navActiveFg,
        ]);
    }

    /**
     * Variables para hovers / resplandor del hero ligadas al acento (sustituyen dorados fijos en CSS).
     *
     * @return array<string, string>
     */
    private function brandInteractionVars(string $goldHex): array
    {
        return [
            '--hero-glow'        => $this->hexToRgba($goldHex, 0.14),
            '--gold-border-soft' => $this->hexToRgba($goldHex, 0.35),
            '--gold-border-hover'=> $this->hexToRgba($goldHex, 0.52),
            '--gold-focus-ring'  => $this->hexToRgba($goldHex, 0.24),
        ];
    }

    private function buildLightVars(float $hue, float $sat, float $accentLight): array
    {
        $neutral = $sat < 0.02;
        /** Marca en gris/negro: el tema claro debe notarse en fondo y tarjetas, no solo en botones activos. */
        $darkNeutralBrand = $neutral && $accentLight < 0.38;

        if ($darkNeutralBrand) {
            $strength = max(0.0, min(1.0, (0.38 - $accentLight) / 0.38));
            /** Más contraste que el tema dorado por defecto: lienzo y tarjetas deben notarse, no solo los pills activos. */
            $bgL = 0.945 - 0.095 * $strength;
            $elL = 0.895 - 0.075 * $strength;
            $cardL = 0.982 - 0.065 * $strength;
            $bg = $this->hslToHex(0, 0, $bgL);
            $surfaceEl = $this->hslToHex(0, 0, $elL);
            $surface = $this->hslToHex(0, 0, $cardL);
            $gold = '#141414';
            $goldLight = '#2a2a2a';
            $goldDim = '#0a0a0a';
            $text = '#0c0c0c';
            $textMuted = $this->hslToHex(0, 0, 0.43);
            $red = '#b91c1c';
            $navActiveFg = $this->foregroundOnAccent($gold, '#1c1917');
            $bgRgb = $this->hexToRgb($bg);
            $textRgb = $this->hexToRgb($text);

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
                '--nav-border'     => $this->hexToRgba($gold, 0.2),
                '--hero-grad-from' => $surfaceEl,
                '--nav-active-fg'  => $navActiveFg,
                '--modal-scrim'    => sprintf('rgba(%d,%d,%d,0.4)', $textRgb[0], $textRgb[1], $textRgb[2]),
                '--prod-border'    => $this->hexToRgba($text, 0.13),
                '--divider'        => $this->hexToRgba($text, 0.11),
                '--chip-bg'        => $this->hexToRgba($text, 0.06),
                '--modal-shadow'   => '0 20px 50px rgba(0,0,0,0.14)',
                '--scroll-top-fg'  => $navActiveFg,
            ]);
        }

        $gold = $this->hslToHex($hue, min(0.82, $sat * 0.95), 0.34);
        $goldLight = $this->hslToHex($hue, $sat * 0.85, 0.42);
        $goldDim = $this->hslToHex($hue, $neutral ? 0.0 : min(0.75, $sat + 0.05), 0.28);
        $bg = $this->hslToHex($hue, min(0.08, $sat * 0.2), 0.97);
        $surface = '#ffffff';
        $surfaceEl = $this->hslToHex($hue, min(0.12, $sat * 0.25), 0.94);
        $text = $this->hslToHex($hue, $neutral ? 0.0 : min(0.12, $sat * 0.2), 0.12);
        $textMuted = $this->hslToHex($hue, $neutral ? 0.0 : 0.06, 0.42);
        $red = $neutral
            ? '#b91c1c'
            : $this->hslToHex(fmod($hue + 115, 360), 0.7, 0.38);
        $navActiveFg = $this->foregroundOnAccent($gold, '#1c1917');

        $bgRgb = $this->hexToRgb($bg);
        $textRgb = $this->hexToRgb($text);

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
            '--nav-border'     => $this->hexToRgba($gold, 0.22),
            '--hero-grad-from' => $surfaceEl,
            '--nav-active-fg'  => $navActiveFg,
            '--modal-scrim'    => sprintf('rgba(%d,%d,%d,0.4)', $textRgb[0], $textRgb[1], $textRgb[2]),
            '--prod-border'    => $this->hexToRgba($text, 0.11),
            '--divider'        => $this->hexToRgba($text, 0.12),
            '--chip-bg'        => $this->hexToRgba($text, 0.05),
            '--modal-shadow'   => '0 20px 50px rgba(0,0,0,0.14)',
            '--scroll-top-fg'  => $navActiveFg,
        ]);
    }

    private function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        switch ($max) {
            case $r:
                $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                break;
            case $g:
                $h = (($b - $r) / $d + 2) / 6;
                break;
            default:
                $h = (($r - $g) / $d + 4) / 6;
        }

        return [$h * 360, $s, $l];
    }

    private function hslToHex(float $h, float $s, float $l): string
    {
        [$r, $g, $b] = $this->hslToRgb($h, $s, $l);

        return $this->rgbToHex($r, $g, $b);
    }

    private function hslToRgb(float $h, float $s, float $l): array
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
            (int) round(255 * $this->hue2rgb($p, $q, $h + 1 / 3)),
            (int) round(255 * $this->hue2rgb($p, $q, $h)),
            (int) round(255 * $this->hue2rgb($p, $q, $h - 1 / 3)),
        ];
    }

    private function hue2rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    private function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }

    /** @return array{0:int,1:int,2:int} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return [10, 14, 23];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $a = max(0, min(1, $alpha));

        return sprintf('rgba(%d,%d,%d,%.2f)', $r, $g, $b, $a);
    }

    /**
     * Texto sobre fondo de acento (--gold): blanco o casi negro según contraste WCAG (luminancia relativa).
     */
    private function foregroundOnAccent(string $accentHex, string $darkTextHex): string
    {
        $bgL = $this->relativeLuminanceFromHex($accentHex);
        $whiteL = $this->relativeLuminanceFromHex('#ffffff');
        $darkL = $this->relativeLuminanceFromHex($darkTextHex);

        $contrastWhite = $this->luminanceContrast($bgL, $whiteL);
        $contrastDark = $this->luminanceContrast($bgL, $darkL);

        return $contrastWhite >= $contrastDark ? '#ffffff' : $darkTextHex;
    }

    private function luminanceContrast(float $lum1, float $lum2): float
    {
        $hi = max($lum1, $lum2);
        $lo = min($lum1, $lum2);

        return ($hi + 0.05) / ($lo + 0.05);
    }

    private function relativeLuminanceFromHex(string $hex): float
    {
        [$r, $g, $b] = $this->hexToRgb($hex);

        return $this->relativeLuminanceSrgb($r / 255, $g / 255, $b / 255);
    }

    private function relativeLuminanceSrgb(float $r, float $g, float $b): float
    {
        $rs = $this->linearizeSrgbChannel($r);
        $gs = $this->linearizeSrgbChannel($g);
        $bs = $this->linearizeSrgbChannel($b);

        return 0.2126 * $rs + 0.7152 * $gs + 0.0722 * $bs;
    }

    private function linearizeSrgbChannel(float $c): float
    {
        if ($c <= 0.04045) {
            return $c / 12.92;
        }

        return (($c + 0.055) / 1.055) ** 2.4;
    }
}
