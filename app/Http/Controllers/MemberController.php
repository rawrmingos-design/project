<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function create()
    {
        return view('components.admin.member', ['users' => User::orderBy('created_at', 'desc')->orderBy('created_at', 'desc')->get()]);
    }

    public function delete($id)
    {
        User::where('id', $id)->delete();
        return back()->with('success', 'Berhasil menghapus pengguna');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'username' => 'required|min:3|unique:users,username|max:255',
            'password' => 'required|min:6|max:255',
            'email' => 'required',
            'no_wa' => 'required|numeric|unique:users,no_wa',
            'role' => 'required'
        ]);
        
        $no = $request->no_wa;
        
        if($no[0] == 0){
            
            $no = str_replace($no[0],'62',$no);
            
        }


        $user = User::create([
            'name' => $request->nama,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'no_wa' => $no,
            'balance' => 0,
            'api_key' => Str::random(32), 
            'role' => $request->role
        ]);

        return back()->with('success', 'Berhasil menambahkan pengguna');
    }

    public function send(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'balance' => 'required|numeric'
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) return back()->with('error', 'Username not found');

        $user->update([
            'balance'  => $user->balance + $request->balance
        ]);

        return back()->with('success', 'Berhasil menambahkan saldo');
    }

    public function show($id)
    {
        $data = User::where('id', $id)->first();

        $send = "
                <form action='" . route("member.detail.update", [$id]) . "' method='POST'>
                    <input type='hidden' name='_token' value='" . csrf_token() . "'>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Name</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='" . $data->name . "' name='name'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Username</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='" . $data->username . "' name='username'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Email</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='" . $data->email . "' name='email'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>No Whatsapp</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='" . $data->no_wa . "' name='no_wa'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label for='' class='col-lg-2 col-form-label'>Role</label>
                        <div class='col-lg-10'>
                            <select class='form-control' name='role'>
                                <option value='Member'>Member</option>
                                <option value='Platinum'>Platinum</option>
                                <option value='Gold'>Gold</option>
                            </select>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Balance</label>
                        <div class='col-lg-10'>
                            <input step='0.01' type='number' class='form-control' value='" . $data->balance . "' name='balance'>
                        </div>
                    </div>    
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-primary'>Save</button>
                    </div>
                </form>
        ";

        return $send;
    }

    public function patch(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,username,' . $id, 
            'email' => 'required',
            'no_wa' => 'required|numeric|unique:users,no_wa,' . $id, 
            'balance' => 'required|numeric|min:0',
            'role' => 'required'
        ]);
    
        $user = User::find($id);
    
        if (!$user) {
            return back()->with('error', 'Pengguna tidak tersedia');
        }
    
        $no = $request->no_wa;
        
        if($no[0] == 0){
            
            $no = str_replace($no[0],'62',$no);
            
        }
    
        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'api_key' => Str::random(32),
            'balance' => $request->balance,
            'email' => $request->email,
            'no_wa' => $no,
            'role' => $request->role
        ]);
    
        return back()->with('success', 'Berhasil update pengguna');
    }

}
