<?php


// 此文件为系统框架核心公共函数文件，为了系统的稳定与安全，未经允许不得擅自改动

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;

if (!function_exists('xml2array')) {

    /**
     * xml转数组
     * @param $xml string xml文本
     * @return string
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function xml2array($xml)
    {
        $xml = simplexml_load_string($xml);

        return json_decode(json_encode($xml), true);
    }
}

if (!function_exists('array2xml')) {

    /**
     * 数组转xml
     * @param $arr array 原始数据(数组)
     * @param bool $ignore 是否忽视true或fasle
     * @param int $level 级别(默认：1)
     * @return string 返回结果
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function array2xml($arr, $ignore = true, $level = 1)
    {
        $s = $level == 1 ? "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n<root>\r\n" : '';
        $space = str_repeat("\t", $level);
        foreach ($arr as $k => $v) {
            if (!is_array($v)) {
                $s .= $space . "<item id=\"$k\">" . ($ignore ? '<![CDATA[' : '') . $v . ($ignore ? ']]>' : '')
                    . "</item>\r\n";
            } else {
                $s .= $space . "<item id=\"$k\">\r\n" . array2xml($v, $ignore, $level + 1) . $space . "</item>\r\n";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s . "</root>" : $s;
    }
}

if (!function_exists('array_merge_multiple')) {

    /**
     * 多为数组合并
     * @param array $array1 数组1
     * @param array $array2 数组2
     * @return array 返回合并后的数组
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function array_merge_multiple($array1, $array2)
    {
        $merge = $array1 + $array2;
        $data = [];
        foreach ($merge as $key => $val) {
            if (isset($array1[$key])
                && is_array($array1[$key])
                && isset($array2[$key])
                && is_array($array2[$key])
            ) {
                $data[$key] = array_merge_multiple($array1[$key], $array2[$key]);
            } else {
                $data[$key] = $array2[$key] ?? $array1[$key];
            }
        }
        return $data;
    }
}

if (!function_exists('array_key_value')) {

    /**
     * 获取数组中某个字段的所有值
     * @param array $arr 数据源
     * @param string $name 字段名
     * @return array 返回结果
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function array_key_value($arr, $name = "")
    {
        $result = [];
        if ($arr) {
            foreach ($arr as $key => $val) {
                if ($name) {
                    $result[] = $val[$name];
                } else {
                    $result[] = $key;
                }
            }
        }
        return array_unique($result);
    }
}

if (!function_exists('curl_url')) {

    /**
     * 获取当前访问的完整地址
     * @return string 返回结果
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function curl_url()
    {
        $page_url = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === 'on') {
            $page_url .= "s";
        }
        $page_url .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $page_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $page_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $page_url;
    }
}

if (!function_exists('curl_get')) {

    /**
     * curl请求(GET)
     * @param $url string 请求地址
     * @param array $data 请求参数
     * @return bool|string 返回结果
     * @author vwms@netsun.com
     * @date 2019/6/5
     */
    function curl_get($url, $data = [])
    {
        if (!empty($data)) {
            $url = $url . '?' . http_build_query($data);
        }
        // 初始化
        $ch = curl_init();
        // 设置抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        // 设置头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 是否要求返回数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 是否检测服务器的证书是否由正规浏览器认证过的授权CA颁发的
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 执行命令
        $result = curl_exec($ch);
        // 关闭URL请求(释放句柄)
        curl_close($ch);

        return $result;
    }
}

if (!function_exists('curl_post')) {

    /**
     * curl请求(POST)
     * @param $url string 请求地址
     * @param array $data 请求参数
     * @return bool|string 返回结果
     * @author vwms@netsun.com
     * @date 2019/6/5
     */
    function curl_post($url, $data = [])
    {
        // 初始化
        $ch = curl_init();
        // 设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);
        // 设置头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // 是否要求返回数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 设置抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        // 提交的数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // 是否检测服务器的证书是否由正规浏览器认证过的授权CA颁发的
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 执行命令
        $result = curl_exec($ch);
        // 关闭URL请求(释放句柄)
        curl_close($ch);

        return $result;
    }
}

