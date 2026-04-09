<?php

namespace App\Http\Livewire\Admin;

use App\Models\Setting;
use App\Services\ImageAssetService;
use App\Services\MenuBrandPaletteService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class AppearanceSettings extends Component
{
    use WithFileUploads;

    /** @var string|null */
    public $currentLogoPath = null;

    /** @var \Livewire\TemporaryUploadedFile|null */
    public $logoFile = null;

    /** @var string Color principal de la carta (#RRGGBB); editable sin depender del logo. */
    public $accentHexInput = '#c9a84c';

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    private function imageAssets(): ImageAssetService
    {
        return app(ImageAssetService::class);
    }

    public function mount(): void
    {
        $this->currentLogoPath = Setting::get('admin_logo_path', null, $this->getRestaurantId());
        $this->syncAccentInputFromStoredPalette();
    }

    /**
     * @return string[]
     */
    private function accentPresetHexes(): array
    {
        return [
            '#6b1026', '#881337', '#9f1239', '#b91c1c', '#be123c', '#c2410c', '#ea580c',
            '#c9a84c', '#ca8a04', '#a16207', '#854d0e',
            '#3f6212', '#166534', '#0f766e', '#0e7490', '#0369a1', '#1d4ed8', '#5b21b6',
            '#1c1917', '#44403c',
        ];
    }

    private function syncAccentInputFromStoredPalette(): void
    {
        $raw = Setting::get(MenuBrandPaletteService::settingKey(), '', $this->getRestaurantId());
        if (! is_string($raw) || $raw === '') {
            return;
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && ! empty($decoded['accent_hex']) && is_string($decoded['accent_hex'])) {
            $this->accentHexInput = $decoded['accent_hex'];
        }
    }

    public function applyAccentSwatch(string $hex): void
    {
        $hex = strtolower(ltrim(trim($hex), '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return;
        }
        $this->accentHexInput = '#' . $hex;
        $this->saveAccentColor();
    }

    public function updatedLogoFile(): void
    {
        $this->validate([
            'logoFile' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:1024',
        ]);
    }

    public function saveLogo(): void
    {
        $this->validate(
            [
                'logoFile' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:1024',
            ],
            [
                'logoFile.required' => __('admin.appearance.logo_file_required'),
            ]
        );

        $old = $this->currentLogoPath;
        $path = $this->imageAssets()->storeUploadedImage($this->logoFile, 'branding', 1400);

        Setting::put('admin_logo_path', $path, $this->getRestaurantId());
        $this->currentLogoPath = $path;
        $this->logoFile = null;

        $rid = $this->getRestaurantId();
        $palette = app(MenuBrandPaletteService::class)->extractFromStoragePublicPath($path);
        Setting::put(MenuBrandPaletteService::settingKey(), $palette ? json_encode($palette) : '', $rid);
        if ($palette && ! empty($palette['accent_hex'])) {
            $this->accentHexInput = $palette['accent_hex'];
        }

        if ($old && $old !== $path) {
            try {
                Storage::disk('public')->delete($old);
            } catch (\Throwable $e) {
                // no-op
            }
        }

        session()->flash('message', __('admin.appearance.flash_logo_updated'));
    }

    public function removeLogo(): void
    {
        $old = Setting::get('admin_logo_path', null, $this->getRestaurantId());
        Setting::put('admin_logo_path', '', $this->getRestaurantId());
        Setting::put(MenuBrandPaletteService::settingKey(), '', $this->getRestaurantId());
        $this->currentLogoPath = null;
        $this->accentHexInput = '#c9a84c';

        if ($old) {
            try {
                Storage::disk('public')->delete($old);
            } catch (\Throwable $e) {
                // no-op
            }
        }

        session()->flash('message', __('admin.appearance.flash_logo_removed'));
    }

    public function saveAccentColor(): void
    {
        $this->validate(
            [
                'accentHexInput' => ['required', 'regex:/^#?([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
            ],
            [
                'accentHexInput.required' => __('admin.appearance.accent_invalid'),
                'accentHexInput.regex'     => __('admin.appearance.accent_invalid'),
            ]
        );

        $hex = strtoupper(trim($this->accentHexInput));
        if ($hex !== '' && $hex[0] !== '#') {
            $hex = '#' . $hex;
        }

        $service = app(MenuBrandPaletteService::class);
        $palette = $service->paletteFromAccentHex($hex);
        if ($palette === null) {
            $this->addError('accentHexInput', __('admin.appearance.accent_invalid'));

            return;
        }

        Setting::put(MenuBrandPaletteService::settingKey(), json_encode($palette), $this->getRestaurantId());
        $this->accentHexInput = $palette['accent_hex'];

        session()->flash('message', __('admin.appearance.accent_saved'));
    }

    public function render()
    {
        $menuPalette = $this->loadMenuPalette();

        $accentPresetsFromLogo = false;
        $accentPresets = $this->accentPresetHexes();
        if ($this->currentLogoPath) {
            $_lp = strtolower($this->currentLogoPath);
            if (substr($_lp, -4) !== '.svg') {
                $fromLogo = app(MenuBrandPaletteService::class)
                    ->extractDistinctSwatchesFromStoragePublicPath($this->currentLogoPath);
                if ($fromLogo !== []) {
                    $accentPresets = $fromLogo;
                    $accentPresetsFromLogo = true;
                }
            }
        }

        return view('livewire.admin.appearance-settings', [
            'menuPalette'             => $menuPalette,
            'accentPresets'           => $accentPresets,
            'accentPresetsFromLogo'   => $accentPresetsFromLogo,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadMenuPalette(): ?array
    {
        $raw = Setting::get(MenuBrandPaletteService::settingKey(), '', $this->getRestaurantId());
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }
        if (! empty($decoded['accent_hex']) && is_string($decoded['accent_hex'])) {
            $rebuilt = app(MenuBrandPaletteService::class)->paletteFromAccentHex($decoded['accent_hex']);
            if ($rebuilt !== null) {
                return $rebuilt;
            }
        }
        if (! empty($decoded['vars_dark']) && ! empty($decoded['vars_light'])) {
            return app(MenuBrandPaletteService::class)->refreshAccentForegrounds($decoded);
        }

        return null;
    }
}
