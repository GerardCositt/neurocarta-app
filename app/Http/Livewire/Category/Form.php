<?php

namespace App\Http\Livewire\Category;

use App\Models\Category;
use Livewire\Component;

class Form extends Component
{
    public Category $category;

    public function mount()
    {
        $this->category = app(Category::class);
    }

    public function render()
    {

        return view('livewire.category.form');
    }

    public function createCategory(): void
    {
        $this->validate(
            ['category.name' => 'required|min:3'],
            [
                'category.name.required' => __('validation.category.form_name_required'),
                'category.name.min' => __('validation.category.form_name_min', ['min' => 3]),
            ]
        );
        Category::create([
            "name" => $this->category->name,
        ]);

        $this->category->name ="";

        $this->emit('categoryCreated');
    }

}
