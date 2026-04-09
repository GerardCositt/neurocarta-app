<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ExpireOffers extends Command
{
    protected $signature   = 'offers:expire';
    protected $description = 'Desactiva automáticamente las ofertas cuya fecha de fin ha pasado';

    public function handle(): int
    {
        $expired = Product::where('offer', true)
            ->whereNotNull('offer_end')
            ->where('offer_end', '<', now()->startOfDay())
            ->get();

        foreach ($expired as $product) {
            $product->offer = false;
            $product->save();
        }

        $count = $expired->count();

        if ($count > 0) {
            $this->info("Se han desactivado {$count} oferta(s) caducada(s).");
        } else {
            $this->info('No hay ofertas caducadas.');
        }

        return self::SUCCESS;
    }
}
