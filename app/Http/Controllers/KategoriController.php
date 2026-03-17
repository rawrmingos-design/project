<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Kategori;
use App\Models\CustomInput;
use App\Support\CustomInputDefaults;

class KategoriController extends Controller
{
    public function create()
    {
        return view('components.admin.kategori', ['data' => Kategori::orderBy('nama', 'asc')->get()]);
    }
 public function store(Request $request)
    {
        
        
        $request->validate([
        'thumbnail' => 'required|image|mimes:jpg,png,webp|max:2048', 
        'banner' => 'required|image|mimes:jpg,png,webp|max:2048',
        'nama' => 'required|string|max:255',
        'sub_nama' => 'required|string|max:255',
        'kode' => 'required|string|max:100|unique:kategoris,kode',
        'tipe' => 'required|string|max:50'
        ]);
        
        $implodeFirstField = implode(',', [
                $request->inputs_1_title,
                $request->inputs_1_name,
                $request->inputs_1_type
        ]);
        
        if(isset($request->inputs_2_title)){
             $implodeSecondField = implode(',',[
                $request->inputs_2_title,
                $request->inputs_2_name,
                $request->inputs_2_type
            ]);
        }

        $file = $request->file('thumbnail');
        $folder = 'assets/thumbnail';
        $file->move($folder, $file->getClientOriginalName());    
        
        $file2 = $request->file('banner');
        $folder2 = 'assets/banner_game';
        $file2->move($folder2, $file2->getClientOriginalName());
        
        $kategori = new Kategori();
        $kategori->nama = $request->nama;
        $kategori->sub_nama = $request->sub_nama;
        $kategori->kode = $request->kode;
        $kategori->server_id = 0;
        $kategori->tipe = $request->tipe;
        $kategori->thumbnail = "/".$folder."/".$file->getClientOriginalName();
        $kategori->banner = "/".$folder2."/".$file2->getClientOriginalName();
        $kategori->deskripsi_game = str_replace("\r\n","<br>",$request->deskripsi_game);
        $kategori->deskripsi_field = str_replace("\r\n","<br>",$request->deskripsi_field);
        $kategori->save();
        
        $customInputData = [
            'kategori_id' => $kategori->id,
            'field_1' => $implodeFirstField,
            'field_2' => isset($implodeSecondField) ? $implodeSecondField : null,
            'field_select_title' => isset($request->inputs_2_label) ? $request->inputs_2_label : null,
            'field_select' => isset($request->inputs_2_value) ? $request->inputs_2_value : null,
        ];

        CustomInput::query()->updateOrCreate(
            ['kategori_id' => $kategori->id],
            array_merge(
                app(CustomInputDefaults::class)->buildDefaults($kategori),
                $customInputData,
            ),
        );

        return back()->with('success', 'Berhasil menambahkan kategori');
    }

    public function delete($id)
    {
        try{
            $data = Kategori::where('id', $id)->select('thumbnail')->first();
            unlink(public_path($data->thumbnail));
            Kategori::where('id', $id)->delete();
            return back()->with('success', 'Berhasil hapus!');
        }catch(\Exception $e){
            Kategori::where('id', $id)->delete();
            return back()->with('success', 'Berhasil hapus!');
        }
    }

