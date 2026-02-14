<?php

namespace Tests\Unit;

use App\Services\TrendAgent\ImportNormalizers;
use App\Services\TrendAgent\TrendAgentImageStoreService;
use Tests\TestCase;

class ImportNormalizersTest extends TestCase
{
    private TrendAgentImageStoreService $imageStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageStore = new TrendAgentImageStoreService();
    }

    public function test_plan_image_url_from_string(): void
    {
        $data = ['plan_image_url' => 'https://example.com/plan.jpg'];
        $result = ImportNormalizers::planImageUrl($data, $this->imageStore);
        $this->assertSame('https://example.com/plan.jpg', $result);
    }

    public function test_plan_image_url_from_array_with_url(): void
    {
        $data = ['plan_image' => ['url' => 'https://example.com/plan.jpg']];
        $result = ImportNormalizers::planImageUrl($data, $this->imageStore);
        $this->assertSame('https://example.com/plan.jpg', $result);
    }

    public function test_plan_image_url_from_array_with_path_and_file_name(): void
    {
        $data = ['plan_image' => ['path' => 'blocks/1', 'file_name' => 'plan.png']];
        $result = ImportNormalizers::planImageUrl($data, $this->imageStore);
        $this->assertNotNull($result);
        $this->assertStringContainsString('selcdn.trendagent.ru', $result);
    }

    public function test_plan_image_url_returns_null_for_empty(): void
    {
        $this->assertNull(ImportNormalizers::planImageUrl([], $this->imageStore));
    }

    public function test_finishing_name_from_string(): void
    {
        $this->assertSame('Чистовая', ImportNormalizers::finishingName('Чистовая'));
    }

    public function test_finishing_name_from_array(): void
    {
        $this->assertSame('Чистовая', ImportNormalizers::finishingName(['name' => 'Чистовая']));
        $this->assertSame('Черновая', ImportNormalizers::finishingName(['value' => 'Черновая']));
    }

    public function test_status_name_from_string(): void
    {
        $this->assertSame('Свободна', ImportNormalizers::statusName('Свободна'));
    }

    public function test_status_name_from_array(): void
    {
        $this->assertSame('Свободна', ImportNormalizers::statusName(['name' => 'Свободна']));
        $this->assertSame('Забронь', ImportNormalizers::statusName(['label' => 'Забронь']));
    }
}
