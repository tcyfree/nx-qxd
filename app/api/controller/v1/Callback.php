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
// | DateTime: 2017/10/16/11:10
// +----------------------------------------------------------------------

namespace app\api\controller\v1;
use app\api\controller\BaseController;
use app\api\model\Callback as CallbackModel;
use app\api\model\Task as TaskModel;

class Callback extends BaseController
{

    /**
     * callback.sh每秒执行此接口
     * 1 挑战模式不通过此接口结束任务
     *
     */
    public function doCallback()
    {
        $this->checkIPWhiteList();
        $where['status'] = ['neq',1];
        $callback_array = CallbackModel::whereTime('deadline','<=',time())->where($where)->select()->toArray();
        $log = $_SERVER['DOCUMENT_ROOT'].'/linux/callback.log';
        if ($callback_array){
            foreach ($callback_array as $v){
                switch ($v['key_type']){
                    case 0:
                        TaskModel::missionComplete($v,$log);
                        break;
                    default:
                        continue;
                }
            }
            return;
        }
//        file_put_contents($log, 'callback_'.date('Y-m-d H:i:s')."\r\n", FILE_APPEND);
    }
}