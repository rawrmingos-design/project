<?php

namespace App\Http\Controllers;

use App\Models\Paket;
use App\Models\Layanan;
use App\Models\Kategori;
use App\Models\PaketLayanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaketController extends Controller
{
   
    public function index()
{
    
    $data['kategoris'] = Kategori::get();
    $data['pakets'] = Paket::all();
    $data['layananOptions'] = Layanan::all(); 
    return view('components.admin.paket.index', $data);
}

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|unique:pakets',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator->errors())->withInput($request->all());
            }

            $data = $validator->validated();

            DB::beginTransaction();

            Paket::create($data);

            DB::commit();

            return redirect()->back()->with('success', 'Data saved successfully');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('An error occurred while saving data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while saving data');
        }
    }

   
    public function show(Paket $paket)
    {
        //
    }

    
    public function edit(Paket $paket)
    {
        //
    }

    
    public function update(Request $request, Paket $paket)
    {
        try {
            $unique = '';
            if ($request->nama != $paket->nama) {
                $unique = '|unique:pakets';
            }
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string' . $unique,
            ]);

            if ($validator->fails()) {
                return redirect()->back()->with('error', 'invalid input');
            }

            $data = $validator->validated();

            DB::beginTransaction();
            $paket->update($data);

            DB::commit();

            return redirect()->back()->with('success', 'Data successfully updated');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('An error occurred while updating data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while updating data');
        }
    }

    
    public function destroy(Paket $paket)
    {
        try {
            DB::beginTransaction();
            if(PaketLayanan::firstWhere('paket_id',$paket->id)){
            PaketLayanan::firstWhere('paket_id',$paket->id)->delete();
            }
            $paket->delete();
            DB::commit();
    
            return redirect()->back()->with('success', 'Data deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('An error occurred while deleting data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while deleting data');
        }
    }
}
