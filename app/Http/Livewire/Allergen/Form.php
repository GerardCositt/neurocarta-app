<?php

namespace App\Http\Livewire\Allergen;

use App\Models\Allergen;
use Livewire\Component;

class Form extends Component
{
    public Allergen $allergen;

    protected array $rules = [
        'allergen.name' => 'required|min:3',
    ];

    public function mount()
    {
        $this->allergen = app(Allergen::class);
    }

    public function render()
    {
        return view('livewire.allergen.form');
    }

    public function createAllergen(): void
    {
        $this->validate();
        Allergen::create([
            "name" => $this->allergen->name,
        ]);
        $this->allergen->name = "";
        $this->emit('allergenCreated');

    }
}
