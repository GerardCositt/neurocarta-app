# CARTA BAR JAÉN III — Guía de Modificaciones v2

## Documento para Cursor + Claude

**Proyecto**: Modificar carta digital existente (Laravel 8 + Livewire 2 + Jetstream + Tailwind)
**Objetivo**: Rediseñar la carta pública como single page moderna + añadir drag & drop al admin
**Hosting**: Plesk en alojamiento.cositt.com (PHP 8+ / MySQL)

---

## ESTADO ACTUAL DEL CÓDIGO

### Stack
- Laravel 8.12, PHP 7.4/8.0
- Livewire 2, Jetstream 2.2, Sanctum
- Tailwind CSS 2.0.2 (CDN en vistas públicas)
- AlpineJS 2.8.0 (CDN)
- Webpack Mix

### Modelos existentes
- **Product**: name, description, active, category_id, pairing_id, price (STRING), offer_price (STRING), photo, aller, offer (boolean)
- **Category**: name, active
- **Pairing**: name, description
- **Allergen**: name (relación belongsToMany comentada)
- **Advice**: title, advice, active (avisos tipo splash)
- **User**: Jetstream estándar

### Problemas detectados que hay que resolver
1. `active=0` = visible, `active=1` = oculto (lógica invertida, confusa)
2. `price` y `offer_price` son STRING en vez de DECIMAL — causa errores como "15,00€€"
3. No existe campo `order` en products ni categories
4. La carta pública es multi-página (categorías → clic → productos)
5. No hay drag & drop en el admin
6. El diseño público es básico (Tailwind utility classes sin diseño premium)

---

## CAMBIOS A REALIZAR

### FASE 1: Migraciones de Base de Datos (2h)

Crear 1 migración nueva que añada los campos necesarios:

```bash
php artisan make:migration add_order_and_improvements_to_tables
```

```php
// database/migrations/XXXX_add_order_and_improvements_to_tables.php

public function up()
{
    // Añadir campo orden a categorías
    Schema::table('categories', function (Blueprint $table) {
        $table->integer('order')->default(0)->after('active');
        $table->string('icon')->nullable()->after('order'); // emoji opcional
    });

    // Añadir campo orden a productos + mejoras ofertas
    Schema::table('products', function (Blueprint $table) {
        $table->integer('order')->default(0)->after('active');
        $table->string('offer_badge')->default('Oferta')->after('offer_price'); // texto personalizable: "Oferta", "-30%", "2x1"
        $table->date('offer_start')->nullable()->after('offer_badge'); // fecha inicio oferta
        $table->date('offer_end')->nullable()->after('offer_start');   // fecha fin oferta
        $table->boolean('featured')->default(false)->after('order');    // producto destacado
    });
}

public function down()
{
    Schema::table('categories', function (Blueprint $table) {
        $table->dropColumn(['order', 'icon']);
    });
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn(['order', 'offer_badge', 'offer_start', 'offer_end', 'featured']);
    });
}
```

**Después de migrar**, ejecutar un comando para asignar orden inicial basado en ID:
```php
// Puedes hacerlo en tinker o crear un seeder
Category::all()->each(fn($cat, $i) => $cat->update(['order' => $i]));
Product::all()->each(fn($prod, $i) => $prod->update(['order' => $i]));
```

### FASE 2: Actualizar Modelos (1h)

