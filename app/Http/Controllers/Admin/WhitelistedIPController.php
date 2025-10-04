<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhitelistedIP;

class WhitelistedIPController extends Controller
{
     public function create()
    {
        return view('components.admin.whitelisted_ips.create');
    }
    public function index()
    {
        $whitelistedIPs = WhitelistedIP::all();
        return view('components.admin.whitelisted_ips.index', compact('whitelistedIPs'));
    }

   public function store(Request $request)
{
    $request->validate([
        'ip_address' => 'required|ip|unique:whitelisted_ips',
    ]);

    WhitelistedIP::create([
        'ip_address' => $request->ip_address,
    ]);

    return redirect()->route('whitelisted-ips.index')->with('success', 'IP Address added to whitelist.');
}

public function destroy(WhitelistedIP $whitelistedIP)
{
    $whitelistedIP->delete();
    return redirect()->route('whitelisted-ips.index')->with('success', 'IP Address removed from whitelist.');
}

}
