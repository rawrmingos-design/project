<?php

namespace App\Livewire\Home;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Models\CategoryType;

class CategoryTabs extends Component
{
    public function placeholder()
    {
        return view('livewire.home.category-tabs-skeleton');
    }

    public function render()
    {   
        $categoryTypes = Cache::remember('category_types_with_kategoris', 300, function () {
            return CategoryType::orderBy('sort', 'asc')
                ->select(['id', 'name', 'slug', 'sort'])
                ->with(['kategoris' => function ($query) {
                    $query
                        ->select(['id', 'category_type_id', 'nama', 'sub_nama', 'thumbnail', 'kode'])
                        ->where('status', 'active')
                        ->orderBy('nama');
                }])
                ->get();
        });

        return view('livewire.home.category-tabs', compact('categoryTypes'));
    }
}
