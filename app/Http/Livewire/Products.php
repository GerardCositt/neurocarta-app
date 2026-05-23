<?php

namespace App\Http\Livewire;

use App\Exceptions\InsufficientAiCreditsException;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Pairing;
use App\Models\Setting;
use App\Services\AiCreditService;
use App\Services\ImageAssetService;
use App\Services\OpenAiService;
use App\Services\PlanEntitlementService;
use App\Support\CaseInsensitiveLike;
use App\Support\DemoContent;
use App\Support\DemoProductPhotoResolver;
use App\Support\OfficialAllergens;
use App\Support\PlanFeatureGate;
use App\Services\ProductImageAiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class Products extends Component
{
    use WithFileUploads;
    use WithPagination;
    use Concerns\ManagesProductBulkActions;
    use Concerns\ManagesProductAi;
    use Concerns\ManagesProductDemoContent;

    public $name, $description, $product_id, $category_id, $pairing_id;
    public $price, $offer_price, $offer_badge, $offer_start, $offer_end;

    /** @var bool */
    public $offer = false;

    /** @var bool Destacado en carta (prioridad de orden). */
    public $featured = false;

    /** @var bool Recomendado / sugerido al cliente. */
    public $recommended = false;

    public $photo, $filename, $aller;
    public $selectedAllergens = [];
    /** @var int 0 = ficha cerrada, 1 = abierta (entero para serialización Livewire) */
    public $isOpen = 0;

    /** Evita que OptimizeRenderedDom omita el HTML cuando solo cambia el modal (hash CRC32 igual). */
    public $panelRenderNonce = 0;
    public $q;
    public $selectedCategory = '';

    /** @var string Filtro comercial: featured, recommended, offer_active, offer_flag, hidden */
    public $commercialFilter = '';

    /** @var array<int> Selección para acciones masivas (IDs). */
    public $selectedProducts = [];

    /** @var string 15 | 30 | 50 | all — cuántos registros mostrar por página (por defecto 15). */
    public $perPageOption = '15';

    public $confirmingLoadDemo = false;
    public $confirmingDeleteDemo = false;
    public $showingDemoWarning = false;
    public $demoWarningPendingAction = 'create'; // 'create' | 'csv' | 'ai' | 'photos'

    public $confirmingProductDeletion = false;
    public $pendingProductDeletionId = null;

    public $confirmingProductPhotoRemoval = false;
    public $confirmingAiAction = false;
    public $pendingAiAction = null;
    public $missingPhotosCount = 0;
    public $confirmingMissingCategory = false;

    /** @var string|null URL interna a la que volver al cerrar la ficha (p. ej. tras abrirla desde alérgenos). */
    public $returnAfterCloseUrl = null;

    /** Marca fila cuya oferta se está configurando (modal abierto sin guardar → checkbox en tabla coherente). */
    public $offerFormOpenedForId = null;

    protected $queryString = [
        'q' => ['except' => ''],
        'perPageOption' => ['except' => '15'],
        'commercialFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        if (request()->query('from') === 'allergen') {
            $this->returnAfterCloseUrl = route('allergen_list');
        } elseif (request()->query('from') === 'pairing') {
            $this->returnAfterCloseUrl = route('pairing');
        }

        $e = request()->query('edit');
        if ($e !== null && $e !== '' && ctype_digit((string) $e)) {
            try {
                $this->loadProductForEdit((int) $e);
                $this->openModal();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
                // Producto inexistente: se ignora el parámetro
            }
        }
    }

    public function updatingQ(): void
    {
        $this->selectedProducts = [];
        $this->resetPage();
    }

    public function updatingSelectedCategory(): void
    {
        $this->selectedProducts = [];
        $this->resetPage();
    }

    public function updatingPerPageOption(): void
    {
        $this->selectedProducts = [];
        $this->resetPage();
    }

    public function updatingCommercialFilter($value): void
    {
        $allowed = ['', 'featured', 'recommended', 'offer_active', 'offer_flag', 'hidden'];
        if (! in_array((string) $value, $allowed, true)) {
            $this->commercialFilter = '';
        }
        $this->selectedProducts = [];
        $this->resetPage();
    }

    public function toggleProductSelection(int $id): void
    {
        $id = (int) $id;
        $ids = array_map('intval', $this->selectedProducts ?? []);
        if (in_array($id, $ids, true)) {
            $this->selectedProducts = array_values(array_diff($ids, [$id]));
        } else {
            $this->selectedProducts = array_values(array_unique(array_merge($ids, [$id])));
        }
    }

    protected function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    /** El sidebar «Ver carta» solo montaba una vez; tras crear/editar desde Livewire hay que refrescar el estado. */
    protected function notifyNavigationMenuRefresh(): void
    {
        $this->emit('navigationMenuRefresh');
    }

    private function imageAssets(): ImageAssetService
    {
        return app(ImageAssetService::class);
    }

    protected function aiCredits(): AiCreditService
    {
        return app(AiCreditService::class);
    }

    private function normalizedCommercialFilter(): string
    {
        $f = (string) $this->commercialFilter;
        $allowed = ['featured', 'recommended', 'offer_active', 'offer_flag', 'hidden'];

        return in_array($f, $allowed, true) ? $f : '';
    }

    protected function buildFilteredProductQuery(): Builder
    {
        $restaurantId = $this->getRestaurantId();

        if (! $restaurantId) {
            return Product::query()->whereRaw('1 = 0');
        }

        $query = Product::query()->where('restaurant_id', $restaurantId);

        $search = trim((string) ($this->q ?? ''));
        if ($search !== '') {
            CaseInsensitiveLike::applyWhere($query, 'products.name', $search);
        }

        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }

        $commercialFilter = $this->normalizedCommercialFilter();

        if ($commercialFilter === 'featured') {
            $query->where('featured', true);
        } elseif ($commercialFilter === 'recommended') {
            $query->where('recommended', true);
        } elseif ($commercialFilter === 'offer_active') {
            $query->withActiveOffer();
        } elseif ($commercialFilter === 'offer_flag') {
            $query->where('offer', true);
        } elseif ($commercialFilter === 'hidden') {
            $query->where('hidden', true);
        }

        return $query;
    }

    public function render()
    {
        if (! in_array($this->perPageOption, ['15', '30', '50', 'all'], true)) {
            $this->perPageOption = '15';
        }

        $commercialFilter = $this->normalizedCommercialFilter();

        $query = $this->buildFilteredProductQuery()
            ->orderBy('order')
            ->with('allergens');

        if ($this->perPageOption === 'all') {
            $products = $query->get();
        } else {
            $products = $query->paginate((int) $this->perPageOption);
        }

        $restaurantId = $this->getRestaurantId();

        $navIds = ($this->isOpen && $this->product_id)
            ? \App\Models\Product::where('restaurant_id', $restaurantId)->orderBy('order')->orderBy('id')->pluck('id')->toArray()
            : [];
        $navIdx = $navIds ? array_search((int) $this->product_id, $navIds, true) : false;
        $navPosition = ($navIdx !== false) ? $navIdx + 1 : null;
        $navTotal = count($navIds);

        $categoriesQuery = Category::orderBy('order');
        if ($restaurantId) {
            $categoriesQuery->where('restaurant_id', $restaurantId);
        }

        $pairingsQuery = Pairing::query();
        if ($restaurantId) {
            $pairingsQuery->where('restaurant_id', $restaurantId);
        }

        $hasNoProducts = $restaurantId
            ? ! \App\Models\Product::where('restaurant_id', $restaurantId)->exists()
            : false;
        $hasOnlyDemoProducts = $restaurantId
            ? $this->restaurantCanBulkDeleteTemplate((int) $restaurantId)
            : false;

        return view('livewire.products', [
            'products'              => $products,
            'hasNoProducts'         => $hasNoProducts,
            'hasOnlyDemoProducts'   => $hasOnlyDemoProducts,
            'commercialFilterNorm'  => $commercialFilter,
            'allowProductDragSort'  => $commercialFilter === '',
            'aiCredits'             => $this->aiCredits()->summary(),
            'aiGenerateCost'        => $this->aiCredits()->cost(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE),
            'aiImproveCost'         => $this->aiCredits()->cost(AiCreditService::ACTION_IMPROVE_PRODUCT_IMAGE),
            'aiBulkGenerateCost'    => $this->aiCredits()->cost(AiCreditService::ACTION_BULK_GENERATE_PRODUCT_IMAGES),
            'aiDescriptionCost'     => $this->aiCredits()->cost(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION),
            'aiAllergenTextCost'    => $this->aiCredits()->cost(AiCreditService::ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT),
            'aiWritingGuideConnected'=> $this->hasAiWritingGuide(),
            'categories'            => $categoriesQuery->get(),
            'pairings'              => $pairingsQuery->get(),
            'allergens'             => Allergen::query()
                ->orderByDesc('is_official')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'canUseAi'              => PlanFeatureGate::allows('ai'),
            'canUseCsvImport'       => PlanFeatureGate::allows('csv_import'),
            'canUseOffers'          => PlanFeatureGate::allows('offers'),
            'currencySymbol'        => \App\Models\Restaurant::find($restaurantId)?->currencySymbol() ?? '€',
            'navPosition'           => $navPosition,
            'navTotal'              => $navTotal,
        ]);
    }

    public function toggleState(Product $product): void
    {
        $product->hidden = !$product->hidden;
        $product->save();
    }

    public function toggleFeatured(int $id): void
    {
        if (! PlanFeatureGate::allows('offers')) {
            session()->flash('plan_error', __('admin.plan.offers_required'));
            return;
        }
        $product = $this->findOwnedProductOrFail($id);
        $product->featured = ! $product->featured;
        $product->save();
    }

    public function toggleRecommended(int $id): void
    {
        $product = $this->findOwnedProductOrFail($id);
        $product->recommended = ! $product->recommended;
        $product->save();
    }

    private function findOwnedProductOrFail(int $id): Product
    {
        $q = Product::query()->where('id', $id);
        $rid = $this->getRestaurantId();
        if ($rid) {
            $q->where('restaurant_id', $rid);
        }

        return $q->firstOrFail();
    }

    /**
     * Desde la tabla: si la oferta está apagada, abre la ficha con oferta activada para rellenar precio;
     * si está encendida, la apaga sin abrir el modal.
     */
    public function offerToggleFromTable(int $id): void
    {
        if (! PlanFeatureGate::allows('offers')) {
            session()->flash('plan_error', __('admin.plan.offers_required'));
            return;
        }
        $product = Product::findOrFail($id);
        if ($product->offer) {
            $product->offer = false;
            $product->save();
            $this->offerFormOpenedForId = null;

            return;
        }

        $this->offerFormOpenedForId = $id;
        $this->loadProductForEdit($id);
        $this->offer = true;
        $this->openModal();
    }

    public function create()
    {
        $restaurantId = $this->getRestaurantId();
        if ($restaurantId && ! \App\Models\Category::where('restaurant_id', $restaurantId)->exists()) {
            $this->confirmingMissingCategory = true;
            return;
        }

        $restaurantId = $this->getRestaurantId();
        if ($restaurantId && ! \App\Models\Product::where('restaurant_id', $restaurantId)->exists()) {
            $this->demoWarningPendingAction = 'create';
            $this->showingDemoWarning = true;
            return;
        }
        $this->returnAfterCloseUrl = null;
        $this->offerFormOpenedForId = null;
        $this->resetInputFields();
        $this->openModal();
    }

    public function cancelMissingCategoryWarning(): void
    {
        $this->confirmingMissingCategory = false;
    }

    public function goToCategoriesFromWarning()
    {
        $this->confirmingMissingCategory = false;

        return redirect()->route('category_list');
    }

    public function interceptIfEmpty(string $action): void
    {
        $restaurantId = $this->getRestaurantId();
        if ($restaurantId && ! \App\Models\Product::where('restaurant_id', $restaurantId)->exists()) {
            $this->demoWarningPendingAction = $action;
            $this->showingDemoWarning = true;
            return;
        }
        $this->proceedAction($action);
    }

    private function proceedAction(string $action): void
    {
        match ($action) {
            'csv'    => $this->redirect(route('settings.import-products')),
            'ai'     => $this->redirect(route('settings.import-ai')),
            'photos' => $this->generateMissingProductPhotos(),
            default  => (function () {
                $this->returnAfterCloseUrl = null;
                $this->offerFormOpenedForId = null;
                $this->resetInputFields();
                $this->openModal();
            })(),
        };
    }

    public function proceedCreateDespiteDemo(): void
    {
        $this->showingDemoWarning = false;
        $this->proceedAction($this->demoWarningPendingAction);
    }

    public function loadDemoFromWarning(): void
    {
        $this->showingDemoWarning = false;
        $this->loadDemoContent();
    }

    public function openModal()
    {
        $this->isOpen = 1;
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function closeModal()
    {
        if ($this->isOpen) {
            $this->panelRenderNonce++;
        }
        $this->isOpen = 0;
        $this->offerFormOpenedForId = null;

        if ($this->returnAfterCloseUrl) {
            $url = $this->returnAfterCloseUrl;
            $this->returnAfterCloseUrl = null;

            return redirect()->to($url);
        }

        return null;
    }

    private function resetInputFields()
    {
        $this->name         = '';
        $this->category_id  = '';
        $this->price        = '0';
        $this->offer        = false;
        $this->featured     = false;
        $this->recommended  = false;
        $this->offer_price  = '';
        $this->offer_badge  = 'Oferta';
        $this->offer_start  = '';
        $this->offer_end    = '';
        $this->description  = '';
        $this->pairing_id   = '';
        $this->product_id   = '';
        $this->photo             = '';
        $this->filename          = '';
        $this->aller             = '';
        $this->selectedAllergens = [];
        $this->offerFormOpenedForId = null;
    }

    public function store()
    {
        $this->persistProduct();

        return null;
    }

    public function storeAndClose()
    {
        $persistReturn = $this->persistProduct();
        if ($persistReturn !== null) {
            return $persistReturn;
        }

        $this->resetInputFields();

        $closeReturn = $this->closeModal();
        if ($closeReturn !== null) {
            return $closeReturn;
        }

        $url = route('product');
        $this->dispatchBrowserEvent('product-stored-navigate', ['url' => $url]);
        $this->redirectRoute('product');
    }

    public function saveAndNext(): void
    {
        $this->persistProduct();
        $next = $this->adjacentProductId(1);
        if ($next) $this->edit($next);
    }

    public function saveAndPrev(): void
    {
        $this->persistProduct();
        $prev = $this->adjacentProductId(-1);
        if ($prev) $this->edit($prev);
    }

    private function adjacentProductId(int $dir): ?int
    {
        $rid = $this->getRestaurantId();
        $ids = \App\Models\Product::where('restaurant_id', $rid)
            ->orderBy('order')->orderBy('id')->pluck('id')->toArray();
        $pos = array_search((int) $this->product_id, $ids, true);
        if ($pos === false) return null;
        return $ids[$pos + $dir] ?? null;
    }

    private function persistProduct()
    {
        $data = $this->validate([
            'name'                => 'required',
            'category_id'         => 'required',
            'price'               => 'required',
            'offer'               => 'boolean',
            'featured'            => 'boolean',
            'recommended'         => 'boolean',
            'offer_price'         => '',
            'offer_badge'         => '',
            'offer_start'         => 'nullable|date',
            'offer_end'           => 'nullable|date',
            'description'         => 'nullable|string|max:20000',
            'pairing_id'          => '',
            'photo'               => '',
            'filename'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'aller'               => 'nullable|string|max:5000',
            'selectedAllergens'   => 'nullable|array',
            'selectedAllergens.*' => 'integer|exists:allergens,id',
        ], [
            'filename.image' => 'La foto debe ser una imagen.',
            'filename.mimes' => 'La foto debe ser JPG, PNG o WebP. No se admiten SVG ni GIF.',
            'filename.max'   => 'La foto no puede superar 8 MB.',
            'category_id.required' => __('admin.product_form.category_required_create_first'),
        ]);

        if ($data['filename'] != null) {
            if ($data['photo'] != null && ! Str::startsWith($data['photo'], ['http://', 'https://'])) {
                Storage::disk('public')->delete($data['photo']);
            }
            try {
                $data['photo'] = $this->imageAssets()->storeUploadedImage($this->filename, 'img', 1600);
            } catch (\RuntimeException $e) {
                $this->addError('filename', $e->getMessage());
                return null;
            }
        } else {
            unset($data['photo']);
        }

        $pid = $this->pairing_id;
        $data['pairing_id'] = ($pid === '' || $pid === null || $pid === 0 || $pid === '0') ? null : $pid;

        $data['offer_start'] = filled($data['offer_start'] ?? null) ? $data['offer_start'] : null;
        $data['offer_end']   = filled($data['offer_end'] ?? null) ? $data['offer_end'] : null;

        if (! PlanFeatureGate::allows('offers')) {
            $data['offer']    = false;
            $data['featured'] = false;
        } else {
            $data['offer']    = (bool) ($data['offer'] ?? false);
            $data['featured'] = (bool) ($data['featured'] ?? false);
        }
        $data['recommended'] = (bool) ($data['recommended'] ?? false);

        unset($data['selectedAllergens']);
        unset($data['filename']);

        $allergenIds = array_values(array_unique(array_map('intval', $this->selectedAllergens ?? [])));
        $data['aller'] = $this->aller !== '' ? $this->aller : null;

        if ($this->product_id) {
            $product = Product::findOrFail($this->product_id);
            $product->update($data);
        } else {
            $svc = app(PlanEntitlementService::class);
            $rid = $this->getRestaurantId();
            $restaurant = $rid ? \App\Models\Restaurant::find($rid) : null;
            $account = $svc->accountForRestaurant($restaurant);
            if ($account) {
                try {
                    $svc->assertCanCreateProduct($account);
                } catch (\RuntimeException $e) {
                    session()->flash('plan_error', $e->getMessage());
                    return null;
                }
            }

            $data['restaurant_id'] = $this->getRestaurantId();
            $product = Product::create($data);
        }

        $product->allergens()->sync($allergenIds);

        $product->forceFill(['is_template' => false])->save();

        session()->flash('message',
            $this->product_id ? __('admin.products.flash_saved_update') : __('admin.products.flash_saved_create'));

        $this->notifyNavigationMenuRefresh();

        if (! $this->product_id) {
            $this->product_id = $product->id;
        }

        $this->photo = $product->photo;
        $this->filename = '';
        if ($this->returnAfterCloseUrl) {
            $url = $this->returnAfterCloseUrl;
            $this->returnAfterCloseUrl = null;

            return redirect()->to($url);
        }

        return null;
    }

    public function edit($id): void
    {
        $this->offerFormOpenedForId = null;
        $this->loadProductForEdit((int) $id);
        $this->openModal();
    }

    private function loadProductForEdit(int $id): void
    {
        $product = Product::with('allergens')->findOrFail($id);

        $this->product_id  = $id;
        $this->name        = $product->name;
        $this->category_id = $product->category_id;
        $this->price       = $product->price;
        $this->offer       = (bool) $product->offer;
        $this->featured    = (bool) $product->featured;
        $this->recommended = (bool) $product->recommended;
        $this->offer_price = $product->offer_price;
        $this->offer_badge = $product->offer_badge ?? 'Oferta';
        $this->offer_start = $product->offer_start ? $product->offer_start->format('Y-m-d') : '';
        $this->offer_end   = $product->offer_end ? $product->offer_end->format('Y-m-d') : '';
        $this->description = $product->description;
        $this->pairing_id  = $product->pairing_id;
        $this->photo       = $product->photo;
        $this->aller       = $product->aller;
        $this->selectedAllergens = array_values(array_map(
            'intval',
            $product->allergens->pluck('id')->all()
        ));
        $this->filename          = '';
    }

    public function confirmDeleteProduct(int $id): void
    {
        $this->pendingProductDeletionId = $id;
        $this->confirmingProductDeletion = true;
    }

    /** Eliminar desde la ficha del producto (modal). */
    public function confirmDeleteCurrentProduct(): void
    {
        if (! $this->product_id) {
            return;
        }
        $this->confirmDeleteProduct((int) $this->product_id);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function deleteProductConfirmed()
    {
        $id = $this->pendingProductDeletionId;
        $this->pendingProductDeletionId = null;
        $this->confirmingProductDeletion = false;
        if (!$id) {
            return null;
        }
        try {
            $product = Product::with('allergens')->find($id);
            if (!$product) {
                session()->flash('message', __('admin.products.flash_missing'));

                return null;
            }

            $product->allergens()->detach();

            if ($product->photo && ! Str::startsWith($product->photo, ['http://', 'https://'])) {
                Storage::disk('public')->delete($product->photo);
            }
            $product->delete();

            $this->resetPage();
            session()->flash('message', __('admin.products.flash_deleted'));
            $this->notifyNavigationMenuRefresh();
            $this->resetInputFields();

            return $this->closeModal();
        } catch (\Throwable $e) {
            session()->flash('message', __('admin.products.flash_delete_fail'));
        }

        return null;
    }

    public function confirmRemoveProductPhoto(): void
    {
        if ($this->product_id && $this->photo) {
            $this->confirmingProductPhotoRemoval = true;
        }
    }

    public function removeProductPhotoConfirmed(): void
    {
        $this->confirmingProductPhotoRemoval = false;
        if (!$this->product_id || !$this->photo) {
            return;
        }
        $product = Product::find($this->product_id);
        if ($product && $product->photo) {
            if (! Str::startsWith($product->photo, ['http://', 'https://'])) {
                Storage::disk('public')->delete($product->photo);
            }
            $product->photo = null;
            $product->save();
        }
        $this->photo = '';
        session()->flash('message', __('admin.products.flash_photo_removed'));
    }


}
