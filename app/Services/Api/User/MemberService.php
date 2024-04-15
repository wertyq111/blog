<?php

namespace App\Services\Api\User;

use App\Models\User\Member;
use App\Services\Api\BaseService;

class MemberService extends BaseService
{

    /**
     * 添加会员
     *
     * @param $data
     * @return Member
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:58
     */
    public function add($data)
    {
        $member = new Member();

        $data = $this->completeMember($data);

        $member->fill($data);
        $member->edit();

        // 增加登录次数
        $member->login_count++;

        $member->edit();

        return $member;
    }

    /**
     * 完善会员信息
     *
     * @param $data
     * @return mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 14:46
     */
    public function completeMember($data)
    {
        // 分解城市代码
        if(isset($data['city'])) {
            $data['province_code'] = $data['city'][0] ?? null;
            $data['city_code'] = $data['city'][1] ?? null;
            $data['district_code'] = $data['city'][2] ?? null;
        }

        // 获取 ip
        $data['login_ip'] = $this->getClientIp();

        // 登录时间
        $data['login_at'] = time();

        return $data;
    }

    /**
     * 获取会员信息
     *
     * @param $data
     * @return mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 11:08
     */
    public function getMember($data)
    {
        $memberModel = new Member();
        $member = null;
        if(isset($data['id'])) {
            $member = $memberModel->find($data['id']);
        } else if(isset($data['user_id'])) {
            $member = $memberModel->where(['user_id' => $data['user_id']])->first();
        }

        if($member == null) {
            throw new \Exception("用户信息不存在");
        }

        return $member;
    }

    /**
     * 获取用户真实 ip
     * @return array|false|mixed|string
     */
    public function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        }

        if (getenv('HTTP_X_REAL_IP')) {
            $ip = getenv('HTTP_X_REAL_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            $ips = explode(',', $ip);
            $ip = $ips[0];
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

    /**
     * 壁纸用户信息
     *
     * @param $member
     * @param $ip
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 15:18
     */
    public function getWallpaperInfo($member, $ip)
    {
        // 通过高德接口获取城市信息
        $cityInfo = $this->sendGaode('ip', ['ip' => $ip]);

        $data = [];
        try {
            if($cityInfo && isset($cityInfo['adcode'])) {
                // 获取天气信息
//            $weatherInfo = $this->sendGaode('weather', ['city' => $cityInfo['adcode']]);
//            if(count($weatherInfo['lives']) > 0) {
//                return $weatherInfo['lives'][0];
//            }

                $data['ip'] = $ip;
                $data['address'] = [
                    'province' => $cityInfo['province'],
                    'city' => $cityInfo['city']
                ];

                // 获取下载壁纸信息
                $downloads = $member->downloads;
                $data['downloadSize'] = count($downloads);

                // 获取已评价壁纸信息
                $scores = $member->scores;
                $data['scoreSize'] = count($scores);

                return $data;
            } else {
                throw new \Exception('找不到指定城市');
            }
        } catch (\Exception $e) {
            throw $e;
        }

    }
}
