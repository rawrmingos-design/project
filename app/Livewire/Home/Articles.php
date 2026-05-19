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
        $articles = Cache::remember('latest_articles', 300, function () {
            return Artikel::where('status', 'active')
                ->select(['id', 'slug', 'title', 'thumbnail', 'created_at', 'views'])
                ->latest()
                ->take(3)
                ->get();
        });

        return view('livewire.home.articles', compact('articles'));
    }
}
