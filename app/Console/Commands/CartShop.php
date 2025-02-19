<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CartShop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abandoned:cart-shop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Voy a enviar un correo a los usuarios que abandonaron el carrito de compras cada 6 horas por 2 días';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        date_default_timezone_set("America/Bogota");

        $users = User::whereHas("carts", function ($query) {
            $query->whereRaw("DATEDIFF(updated_at, created_at) < 3");
        })->get();

        foreach ($users as $key => $user) {
            foreach ($user->carts as $cart) {
                $cart->update([
                    "updated_at" => now(),
                ]);
            }
        }
    }
}
