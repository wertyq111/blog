<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BaseResource;
use App\Models\Web\City;
use GuzzleHttp\Client;
use GuzzleHttp\Utils;
use Spatie\QueryBuilder\QueryBuilder;

class WeatherController extends Controller
{

    /**
     * 获取天气信息
     * @return array|mixed|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/5/17 15:01
     */
    public function index()
    {
        $ip = request()->getClientIp();

        // 测试 ip
        if($ip == '192.168.28.59') {
            $ip = '125.118.5.27';
        }

        try {
            // 根据 ip 获取 ip 定位
            $addressInfo = $this->send('ip', ['ip' => $ip]);
            if($addressInfo && isset($addressInfo['city'])) {
                $city = QueryBuilder::for(City::class)->where(['name' => $addressInfo['city']])->first()->toArray();
                $cityCode = $city['citycode'];

                // 查询天气信息
                $weatherInfo = $this->send('weather', ['city' => $cityCode]);
                if($weatherInfo['status'] && $weatherInfo['infocode'] == config('weather.amap.response.infocode')) {
                    return new BaseResource($weatherInfo['lives'][0]);
                }
            } else {
                throw new \Exception('找不到指定城市');
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * 发送请求
     * @param $type
     * @param $params
     * @return array|bool|float|int|object|string|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/5/17 14:59
     */
    protected function send($type, $params)
    {
        // 请求 url
        $url = null;

        // 网关信息
        $params['key'] = config('weather.amap.key');

        switch($type) {
            case 'ip':
                $url = config('weather.amap.ip_position');
                break;
            case 'weather':
                $url = config('weather.amap.weather_info');
                break;
        }

        $client = new Client();
        $res = $client->get($url, [
            'query' => $params
        ]);

        return Utils::jsonDecode($res->getBody(), true);
    }
}
