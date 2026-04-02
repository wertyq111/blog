<?php

namespace Tests\Unit;

use App\Http\Middleware\FilterProcess;
use Illuminate\Http\Request;
use Tests\TestCase;

class FilterProcessTest extends TestCase
{
    public function test_it_accepts_array_filter_queries_and_merges_flat_request_filters(): void
    {
        $request = Request::create('/api/members/index', 'GET', [
            'filter' => [
                'nickname' => '已存在筛选',
            ],
            'username' => 'member_filter_target',
        ]);

        $middleware = new FilterProcess();
        $model = new class
        {
            public function getRequestFilters(): array
            {
                return [
                    'username' => ['column' => 'user.username'],
                    'nickname' => ['column' => 'nickname'],
                ];
            }
        };

        $result = $middleware->handle(
            $request,
            static fn (Request $nextRequest) => $nextRequest,
            $model::class,
        );

        $this->assertSame([
            'nickname' => '已存在筛选',
            'user.username' => 'member_filter_target',
        ], $result->query->all('filter'));
    }
}