if (!function_exists('curl_request')) {

    /**
     * curl请求(支持get和post)
     * @param $url string 请求地址
     * @param array $data 请求参数
     * @param string $type 请求类型(默认：post)
     * @param bool $https 是否https请求true或false
     * @return bool|string 返回请求结果
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function curl_request($url, $data = [], $type = 'post', $https = false)
    {
        // 初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        // 设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        // 是否要求返回数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($https) {
            // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (strtolower($type) == 'post') {
            // 设置post方式提交
            curl_setopt($ch, CURLOPT_POST, true);
            // 提交的数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif (!empty($data) && is_array($data)) {
            // get网络请求
            $url = $url . '?' . http_build_query($data);
        }
        // 设置抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行命令
        $result = curl_exec($ch);
        if ($result === false) {
            return false;
        }
        // 关闭URL请求(释放句柄)
        curl_close($ch);
        return $result;
    }
}

if (!function_exists('datetime')) {

    /**
     * 格式化日期函数
     * @param $time numeric|string 时间戳
     * @param string $format 输出日期格式
     * @return string 返回格式化的日期
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }

}

if (!function_exists('data_auth_sign')) {

    /**
     * 数据签名认证
     * @param $data  array 数据源
     * @return string
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function data_auth_sign($data)
    {
        // 数据类型检测
        if (!is_array($data)) {
            $data = (array)$data;
        }
        // 排序
        ksort($data);
        // url编码并生成query字符串
        $code = http_build_query($data);
        // 生成签名
        return sha1($code);
    }
}

if (!function_exists('export_excel')) {

    /**
     * 导出Excel文件
     * @param string $file_name 文件名
     * @param array $title 标题
     * @param array $data 数据源
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function export_excel($file_name, $title = [], $data = [])
    {
        // 默认支持最大512M
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv");
        header("Content-Disposition:filename=" . $file_name);
        $fp = fopen('php://output', 'w');
        // 转码 防止乱码(比如微信昵称)
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, $title);
        $index = 0;
        foreach ($data as $item) {
            if ($index == 1000) {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp, $item);
        }
        ob_flush();
        flush();
        ob_end_clean();
    }
}

if (!function_exists('ecm_define')) {
    /**
     * 定义常量(读取数组或引用文件)
     * @param $value string|array 数据源
     * @return bool
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function ecm_define($value)
    {
        if (is_string($value)) {
            /* 导入数组 */
            $value = include($value);
        }
        if (!is_array($value)) {
            /* 不是数组，无法定义 */
            return false;
        }
        foreach ($value as $key => $val) {
            if (is_string($val) || is_numeric($val) || is_bool($val) || is_null($val)) {
                // 判断是否已定义过,否则进行定义
                defined(strtoupper($key)) or define(strtoupper($key), $val);
            }
        }

        return true;
    }
}

