<?php

use App\Models\User\User;
use App\Services\Api\Admin\ProductImage\Contracts\HostResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    Schema::dropAllTables();
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('username')->nullable();
        $table->string('email')->nullable()->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('openid')->nullable()->unique();
        $table->string('unionid')->nullable()->unique();
        $table->string('password')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->integer('status')->default(0);
        $table->rememberToken();
        $table->unsignedInteger('created_at')->default(0);
        $table->integer('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('code')->nullable();
        $table->unsignedInteger('deleted_at')->default(0);
    });
    Schema::create('user_role', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id')->default(0);
        $table->unsignedBigInteger('role_id')->default(0);
    });

    $this->app->instance(HostResolver::class, new class implements HostResolver {
        public function resolve(string $host): array
        {
            return ['1.1.1.1'];
        }
    });
});

it('返回已注册的商品图片平台', function () {
    $token = productImageExtractorLoginAsAdmin();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/design/product-image-extractor/platforms')
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.0.code', 'honor')
        ->assertJsonPath('data.0.name', '荣耀商城')
        ->assertJsonPath('data.0.domains.0', 'www.honor.com');
});

it('提取多颜色商品轮播图及响应头元数据', function () {
    $token = productImageExtractorLoginAsAdmin();
    productImageExtractorFakeHonorResponses();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/design/product-image-extractor/extract', [
            'platform' => 'honor',
            'url' => 'https://www.honor.com/cn/shop/product/90001.html',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.platform.code', 'honor')
        ->assertJsonPath('data.variants.0.attributes.颜色', '棱镜黑')
        ->assertJsonPath('data.product.title', '荣耀Earbuds耳夹式耳机Pro')
        ->assertJsonPath('data.variants.0.skuId', '10001')
        ->assertJsonPath('data.variants.0.images.0.index', 1)
        ->assertJsonPath('data.variants.0.images.0.mimeType', 'image/png')
        ->assertJsonPath('data.variants.0.images.0.bytes', strlen(productImageExtractorPng()))
        ->assertJsonCount(4, 'data.variants.0.images')
        ->assertJsonCount(3, 'data.variants.1.images');

    expect($response->json('data.variants.0.images.0.id'))->toHaveLength(64);
});

it('服务端重新解析商品页并仅下载匹配的图片 ID', function () {
    $token = productImageExtractorLoginAsAdmin();
    productImageExtractorFakeHonorResponses();
    $sourceUrl = 'https://www.honor.com/cn/shop/product/90001.html';

    $extractResponse = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/design/product-image-extractor/extract', [
            'platform' => 'honor',
            'url' => $sourceUrl,
        ]);
    $imageId = $extractResponse->json('data.variants.0.images.0.id');

    $downloadResponse = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/design/product-image-extractor/download', [
            'platform' => 'honor',
            'url' => $sourceUrl,
            'imageIds' => [$imageId],
        ]);

    $downloadResponse
        ->assertOk()
        ->assertHeader('content-type', 'application/zip');

    $binaryResponse = $downloadResponse->baseResponse;
    $zipPath = $binaryResponse->getFile()->getPathname();
    $zip = new \ZipArchive();

    expect($zip->open($zipPath))
        ->toBeTrue()
        ->and($zip->numFiles)
        ->toBe(1)
        ->and($zip->getNameIndex(0))
        ->toContain('10001/01_poster.png');

    $zip->close();
    unlink($zipPath);
});

it('拒绝伪造的图片 ID', function () {
    $token = productImageExtractorLoginAsAdmin();
    productImageExtractorFakeHonorResponses();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/design/product-image-extractor/download', [
            'platform' => 'honor',
            'url' => 'https://www.honor.com/cn/shop/product/90001.html',
            'imageIds' => [str_repeat('a', 64)],
        ])
        ->assertOk()
        ->assertJsonPath('code', 422)
        ->assertJsonPath('msg', '选中的图片已失效，请重新提取商品图片');
});

/**
 * 伪造荣耀商品页和图片响应。
 *
 * @return void
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/20
 */
function productImageExtractorFakeHonorResponses(): void
{
    $html = file_get_contents(base_path('tests/Fixtures/product-image/honor-two-variants.html'));
    $png = productImageExtractorPng();

    Http::fake(function (Request $request) use ($html, $png) {
        if (str_contains($request->url(), '/cn/shop/product/90001.html')) {
            return Http::response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Length' => (string) strlen($html),
            ]);
        }

        return Http::response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($png),
        ]);
    });
}

/**
 * 获取有效的单像素 PNG 测试图片。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/20
 */
function productImageExtractorPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
}

/**
 * 创建商品图片提取测试管理员令牌。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/20
 */
function productImageExtractorLoginAsAdmin(): string
{
    $admin = User::query()->create([
        'username' => 'product_image_admin',
        'email' => 'product-image-admin@example.com',
        'phone' => '13800000013',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);
    $roleId = DB::table('roles')->insertGetId([
        'name' => '超级管理员',
        'code' => 'super',
        'deleted_at' => 0,
    ]);
    DB::table('user_role')->insert([
        'user_id' => $admin->id,
        'role_id' => $roleId,
    ]);

    return auth('api')->login($admin);
}
