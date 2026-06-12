<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Webkul\Support\Settings\BrandSettings;

class ApplyBrandSettings
{
    /**
     * The shade whose lightness/chroma a picked color is anchored to, so the
     * resting button background (Filament's `--primary-600`) matches the
     * exact hex the admin chose.
     */
    protected const ANCHOR_SHADE = 600;

    /**
     * Filament's reference lightness per shade (see Color::generatePalette).
     * Used as the ramp's shape; remapped so the anchor shade hits the picked
     * color's own lightness instead of Filament's fixed value.
     *
     * @var array<int, float>
     */
    protected const LIGHTNESS = [
        50  => 0.97718,
        100 => 0.95035,
        200 => 0.90547,
        300 => 0.84047,
        400 => 0.75353,
        500 => 0.68271,
        600 => 0.59782,
        700 => 0.51494,
        800 => 0.44612,
        900 => 0.39459,
        950 => 0.27788,
    ];

    /**
     * Filament's reference chroma per shade. Scaled by the picked color's
     * chroma so the saturation taper across the ramp is preserved.
     *
     * @var array<int, float>
     */
    protected const CHROMA = [
        50  => 0.01395,
        100 => 0.03273,
        200 => 0.06318,
        300 => 0.10605,
        400 => 0.15027,
        500 => 0.17009,
        600 => 0.16914,
        700 => 0.14941,
        800 => 0.12332,
        900 => 0.09964,
        950 => 0.07136,
    ];

    /**
     * Override the current panel's branding (colors, logos, favicon and logo
     * height) from the BrandSettings, for every panel this middleware is
     * attached to. Any value left empty falls back to the panel's default.
     *
     * Runs after Filament's `SetUpPanel` middleware, so the panel's default
     * colors are already registered and the values applied here take priority.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentPanel();

        if ($panel === null) {
            return $next($request);
        }

        try {
            $brand = app(BrandSettings::class);

            $panelDefaultColors = $panel->getColors();

            $colorKeys = ['primary', 'success', 'danger', 'warning', 'info', 'gray'];

            $brandColors = [];

            foreach ($colorKeys as $colorKey) {
                $hexColor = $brand->{$colorKey.'_color'};

                if (! $hexColor) {
                    $hexColor = $panelDefaultColors[$colorKey][600] ?? null;
                }

                if ($hexColor) {
                    $brandColors[$colorKey] = $this->paletteFromHex($hexColor);
                }
            }

            if ($brandColors !== []) {
                FilamentColor::register($brandColors);
            }

            if (! empty($brand->light_logo)) {
                $panel->brandLogo(Storage::disk('public')->url($brand->light_logo));
            }

            if (! empty($brand->dark_logo)) {
                $panel->darkModeBrandLogo(Storage::disk('public')->url($brand->dark_logo));
            }

            if (! empty($brand->favicon)) {
                $panel->favicon(Storage::disk('public')->url($brand->favicon));
            }

            if (! empty($brand->logo_height)) {
                $panel->brandLogoHeight($brand->logo_height);
            }
        } catch (Throwable) {
            // Settings not migrated yet or repository unavailable — keep defaults.
        }

        return $next($request);
    }

    /**
     * Build a full 50–950 OKLCH palette from a single hex, anchoring the
     * picked color at ANCHOR_SHADE so the resting button matches it exactly.
     *
     * Lightness is remapped with two linear segments — [anchor..white] above
     * the anchor and [black..anchor] below — preserving shade ordering for any
     * input, including pure black or white. Chroma keeps Filament's taper,
     * scaled to the picked color's saturation; neutral picks stay achromatic.
     *
     * @return array<int, string>
     */
    protected function paletteFromHex(string $hex): array
    {
        [$lightness, $chroma, $hue] = sscanf(Color::convertToOklch($hex), 'oklch(%f %f %f)');

        $anchorLightness = self::LIGHTNESS[self::ANCHOR_SHADE];
        $anchorChroma = self::CHROMA[self::ANCHOR_SHADE];

        $isAchromatic = $chroma < 0.03;
        $chromaScale = $anchorChroma > 0 ? $chroma / $anchorChroma : 0.0;

        $palette = [];

        foreach (self::LIGHTNESS as $shade => $referenceLightness) {
            if ($referenceLightness >= $anchorLightness) {
                $newLightness = $lightness
                    + (($referenceLightness - $anchorLightness) / (1 - $anchorLightness)) * (1 - $lightness);
            } else {
                $newLightness = ($referenceLightness / $anchorLightness) * $lightness;
            }

            $newLightness = max(0.0, min(1.0, $newLightness));
            $newChroma = $isAchromatic ? 0.0 : round(self::CHROMA[$shade] * $chromaScale, 5);

            $palette[$shade] = 'oklch('.round($newLightness, 5).' '.$newChroma.' '.round($hue, 3).')';
        }

        return $palette;
    }
}
