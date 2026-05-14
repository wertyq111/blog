<?php

namespace App\Services\Api\Admin\Dashboard;

use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    public const TTL_SECONDS = 300;

    /**
     * @return array{0: array, 1: bool}
     */
    public function remember(int $userId, bool $isManager, string $view, string $range, Closure $resolver): array
    {
        $scope = $isManager ? 'manager' : "user:{$userId}";
        $version = $this->versionForScope($scope);
        $cacheKey = "dashboard:stats:{$scope}:v{$version}:{$view}:{$range}";

        if (Cache::has($cacheKey)) {
            return [Cache::get($cacheKey), true];
        }

        $data = $resolver();
        Cache::put($cacheKey, $data, now()->addSeconds(self::TTL_SECONDS));

        return [$data, false];
    }

    public function flushForUser(?int $userId): void
    {
        if ($userId) {
            $this->bumpVersion("user:{$userId}");
        }

        $this->bumpVersion('manager');
    }

    private function versionForScope(string $scope): int
    {
        $versionKey = $this->versionKey($scope);
        $version = Cache::get($versionKey);

        if ($version === null) {
            Cache::forever($versionKey, 1);
            return 1;
        }

        return (int) $version;
    }

    private function bumpVersion(string $scope): void
    {
        $versionKey = $this->versionKey($scope);
        $current = Cache::get($versionKey);
        if ($current === null) {
            Cache::forever($versionKey, 2);
            return;
        }

        Cache::forever($versionKey, (int) $current + 1);
    }

    private function versionKey(string $scope): string
    {
        return "dashboard:stats:version:{$scope}";
    }
}
