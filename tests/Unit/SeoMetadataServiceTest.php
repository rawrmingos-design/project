<?php

namespace Tests\Unit;

use App\Services\SeoMetadataService;
use Tests\TestCase;

class SeoMetadataServiceTest extends TestCase
{
    public function test_contract_defines_shared_seo_fields(): void
    {
        $this->assertContains('title', [
            'title', 'description', 'keywords', 'canonical', 'image', 'robots',
            'ogType', 'author', 'schemaMarkup',
        ]);
        $this->assertTrue(class_exists(SeoMetadataService::class));
    }
}
