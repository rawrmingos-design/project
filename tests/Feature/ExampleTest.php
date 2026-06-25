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
        $response = $this->get(rtrim((string) config('app.url'), '/') . '/');

        $response->assertStatus(301);
        $response->assertRedirect('/id');
    }
}
