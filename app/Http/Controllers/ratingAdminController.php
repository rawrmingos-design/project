<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;

class ratingAdminController extends Controller
{
    public function create()
    {
        
        $ratings = Rating::select('bintang', 'comment', 'id', 'layanan','created_at')
        ->get();
        
        return view('components.admin.rating-customer', [
            'ratings' => $ratings
            
        ]);
    }
    
    public function destroy($id) {
        $rating = Rating::findOrFail($id);
        $rating->delete();
        return redirect()->back()->with('success', 'Rating berhasil Dihapus');
    }
}
