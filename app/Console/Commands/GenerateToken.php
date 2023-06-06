<?php

namespace App\Console\Commands;

use App\Models\User\User;
use Illuminate\Console\Command;

class GenerateToken extends Command
{
    protected $signature = 'blog:generate-token';

    protected $description = '快速为用户生成 token';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 16:19
     */
    public function handle()
    {
        $userId = $this->ask('输入用户 id');

        $user = User::find($userId);

        if (!$user) {
            $this->error('用户不存在');
        }

        // 一年以后过期，单位分钟
        $ttl = 365*24*60;
        $this->info(auth('api')->setTTL($ttl)->login($user));
    }
}
