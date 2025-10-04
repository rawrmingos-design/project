<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tabpills; 
use Carbon\Carbon;

class TabmenuController extends Controller
{
    public function create()
{
    $tabpills = Tabpills::all(); 
    return view('components.admin.tabmenu', compact('tabpills'));
}

    public function post(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255', 
        ]);

        Tabpills::create([
            'tabname' => $request->nama, 
            'created_at' => Carbon::now(), 
        ]);

        return redirect()->back()->with('success', 'Data berhasil disimpan!');
    }

    public function delete($id)
    {
        // Menghapus data berdasarkan ID
        $data = Tabpills::find($id);
        if ($data) {
            $data->delete();
            return back()->with('success', 'Berhasil hapus!');
        }
        return back()->with('error', 'Data tidak ditemukan.');
    }
}
