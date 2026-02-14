<?php

namespace Tests\Unit;

use App\Services\TrendAgent\TrendAgentImageStoreService;
use Tests\TestCase;

class TrendAgentImageStoreServiceTest extends TestCase
{
    private TrendAgentImageStoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrendAgentImageStoreService('public', 'trendagent', 30, 25, 2);
    }

    public function test_build_path_formats_correctly(): void
    {
        $path = $this->service->buildPath('apartment', 'abc123', 'sha1hash', 'jpg');
        $this->assertSame('apartment/abc123/sha1hash.jpg', $path);
    }

    public function test_build_path_sanitizes_entity_id(): void
    {
        $path = $this->service->buildPath('complex', '63c50acc9a85d53360f63a76', 'hash', 'png');
        $this->assertSame('complex/63c50acc9a85d53360f63a76/hash.png', $path);
    }

    public function test_build_url_from_path_and_file(): void
    {
        $url = $this->service->buildUrlFromPathAndFile('path/to', 'image.jpg');
        $this->assertStringContainsString('selcdn.trendagent.ru', $url);
        $this->assertStringContainsString('path/to', $url);
        $this->assertStringContainsString('image.jpg', $url);
    }

    public function test_build_url_from_path_and_file_returns_null_for_empty(): void
    {
        $this->assertNull($this->service->buildUrlFromPathAndFile(null, 'file.jpg'));
        $this->assertNull($this->service->buildUrlFromPathAndFile('path', null));
    }

}
