<?php

namespace App\Observers;

use App\Models\Kategori;
use App\Support\CustomInputDefaults;

class KategoriObserver
{
    public function created(Kategori $kategori): void
    {
        app(CustomInputDefaults::class)->ensureExists($kategori);
    }
}
