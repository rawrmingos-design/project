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
        // Delay to show skeleton placeholder for better UX (2 seconds)
        sleep(2);
        
        $categoryTypes = Cache::remember('category_types_with_kategoris', 300, function () {
            return CategoryType::orderBy('sort', 'asc')
                ->with(['kategoris' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->get();
        });

        return view('livewire.home.category-tabs', compact('categoryTypes'));
    }
}
