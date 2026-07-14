<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Method;

class MethodController extends Controller
{
    public function create()
    {
        return view('components.admin.method', ['data' => method::orderBy('name', 'asc')->paginate(100)]);
    }

    public function store(Request $request)
    {
        if (! \App\Support\PaymentCatalogAccess::isMaster()) {
            abort(403, 'Akses ditolak: Hanya Master Admin yang dapat menambahkan metode pembayaran.');
        }

        $request->validate([
            'name' => 'required',
            'images' => 'required|file|mimes:jpg,png,webp',
            'code' => 'required',
            'keterangan' => 'required',
            'payment' => 'required',
            'tipe' => 'required',
            'fee_percent' => 'required|numeric',
            'fix_fee' => 'required|numeric',
            'min_pembelian' => 'required|numeric',
            'max_pembelian' => 'required|numeric',
        ]);

        $file = $request->file('images');
        $folder = 'assets/thumbnail';
        $file->move($folder, $file->getClientOriginalName());
        $method = new method();
        $method->name = $request->name;
        $method->code = $request->code;
        $method->keterangan = $request->keterangan;
        $method->tipe = $request->tipe;
        $method->payment = $request->payment;
        $method->images = "/" . $folder . "/" . $file->getClientOriginalName();
        $method->fee_percent = $request->fee_percent;
        $method->fix_fee = $request->fix_fee;
        $method->min_pembelian = $request->min_pembelian;
        $method->max_pembelian = $request->max_pembelian;
        $method->save();

        return back()->with('success', 'Berhasil menambahkan payment');
    }

    public function delete($id)
    {
        if (! \App\Support\PaymentCatalogAccess::isMaster()) {
            abort(403, 'Akses ditolak: Hanya Master Admin yang dapat menghapus metode pembayaran.');
        }

        try {
            $data = method::where('id', $id)->select('images')->first();
            unlink(public_path($data->images));
            method::where('id', $id)->delete();
            return back()->with('success', 'Berhasil hapus!');
        } catch (\Exception $e) {
            method::where('id', $id)->delete();
            return back()->with('success', 'Berhasil hapus!');
        }
    }