if (!function_exists('format_time')) {

    /**
     * 格式化时间段
     * @param int $time 时间戳
     * @return string 输出格式化时间
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function format_time($time)
    {
        $interval = time() - $time;
        $format = array(
            '31536000' => '年',
            '2592000' => '个月',
            '604800' => '星期',
            '86400' => '天',
            '3600' => '小时',
            '60' => '分钟',
            '1' => '秒',
        );
        foreach ($format as $key => $val) {
            $match = floor($interval / (int)$key);
            if (0 != $match) {
                return $match . $val . '前';
            }
        }
        return date('Y-m-d', $time);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int $size 字节大小
     * @param string $delimiter 分隔符
     * @return string 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $delimiter . $units[$i];
    }

}

if (!function_exists('format_yuan')) {

    /**
     * 以分为单位的金额转换成元
     * @param int $money 金额
     * @return string 返回格式化的金额
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function format_yuan($money = 0)
    {
        if ($money > 0) {
            return number_format($money / 100, 2, ".", "");
        }
        return "0.00";
    }

}

if (!function_exists('format_cent')) {

    /**
     * 以元为单位的金额转化成分
     * @param $money int 金额
     * @return string 返回格式化的金额
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function format_cent($money)
    {
        return (string)($money * 100);
    }

}

if (!function_exists('format_bank_card')) {

    /**
     * 银行卡格式转换
     * @param string $card_no 银行卡号
     * @param bool $is_format 是否格式化
     * @return string 输出结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function format_bank_card($card_no, $is_format = true)
    {
        if ($is_format) {
            // 截取银行卡号前4位
            $prefix = substr($card_no, 0, 4);
            // 截取银行卡号后4位
            $suffix = substr($card_no, -4, 4);

            $format_card_no = $prefix . " **** **** **** " . $suffix;
        } else {
            // 4的意思就是每4个为一组
            $arr = str_split($card_no, 4);
            $format_card_no = implode(' ', $arr);
        }
        return $format_card_no;
    }

}

if (!function_exists('format_mobile')) {

    /**
     * 格式化手机号码
     * @param string $mobile 手机号码
     * @return string 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function format_mobile($mobile)
    {
        return substr($mobile, 0, 5) . "****" . substr($mobile, 9, 2);
    }
}

if (!function_exists('get_random_code')) {

    /**
     * 获取指定位数的随机码
     * @param int $num 随机码长度
     * @return string 返回字符串
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function get_random_code($num = 12)
    {
        $codeSeeds = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeSeeds .= "abcdefghijklmnopqrstuvwxyz";
        $codeSeeds .= "0123456789_";
        $len = strlen($codeSeeds);
        $code = "";
        for ($i = 0; $i < $num; $i++) {
            $rand = rand(0, $len - 1);
            $code .= $codeSeeds[$rand];
        }
        return $code;
    }
}

if (!function_exists('get_password')) {

    /**
     * 获取双MD5加密密码
     * @param string $password 加密字符串
     * @return string 输出MD5加密字符串
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function get_password($password)
    {
        return md5(md5($password));
    }

}

if (!function_exists('get_order_num')) {

    /**
     * 生成订单号
     * @param string $prefix 订单前缀(如：JD-)
     * @return string 输出订单号字符串
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function get_order_num($prefix = '')
    {
        $micro = substr(microtime(), 2, 3);
        return $prefix . date("YmdHis") . $micro . rand(100000, 999999);
    }

}

if (!function_exists('getter')) {

    /**
     * 获取数组的下标值
     * @param array $data 数据源
     * @param string $field 字段名称
     * @param string $default 默认值
     * @return mixed|string 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function getter($data, $field, $default = '')
    {
        $result = $default;
        if (isset($data[$field])) {
            $result = $data[$field];
        }
        return $result;
    }
}

if (!function_exists('get_image_url')) {

    /**
     * 获取图片地址
     * @param $image_url string 图片地址
     * @return string 返回图片网络地址
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function get_image_url($image_url)
    {
        return IMG_URL . $image_url;
    }
}

if (!function_exists('get_hash')) {

    /**
     * 获取HASH值
     * @return string 返回hash字符串
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function get_hash()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()+-';
        $random = $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)]
            . $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)];
        $content = uniqid() . $random;
        return sha1($content);
    }
}

if (!function_exists('get_server_ip')) {

    /**
     * 获取服务端IP地址
     * @return string 返回IP地址
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function get_server_ip()
    {
        if (isset($_SERVER)) {
            if ($_SERVER['SERVER_ADDR']) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } else {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }
}

if (!function_exists('get_client_ip')) {

    /**
     * 获取客户端IP地址
     * @param int $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param bool $adv 否进行高级模式获取（有可能被伪装）
     * @return mixed 返回IP
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function get_client_ip($type = 0, $adv = false)
    {
        $type = $type ? 1 : 0;
        static $ip = null;
        if ($ip !== null) {
            return $ip[$type];
        }
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

}

if (!function_exists('get_guid_v4')) {

    /**
     * 获取唯一性GUID
     * @param bool $trim 是否去除{}
     * @return string 返回GUID字符串
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function get_guid_v4($trim = true)
    {
        // Windows
        if (function_exists('com_create_guid') === true) {
            $charid = com_create_guid();
            return $trim ? trim($charid, '{}') : $charid;
        }
        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
        // Fallback (PHP 4.2+)
        mt_srand((double)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"

        return $lbrace .
            substr($charid, 0, 8) . $hyphen .
            substr($charid, 8, 4) . $hyphen .
            substr($charid, 12, 4) . $hyphen .
            substr($charid, 16, 4) . $hyphen .
            substr($charid, 20, 12) .
            $rbrace;
    }
}

if (!function_exists('is_email')) {

    /**
     * 判断是否为邮箱
     * @param string $str 邮箱
     * @return false 返回结果true或false
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function is_email($str)
    {
        return preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $str);
    }

}

if (!function_exists('is_mobile')) {

    /**
     * 判断是否为手机号
     * @param string $num 手机号码
     * @return false 返回结果true或false
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function is_mobile($num)
    {
        return preg_match('/^1(3|4|5|7|8)\d{9}$/', $num);
    }

}

if (!function_exists('is_zipcode')) {

    /**
     * 验证邮编是否正确
     * @param string $code 邮编
     * @return false 返回结果true或false
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function is_zipcode($code)
    {
        return preg_match('/^[1-9][0-9]{5}$/', $code);
    }

}

if (!function_exists('is_idcard')) {

    /**
     * 验证身份证是否正确
     * @param string $id_no 身份证号
     * @return bool 返回结果true或false
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function is_idcard($id_no)
    {
        $id_no = strtoupper($id_no);
        $regx = '/(^\d{15}$)|(^\d{17}([0-9]|X)$)/';
        $arr_split = array();
        if (!preg_match($regx, $id_no)) {
            return false;
        }
        // 检查15位
        if (15 == strlen($id_no)) {
            $regx = '/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/';
            @preg_match($regx, $id_no, $arr_split);
            $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            if (!strtotime($dtm_birth)) {
                return false;
            } else {
                return true;
            }
        } else {
            // 检查18位
            $regx = '/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/';
            @preg_match($regx, $id_no, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            // 检查生日日期是否正确
            if (!strtotime($dtm_birth)) {
                return false;
            } else {
                // 检验18位身份证的校验码是否正确。
                // 校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ($i = 0; $i < 17; $i++) {
                    $b = (int)$id_no[$i];
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id_no, 17, 1)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

}

if (!function_exists('is_empty')) {

    /**
     * 判断是否为空
     * @param $value mixed 参数值
     * @return bool 返回结果true或false
     * @author vwms@netsun.com
     * @date 2019/6/5
     */
    function is_empty($value)
    {
        // 判断是否存在该值
        if (!isset($value)) {
            return true;
        }

        // 判断是否为empty
        if (empty($value)) {
            return true;
        }

        // 判断是否为空字符串
        if (trim($value) === '') {
            return true;
        }

        // 默认返回false
        return false;
    }
}

