<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman root '/' redirect ke halaman login atau home.
     * Sesuai behavior Laravel: guest diredirect ke login (302).
     */
    public function test_example()
    {
        $response = $this->get('/');

        // Root redirects to login or home page for guests — 302 is expected
        $response->assertStatus(302);
    }
}
