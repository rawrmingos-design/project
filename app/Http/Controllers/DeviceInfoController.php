<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DeviceInfoController extends Controller {

    public function info() {
        
        $api = \DB::table('setting_webs')->where('id',1)->first();
        
        $response = Http::withOptions([
            'verify' => true,
        ])->get("https://pati.wablas.com/api/device/info", [
            'token' => $api->wa_key,
        ]);

        $data = $response->json()['data'];
        $status = $data['status'];
        $account = $data['name'];
        $expired = $data['expired_date'];

        return $data;
    }

}