if (!function_exists('mkdirs')) {

    /**
     * 递归创建目录
     * @param string $dir 需要创建的目录路径
     * @param int $mode 权限值
     * @return bool 返回结果true或false
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || mkdir($dir, $mode, true)) {
            return true;
        }
        if (!mkdirs(dirname($dir), $mode)) {
            return false;
        }
        return mkdir($dir, $mode, true);
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dir 文件夹路径
     * @param bool $rmself 是否删除本身true或false
     * @return bool 返回删除结果
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function rmdirs($dir, $rmself = true)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $todo = ($file->isDir() ? 'rmdir' : 'unlink');
            $todo($file->getRealPath());
        }
        if ($rmself) {
            @rmdir($dir);
        }

        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 原文件夹路径
     * @param string $dest 目的文件夹路径
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $sent_dir = $dest . "/" . $iterator->getSubPathName();
                if (!is_dir($sent_dir)) {
                    mkdir($sent_dir, 0755, true);
                }
            } else {
                copy($item, $dest . "/" . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('message')) {

    /**
     * 消息数组
     * @param string $msg 提示文字
     * @param bool $success 是否成功true或false
     * @param array $data 结果数据
     * @param int $code 编码
     * @return array 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/28
     */
    function message($msg = "操作成功", $success = true, $data = [], $code = 0)
    {
        $result = ['success' => $success, 'msg' => $msg, 'data' => $data];
        if ($success) {
            $result['code'] = 0;
        } else {
            $result['code'] = $code ? $code : -1;
        }
        return $result;
    }
}

