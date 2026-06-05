<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SandboxController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $sandboxIntegration = $user->resellerIntegrations()->where('mode', 'sandbox')->first();

        // Ambil 5 pesanan sandbox terakhir
        $recentOrders = Pembelian::query()
            ->where('username', $user->username)
            ->where(function ($query) {
                $query->where('is_sandbox', true)
                    ->orWhere('environment', 'sandbox');
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['order_id', 'status', 'created_at', 'layanan', 'harga']);

        return Inertia::render('Reseller/Sandbox', [
            'is_sandbox_active' => $sandboxIntegration && $sandboxIntegration->is_active,
            'recent_orders' => $recentOrders,
        ]);
    }

    public function simulateStatus(Request $request)
    {
        $request->validate([
            'invoice' => 'required|string',
            'status' => 'required|string|in:Pending,Processing,Success,Failed,Cancelled',
        ]);

        $user = $request->user();
        $invoice = $request->input('invoice');
        $requestedStatus = $request->input('status');

        $pembelian = Pembelian::query()
            ->where('order_id', trim($invoice))
            ->where('username', $user->username)
            ->where(function ($query) {
                $query->where('is_sandbox', true)
                    ->orWhere('environment', 'sandbox');
            })
            ->first();

        if (! $pembelian) {
            return redirect()->back()->with('flash_error', 'Invoice tidak ditemukan atau bukan milik Anda.');
        }

        $nextStatus = PembelianStatus::preferredDatabaseLabel($requestedStatus);

        if ($pembelian->status !== $nextStatus) {
            $metadata = $pembelian->sandboxMetadata();
            $metadata['environment'] = 'sandbox';
            $metadata['source'] = 'reseller_h2h';
            $metadata['sandbox'] = true;
            $metadata['simulated'] = true;
            $metadata['last_simulated_status'] = PembelianStatus::apiStatusCode($requestedStatus);

            $pembelian->fill([
                'status' => $nextStatus,
                'log' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ])->save();
            
            return redirect()->back()->with('flash_success', "Status invoice {$invoice} berhasil diubah menjadi {$nextStatus}. Webhook akan segera diproses.");
        }

        return redirect()->back()->with('flash_info', "Status invoice {$invoice} sudah {$nextStatus}.");
    }
}