    public function update($id, $status)
    {
        $data = Kategori::where('id', $id)->update([
            'status' => $status
        ]);

        return back()->with('success', 'Berhasil update!');
    }

public function detail($id)
    {
        $data = Kategori::where('id', $id)->first();
        $fieldInput = DB::table('custom_inputs')->where('kategori_id', $data->id)->first();
        $field1Values = explode(',', $fieldInput->field_1);
        
        if($fieldInput->field_2 !== null){
            $field2Values = explode(',', $fieldInput->field_2);
            $selectValue = isset($field2Values[2]) ? trim($field2Values[2]) : null;
            
            $buka= fopen(storage_path('logging.txt'), 'w');
            fwrite($buka,'test '. $selectValue);
        }
        
        $send = "
                <form action='".route("kategori.detail.update", [$id])."' method='POST' enctype='multipart/form-data'>
                    <input type='hidden' name='_token' value='".csrf_token()."'>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Nama</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='".$data->nama. "' name='kategori'>
                        </div>
                    </div>    
                         <div class='mb-3 row'>
                <label class='col-lg-2 col-form-label'>Tipe</label>
                <div class='col-lg-10'>
                    <select class='form-select' name='tipe'>
                        <option value='populer' " . ($data->tipe == 'populer' ? 'selected' : '') . ">Populer</option>
                        <option value='game' " . ($data->tipe == 'game' ? 'selected' : '') . ">Topup Game</option>
                        <option value='app' " . ($data->tipe == 'app' ? 'selected' : '') . ">APP</option>
                        <option value='pulsa' " . ($data->tipe == 'pulsa' ? 'pulsa' : '') . ">Pulsa & Data</option>
                        <option value='voucher' " . ($data->tipe == 'voucher' ? 'selected' : '') . ">Voucher</option>
                        <option value='joki' " . ($data->tipe == 'joki' ? 'selected' : '') . ">Joki</option>
                        <option value='jokigendong' " . ($data->tipe == 'jokigendong' ? 'selected' : '') . ">Joki Gendong</option>
                        <option value='giftskin' " . ($data->tipe == 'giftskin' ? 'selected' : '') . ">Gift Skin</option>
                        <option value='vilogml' " . ($data->tipe == 'vilogml' ? 'selected' : '') . ">Vilog ML</option>
                    </select>
                </div>
            </div>    
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Url</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='" . $data->kode . "' name='kode'>
                        </div>
                    </div> 
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Sub Nama</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='" . $data->sub_nama . "' name='sub_nama'>
                        </div>
                    </div> 
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Brand</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='" . $data->brand . "' name='brand'>
                        </div>
                    </div> 
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Deskripsi Game</label>
                        <div class='col-lg-10'>
                            <textarea class='form-control' name='deskripsi_game'>".$data->deskripsi_game."</textarea>
                        </div>
                    </div>
                    <div class='flex flex-col gap-2 p-4 mb-3 border border-secondary-400 rounded-lg rounded'>
                        <div class=flex justify-between p-4'>
                            <div class='text-dark'>Field 1</div>
                        </div>
                        <div class='mb-3 row'>
                            <label class='col-lg-2 col-form-label' for='example-fileinput'>Title Input</label>
                            <div class='col-lg-10'>
                                <input type='text' class='form-control' value='" . $field1Values[0] . "' name='inputs_1_title'>
                            </div>
                        </div> 
                        <div class='mb-3 row'>
                            <label class='col-lg-2 col-form-label' for='example-fileinput'>PlaceHolder Input</label>
                            <div class='col-lg-10'>
                                <input type='text' class='form-control' value='" . $field1Values[1] . "' name='inputs_1_name'>
                            </div>
                        </div> 
                        <div class='mb-3 row'>
                            <label class='col-lg-2 col-form-label' for='example-fileinput'>Type Input</label>
                            <div class='col-lg-10'>
                                <input type='text' class='form-control' value='" . $field1Values[2] . "' name='inputs_1_type'>
                            </div>
                        </div>";
                        
                        
                        if($fieldInput->field_2 !== null){
                            $send .= "
                                <div class=flex justify-between p-4'>
                                <div class='text-dark'>Field 2</div>
                                </div>
                                <div class='mb-3 row'>
                                    <label class='col-lg-2 col-form-label' for='example-fileinput'>Title Input</label>
                                    <div class='col-lg-10'>
                                        <input type='text' class='form-control' value='" . $field2Values[0] . "' name='inputs_2_title'>
                                    </div>
                                </div> 
                                <div class='mb-3 row'>
                                    <label class='col-lg-2 col-form-label' for='example-fileinput'>PlaceHolder Input</label>
                                    <div class='col-lg-10'>
                                        <input type='text' class='form-control' value='" . $field2Values[1] . "' name='inputs_2_name'>
                                    </div>
                                </div> 
                                <div class='mb-3 row'>
                                    <label class='col-lg-2 col-form-label' for='example-fileinput'>Type Input</label>
                                    <div class='col-lg-10'>
                                        <input type='text' class='form-control' value='" . $field2Values[2] . "' name='inputs_2_type'>
                                    </div>
                                </div>
                                
                            ";
                            if($selectValue == "select") {
                                $send .= "
                                        <div class=flex justify-between p-4'>
                                            <div class='text-dark'>Select</div>
                                        </div>
                                        <div class='mb-3 row'>
                                            <label class='col-lg-2 col-form-label' for='example-fileinput'>Select Name</label>
                                            <div class='col-lg-10'>
                                                <input type='text' class='form-control' value='" . $fieldInput->field_select_title . "' name='field_select_title'>
                                            </div>
                                        </div> 
                                        <div class='mb-3 row'>
                                            <label class='col-lg-2 col-form-label' for='example-fileinput'>Select Value</label>
                                            <div class='col-lg-10'>
                                                <input type='text' class='form-control' value='" . $fieldInput->field_select . "' name='field_select'>
                                            </div>
                                        </div> 
                                        
                                ";
                            }
                        
                        }
                        
        $send .=    "</div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Deskripsi Field User ID & Zone ID</label>
                        <div class='col-lg-10'>
                            <textarea class='form-control' name='deskripsi_field'>".$data->deskripsi_field."</textarea>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Thumbnail</label>
                        <div class='col-lg-10'>
                            <input type='file' class='form-control' value='" . $data->thumbnail . "' name='thumbnail'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Banner</label>
                        <div class='col-lg-10'>
                            <input type='file' class='form-control' value='" . $data->banner . "' name='banner'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Status</label>
                        <div class='col-lg-10'>
                            <select class='form-control' name='status'>
                                <option value='active'>Active</option>
                                <option value='unactive'>Unactive</option>
                            </select>
                        </div>
                    </div>                    
                    <div class='modal-footer'>
                        <button type='submit' class='btn btn-primary bg-gradient waves-effect waves-light'>Simpan</button>
                        <button type='button' class='btn btn-dark bg-gradient waves-effect waves-light' data-bs-dismiss='modal'>Close</button>
                    </div>
                </form>
        ";

        return $send;        
    }
    