    public function detail($id)
    {
        if (! \App\Support\PaymentCatalogAccess::isMaster()) {
            abort(403, 'Akses ditolak: Hanya Master Admin yang dapat mengedit metode pembayaran.');
        }

        $data = Method::where('id', $id)->first();
        if (!$data) {
            return back()->withErrors('Metode pembayaran tidak ditemukan');
        }
        $send = "
            <form action='" . route('method.detail.update', [$id]) . "' method='POST' enctype='multipart/form-data'>
                <input type='hidden' name='_token' value='" . csrf_token() . "'>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='example-fileinput'>Nama</label>
                    <div class='col-lg-10'>
                        <input type='text' class='form-control' value='" . $data->name . "' name='name' required>
                    </div>
                </div>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='example-fileinput'>Kode</label>
                    <div class='col-lg-10'>
                        <input type='text' class='form-control' value='" . $data->code . "' name='code' required>
                    </div>
                </div>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='example-fileinput'>Keterangan</label>
                    <div class='col-lg-10'>
                        <input type='text' class='form-control' value='" . $data->keterangan . "' name='keterangan' required>
                    </div>
                </div>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label'>Payment</label>
                    <div class='col-lg-10'>
                        <select class='form-select' name='payment' required>                            
                            <option value='tripay' " . ($data->payment == 'tripay' ? 'selected' : '') . ">TRIPAY.CO.ID</option>
                            <option value='tokopay' " . ($data->payment == 'tokopay' ? 'selected' : '') . ">TOKOPAY.ID</option>
                            <option value='paydisini' " . ($data->payment == 'paydisini' ? 'selected' : '') . ">PAYDISINI.CO.ID</option>                            
                            <option value='SALDO' " . ($data->payment == 'SALDO' ? 'selected' : '') . ">SALDO MEMBER</option>
                        </select>
                    </div>
                </div>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label'>Tipe</label>
                    <div class='col-lg-10'>
                        <select class='form-select' name='tipe' required>
                            <option value='qris' " . ($data->tipe == 'qris' ? 'selected' : '') . ">QRIS</option>
                            <option value='e-walet' " . ($data->tipe == 'e-walet' ? 'selected' : '') . ">E-Wallet</option>
                            <option value='virtual-account' " . ($data->tipe == 'virtual-account' ? 'selected' : '') . ">Virtual Account</option>
                            <option value='convenience-store' " . ($data->tipe == 'convenience-store' ? 'selected' : '') . ">Convenience Store</option>
                            <option value='pulsa' " . ($data->tipe == 'pulsa' ? 'selected' : '') . ">PULSA</option>
                            <option value='SALDO' " . ($data->tipe == 'SALDP' ? 'selected' : '') . ">Saldo Member</option>
                        </select>
                    </div>
                </div>
                  <div class='mb-3 row'>
                <label class='col-lg-2 col-form-label' for='min_pembelian'>Minim Pembelian</label>
                <div class='col-lg-10'>
                    <input type='number' step='0.01' class='form-control' value='" . $data->min_pembelian . "' name='min_pembelian' required>
                </div>
            </div>
            
            <div class='mb-3 row'>
                <label class='col-lg-2 col-form-label' for='max_pembelian'>Max Pembelian</label>
                <div class='col-lg-10'>
                    <input type='number' step='0.01' class='form-control' value='" . $data->max_pembelian . "' name='max_pembelian' required>
                </div>
            </div>
            
            <div class='mb-3 row'>
                <label class='col-lg-2 col-form-label' for='fix_fee'>Biaya Tetap</label>
                <div class='col-lg-10'>
                    <input type='number' step='0.01' class='form-control' value='" . $data->fix_fee . "' name='fix_fee' required>
                </div>
            </div>
            
            <div class='mb-3 row'>
                <label class='col-lg-2 col-form-label' for='fee_percent'>Biaya Persentase</label>
                <div class='col-lg-10'>
                    <input type='number' step='0.01' class='form-control' value='" . $data->fee_percent . "' name='fee_percent' required>
                </div>
            </div>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='example-fileinput'>Thumbnail</label>
                    <div class='col-lg-10'>
                        <input type='file' class='form-control' name='images'>
                        <img src='" . asset($data->images) . "' alt='thumbnail' class='mt-3' width='100'>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Close</button>
                    <button type='submit' class='btn btn-primary'>Simpan</button>
                </div>
            </form>
        ";

        return $send;
    }


    public function patch(Request $request, $id)
    {
        if (! \App\Support\PaymentCatalogAccess::isMaster()) {
            abort(403, 'Akses ditolak: Hanya Master Admin yang dapat mengedit metode pembayaran.');
        }

        if ($request->file('images')) {
            $file = $request->file('images');
            $folder = 'assets/thumbnail';
            $file->move($folder, $file->getClientOriginalName());
            method::where('id', $id)->update([
                'images' => "/" . $folder . "/" . $file->getClientOriginalName()
            ]);
        }

        $method = method::where('id', $id)->update([
            'name' => $request->name,
            'code' => $request->code,
            'keterangan' => $request->keterangan,
            'tipe' => $request->tipe,
            'payment' => $request->payment,
            'fee_percent' => $request->fee_percent,
            'fix_fee' => $request->fix_fee,
            'min_pembelian' => $request->min_pembelian,
            'max_pembelian' => $request->max_pembelian,
        ]);

        return back()->with('success', 'Berhasil update payment');
    }

    public function toggleStatus($id)
    {
        $payMethod = method::findOrFail($id);

        if (\App\Support\PaymentCatalogAccess::isMaster()) {
            $payMethod->statuspayment = !$payMethod->statuspayment;
            $payMethod->save();
        } else {
            $setting = \App\Models\TenantPaymentMethodSetting::query()->firstOrCreate(
                [
                    'tenant_id' => \App\Support\PaymentCatalogAccess::currentTenantId(),
                    'method_id' => $payMethod->id,
                ],
                ['is_visible' => true]
            );

            $setting->is_visible = !$setting->is_visible;
            $setting->save();
        }

        return redirect()->back()->with('success', 'Status metode pembayaran berhasil diperbarui.');
    }
}
