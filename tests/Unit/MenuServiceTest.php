<?php

namespace Tests\Unit;

use App\Services\Api\MenuService;
use PHPUnit\Framework\TestCase;

class MenuServiceTest extends TestCase
{
    public function test_permission_contrast_map_covers_all_frontend_checked_list_options(): void
    {
        $map = MenuService::permissionContrastMap();

        $expectedKeys = [1, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $map);
            $this->assertArrayHasKey('title', $map[$key]);
            $this->assertArrayHasKey('permission', $map[$key]);
        }
    }
}
