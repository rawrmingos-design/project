<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::whereNull('referral_code')->orWhere('referral_code', '')->get();

        foreach ($users as $user) {
            do {
                $referralCode = 'REF-' . Str::upper(Str::random(6));
            } while (User::where('referral_code', $referralCode)->exists());

            $user->referral_code = $referralCode;
            $user->save();
        }
    }
}