#### app/Models/Product.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
        'category_id',
        'pairing_id',
        'price',
        'offer_price',
        'offer',
        'offer_badge',
        'offer_start',
        'offer_end',
        'photo',
        'aller',
        'order',
        'featured',
    ];

    protected $casts = [
        'offer' => 'boolean',
        'active' => 'boolean',
        'featured' => 'boolean',
        'offer_start' => 'date',
        'offer_end' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function pairing()
    {
        return $this->belongsTo(Pairing::class);
    }

    /**
     * Scope: productos visibles (active=0 en la lógica actual)
     */
    public function scopeVisible($query)
    {
        return $query->where('active', 0);
    }

    /**
     * Scope: productos con oferta activa ahora
     */
    public function scopeWithActiveOffer($query)
    {
        return $query->where('offer', 1)
            ->where(function ($q) {
                $q->whereNull('offer_start')->orWhere('offer_start', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('offer_end')->orWhere('offer_end', '>=', now());
            });
    }

    /**
     * Comprobar si la oferta está activa ahora
     */
    public function isOfferActive(): bool
    {
        if (!$this->offer) return false;
        if ($this->offer_start && $this->offer_start->isFuture()) return false;
        if ($this->offer_end && $this->offer_end->isPast()) return false;
        return true;
    }
}
```

#### app/Models/Category.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
        'order',
        'icon',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class)->orderBy('order');
    }

    /**
     * Scope: categorías visibles (active=0 en la lógica actual)
     */
    public function scopeVisible($query)
    {
        return $query->where('active', 0)->orderBy('order');
    }
}
```

---

### FASE 3: API para Reordenar (Drag & Drop Backend) (2h)

Crear un nuevo controlador para las operaciones de reorden vía AJAX:

#### app/Http/Controllers/Api/ReorderController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ReorderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Reordenar categorías
     * POST /api/reorder/categories
     * Body: { "ids": [5, 2, 8, 1, 3] }
     */
    public function categories(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        foreach ($request->ids as $order => $id) {
            Category::where('id', $id)->update(['order' => $order]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Reordenar productos dentro de una categoría
     * POST /api/reorder/products
     * Body: { "ids": [12, 7, 3, 15, 1] }
     */
    public function products(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        foreach ($request->ids as $order => $id) {
            Product::where('id', $id)->update(['order' => $order]);
        }

        return response()->json(['ok' => true]);
    }
}
```

#### routes/api.php — añadir:
```php
use App\Http\Controllers\Api\ReorderController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reorder/categories', [ReorderController::class, 'categories']);
    Route::post('/reorder/products', [ReorderController::class, 'products']);
});
```

---

### FASE 4: Admin — Drag & Drop con SortableJS (4h)

#### 4.1 Añadir SortableJS al layout del admin

En `resources/views/layouts/app.blade.php`, antes del cierre de `</body>`:
```html
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
@stack('scripts')
```

#### 4.2 Modificar vista de Categorías — resources/views/livewire/category/show.blade.php

Reemplazar la tabla actual por una lista sortable:
```html
<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
    @if($msgError)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ $msgError }}
        </div>
    @endif

    <div id="categories-sortable">
        @foreach($categories as $category)
            <div class="flex items-center justify-between p-4 mb-2 bg-gray-50 rounded-lg border border-gray-200 cursor-move"
                 data-id="{{ $category->id }}">

                <!-- Handle de arrastre -->
                <div class="flex items-center gap-4 flex-1">
                    <span class="text-gray-400 cursor-grab active:cursor-grabbing">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                        </svg>
                    </span>

                    <span class="font-medium {{ $category->active ? 'line-through text-gray-400' : 'text-gray-900' }}">
                        {{ $category->name }}
                    </span>

                    <span class="text-sm text-gray-500">
                        ({{ $category->products->count() }} productos)
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Toggle visibilidad -->
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               wire:click="toggleState({{ $category->id }})"
                               {{ $category->active ? 'checked' : '' }}
                               class="form-checkbox text-yellow-500">
                        <span class="ml-1 text-xs text-gray-500">Ocultar</span>
                    </label>

                    <!-- Eliminar -->
                    <button wire:click="deleteCategory({{ $category->id }})"
                            class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('categories-sortable');
    if (el) {
        new Sortable(el, {
            animation: 200,
            ghostClass: 'bg-yellow-50',
            chosenClass: 'shadow-lg',
            dragClass: 'opacity-50',
            onEnd: function() {
                const ids = [...el.children].map(child => parseInt(child.dataset.id));
                fetch('/api/reorder/categories', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ids })
                }).then(r => r.json()).then(data => {
                    if (data.ok) {
                        // Feedback visual
                        el.classList.add('ring-2', 'ring-green-300');
                        setTimeout(() => el.classList.remove('ring-2', 'ring-green-300'), 800);
                    }
                });
            }
        });
    }
});
</script>
@endpush
```

#### 4.3 Modificar vista de Productos — resources/views/livewire/products.blade.php

Añadir filtro por categoría + lista sortable. Modificar la tabla existente:

**Añadir selector de categoría arriba** (después del buscador):
```html
<div class="flex justify-between items-center mb-4">
    <div class="flex gap-4">
        <input wire:model.debounce.500ms="q" type="search" placeholder="Buscar..."
               class="shadow border rounded py-2 px-3 text-gray-700">

        <select wire:model="selectedCategory" class="shadow border rounded py-2 px-3 text-gray-700">
            <option value="">Todas las categorías</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>

    <button wire:click="create()"
            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-3 rounded">
        Agregar producto
    </button>
