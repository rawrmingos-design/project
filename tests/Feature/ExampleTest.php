<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman root '/' permanently redirects ke homepage bahasa Indonesia.
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(301);
        $response->assertRedirect('/id');
    }
}
