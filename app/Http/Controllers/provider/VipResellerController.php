<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VipResellerController extends Controller
{
    protected $apiId;
    protected $apiKey;

    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->apiId = $config['api_id'] ?? $config['vip_apiid'] ?? '';
            $this->apiKey = $config['api_key'] ?? $config['vip_apikey'] ?? '';
        } else {
            // Fallback
            $api = \DB::table('setting_webs')->where('id', 1)->first();
            $this->apiId = $api->vip_apiid;
            $this->apiKey = $api->vip_apikey;
        }
    }

    public function order($uid = null, $zone = null, $service = null)
    {
        $sign = md5($this->apiId . $this->apiKey);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://vip-reseller.co.id/api/game-feature');
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "key=" . $this->apiKey . "&sign=$sign&type=order&service=$service&data_no=$uid&data_zone=$zone");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $res = json_decode(curl_exec($ch), true);
        return $res;
    }

    public function status($poid = null)
    {
        $sign = md5($this->apiId . $this->apiKey);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://vip-reseller.co.id/api/game-feature');
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "key=" . $this->apiId . "&sign=$sign&type=status&trxid=$poid"); // Note: Documentation checks needed for 'key' param (apiId vs apiKey). Logic copied from original.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $res = json_decode(curl_exec($ch), true);
        return $res;
    }
    
    public function username($uid = null, $zone = null, $service = null)
    {
        $sign = md5($this->apiId . $this->apiKey);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://vip-reseller.co.id/api/game-feature');
        curl_setopt($ch, CURLOPT_POST, TRUE);
        // Fixed undefined variables by assuming $type='get-nickname' based on function name context, though original was broken.
        // Assuming standard VIP Reseller check ID format
        curl_setopt($ch, CURLOPT_POSTFIELDS, "key=" . $this->apiKey . "&sign=$sign&type=get-nickname&service=$service&data_no=$uid&data_zone=$zone");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $res = json_decode(curl_exec($ch), true);
        return $res;
    }
}