</div>
```

**Hacer la tabla sortable** — envolver el `<tbody>` con id:
```html
<tbody id="products-sortable">
    @forelse($products as $product)
        <tr data-id="{{ $product->id }}" class="cursor-move hover:bg-gray-50">
            <!-- Añadir handle como primera columna -->
            <td class="border-dashed border-t border-gray-200 px-2 py-2 text-gray-400">
                <svg class="w-5 h-5 cursor-grab" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                </svg>
            </td>
            <!-- ... resto de columnas igual que ahora ... -->
        </tr>
    @empty
        <tr><td colspan="8" class="text-center py-4">Aún no has creado productos</td></tr>
    @endforelse
</tbody>
```

**Script de SortableJS para productos** (al final de la vista):
```html
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('products-sortable');
    if (el) {
        new Sortable(el, {
            animation: 200,
            handle: 'svg',
            ghostClass: 'bg-yellow-50',
            onEnd: function() {
                const ids = [...el.querySelectorAll('tr[data-id]')]
                    .map(tr => parseInt(tr.dataset.id));
                fetch('/api/reorder/products', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ids })
                });
            }
        });
    }
});
</script>
@endpush
```

#### 4.4 Actualizar Livewire Products.php

Añadir `$selectedCategory` y ordenar por `order`:

```php
// Añadir propiedad
public $selectedCategory = '';

// Modificar render()
public function render()
{
    $query = Product::query();

    if ($this->q) {
        $query->where('name', 'like', '%' . $this->q . '%');
    }

    if ($this->selectedCategory) {
        $query->where('category_id', $this->selectedCategory);
    }

    $this->products = $query->orderBy('order')->get();
    $this->categories = Category::orderBy('order')->get();
    $this->pairings = Pairing::all();

    return view('livewire.products');
}
```

#### 4.5 Añadir campos de oferta al formulario de producto

En `resources/views/livewire/productfrm.blade.php`, después del campo offer_price:
```html
<div class="mb-4 inline-flex space-x-4">
    <div class="flex-1">
        <label class="block text-gray-700 text-sm font-bold mb-2">Texto badge oferta:</label>
        <input type="text" wire:model="offer_badge"
               placeholder="Oferta" maxlength="20"
               class="shadow border rounded w-full py-2 px-3 text-gray-700">
    </div>
    <div class="flex-1">
        <label class="block text-gray-700 text-sm font-bold mb-2">Inicio oferta:</label>
        <input type="date" wire:model="offer_start"
               class="shadow border rounded w-full py-2 px-3 text-gray-700">
    </div>
    <div class="flex-1">
        <label class="block text-gray-700 text-sm font-bold mb-2">Fin oferta:</label>
        <input type="date" wire:model="offer_end"
               class="shadow border rounded w-full py-2 px-3 text-gray-700">
    </div>