if (!function_exists('num2rmb')) {

    /**
     * 数字金额转大写
     * @param float $num 金额
     * @return string 返回大写金额
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function num2rmb($num)
    {
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        $num = round($num, 2);
        $num = $num * 100;
        if (strlen($num) > 10) {
            return "oh,sorry,the number is too long!";
        }
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                $n = substr($num, strlen($num) - 1, 1);
            } else {
                $n = $num % 10;
            }
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || (($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            $num = $num / 10;
            $num = (int)$num;
            if ($num == 0) {
                break;
            }
        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            $m = substr($c, $j, 6);
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j - 3;
                $slen = $slen - 3;
            }
            $j = $j + 3;
        }
        if (substr($c, strlen($c) - 3, 3) == '零') {
            $c = substr($c, 0, strlen($c) - 3);
        } // if there is a '0' on the end , chop it out
        return $c . "整";
    }
}

if (!function_exists('object_array')) {

    /**
     * 对象转数组
     * @param $object mixed 对象
     * @return mixed 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function object_array($object)
    {
        //先编码成json字符串，再解码成数组
        return json_decode(json_encode($object), true);
    }
}

if (!function_exists('parse_attr')) {

    /**
     * 配置值解析成数组
     * @param string $value 参数值
     * @return array 返回结果
     * @author vwms@netsun.com
     * @date 2019/6/6
     */
    function parse_attr($value = '')
    {
        if (is_array($value)) {
            return $value;
        }
        $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
        if (strpos($value, ':')) {
            $value = array();
            foreach ($array as $val) {
                list($k, $v) = explode(':', $val);
                $value[$k] = $v;
            }
        } else {
            $value = $array;
        }
        return $value;
    }
}

