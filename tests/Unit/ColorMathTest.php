<?php

namespace Tests\Unit;

use App\Support\ColorMath;
use PHPUnit\Framework\TestCase;

class ColorMathTest extends TestCase
{
    // ── RGB ↔ HSL ─────────────────────────────────────────────────────────────

    public function test_pure_red_converts_to_hsl(): void
    {
        [$h, $s, $l] = ColorMath::rgbToHsl(255, 0, 0);
        $this->assertEqualsWithDelta(0.0, $h, 1.0);
        $this->assertEqualsWithDelta(1.0, $s, 0.01);
        $this->assertEqualsWithDelta(0.5, $l, 0.01);
    }

    public function test_pure_green_converts_to_hsl(): void
    {
        [$h, $s, $l] = ColorMath::rgbToHsl(0, 255, 0);
        $this->assertEqualsWithDelta(120.0, $h, 1.0);
        $this->assertEqualsWithDelta(1.0, $s, 0.01);
        $this->assertEqualsWithDelta(0.5, $l, 0.01);
    }

    public function test_pure_blue_converts_to_hsl(): void
    {
        [$h, $s, $l] = ColorMath::rgbToHsl(0, 0, 255);
        $this->assertEqualsWithDelta(240.0, $h, 1.0);
    }

    public function test_white_has_full_lightness(): void
    {
        [, , $l] = ColorMath::rgbToHsl(255, 255, 255);
        $this->assertEqualsWithDelta(1.0, $l, 0.001);
    }

    public function test_black_has_zero_lightness(): void
    {
        [, , $l] = ColorMath::rgbToHsl(0, 0, 0);
        $this->assertEqualsWithDelta(0.0, $l, 0.001);
    }

    public function test_neutral_gray_has_zero_saturation(): void
    {
        [, $s] = ColorMath::rgbToHsl(128, 128, 128);
        $this->assertEqualsWithDelta(0.0, $s, 0.01);
    }

    public function test_rgb_hsl_roundtrip(): void
    {
        foreach ([[200, 80, 40], [30, 120, 200], [180, 180, 10]] as [$r, $g, $b]) {
            [$h, $s, $l] = ColorMath::rgbToHsl($r, $g, $b);
            [$r2, $g2, $b2] = ColorMath::hslToRgb($h, $s, $l);
            $this->assertEqualsWithDelta($r, $r2, 2, "roundtrip r for rgb($r,$g,$b)");
            $this->assertEqualsWithDelta($g, $g2, 2, "roundtrip g for rgb($r,$g,$b)");
            $this->assertEqualsWithDelta($b, $b2, 2, "roundtrip b for rgb($r,$g,$b)");
        }
    }

    // ── Hex conversions ───────────────────────────────────────────────────────

    public function test_rgb_to_hex_formats_correctly(): void
    {
        $this->assertSame('#ff0000', ColorMath::rgbToHex(255, 0, 0));
        $this->assertSame('#000000', ColorMath::rgbToHex(0, 0, 0));
        $this->assertSame('#ffffff', ColorMath::rgbToHex(255, 255, 255));
        $this->assertSame('#1a2b3c', ColorMath::rgbToHex(26, 43, 60));
    }

    public function test_rgb_to_hex_clamps_out_of_range(): void
    {
        $this->assertSame('#ff0000', ColorMath::rgbToHex(999, -1, 0));
    }

    public function test_hex_to_rgb_parses_six_digit(): void
    {
        $this->assertSame([255, 0, 0], ColorMath::hexToRgb('#ff0000'));
        $this->assertSame([26, 43, 60], ColorMath::hexToRgb('#1a2b3c'));
    }

    public function test_hex_to_rgb_expands_three_digit(): void
    {
        $this->assertSame([255, 0, 0], ColorMath::hexToRgb('#f00'));
        $this->assertSame([17, 34, 51], ColorMath::hexToRgb('#123'));
    }

    public function test_hsl_to_hex_roundtrips(): void
    {
        $hex = ColorMath::hslToHex(30.0, 0.8, 0.5);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $hex);
    }

    public function test_hex_to_rgba_formats_with_alpha(): void
    {
        $this->assertSame('rgba(255,0,0,0.50)', ColorMath::hexToRgba('#ff0000', 0.5));
        $this->assertSame('rgba(0,0,0,1.00)', ColorMath::hexToRgba('#000', 1.0));
    }

    public function test_hex_to_rgba_clamps_alpha(): void
    {
        $this->assertSame('rgba(255,255,255,0.00)', ColorMath::hexToRgba('#ffffff', -0.5));
        $this->assertSame('rgba(255,255,255,1.00)', ColorMath::hexToRgba('#ffffff', 1.5));
    }

    // ── WCAG luminance & contrast ─────────────────────────────────────────────

    public function test_white_has_luminance_one(): void
    {
        $this->assertEqualsWithDelta(1.0, ColorMath::relativeLuminanceFromHex('#ffffff'), 0.001);
    }

    public function test_black_has_luminance_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, ColorMath::relativeLuminanceFromHex('#000000'), 0.001);
    }

    public function test_white_on_black_has_maximum_contrast(): void
    {
        $lWhite = ColorMath::relativeLuminanceFromHex('#ffffff');
        $lBlack = ColorMath::relativeLuminanceFromHex('#000000');
        $ratio  = ColorMath::luminanceContrast($lWhite, $lBlack);
        $this->assertEqualsWithDelta(21.0, $ratio, 0.1);
    }

    public function test_foreground_on_dark_accent_is_white(): void
    {
        // Deep navy — white should have better contrast
        $fg = ColorMath::foregroundOnAccent('#1a3a6e', '#0a0e17');
        $this->assertSame('#ffffff', $fg);
    }

    public function test_foreground_on_very_light_accent_is_dark(): void
    {
        // Very pale yellow — dark text should win
        $fg = ColorMath::foregroundOnAccent('#ffffc0', '#1c1917');
        $this->assertSame('#1c1917', $fg);
    }
}
