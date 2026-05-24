<?php

namespace Tests\Feature;

use App\Http\Livewire\Category\Show as CategoryShow;
use App\Http\Livewire\Products;
use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(string $planCode = 'trial', string $status = 'trialing'): array
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Crud Test ' . uniqid()]);
        $account->users()->attach($user);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Test Restaurant',
            'subdomain'  => 'test-crud-' . uniqid(),
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => $planCode,
            'status'                => $status,
            'current_period_end_at' => $status === 'trialing' ? now()->addDays(7) : now()->addMonth(),
        ]);

        return [$user, $restaurant, $account];
    }

    // ── Category CRUD ─────────────────────────────────────────────────────────

    public function test_can_create_category(): void
    {
        [$user, $restaurant] = $this->makeStack();

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        Livewire::test(CategoryShow::class)
            ->set('name', 'Entrantes')
            ->call('storeAndClose')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name'          => 'Entrantes',
            'restaurant_id' => $restaurant->id,
        ]);
    }

    public function test_category_name_is_required(): void
    {
        [$user, $restaurant] = $this->makeStack();

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        Livewire::test(CategoryShow::class)
            ->set('name', '')
            ->call('storeAndClose')
            ->assertHasErrors(['name']);
    }

    public function test_toggle_category_hidden(): void
    {
        [$user, $restaurant] = $this->makeStack();

        $category = Category::create([
            'name'          => 'Postres',
            'restaurant_id' => $restaurant->id,
            'hidden'        => false,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        Livewire::test(CategoryShow::class)
            ->call('toggleState', $category->id);

        $this->assertTrue($category->fresh()->hidden);
    }

    // ── Product CRUD ──────────────────────────────────────────────────────────

    public function test_can_create_product(): void
    {
        Storage::fake('public');

        [$user, $restaurant] = $this->makeStack();

        $category = Category::create([
            'name'          => 'Platos',
            'restaurant_id' => $restaurant->id,
        ]);

        // Seed one product so the demo-warning is not triggered
        Product::create([
            'name'          => 'Seed product',
            'price'         => '5.00',
            'category_id'   => $category->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        Livewire::test(Products::class)
            ->set('name', 'Hamburguesa')
            ->set('price', '12.50')
            ->set('category_id', $category->id)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name'          => 'Hamburguesa',
            'restaurant_id' => $restaurant->id,
        ]);
    }

    public function test_product_name_and_price_are_required(): void
    {
        Storage::fake('public');

        [$user, $restaurant] = $this->makeStack();

        $category = Category::create([
            'name'          => 'Platos',
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        Livewire::test(Products::class)
            ->set('name', '')
            ->set('price', '')
            ->set('category_id', $category->id)
            ->call('store')
            ->assertHasErrors(['name', 'price']);
    }

    public function test_rejects_svg_image_upload(): void
    {
        Storage::fake('public');

        [$user, $restaurant] = $this->makeStack();

        $category = Category::create([
            'name'          => 'Platos',
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        $svgFile = UploadedFile::fake()->create('menu.svg', 100, 'image/svg+xml');

        Livewire::test(Products::class)
            ->set('name', 'Ensalada')
            ->set('price', '8.00')
            ->set('category_id', $category->id)
            ->set('filename', $svgFile)
            ->call('store')
            ->assertHasErrors(['filename']);
    }

    public function test_rejects_gif_image_upload(): void
    {
        Storage::fake('public');

        [$user, $restaurant] = $this->makeStack();

        $category = Category::create([
            'name'          => 'Platos',
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        $gifFile = UploadedFile::fake()->create('animated.gif', 100, 'image/gif');

        Livewire::test(Products::class)
            ->set('name', 'Gazpacho')
            ->set('price', '6.00')
            ->set('category_id', $category->id)
            ->set('filename', $gifFile)
            ->call('store')
            ->assertHasErrors(['filename']);
    }

    public function test_accepts_png_image_upload(): void
    {
        Storage::fake('public');

        [$user, $restaurant] = $this->makeStack();

        $category = Category::create([
            'name'          => 'Platos',
            'restaurant_id' => $restaurant->id,
        ]);

        // Seed one product so the demo-warning is not triggered
        Product::create([
            'name'          => 'Seed product',
            'price'         => '5.00',
            'category_id'   => $category->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        $pngFile = UploadedFile::fake()->image('plato.png', 400, 300);

        Livewire::test(Products::class)
            ->set('name', 'Croquetas')
            ->set('price', '7.50')
            ->set('category_id', $category->id)
            ->set('filename', $pngFile)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name'          => 'Croquetas',
            'restaurant_id' => $restaurant->id,
        ]);
    }

    public function test_toggle_product_hidden(): void
    {
        [$user, $restaurant] = $this->makeStack();

        $category = Category::create([
            'name'          => 'Platos',
            'restaurant_id' => $restaurant->id,
        ]);

        $product = Product::create([
            'name'          => 'Sopa del día',
            'price'         => '5.00',
            'category_id'   => $category->id,
            'restaurant_id' => $restaurant->id,
            'hidden'        => false,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        Livewire::test(Products::class)
            ->call('toggleState', $product);

        $this->assertTrue($product->fresh()->hidden);
    }

    public function test_basico_plan_cannot_toggle_featured(): void
    {
        [$user, $restaurant] = $this->makeStack('basico', 'active');

        $category = Category::create([
            'name'          => 'Platos',
            'restaurant_id' => $restaurant->id,
        ]);

        $product = Product::create([
            'name'          => 'Arroz con leche',
            'price'         => '4.00',
            'category_id'   => $category->id,
            'restaurant_id' => $restaurant->id,
            'featured'      => false,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        Livewire::test(Products::class)
            ->call('toggleFeatured', $product->id);

        // Básico plan gate blocks the toggle
        $this->assertFalse($product->fresh()->featured);
    }

    public function test_pro_plan_can_toggle_featured(): void
    {
        [$user, $restaurant] = $this->makeStack('pro', 'active');

        $category = Category::create([
            'name'          => 'Platos',
            'restaurant_id' => $restaurant->id,
        ]);

        $product = Product::create([
            'name'          => 'Paella valenciana',
            'price'         => '15.00',
            'category_id'   => $category->id,
            'restaurant_id' => $restaurant->id,
            'featured'      => false,
        ]);

        $this->actingAs($user);
        session()->put('admin_restaurant_id', $restaurant->id);

        Livewire::test(Products::class)
            ->call('toggleFeatured', $product->id);

        $this->assertTrue($product->fresh()->featured);
    }
}