if (!function_exists('strip_html_tags')) {

    /**
     * 去除HTML标签、图像等 仅保留文本
     * @param string $str 字符串
     * @param int $length 长度
     * @return string 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function strip_html_tags($str, $length = 0)
    {
        // 把一些预定义的 HTML 实体转换为字符
        $str = htmlspecialchars_decode($str);
        // 将空格替换成空
        $str = str_replace("&nbsp;", "", $str);
        // 函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
        $str = strip_tags($str);
        $str = str_replace(array("\n", "\r\n", "\r"), ' ', $str);
        $preg = '/<script[\s\S]*?<\/script>/i';
        // 剥离JS代码
        $str = preg_replace($preg, "", $str, -1);
        if ($length == 2) {
            // 返回字符串中的前100字符串长度的字符
            $str = mb_substr($str, 0, $length, "utf-8");
        }
        return $str;
    }

}

if (!function_exists('sub_str')) {

    /**
     * 字符串截取
     * @param string $str 需要截取的字符串
     * @param int $start 开始位置
     * @param int $length 截取长度
     * @param bool $suffix 截断显示字符
     * @param string $charset 编码格式
     * @return string 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function sub_str($str, $start = 0, $length = 10, $suffix = true, $charset = "utf-8")
    {
        if (function_exists("mb_substr")) {
            $slice = mb_substr($str, $start, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        $omit = mb_strlen($str) >= $length ? '...' : '';
        return $suffix ? $slice . $omit : $slice;
    }

}

if (!function_exists('save_image')) {

    /**
     * 保存图片
     *
     * @param string $img_url 网络图片地址
     * @param string $save_dir 图片保存目录
     * @return string 返回路径
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function save_image($img_url, $save_dir = '/')
    {
        if (!$img_url) {
            return false;
        }
        $save_dir = trim($save_dir, "/");
        $imgExt = pathinfo($img_url, PATHINFO_EXTENSION);
        // 是否是本站图片
        if (str_contains($img_url, IMG_URL)) {
            // 是否是临时文件
            if (!str_contains($img_url, 'temp')) {
                return str_replace(IMG_URL, "", $img_url);
            }
            $new_path = create_image_path($save_dir, $imgExt);
            $old_path = str_replace(IMG_URL, ATTACHMENT_PATH, $img_url);
            if (!file_exists($old_path)) {
                return false;
            }
            rename($old_path, ATTACHMENT_PATH . $new_path);

            return $new_path;
        } else {
            // 保存远程图片
            $new_path = save_remote_image($img_url, $save_dir);
        }
        return $new_path;
    }
}

if (!function_exists('create_image_path')) {

    /**
     * 创建图片存储目录
     * @param string $save_dir 存储目录
     * @param string $image_ext 图片后缀
     * @param string $image_root 图片存储根目录路径
     * @return string 返回文件目录
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function create_image_path($save_dir = "", $image_ext = "", $image_root = IMG_PATH)
    {
        $image_dir = date("/Ymd/");
        if ($image_dir) {
            $image_dir = ($save_dir ? "/" : '') . $save_dir . $image_dir;
        }
        // 未指定后缀默认使用JPG
        if (!$image_ext) {
            $image_ext = "jpg";
        }
        $image_path = $image_root . $image_dir;
        if (!is_dir($image_path)) {
            // 创建目录并赋予权限
            mkdir($image_path, 0777, true);
        }
        $file_name = substr(md5(time() . rand(0, 999999)), 8, 16) . rand(100, 999) . ".{$image_ext}";

        return str_replace(ATTACHMENT_PATH, "", IMG_PATH) . $image_dir . $file_name;
    }
}

if (!function_exists('save_remote_image')) {

    /**
     * 保存网络图片到本地
     * @param string $img_url 网络图片地址
     * @param string $save_dir 保存目录
     * @return bool|string 图片路径
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function save_remote_image($img_url, $save_dir = '/')
    {
        $content = file_get_contents($img_url);
        if (!$content) {
            return false;
        }
        if ($content[0] . $content[1] == "\xff\xd8") {
            $image_ext = 'jpg';
        } elseif ($content[0] . $content[1] . $content[2] == "\x47\x49\x46") {
            $image_ext = 'gif';
        } elseif ($content[0] . $content[1] . $content[2] == "\x89\x50\x4e") {
            $image_ext = 'png';
        } else {
            // 不是有效图片
            return false;
        }
        $save_path = create_image_path($save_dir, $image_ext);
        return file_put_contents(ATTACHMENT_PATH . $save_path, $content) ? $save_path : false;
    }
}

if (!function_exists('save_image_content')) {

    /**
     * 富文本信息处理
     * @param string $content 富文本内容
     * @param bool $title 标题
     * @param string $path 图片存储路径
     * @return bool 返回结果
     * @author vwms@netsun.com
     * @date 2020-04-21
     */
    function save_image_content(&$content, $title = false, $path = 'article')
    {
        // 图片处理
        preg_match_all("/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i", str_ireplace("\\", "", $content), $match);
        if ($match[1]) {
            foreach ($match[1] as $id => $val) {
                $save_image = save_image($val, $path);
                if ($save_image) {
                    $content = str_replace($val, "[IMG_URL]" . $save_image, $content);
                }
            }
        }
        // 视频处理
        preg_match_all("/<embed .*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i", str_ireplace("\\", "", $content), $match2);
        if ($match2[1]) {
            foreach ($match2[1] as $vo) {
                $save_video = save_image($vo, $path);
                if ($save_video) {
                    $content = str_replace($vo, "[IMG_URL]" . str_replace(ATTACHMENT_PATH, "", IMG_PATH) . $save_video, $content);
                }
            }
        }
        // 提示标签替换
        if ((str_contains($content, 'alt=\"\"')) && $title) {
            $content = str_replace('alt=\"\"', 'alt=\"' . $title . '\"', $content);
        }
        return true;
    }
}

