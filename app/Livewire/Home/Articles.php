<?php

namespace App\Livewire\Home;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Models\Artikel;

class Articles extends Component
{
    public function placeholder()
    {
        return view('livewire.home.articles-skeleton');
    }

    public function render()
    {
        // Delay to show skeleton placeholder for better UX (2 seconds)
        sleep(2);
        
        $articles = Cache::remember('latest_articles', 300, function () {
            return Artikel::where('status', 'active')
                ->latest()
                ->take(3)
                ->get();
        });

        return view('livewire.home.articles', compact('articles'));
    }
}
