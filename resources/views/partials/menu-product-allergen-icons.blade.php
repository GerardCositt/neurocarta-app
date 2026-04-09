{{-- Iconos pequeños de alérgenos en la carta pública (sin abrir el producto)
     Nota: no filtramos por allergens.active (en BD el default es false); si está asignado al plato, se muestra. --}}
@php
    $localeA = $locale ?? 'es';
    $items = collect($product->visibleAllergens ?? [])->sortBy(function ($a) {
        return $a->sort_order ?? 0;
    });
@endphp
@if($items->count() > 0)
    <div class="prod-allergens" role="group" aria-label="{{ __('public_menu.allergens_aria_group') }}">
        @foreach($items as $allergen)
            @php
                $aName = $allergen->translate($localeA, 'name');
                $imgUrl = $allergen->image_url;
            @endphp
            @if($imgUrl)
                <span class="prod-allergen-icon" title="{{ $aName }}">
                    <img src="{{ $imgUrl }}" alt="{{ $aName }}" loading="lazy" width="42" height="42"
                         onerror="this.onerror=null;this.src={{ json_encode(asset('img/noimg.png')) }}">
                </span>
            @else
                <span class="prod-allergen-chip" title="{{ $aName }}">{{ \Illuminate\Support\Str::limit($aName, 10, '…') }}</span>
            @endif
        @endforeach
    </div>
@endif
