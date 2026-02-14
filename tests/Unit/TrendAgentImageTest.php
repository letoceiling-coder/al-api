<?php

namespace Tests\Unit;

use App\Models\TrendAgent\TrendAgentImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrendAgentImageTest extends TestCase
{

    /**
     * image_url: при local_path — Storage::url(local_path), иначе — url.
     */
    public function test_image_url_returns_storage_url_when_local_path_set(): void
    {
        Storage::fake('public');

        $img = new TrendAgentImage([
            'url' => 'https://example.com/photo.jpg',
            'local_path' => 'trendagent/apartments/123/abc.jpg',
        ]);

        $expected = Storage::url('trendagent/apartments/123/abc.jpg');
        $this->assertSame($expected, $img->image_url);
    }

    public function test_image_url_returns_original_url_when_local_path_empty(): void
    {
        $img = new TrendAgentImage([
            'url' => 'https://example.com/photo.jpg',
            'local_path' => null,
        ]);

        $this->assertSame('https://example.com/photo.jpg', $img->image_url);
    }

    public function test_image_url_returns_empty_string_when_both_empty(): void
    {
        $img = new TrendAgentImage([
            'url' => null,
            'local_path' => null,
        ]);

        $this->assertSame('', $img->image_url);
    }
}
