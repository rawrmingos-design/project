<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Berita as BeritaModel;

class Berita extends Controller
{
    public function create()
    {
        $popup = BeritaModel::where('tipe', 'mlbb')->first();

        return view('components.admin.berita', [
            'berita' => BeritaModel::all(),
            'popup' => $popup, 
        ]);
    }

     public function post(Request $request)
    {
        $request->validate([
            'tipe' => 'required'
        ]);
    
        $berita = new BeritaModel();
        $berita->tipe = $request->tipe;
    
        if ($request->hasFile('banner')) {
            $request->validate([
                'banner' => 'required|file|mimes:jpg,png,webp',
            ]);
            $file = $request->file('banner');
            $folder = 'assets/banner';
            $file->move($folder, $file->getClientOriginalName());
            $berita->path = "/".$folder."/".$file->getClientOriginalName();
        }
    
        if ($request->has('deskripsi')) {
            $berita->deskripsi = $request->deskripsi;
        }
    
        $berita->save();
    
        return back()->with('success', 'Berhasil upload!');
    }


    public function delete($id)
    {
        $data = BeritaModel::where('id', $id)->select('path')->first();
        BeritaModel::where('id', $id)->delete();
        
        return back()->with('success', 'Berhasil hapus!');
    }
}