if (!function_exists('upload_image')) {

    /**
     * 上传图片
     * @param $request Request 网络请求
     * @param string $form_name 文件表单名
     * @return array 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function upload_image($request, $form_name = 'file')
    {
        // 检测请求中是否包含name=$form_name的上传文件
        if (!$request->hasFile($form_name)) {
            return message("请上传文件", false);
        }
        // 文件对象
        $file = $request->file($form_name);
        // 判断图片上传是否错误
        if (!$file->isValid()) {
            // 文件上传失败
            return message("上传文件验证失败", false);
        }
        // 文件原名
        $original_name = $file->getClientOriginalName();
        // 文件扩展名(文件后缀)
        $ext = $file->getClientOriginalExtension();
        // 临时文件的绝对路径
        $real_path = $file->getRealPath();
        // 文件类型
        $type = $file->getClientMimeType();
        // 文件大小
        $size = $file->getSize();

        // 文件大小校验
        if ($size > 5 * 1024 * 1024) {
            return message("文件大小超过了5M", false);
        }

        // 文件后缀校验
        $ext_arr = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($ext, $ext_arr)) {
            return message("文件格式不正确", false);
        }

        // 文件路径
        $file_dir = UPLOAD_TEMP_PATH . "/" . date('Ymd');

        // 检测文件路径是否存在,不存在则创建
        if (!file_exists($file_dir)) {
            mkdir($file_dir, 0777, true);
        }

        // 文件名称
        $file_name = uniqid() . '.' . $ext;
        // 重命名保存
        $path = $file->move($file_dir, $file_name);
        // 文件临时路径
        $file_path = str_replace(ATTACHMENT_PATH, '', $file_dir) . '/' . $file_name;

        // 返回结果
        $result = [
            'img_original_name' => $original_name,
            'img_ext' => $ext,
            'img_real_path' => $real_path,
            'img_type' => $type,
            'img_size' => $size,
            'img_name' => $file_name,
            'img_path' => $file_path,
        ];
        return message(MESSAGE_OK, true, $result);
    }
}

if (!function_exists('upload_file')) {

    /**
     * 上传文件
     * @param $request Request 网络请求
     * @param string $form_name 文件表单名
     * @return array 返回结果
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function upload_file($request, $form_name = 'file')
    {
        // 检测请求中是否包含上传的文件
        if (!$request->hasFile($form_name)) {
            return message("请上传文件");
        }
        // 文件对象
        $file = $request->file($form_name);
        // 判断图片上传是否错误
        if (!$file->isValid()) {
            // 文件上传失败
            return message("上传文件验证失败");
        }
        // 文件原名
        $original_name = $file->getClientOriginalName();
        // 文件扩展名(文件后缀)
        $ext = $file->getClientOriginalExtension();
        // 临时文件的绝对路径
        $real_path = $file->getRealPath();
        // 文件类型
        $type = $file->getClientMimeType();
        // 文件大小
        $size = $file->getSize();

        // 文件大小校验(MAX=5M)
        $file_max_size = 5 * 1024 * 1024;
        if ($size > $file_max_size) {
            return message("您上传的文件过大,最大值为" . $file_max_size / 1024 / 1024 . "MB");
        }

        // 允许上传的文件后缀
        $file_exts = array('xls', 'xlsx', 'csv');
        if (!in_array($ext, $file_exts)) {
            return message("文件格式不正确");
        }

        // 文件路径
        $file_dir = UPLOAD_TEMP_PATH . "/" . date('Ymd');

        // 检测文件路径是否存在,不存在则创建
        if (!file_exists($file_dir)) {
            mkdir($file_dir, 0777, true);
        }

        // 文件名称
        $file_name = uniqid() . '.' . $ext;
        // 重命名保存
        $path = $file->move($file_dir, $file_name);
        // 文件临时路径
        $file_path = str_replace(ATTACHMENT_PATH, '', $file_dir) . '/' . $file_name;

        // 返回结果
        $result = [
            'file_original_name' => $original_name,
            'file_ext' => $ext,
            'file_real_path' => $real_path,
            'file_type' => $type,
            'file_size' => $size,
            'file_name' => $file_name,
            'file_path' => $file_path,
        ];
        return message(MESSAGE_OK, true, $result);
    }
}

if (!function_exists('widget')) {

    /**
     * 加载系统组件，传入的名字会以目录和类名区别
     * 如Home.Common就代表Widget目录下的Home/Common.php这个widget。
     * @param $widgetName string 组件名称
     * @return bool|mixed
     * @throws BindingResolutionException
     * @author vwms@netsun.com
     * @date 2019/5/23
     */
    function widget($widgetName)
    {
        $widgetNameEx = explode('.', $widgetName);
        if (!isset($widgetNameEx[1])) {
            return false;
        }
        $widgetClass = 'App\\Widget\\' . $widgetNameEx[0] . '\\' . $widgetNameEx[1];
        if (app()->bound($widgetName)) {
            return app()->make($widgetName);
        }
        app()->singleton($widgetName, function () use ($widgetClass) {
            return new $widgetClass();
        });
        return app()->make($widgetName);
    }
}
