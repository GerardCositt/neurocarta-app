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
use App\Services\ProductImageAiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Products extends Component
{
    use WithFileUploads;
    use WithPagination;

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

    public $confirmingProductDeletion = false;
    public $pendingProductDeletionId = null;

    public $confirmingProductPhotoRemoval = false;
    public $confirmingAiAction = false;
    public $pendingAiAction = null;

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

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    private function imageAssets(): ImageAssetService
    {
        return app(ImageAssetService::class);
    }

    private function productImageAi(): ProductImageAiService
    {
        return app(ProductImageAiService::class);
    }

    private function openAi(): OpenAiService
    {
        return app(OpenAiService::class);
    }

    private function aiCredits(): AiCreditService
    {
        return app(AiCreditService::class);
    }

    private function notifyAiCreditsChanged(): void
    {
        $this->emit('aiCreditsUpdated');
    }

    private function normalizedCommercialFilter(): string
    {
        $f = (string) $this->commercialFilter;
        $allowed = ['featured', 'recommended', 'offer_active', 'offer_flag', 'hidden'];

        return in_array($f, $allowed, true) ? $f : '';
    }

    private function buildFilteredProductQuery(): Builder
    {
        $restaurantId = $this->getRestaurantId();
        $query = Product::query();

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        if ($this->q) {
            $query->where('name', 'like', '%' . $this->q . '%');
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
            $query->where('active', true);
        }

        return $query;
    }

    /** IDs seleccionados que pertenecen al restaurante actual. */
    private function validatedSelectedIds(): array
    {
        $ids = array_values(array_filter(array_map('intval', $this->selectedProducts ?? [])));
        if ($ids === []) {
            return [];
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }

        return $q->pluck('id')->all();
    }

    /** Página activa del listado (misma que usa el paginador en la vista). */
    private function currentListPage(): int
    {
        $p = (int) (data_get($this->paginators, 'page', $this->page ?? 1) ?: 1);

        return $p >= 1 ? $p : 1;
    }

    public function toggleSelectCurrentPage(): void
    {
        $query = $this->buildFilteredProductQuery()->orderBy('order');

        if ($this->perPageOption === 'all') {
            $ids = $query->pluck('id')->map(static fn ($id) => (int) $id)->all();
        } else {
            $perPage = (int) $this->perPageOption;
            $currentPage = $this->currentListPage();
            $paginator = $query->paginate($perPage, ['*'], 'page', $currentPage);
            $ids = $paginator->getCollection()->pluck('id')->map(static fn ($id) => (int) $id)->all();
        }

        if ($ids === []) {
            return;
        }

        $selectedInts = array_map('intval', $this->selectedProducts ?? []);
        $allOnPageSelected = count(array_intersect($ids, $selectedInts)) === count($ids);

        if ($allOnPageSelected) {
            $this->selectedProducts = array_values(array_diff($selectedInts, $ids));
        } else {
            $this->selectedProducts = array_values(array_unique(array_merge($selectedInts, $ids)));
        }
    }

    public function clearBulkSelection(): void
    {
        $this->selectedProducts = [];
    }

    public function bulkSetFeatured(bool $on): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['featured' => $on]);

        $this->selectedProducts = [];
        session()->flash('message', $on ? __('admin.products.flash_bulk_featured_on') : __('admin.products.flash_bulk_featured_off'));
    }

    public function bulkSetRecommended(bool $on): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['recommended' => $on]);

        $this->selectedProducts = [];
        session()->flash('message', $on ? __('admin.products.flash_bulk_recommended_on') : __('admin.products.flash_bulk_recommended_off'));
    }

    public function bulkShowOnMenu(): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['active' => false]);

        $this->selectedProducts = [];
        session()->flash('message', __('admin.products.flash_bulk_visible'));
    }

    public function bulkHideFromMenu(): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['active' => true]);

        $this->selectedProducts = [];
        session()->flash('message', __('admin.products.flash_bulk_hidden'));
    }

    /** Quita solo el flag de oferta; no borra precios por si quieres reactivarlas. */
    public function bulkClearOfferFlag(): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['offer' => false]);

        $this->selectedProducts = [];
        session()->flash('message', __('admin.products.flash_bulk_offer_cleared'));
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

        $categoriesQuery = Category::orderBy('order');
        if ($restaurantId) {
            $categoriesQuery->where('restaurant_id', $restaurantId);
        }

        $pairingsQuery = Pairing::query();
        if ($restaurantId) {
            $pairingsQuery->where('restaurant_id', $restaurantId);
        }

        return view('livewire.products', [
            'products'              => $products,
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
        ]);
    }

    public function toggleState(Product $product): void
    {
        $product->active = !$product->active;
        $product->save();
    }

    public function toggleFeatured(int $id): void
    {
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
        $this->returnAfterCloseUrl = null;
        $this->offerFormOpenedForId = null;
        $this->resetInputFields();
        $this->openModal();
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
            'filename'            => 'nullable|image|mimes:jpg,jpeg,png,webp,svg,gif|max:8192',
            'aller'               => 'nullable|string|max:5000',
            'selectedAllergens'   => 'nullable|array',
            'selectedAllergens.*' => 'integer|exists:allergens,id',
        ]);

        if ($data['filename'] != null) {
            if ($data['photo'] != null) {
                Storage::disk('public')->delete($data['photo']);
            }
            $data['photo'] = $this->imageAssets()->storeUploadedImage($this->filename, 'img', 1600);
        } else {
            unset($data['photo']);
        }

        $pid = $this->pairing_id;
        $data['pairing_id'] = ($pid === '' || $pid === null || $pid === 0 || $pid === '0') ? null : $pid;

        $data['offer_start'] = filled($data['offer_start'] ?? null) ? $data['offer_start'] : null;
        $data['offer_end']   = filled($data['offer_end'] ?? null) ? $data['offer_end'] : null;
        $data['offer']       = (bool) ($data['offer'] ?? false);
        $data['featured']    = (bool) ($data['featured'] ?? false);
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
                    session()->flash('message', $e->getMessage());
                    return null;
                }
            }

            $data['restaurant_id'] = $this->getRestaurantId();
            $product = Product::create($data);
        }

        $product->allergens()->sync($allergenIds);

        session()->flash('message',
            $this->product_id ? __('admin.products.flash_saved_update') : __('admin.products.flash_saved_create'));

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

            if ($product->photo) {
                Storage::disk('public')->delete($product->photo);
            }
            $product->delete();

            $this->resetPage();
            session()->flash('message', __('admin.products.flash_deleted'));
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
            Storage::disk('public')->delete($product->photo);
            $product->photo = null;
            $product->save();
        }
        $this->photo = '';
        session()->flash('message', __('admin.products.flash_photo_removed'));
    }

    public function confirmGenerateCurrentProductPhotoWithAi(): void
    {
        $this->pendingAiAction = 'generate_current_product_photo';
        $this->confirmingAiAction = true;
    }

    public function confirmImproveCurrentProductPhotoWithAi(): void
    {
        $this->pendingAiAction = 'improve_current_product_photo';
        $this->confirmingAiAction = true;
    }

    public function confirmGenerateMissingProductPhotos(): void
    {
        $this->pendingAiAction = 'generate_missing_product_photos';
        $this->confirmingAiAction = true;
    }

    public function confirmGenerateDescriptionWithAi(): void
    {
        $this->pendingAiAction = 'generate_description';
        $this->confirmingAiAction = true;
    }

    public function confirmGenerateAllergenTextWithAi(): void
    {
        $this->pendingAiAction = 'generate_allergen_text';
        $this->confirmingAiAction = true;
    }

    public function cancelAiActionConfirmation(): void
    {
        $this->confirmingAiAction = false;
        $this->pendingAiAction = null;
    }

    public function confirmAiAction(): void
    {
        $action = $this->pendingAiAction;
        $this->confirmingAiAction = false;
        $this->pendingAiAction = null;

        if ($action === 'generate_current_product_photo') {
            $this->generateCurrentProductPhotoWithAi();
            return;
        }

        if ($action === 'improve_current_product_photo') {
            $this->improveCurrentProductPhotoWithAi();
            return;
        }

        if ($action === 'generate_missing_product_photos') {
            $this->generateMissingProductPhotos();
            return;
        }

        if ($action === 'generate_description') {
            $this->generateDescriptionWithAi();
            return;
        }

        if ($action === 'generate_allergen_text') {
            $this->generateAllergenTextWithAi();
        }
    }

    public function generateDescriptionWithAi(): void
    {
        try {
            if (! $this->openAi()->isConfigured()) {
                session()->flash('message', 'Configura la API key de OpenAI para generar descripciones.');
                return;
            }

            if (trim((string) $this->name) === '') {
                session()->flash('message', 'Escribe primero el nombre del plato para generar la descripcion.');
                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION);

            $allergens = $this->selectedAllergenNames();
            $categoryName = Category::find($this->category_id)?->name ?? '';
            $pairingName = Pairing::find($this->pairing_id)?->name ?? '';

            $this->description = $this->openAi()->generateProductDescription([
                'name' => $this->name,
                'category' => $categoryName,
                'pairing' => $pairingName,
                'existing_description' => $this->description,
                'allergens' => $allergens,
            ]);

            $this->aiCredits()->spend(
                AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION,
                1,
                ['product_name' => $this->name],
                $this->product_id ? (int) $this->product_id : null
            );
            $this->notifyAiCreditsChanged();

            session()->flash('message', 'Descripcion generada con IA.');
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo generar la descripcion con IA', [
                'product_id' => $this->product_id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', 'No se pudo generar la descripcion con IA.');
        }
    }

    public function generateAllergenTextWithAi(): void
    {
        try {
            if (! $this->openAi()->isConfigured()) {
                session()->flash('message', 'Configura la API key de OpenAI para generar el texto de alergenos.');
                return;
            }

            if (trim((string) $this->name) === '') {
                session()->flash('message', 'Escribe primero el nombre del plato para generar el texto de alergenos.');
                return;
            }

            $allergens = $this->selectedAllergenNames();
            if ($allergens === []) {
                session()->flash('message', 'Selecciona al menos un alergeno para generar el texto alternativo.');
                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT);

            $this->aller = $this->openAi()->generateProductAllergenText([
                'name' => $this->name,
                'description' => $this->description,
                'allergens' => $allergens,
            ]);

            $this->aiCredits()->spend(
                AiCreditService::ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT,
                1,
                ['product_name' => $this->name, 'allergens' => $allergens],
                $this->product_id ? (int) $this->product_id : null
            );
            $this->notifyAiCreditsChanged();

            session()->flash('message', 'Texto de alergenos generado con IA.');
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo generar el texto de alergenos con IA', [
                'product_id' => $this->product_id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', 'No se pudo generar el texto de alergenos con IA.');
        }
    }

    public function improveCurrentProductPhotoWithAi(): void
    {
        try {
            @ini_set('max_execution_time', '180');
            @set_time_limit(180);
            @ini_set('memory_limit', '512M');

            if (! $this->product_id) {
                session()->flash('message', __('admin.products.flash_improve_save_first'));

                return;
            }

            if (! $this->productImageAi()->isConfigured()) {
                session()->flash('message', __('admin.products.flash_improve_no_key'));

                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_IMPROVE_PRODUCT_IMAGE);

            $product = Product::find($this->product_id);
            if (! $product || ! $product->photo) {
                session()->flash('message', __('admin.products.flash_improve_no_image'));

                return;
            }

            $oldPath = $product->photo;
            $newPath = $this->productImageAi()->improveExistingProductPhoto($product);
            $product->photo = $newPath;
            $product->save();
            $this->photo = $newPath;
            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }

            $this->aiCredits()->spend(
                AiCreditService::ACTION_IMPROVE_PRODUCT_IMAGE,
                1,
                ['product_name' => $product->name],
                (int) $product->id
            );

            session()->flash('message', __('admin.products.flash_improved'));
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo mejorar la imagen con IA', [
                'product_id' => $this->product_id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', __('admin.products.flash_improve_fail'));
        }
    }

    public function generateCurrentProductPhotoWithAi(): void
    {
        try {
            @ini_set('max_execution_time', '180');
            @set_time_limit(180);
            @ini_set('memory_limit', '512M');

            if (! $this->product_id) {
                session()->flash('message', __('admin.products.flash_gen_save_first'));

                return;
            }

            if (! $this->productImageAi()->isConfigured()) {
                session()->flash('message', __('admin.products.flash_gen_no_key'));

                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE);

            $product = Product::find($this->product_id);
            if (! $product) {
                session()->flash('message', __('admin.products.flash_gen_not_found'));

                return;
            }

            $oldPath = $product->photo;
            $newPath = $this->productImageAi()->generateForProduct($product);
            $product->photo = $newPath;
            $product->save();
            $this->photo = $newPath;

            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }

            $this->aiCredits()->spend(
                AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE,
                1,
                ['product_name' => $product->name],
                (int) $product->id
            );
            $this->notifyAiCreditsChanged();

            session()->flash('message', __('admin.products.flash_generated'));
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo generar la imagen con IA', [
                'product_id' => $this->product_id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', __('admin.products.flash_gen_fail'));
        }
    }

    public function generateMissingProductPhotos(): void
    {
        try {
            if (! $this->productImageAi()->isConfigured()) {
                session()->flash('message', __('admin.products.flash_bulk_gen_no_key'));

                return;
            }

            @ini_set('max_execution_time', '300');
            @ini_set('memory_limit', '512M');
            @set_time_limit(300);

            $query = Product::query()
                ->where(function ($q) {
                    $q->whereNull('photo')->orWhere('photo', '');
                });

            if ($rid = $this->getRestaurantId()) {
                $query->where('restaurant_id', $rid);
            }

            $products = $query->orderBy('id')->get();
            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_BULK_GENERATE_PRODUCT_IMAGES, $products->count());

            $generated = 0;

            foreach ($products as $product) {
                $path = $this->productImageAi()->safelyGenerateForProduct($product);
                if (! $path) {
                    continue;
                }

                $product->photo = $path;
                $product->save();
                $generated++;
            }

            if ($generated > 0) {
                $this->aiCredits()->spend(
                    AiCreditService::ACTION_BULK_GENERATE_PRODUCT_IMAGES,
                    $generated,
                    ['mode' => 'bulk_missing_photos']
                );
                $this->notifyAiCreditsChanged();
            }

            session()->flash('message', $generated > 0
                ? __('admin.products.flash_bulk_gen_done', ['count' => $generated])
                : __('admin.products.flash_bulk_gen_none'));
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo generar imágenes IA en lote', [
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', __('admin.products.flash_bulk_gen_fail'));
        }
    }

    public function aiCreditSummary(): array
    {
        return $this->aiCredits()->summary();
    }

    public function aiCost(string $action, int $units = 1): int
    {
        return $this->aiCredits()->cost($action, $units);
    }

    private function selectedAllergenNames(): array
    {
        $ids = array_values(array_unique(array_map('intval', $this->selectedAllergens ?? [])));
        if ($ids === []) {
            return [];
        }

        return Allergen::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function hasAiWritingGuide(): bool
    {
        $restaurantId = $this->getRestaurantId();
        foreach (['ai_writing_guide', 'openai_writing_guide', 'writing_guide', 'brand_guide', 'style_guide'] as $key) {
            if (trim((string) Setting::get($key, '', $restaurantId)) !== '') {
                return true;
            }

            if (trim((string) Setting::get($key, '', null)) !== '') {
                return true;
            }
        }

        return false;
    }
}
