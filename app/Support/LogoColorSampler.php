<?php

namespace App\Support;

/**
 * Samples dominant and distinct colors from a raster image stored in storage/public.
 * Depends only on GD and ColorMath — no DB, no filesystem writes.
 */
class LogoColorSampler
{
    /**
     * RGB of the most visually prominent saturated color in the image.
     * Returns null when the image cannot be loaded or yields no valid accent.
     *
     * @return array{0:int,1:int,2:int}|null
     */
    public function dominantAccentRgb(string $relativePath): ?array
    {
        $buf = $this->loadAndResize($relativePath, 56);
        if ($buf === null) {
            return null;
        }

        [$im, $w, $h] = $buf;

        try {
            return $this->extractDominantSaturatedRgb($im, $w, $h);
        } finally {
            $this->destroyImage($im);
        }
    }

    /**
     * Up to $maxSwatches visually distinct colors from the logo, sorted by pixel frequency.
     *
     * @return string[]  hex strings, e.g. ['#c4a03e', '#1b2a40', …]
     */
    public function distinctSwatches(string $relativePath, int $maxSwatches = 16): array
    {
        $buf = $this->loadAndResize($relativePath, 72);
        if ($buf === null) {
            return [];
        }

        [$im, $w, $h] = $buf;

        try {
            return $this->collectSwatches($im, $w, $h, $maxSwatches);
        } finally {
            $this->destroyImage($im);
        }
    }

    // ── Private image helpers ─────────────────────────────────────────────────

    /**
     * Load + downscale to max $maxDim × $maxDim for fast sampling.
     *
     * @return array|null [GD image resource, width, height]
     */
    private function loadAndResize(string $relativePath, int $maxDim): ?array
    {
        $relativePath = ltrim($relativePath, '/');
        $fullPath     = storage_path('app/public/' . $relativePath);

        if ($relativePath === '' || ! is_file($fullPath)) {
            return null;
        }

        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $binary = @file_get_contents($fullPath);
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
                $scale   = min($maxDim / $w, $maxDim / $h);
                $nw      = max(1, (int) round($w * $scale));
                $nh      = max(1, (int) round($h * $scale));
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
                $w  = $nw;
                $h  = $nh;
            }

            return [$im, $w, $h];
        } catch (\Throwable) {
            $this->destroyImage($im);

            return null;
        }
    }

    /**
     * Dominant saturated RGB: weights pixels by saturation² and center proximity.
     * Hue buckets of 10° + circular smoothing prevent red/garnet from splitting across
     * the 0°/360° boundary. Falls back to any lightly-muted color when no vivid accent is found.
     *
     * @return array{0:int,1:int,2:int}|null
     */
    private function extractDominantSaturatedRgb($im, int $w, int $h): ?array
    {
        $buckets   = array_fill(0, 36, ['score' => 0.0, 'r' => 0.0, 'g' => 0.0, 'b' => 0.0]);
        $fbR = $fbG = $fbB = $fbW = 0.0;
        $sigma = 0.26;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($im, $x, $y);
                $r = ($c >> 16) & 255;
                $g = ($c >> 8)  & 255;
                $b = $c & 255;
                [, $ss, $ll] = ColorMath::rgbToHsl($r, $g, $b);
                [$hh]        = ColorMath::rgbToHsl($r, $g, $b);

                $nx = ($x + 0.5) / $w - 0.5;
                $ny = ($y + 0.5) / $h - 0.5;
                $cw = 0.45 + 1.55 * exp(-(($nx * $nx + $ny * $ny) / (2 * $sigma * $sigma)));

                if ($ll <= 0.82 && $ss >= 0.08) {
                    $fb = $ss * $ss * $cw * (1.0 - 0.85 * max(0.0, $ll - 0.72));
                    if ($fb > 0) {
                        $fbR += $r * $fb;
                        $fbG += $g * $fb;
                        $fbB += $b * $fb;
                        $fbW += $fb;
                    }
                }

                if ($ss < 0.14 || $ll < 0.05 || $ll > 0.82) {
                    continue;
                }

                $wPixel = $cw * $ss * $ss;
                $bi     = (int) floor(fmod($hh, 360) / 10);
                if ($bi < 0 || $bi > 35) $bi = 0;
                $buckets[$bi]['score'] += $wPixel;
                $buckets[$bi]['r']     += $r * $wPixel;
                $buckets[$bi]['g']     += $g * $wPixel;
                $buckets[$bi]['b']     += $b * $wPixel;
            }
        }

        $bestI = -1;
        $bestScore = 0.0;
        for ($i = 0; $i < 36; $i++) {
            $smooth = $buckets[$i]['score']
                + 0.35 * ($buckets[($i + 35) % 36]['score'] + $buckets[($i + 1) % 36]['score']);
            if ($smooth > $bestScore) {
                $bestScore = $smooth;
                $bestI     = $i;
            }
        }

        if ($bestI >= 0 && $bestScore >= 1e-4) {
            $bk  = $buckets[$bestI];
            $den = max(1e-6, $bk['score']);

            return [(int) round($bk['r'] / $den), (int) round($bk['g'] / $den), (int) round($bk['b'] / $den)];
        }

        if ($fbW < 1e-6) {
            return [201, 168, 76];  // neutral gold fallback
        }

        return [(int) round($fbR / $fbW), (int) round($fbG / $fbW), (int) round($fbB / $fbW)];
    }

    /**
     * Picks $maxSwatches visually distinct colors by pixel frequency,
     * rejecting candidates within Euclidean RGB distance 42 of an already-picked color.
     *
     * @return string[]
     */
    private function collectSwatches($im, int $w, int $h, int $maxSwatches): array
    {
        $counts = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c   = imagecolorat($im, $x, $y);
                $rq  = (($c >> 16) & 255) >> 3 << 3;
                $gq  = (($c >> 8) & 255)  >> 3 << 3;
                $bq  = ($c & 255)          >> 3 << 3;
                $key = ($rq << 16) | ($gq << 8) | $bq;
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return [];
        }

        arsort($counts);
        $hexOut = [];
        $rgbOut = [];

        foreach ($counts as $key => $_) {
            $rq = ($key >> 16) & 255;
            $gq = ($key >> 8)  & 255;
            $bq = $key & 255;

            $tooClose = false;
            foreach ($rgbOut as [$pr, $pg, $pb]) {
                $d = ($rq - $pr) ** 2 + ($gq - $pg) ** 2 + ($bq - $pb) ** 2;
                if ($d < 42 * 42) {
                    $tooClose = true;
                    break;
                }
            }

            if ($tooClose) {
                continue;
            }

            $hexOut[] = ColorMath::rgbToHex($rq, $gq, $bq);
            $rgbOut[] = [$rq, $gq, $bq];

            if (count($hexOut) >= $maxSwatches) {
                break;
            }
        }

        return $hexOut;
    }

    private function destroyImage(mixed $im): void
    {
        if (is_object($im) || is_resource($im)) {
            @imagedestroy($im);
        }
    }
}
