<?php
// +----------------------------------------------------------------------
// | ThinkNuan-x [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2018 http://www.nuan-x.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: probe <1946644259@qq.com>
// +----------------------------------------------------------------------
// | DateTime: 2017/8/29/14:08
// +----------------------------------------------------------------------

namespace app\api\model;


use think\Paginator;

class Task extends BaseModel
{
    protected $autoWriteTimestamp = true;

    /**
     * @param $id
     * @param $page
     * @param $size
     * @return Paginator
     */
    public static function getSummaryList($id, $page, $size)
    {
        $where['act_plan_id'] = $id;
        $where['release'] = 1;
        $pagingData = self::where($where)
            ->order('create_time asc')
            ->paginate($size, true, ['page' => $page]);

        return $pagingData;
    }
}