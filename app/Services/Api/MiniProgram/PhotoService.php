<?php

namespace App\Services\Api\MiniProgram;

use App\Models\MiniProgram\Photo;
use App\Models\MiniProgram\PhotoCategory;
use App\Services\Api\BaseService;

class PhotoService  extends BaseService
{
    public function add(Photo $photo, array $data)
    {
        // 相册分类处理
        $data['category_id'] = $this->handleCategory($data);

        // 补充会员信息
        $data = array_merge($data, ['member_id' => auth('api')->user()->member->id]);

        $photo->fill($data);
        $photo->edit();

        return $photo;
    }


    /**
     * 处理相册分类
     *
     * @param $data
     * @return mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:46
     */
    public function handleCategory($data)
    {
        if(isset($data['category_id'])) {
            $categoryModel = new PhotoCategory();
            $category = $categoryModel->find($data['category_id']);
            if($category && $category->member_id == auth('api')->user()->member->id) {
                return $data['category_id'];
            }
        }

        $memberCategories = $this->getCategories();
        if(count($memberCategories) > 0) {
            $category = $memberCategories->first();
            return $category->id;
        } else {
            // 创建默认相册分类
            $category = $this->createDefaultCategory();

            return $category->id;
        }
    }

    /**
     * 获取登录用户所有的相册列表
     *
     * @return mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:38
     */
    public function getCategories()
    {
        return PhotoCategory::where('member_id', auth('api')->user()->member->id)->orderBy('id', 'asc')->get();
    }

    /**
     * 创建默认相册
     *
     * @return mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:45
     */
    public function createDefaultCategory()
    {
        $category = PhotoCategory::Create([
            'name' => '默认相册',
            'member_id' => auth('api')->user()->member->id
        ]);

        return $category;
    }
}
