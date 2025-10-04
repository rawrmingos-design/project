<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\PaketLayanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaketLayananController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paket_id' => 'required',
            'layanan_id' => 'required|array',
            'product_logo' => 'bail|required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput($request->all());
        }

        try {
            DB::beginTransaction();

            // Simpan file product_logo
            $img = $request->file('product_logo');
            $filename = Str::random(15) . '.' . $img->extension();
            $img->move('assets/product_logo', $filename);

            foreach ($request->layanan_id as $layananId) {
                // Cek apakah sudah ada entri PaketLayanan untuk layanan ini
                $existingPaketLayanan = PaketLayanan::where('layanan_id', $layananId)
                    ->where('paket_id', $request->paket_id)
                    ->first();

                if (!$existingPaketLayanan) {
                    PaketLayanan::create([
                        'paket_id' => $request->paket_id,
                        'layanan_id' => $layananId,
                        'product_logo' => '/assets/product_logo/' . $filename,
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Service Pack successfully added');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('An error occurred while saving the service package data: ' . $e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while saving the service package data');
        }
    }

    public function destroy(Request $request)
    {
        $layananIds = $request->input('layanan_ids');
        if ($layananIds) {
            try {
                DB::beginTransaction();
                // Log the IDs to check what is being received
                Log::info('Deleting PaketLayanan IDs: ', $layananIds);
                
                // Check if there are items to delete
                $paketLayananItems = PaketLayanan::whereIn('layanan_id', $layananIds)->get();

                if ($paketLayananItems->isEmpty()) {
                    DB::rollback();
                    return redirect()->back()->with('error', 'No items found to delete.');
                }

                // Delete the items
                PaketLayanan::whereIn('layanan_id', $layananIds)->delete();

                DB::commit();
                return redirect()->back()->with('success', 'The selected item was successfully deleted.');
            } catch (\Exception $e) {
                DB::rollback();
                Log::error('An error occurred while deleting service pack data: ' . $e->getMessage());
                return redirect()->back()->with('error', 'An error occurred while deleting service pack data');
            }
        } else {
            return redirect()->back()->with('error', 'No items selected to delete.');
        }
    }

    public function get_layanan(Request $request)
    {
        try {
            $layanan = Layanan::where('kategori_id', $request->kategori_id)->get();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil',
                'data' => $layanan
            ], 200);
        } catch (\Throwable $th) {
            Log::error('An error occurred while retrieving service data: ' . $th->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong!',
                'data' => null
            ], 500);
        }
    }
}
