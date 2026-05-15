<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserAffiliateStatusTest extends TestCase
{
    public function test_affiliate_status_helpers_are_case_insensitive(): void
    {
        $user = new User();

        $user->affiliate_status = 'Active';
        $this->assertTrue($user->isAffiliateActive());

        $user->affiliate_status = 'PENDING';
        $this->assertTrue($user->isAffiliatePending());

        $user->affiliate_status = ' inactive ';
        $this->assertTrue($user->isAffiliateInactive());
    }
}

