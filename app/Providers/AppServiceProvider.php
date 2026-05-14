<?php

namespace App\Providers;

use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkDoc;
use App\Models\User\User;
use App\Models\Web\Comment;
use App\Observers\Admin\WorkDailyLogObserver;
use App\Observers\Admin\WorkDocObserver;
use App\Observers\UserObserver;
use App\Observers\Web\CommentObserver;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Comment::observe(CommentObserver::class);
        User::observe(UserObserver::class);
        WorkDailyLog::observe(WorkDailyLogObserver::class);
        WorkDoc::observe(WorkDocObserver::class);
        JsonResource::withoutWrapping();
    }
}