</div>
```

**Actualizar el componente Products.php**: añadir las propiedades `$offer_badge`, `$offer_start`, `$offer_end` y incluirlas en `store()` y `edit()`.

---

### FASE 5: Rediseño Carta Pública — Single Page Moderna (6h)

#### 5.1 Actualizar ProductController@index

```php
public function index(Category $category = null)
{
    $advices = Advice::where('status', 1)->orderBy('created_at', 'desc')->get();
    $showAlerts = $advices->count() > 0 ? $this->spash() : 0;

    // Todas las categorías visibles con sus productos ordenados
    $categories = Category::visible()
        ->with(['products' => function ($query) {
            $query->visible()->orderBy('order');
        }])
        ->get();

    // Ofertas activas
    $offers = Product::visible()
        ->withActiveOffer()
        ->orderBy('order')
        ->get();

    // Ya no necesitamos $showOffer ni la lógica de categoría individual
    return view('menu', compact('categories', 'offers', 'showAlerts', 'advices'));
}
```

**Eliminar** la ruta `/list/{category?}` y la vista `menulist.blade.php` (ya no necesarias).
**Eliminar** la ruta `/list/offer` y el método `offer()` (las ofertas van integradas).

#### 5.2 Nueva vista menu.blade.php — Diseño Premium Single Page

Reemplazar completamente `resources/views/menu.blade.php`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name') }} — Marisquería</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0e17;
            --surface: #111827;
            --surface-el: #1a2235;
            --gold: #c9a84c;
            --gold-light: #dfc06e;
            --gold-dim: #8a6d2b;
            --text: #e8e4dc;
            --text-muted: #9ca3af;
            --red: #dc2626;
            --red-bg: rgba(220,38,38,0.12);
            --font-title: 'Playfair Display', Georgia, serif;
            --font-body: 'Montserrat', system-ui, sans-serif;
            --radius: 12px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* HERO */
        .hero {
            text-align:center; padding:48px 20px 24px;
            background: linear-gradient(180deg, #111827, var(--bg));
            position: relative;
        }
        .hero::before {
            content:''; position:absolute; top:-60px; left:50%;
            transform:translateX(-50%); width:320px; height:320px;
            background:radial-gradient(circle, rgba(201,168,76,0.12), transparent 70%);
            pointer-events:none;
        }
        .hero-logo {
            width:68px; height:68px; border-radius:50%;
            border:2px solid var(--gold); margin:0 auto 14px;
            display:flex; align-items:center; justify-content:center;
            background:var(--surface); font-family:var(--font-title);
            font-size:22px; color:var(--gold); font-weight:700;
        }
        .hero h1 { font-family:var(--font-title); font-size:26px; color:var(--gold); letter-spacing:1px; }
        .hero p { font-size:11px; font-weight:500; letter-spacing:4px; text-transform:uppercase; color:var(--text-muted); margin-top:3px; }
        .hero-line { width:50px; height:1px; background:var(--gold-dim); margin:18px auto 0; }

        /* NAV STICKY */
        .nav { position:sticky; top:0; z-index:100; background:rgba(10,14,23,0.95); backdrop-filter:blur(12px); border-bottom:1px solid rgba(201,168,76,0.12); }
        .nav-inner { display:flex; overflow-x:auto; scrollbar-width:none; padding:0 12px; gap:2px; }
        .nav-inner::-webkit-scrollbar { display:none; }
        .nav-tab {
            flex-shrink:0; padding:13px 15px; font-size:11px; font-weight:500;
            letter-spacing:0.5px; text-transform:uppercase; color:var(--text-muted);
            text-decoration:none; border-bottom:2px solid transparent;
            transition:all .3s; white-space:nowrap; cursor:pointer;
        }
        .nav-tab:hover, .nav-tab.active { color:var(--gold); border-color:var(--gold); }
        .nav-tab--offer { color:var(--red); font-weight:700; }
        .nav-tab--offer:hover, .nav-tab--offer.active { color:var(--red); border-color:var(--red); }

        /* OFERTAS */
        .offers { padding:28px 16px 12px; }
        .offers-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:var(--red); color:#fff; font-size:11px; font-weight:700;
            letter-spacing:1.5px; text-transform:uppercase;
            padding:6px 14px; border-radius:20px; margin-bottom:16px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%,100% { box-shadow:0 0 0 0 rgba(220,38,38,0.4); }
            50% { box-shadow:0 0 0 8px rgba(220,38,38,0); }
        }
        .offers-scroll { display:flex; gap:14px; overflow-x:auto; scroll-snap-type:x mandatory; scrollbar-width:none; padding-bottom:8px; }
        .offers-scroll::-webkit-scrollbar { display:none; }
        .offer-card {
            flex-shrink:0; width:260px; scroll-snap-align:start;
            background:var(--surface); border-radius:var(--radius);
            overflow:hidden; border:1px solid rgba(220,38,38,0.2);
            transition:transform .3s;
        }
        .offer-card:active { transform:scale(0.98); }
        .offer-card img { width:100%; height:150px; object-fit:cover; display:block; }
        .offer-card-tag {
            position:absolute; top:10px; right:10px;
            background:var(--red); color:#fff; font-size:11px; font-weight:700;
            padding:4px 10px; border-radius:6px;
        }
        .offer-card-body { padding:12px 14px; }
        .offer-card-name { font-family:var(--font-title); font-size:15px; font-weight:700; margin-bottom:6px; }
        .offer-card-old { font-size:13px; color:var(--text-muted); text-decoration:line-through; }
        .offer-card-new { font-size:19px; font-weight:700; color:var(--red); margin-left:8px; }

        /* CATEGORÍA */
        .cat-section { padding:28px 16px 12px; max-width:600px; margin:0 auto; }
        .cat-title { font-family:var(--font-title); font-size:22px; color:var(--gold); margin-bottom:4px; scroll-margin-top:54px; }
        .cat-line { width:36px; height:2px; background:var(--gold-dim); margin-bottom:18px; }

        /* PRODUCTO */
        .prod {
            display:flex; gap:14px; background:var(--surface);
            border-radius:var(--radius); overflow:hidden; margin-bottom:12px;
            border:1px solid rgba(255,255,255,0.04); cursor:pointer;
            transition:transform .2s;
        }
        .prod:active { transform:scale(0.985); }
        .prod.has-offer { border-color:rgba(220,38,38,0.2); }
        .prod-img { flex-shrink:0; width:110px; min-height:110px; }
        .prod-img img { width:100%; height:100%; object-fit:cover; display:block; }
        .prod-img-tag {
            position:absolute; top:6px; left:6px;
            background:var(--red); color:#fff; font-size:9px; font-weight:700;
            letter-spacing:0.5px; text-transform:uppercase;
            padding:3px 7px; border-radius:4px;
        }
        .prod-body { flex:1; padding:12px 14px 12px 0; display:flex; flex-direction:column; justify-content:center; }
        .prod-name { font-family:var(--font-title); font-size:15px; font-weight:700; line-height:1.3; margin-bottom:4px; }
        .prod-desc {
            font-size:12px; color:var(--text-muted); line-height:1.4;
            display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
            overflow:hidden; margin-bottom:8px;
        }
        .prod-price { font-size:16px; font-weight:700; color:var(--gold); }
        .prod-price-old { font-size:13px; color:var(--text-muted); text-decoration:line-through; margin-right:6px; }
        .prod-price-offer { color:var(--red); }
        .prod-unit { font-size:10px; color:var(--text-muted); }

        /* MODAL */
        .modal-bg {
            display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85);
            z-index:1000; justify-content:center; align-items:flex-end;
            backdrop-filter:blur(4px);
        }
        .modal-bg.open { display:flex; }
        .modal {
            background:var(--surface); width:100%; max-width:500px; max-height:90vh;
            border-radius:20px 20px 0 0; overflow-y:auto;
            animation:slideUp .35s ease-out;
        }
        @keyframes slideUp { from{transform:translateY(100%);opacity:0} to{transform:translateY(0);opacity:1} }
        .modal img { width:100%; height:220px; object-fit:cover; }
        .modal-body { padding:20px 20px 32px; }
        .modal-name { font-family:var(--font-title); font-size:22px; font-weight:700; margin-bottom:8px; }
        .modal-desc { font-size:14px; line-height:1.6; color:var(--text-muted); margin-bottom:14px; }
        .modal-label { font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--gold); margin-bottom:6px; }
        .modal-info { font-size:13px; color:var(--text-muted); line-height:1.5; padding:10px 12px; background:rgba(255,255,255,0.03); border-radius:8px; border-left:3px solid var(--gold-dim); margin-bottom:12px; }
        .modal-info--pairing { background:rgba(201,168,76,0.06); border-left-color:var(--gold); }
        .modal-close {
            position:sticky; top:10px; float:right; margin:10px;
            width:36px; height:36px; border-radius:50%;
            background:rgba(0,0,0,0.6); color:#fff; border:none;
            font-size:20px; cursor:pointer; z-index:10;
            display:flex; align-items:center; justify-content:center;
        }

        /* SCROLL TOP */
        .scroll-top {
            position:fixed; bottom:20px; right:20px; width:44px; height:44px;
            border-radius:50%; background:var(--gold); color:var(--bg);
            border:none; cursor:pointer; display:none; align-items:center;
            justify-content:center; box-shadow:0 4px 24px rgba(0,0,0,0.4); z-index:50;
        }
        .scroll-top.show { display:flex; }

        /* FOOTER */
        footer { text-align:center; padding:32px 16px 24px; font-size:11px; color:var(--text-muted); }
        footer a { color:var(--gold-dim); text-decoration:none; }

        /* AVISO MODAL (reutilizar el existente) */
        .advice-overlay {
            position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2000;
            display:flex; align-items:center; justify-content:center;
        }
        .advice-box { background:#fff; max-width:480px; width:90%; border-radius:12px; padding:24px; }
        .advice-title { font-size:18px; font-weight:600; color:#111; margin-bottom:8px; }
        .advice-text { font-size:14px; color:#666; line-height:1.5; margin-bottom:12px; }
        .advice-btn {
            display:block; width:100%; padding:12px; background:var(--gold);
            color:#fff; border:none; border-radius:8px; font-size:14px;
            font-weight:600; cursor:pointer;
        }

        @media(min-width:600px) {
            .cat-section, .offers { max-width:600px; margin-left:auto; margin-right:auto; }
            .modal { border-radius:20px; margin:auto; }
        }
    </style>
</head>
<body>

    {{-- AVISOS (splash) --}}
    @if($showAlerts && $advices->count() > 0)
    <div class="advice-overlay" id="adviceOverlay" onclick="if(event.target===this)this.style.display='none'">
        <div class="advice-box">
            @foreach($advices as $advice)
                <h3 class="advice-title">{{ $advice->title }}</h3>
                <p class="advice-text">{{ $advice->advice }}</p>
            @endforeach
            <button class="advice-btn" onclick="document.getElementById('adviceOverlay').style.display='none'">
                Cerrar aviso
            </button>
        </div>
    </div>
    @endif

    {{-- HERO --}}
    <header class="hero">
        <div class="hero-logo">BJ</div>
        <h1>{{ config('app.name') }}</h1>
        <p>Marisquería</p>
        <div class="hero-line"></div>
    </header>

    {{-- NAV STICKY --}}
    <nav class="nav">
        <div class="nav-inner">
            @if($offers->count() > 0)
                <a href="#ofertas" class="nav-tab nav-tab--offer">🔥 Ofertas</a>
            @endif
            @foreach($categories as $cat)
                @if($cat->products->count() > 0)
                    <a href="#cat-{{ $cat->id }}" class="nav-tab">{{ $cat->name }}</a>
                @endif
            @endforeach
        </div>
    </nav>

    <main>
        {{-- OFERTAS DEL DÍA --}}
        @if($offers->count() > 0)
        <section class="offers" id="ofertas" style="scroll-margin-top:54px">
            <div class="offers-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Ofertas del día
            </div>
            <div class="offers-scroll">
                @foreach($offers as $product)
                <div class="offer-card" style="position:relative" onclick="openModal({{ $product->id }})">
                    <img src="/storage/{{ $product->photo ?? 'img/noimg.png' }}" alt="{{ $product->name }}" loading="lazy">
                    <span class="offer-card-tag">{{ $product->offer_badge ?? 'Oferta' }}</span>
                    <div class="offer-card-body">
                        <div class="offer-card-name">{{ $product->name }}</div>
                        <div>
                            <span class="offer-card-old">{{ $product->price }}</span>
                            <span class="offer-card-new">{{ $product->offer_price }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- CATEGORÍAS CON PRODUCTOS --}}
        @foreach($categories as $cat)
            @if($cat->products->count() > 0)
            <section class="cat-section">
                <h2 class="cat-title" id="cat-{{ $cat->id }}">{{ $cat->name }}</h2>
                <div class="cat-line"></div>

                @foreach($cat->products as $product)
                <div class="prod {{ $product->isOfferActive() ? 'has-offer' : '' }}"
                     onclick="openModal({{ $product->id }})">
                    <div class="prod-img" style="position:relative">
                        <img src="/storage/{{ $product->photo ?? 'img/noimg.png' }}"
                             alt="{{ $product->name }}" loading="lazy">
                        @if($product->isOfferActive())
                            <span class="prod-img-tag">{{ $product->offer_badge ?? 'Oferta' }}</span>
                        @endif
                    </div>
                    <div class="prod-body">
                        <div class="prod-name">{{ $product->name }}</div>
                        <div class="prod-desc">{{ $product->description }}</div>
                        <div>
                            @if($product->isOfferActive())
                                <span class="prod-price-old">{{ $product->price }}</span>
                                <span class="prod-price prod-price-offer">{{ $product->offer_price }}</span>
                            @else
                                <span class="prod-price">{{ $product->price }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </section>
            @endif
        @endforeach
    </main>

    {{-- MODAL --}}
    <div class="modal-bg" id="modalBg" onclick="if(event.target===this)closeModal()">
        <div class="modal" id="modalContent"></div>
    </div>

    {{-- SCROLL TOP --}}
    <button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>

    <footer>
        <p>© <script>document.write(new Date().getFullYear())</script> {{ config('app.name') }} · Marisquería</p>
        <p style="margin-top:4px">Hecho con ♥ por <a href="https://cositt.com/" target="_blank">Cositt Technology®</a></p>
    </footer>

    {{-- DATOS PARA JS --}}
    <script>
    const PRODUCTS_DATA = @json($categories->flatMap->products->keyBy('id')->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'description' => $p->description,
        'price' => $p->price,
        'offer_price' => $p->offer_price,
        'offer' => $p->isOfferActive(),
        'offer_badge' => $p->offer_badge ?? 'Oferta',
        'photo' => $p->photo,
        'aller' => $p->aller,
        'pairing' => $p->pairing?->description,
    ]));

    function openModal(id) {
        const p = PRODUCTS_DATA[id];
        if (!p) return;

        let html = `
            <button class="modal-close" onclick="closeModal()">✕</button>
            <img src="/storage/${p.photo || 'img/noimg.png'}" alt="${p.name}">
            <div class="modal-body">
                <div class="modal-name">${p.name}</div>
                <div class="modal-desc">${p.description || ''}</div>
                ${p.aller ? `<p class="modal-label">Alérgenos</p><div class="modal-info">${p.aller}</div>` : ''}
                ${p.pairing ? `<p class="modal-label">Maridaje</p><div class="modal-info modal-info--pairing">${p.pairing}</div>` : ''}
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.06)">
                    ${p.offer ?
                        `<span style="font-size:16px;color:var(--text-muted);text-decoration:line-through">${p.price}</span>
                         <span style="font-size:24px;font-weight:700;color:var(--red);margin-left:8px">${p.offer_price}</span>
                         <span style="display:inline-block;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:3px 8px;border-radius:4px;margin-left:8px">${p.offer_badge}</span>`
                        :
                        `<span style="font-size:24px;font-weight:700;color:var(--gold)">${p.price}</span>`
                    }
                </div>
            </div>`;

        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('modalBg').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('modalBg').classList.remove('open');
        document.body.style.overflow = '';
    }

    // Scroll spy para tabs activos
    const sections = document.querySelectorAll('.cat-title, #ofertas');
    const tabs = document.querySelectorAll('.nav-tab');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                tabs.forEach(tab => {
                    tab.classList.toggle('active', tab.getAttribute('href') === '#' + id);
                });
            }
        });
    }, { rootMargin: '-20% 0px -70% 0px' });
    sections.forEach(s => observer.observe(s));

    // Scroll to top button
    window.addEventListener('scroll', () => {
        document.getElementById('scrollTop').classList.toggle('show', window.scrollY > 400);
    });

    // ESC para cerrar modal
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    </script>

</body>
</html>
```

