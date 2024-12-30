<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use function request;

/**
 * 基类控制器
 * @author vwms@netsun.com
 * @date: 2022/10/30 10:30
 */
class BaseController extends Controller
{

    /**
     * 构造函数
     * @author vwms@netsun.com
     * @date: 2022/10/30 10:30
     */
    public function __construct()
    {
        // 初始化网络请求配置
        $this->initRequestConfig();

        // 初始化系统常量
        $this->initSystemConst();
    }

    /**
     * 初始化请求配置
     * @since 2020/11/10
     * @author vwms@netsun.com
     */
    private function initRequestConfig()
    {
        // 定义是否GET请求
        defined('IS_GET') or define('IS_GET', request()->isMethod('GET'));

        // 定义是否POST请求
        defined('IS_POST') or define('IS_POST', request()->isMethod('POST'));

        // 定义是否AJAX请求
        defined('IS_AJAX') or define('IS_AJAX', request()->ajax());

        // 定义是否PJAX请求
        defined('IS_PJAX') or define('IS_PJAX', request()->pjax());

        // 定义是否PUT请求
        defined('IS_PUT') or define('IS_PUT', request()->isMethod('PUT'));

        // 定义是否DELETE请求
        defined('IS_DELETE') or define('IS_DELETE', request()->isMethod('DELETE'));

        // 请求方式
        defined('REQUEST_METHOD') or define('REQUEST_METHOD', request()->method());
    }

    /**
     * 初始化系统常量
     * @author vwms@netsun.com
     * @since 2020/11/10
     */
    private function initSystemConst()
    {
        // 项目根目录
        defined('ROOT_PATH') or define('ROOT_PATH', base_path());

        // 文件上传目录
        defined('ATTACHMENT_PATH') or define('ATTACHMENT_PATH', base_path('public/uploads'));

        // 图片上传目录
        defined('IMG_PATH') or define('IMG_PATH', base_path('public/uploads/images'));

        // 临时存放目录
        defined('UPLOAD_TEMP_PATH') or define('UPLOAD_TEMP_PATH', ATTACHMENT_PATH . "/temp");

        // 定义普通图片域名
        defined('IMG_URL') or define('IMG_URL', env('IMG_URL'));

        // 数据表前缀
        defined('DB_PREFIX') or define('DB_PREFIX', DB::connection()->getTablePrefix());

        // 数据库名
        defined('DB_NAME') or define('DB_NAME', DB::connection()->getDatabaseName());

        // 系统全称
        define('SITE_NAME', env('SITE_NAME'));

        // 系统简称
        define('NICK_NAME', env('NICK_NAME'));

        // 系统版本号
        define('VERSION', env('VERSION'));
    }

}
