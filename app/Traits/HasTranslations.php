<?php

namespace App\Traits;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTranslations
{
    // Cada modelo debe declarar:
    //   protected array $translatable = ['name', 'description'];

    public static function bootHasTranslations(): void
    {
        static::deleting(function ($model) {
            $model->translations()->delete();
        });
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /**
     * Devuelve el valor traducido para un campo y locale.
     * Si el locale es el de origen (es) devuelve el campo nativo.
     * Si no hay traducción disponible, hace fallback al campo nativo.
     */
    public function translate(string $locale, string $key): ?string
    {
        $locale = $this->normalizeLocaleForStorage($locale);
        $sourceLocale = config('app.source_locale', 'es');

        if ($locale === $sourceLocale) {
            return $this->getAttribute($key);
        }

        // Busca en la colección cargada (evita N+1 si se hizo eager load)
        if ($this->relationLoaded('translations')) {
            $found = $this->translations
                ->where('locale', $locale)
                ->where('key', $key)
                ->first();
        } else {
            $found = $this->translations()
                ->where('locale', $locale)
                ->where('key', $key)
                ->first();
        }

        return $found?->value ?? $this->getAttribute($key);
    }

    /**
     * Misma forma que en BD (DeepL / admin): en, pt_BR, de… Evita fallos si llega EN, pt_br, en-US.
     */
    protected function normalizeLocaleForStorage(string $locale): string
    {
        $k = strtolower(str_replace('-', '_', trim($locale)));
        if ($k === 'pt_br') {
            return 'pt_BR';
        }
        if ($k === 'en' || strncmp($k, 'en_', 3) === 0) {
            return 'en';
        }

        return $k;
    }

    /**
     * Guarda o actualiza una traducción individual.
     */
    public function setTranslation(string $locale, string $key, ?string $value): void
    {
        $this->translations()->updateOrCreate(
            ['locale' => $locale, 'key' => $key],
            ['value' => $value]
        );

        // Refresca la relación si ya estaba cargada
        if ($this->relationLoaded('translations')) {
            $this->load('translations');
        }
    }

    /**
     * Lista de campos traducibles del modelo.
     */
    public function getTranslatableFields(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Devuelve todas las traducciones como array indexado por [locale][key].
     */
    public function getAllTranslations(): array
    {
        $result = [];
        foreach ($this->translations as $t) {
            $result[$t->locale][$t->key] = $t->value;
        }
        return $result;
    }

    /**
     * True si el modelo tiene al menos una traducción para el locale dado.
     */
    public function hasTranslationFor(string $locale): bool
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->where('locale', $locale)->isNotEmpty();
        }
        return $this->translations()->where('locale', $locale)->exists();
    }
}
