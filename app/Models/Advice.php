<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advice extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'advice';

    protected array $translatable = ['title', 'advice'];

    protected $fillable = ['title', 'advice', 'status', 'starts_at', 'ends_at', 'restaurant_id'];

    protected $casts = [
        'status' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /** Hay al menos una fecha/hora de programación. */
    public function hasSchedule(): bool
    {
        return $this->starts_at !== null || $this->ends_at !== null;
    }

    /**
     * Ventana [starts_at, ends_at] con hora; null en un extremo = sin límite en ese lado.
     */
    public function isInScheduledWindow(): bool
    {
        if (! $this->hasSchedule()) {
            return false;
        }
        $now = now();
        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Misma regla que scopeVisibleNow, para UI (tabla/modal).
     * status=false siempre desactiva el aviso, aunque esté en ventana programada.
     */
    public function isVisibleOnPublicCarta(): bool
    {
        if (! $this->status) {
            return false;
        }

        if ($this->hasSchedule()) {
            return $this->isInScheduledWindow();
        }

        return true;
    }

    /**
     * Valores del formulario (cadenas `Y-m-d H:i` desde Flatpickr/Livewire) para saber si estamos en ventana antes de guardar.
     */
    public static function isFormInScheduledWindow(?string $startsAt, ?string $endsAt): bool
    {
        $has = filled($startsAt) || filled($endsAt);
        if (! $has) {
            return false;
        }
        $now = now();
        if ($startsAt) {
            $s = Carbon::parse($startsAt);
            if ($now->lt($s)) {
                return false;
            }
        }
        if ($endsAt) {
            $e = Carbon::parse($endsAt);
            if ($now->gt($e)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Carta pública:
     * status=false siempre oculta el aviso.
     * status=true + sin programación: visible.
     * status=true + con programación: visible solo dentro de la ventana [starts_at, ends_at].
     */
    public function scopeVisibleNow($query)
    {
        $now = now();

        return $query->where('status', true)->where(function ($q) use ($now) {
            // Sin programación: siempre visible (ya filtrado por status=true arriba)
            $q->where(function ($q2) {
                $q2->whereNull('starts_at')->whereNull('ends_at');
            })
            // Con programación: dentro de la ventana
            ->orWhere(function ($q2) use ($now) {
                $q2->where(function ($q3) {
                    $q3->whereNotNull('starts_at')->orWhereNotNull('ends_at');
                });
                $q2->where(function ($q3) use ($now) {
                    $q3->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                });
                $q2->where(function ($q3) use ($now) {
                    $q3->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                });
            });
        });
    }

}
