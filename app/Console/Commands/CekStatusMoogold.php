<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\provider\moogold\MoogoldController;

class CekStatusMoogold extends Command
{
    protected $signature = 'moogold:cek-status';
    protected $description = 'Cek status order Moogold yang masih dalam proses';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $moogold = new MoogoldController();
        $moogold->cekStatusOrder();
        
        $this->info('Cek status Moogold selesai.');
    }
}