---

### FASE 6: Limpieza y Deploy (3h)

#### Archivos a eliminar
- `resources/views/menulist.blade.php` (ya no se usa)
- `resources/views/menu.blade.php.bak` (backup viejo)

#### Rutas a limpiar en routes/web.php
```php
// ELIMINAR estas líneas:
// Route::get('/list/offer',[ProductController::class,'offer'])->name('offer');
// Route::get('/list/{category?}',[ProductController::class,'index'])->name('menulist');

// DEJAR solo:
Route::get('/',[ProductController::class,'index'])->name('menu');
```

#### Testing checklist
- [ ] Carta pública: todas las categorías se ven en scroll
- [ ] Ofertas del día aparecen arriba
- [ ] Modal de detalle funciona
- [ ] Nav sticky resalta la categoría visible
- [ ] Admin: drag & drop categorías guarda el orden
- [ ] Admin: drag & drop productos guarda el orden
- [ ] Admin: crear/editar producto con oferta (badge, fechas)
- [ ] Responsive en móvil (360px - 428px)
- [ ] Imágenes con lazy loading
- [ ] Productos ordenados correctamente en la carta

---

## PROMPT PARA CURSOR

Cuando abras el proyecto en Cursor, usa este prompt inicial:

```
Estoy modificando una carta digital Laravel 8 + Livewire 2 existente.
Lee el archivo MODIFICATIONS.md que contiene todas las especificaciones.

Empieza por la FASE 1: crea la migración para añadir los campos
'order', 'icon', 'offer_badge', 'offer_start', 'offer_end' y 'featured'.

Después actualiza los modelos Product y Category según la especificación.
```

Para cada fase siguiente, dile a Cursor:
```
Continúa con la FASE X del archivo MODIFICATIONS.md
```

---

## NOTAS IMPORTANTES

1. **La lógica active está invertida**: `active=0` = visible. NO cambiar esto en la v2 porque rompería los datos existentes. Los scopes `scopeVisible()` lo manejan.

2. **Los precios son STRING**: Los precios están guardados como texto (ej: "15,00€", "19,00€ (100 GRAMOS)"). NO convertir a DECIMAL en esta versión porque habría que limpiar todos los datos. Dejarlo como string por ahora.

3. **Las imágenes** están en `/storage/img/` con nombres hash. No tocar las rutas, `/storage/` está linkeado a `/public/storage/`.

4. **CSRF en las llamadas API de SortableJS**: Las peticiones fetch necesitan el header X-CSRF-TOKEN. El meta tag csrf ya está en el layout de Jetstream.

5. **Livewire y SortableJS**: Livewire re-renderiza el DOM, lo que puede romper SortableJS. Si hay conflictos, usar `wire:ignore` en el contenedor sortable para que Livewire no lo toque.

---

*Documento generado para Cositt Technology® — Proyecto Carta Bar Jaén III*
*Marzo 2026*