    public function patch(Request $request, $id)
    {
    if($request->file('thumbnail')){
        $file = $request->file('thumbnail');
        $folder = 'assets/thumbnail';
        $filename = sha1(uniqid(time(), true)) . '.' . $file->getClientOriginalExtension();
        $file->move($folder, $filename);      
        Kategori::where('id', $id)->update([
            'thumbnail' => "/".$folder."/".$filename
        ]);
    }
    
    if($request->file('banner')){
        $file2 = $request->file('banner');
        $folder2 = 'assets/banner_game';
        $filename2 = sha1(uniqid(time(), true)) . '.' . $file2->getClientOriginalExtension();
        $file2->move($folder2, $filename2);      
        Kategori::where('id', $id)->update([
            'banner' => "/".$folder2."/".$filename2
        ]);
    }

        
        $implodeFirstField = implode(',', [
                $request->inputs_1_title,
                $request->inputs_1_name,
                $request->inputs_1_type
        ]);
        
        if(isset($request->inputs_2_title)){
             $implodeSecondField = implode(',',[
                $request->inputs_2_title,
                $request->inputs_2_name,
                $request->inputs_2_type
            ]);
        }
        
        $pembayaran = Kategori::where('id', $id)->update([
            'nama' => htmlspecialchars($request->kategori, ENT_QUOTES, 'UTF-8'),
            'sub_nama' => htmlspecialchars($request->sub_nama, ENT_QUOTES, 'UTF-8'),
            'kode' => htmlspecialchars($request->kode, ENT_QUOTES, 'UTF-8'),
            'tipe' => htmlspecialchars($request->tipe, ENT_QUOTES, 'UTF-8'),
            'status' => htmlspecialchars($request->status, ENT_QUOTES, 'UTF-8'),
            'server_id' => $request->serverOption == 'ya' ? 1 : 0,
            
            'deskripsi_game' => str_replace("\r\n","<br>",$request->deskripsi_game),
            'deskripsi_field' => str_replace("\r\n","<br>",$request->deskripsi_field)
        ]);
        
        $oldFieldValue = \DB::table('custom_inputs')->where('kategori_id', $id)->value('field_2');
        
        $newFieldValue = isset($implodeSecondField) ? $implodeSecondField : $oldFieldValue;
        
        $customInput = \DB::table('custom_inputs')->where('kategori_id', $id)->update([
            'field_1' => $implodeFirstField,
            'field_2' => $newFieldValue,
            'field_select_title' => isset($request->field_select_title) ? $request->field_select_title : null,
            'field_select' => isset($request->field_select) ? $request->field_select : null,
            
            ]);
           
        return back()->with('success', 'Berhasil update kategori');        
    }        
}
