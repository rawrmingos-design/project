<?php

namespace App\Http\Controllers\policyandtermss;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Berita;

class TermsController extends Controller 
{
    public function terms()
    {
        return view('template.privacyandterms.termsandcondition', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
    
    public function policy()
    {
        return view('template.privacyandterms.privacypolicy', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
    
    public function privacy()
    {
        return view('template.privacyandterms.privacy', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
    
}
