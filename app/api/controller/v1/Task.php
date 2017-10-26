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
// | DateTime: 2017/8/29/13:34
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\model\ActPlan as ActPlanModel;
use app\api\model\ActPlanUser;
use app\api\model\Task as TaskModel;
use app\api\model\TaskAccelerate as TaskAccelerateModel;
use app\api\model\TaskFeedback as TaskFeedbackModel;
use app\api\model\TaskRecord as TaskRecordModel;
use app\api\model\TaskUser;
use app\api\service\Community as CommunityService;
use app\api\service\Task as TaskService;
use app\api\service\Token as TokenService;
use app\api\validate\AccelerateTask;
use app\api\validate\Feedback;
use app\api\validate\FeedbackFailReason;
use app\api\validate\FeedbackPassOrFail;
use app\api\validate\GetFeedback;
use app\api\validate\TaskList;
use app\api\validate\TaskNew;
use app\api\validate\TaskUpdate;
use app\api\validate\UUID;
use app\lib\exception\ParameterException;
use app\lib\exception\SuccessMessage;
use think\Db;
use think\Exception;
use app\api\service\Execution as ExecutionService;
use app\api\service\Execution as Es;
use app\api\model\Callback as CallbackModel;
use app\api\model\TaskUser as TaskUserModel;
use app\api\service\TaskFeedback as TaskFeedbackService;

class Task extends BaseController
{
    protected $beforeActionList = [
        'checkPrimaryScope' => ['only' => 'feedbackDetail']
    ];
    /**
     * 创建任务
     * 1.鉴权
     * @return \think\response\Json
     * @throws Exception
     */
    public function createTask()
    {
        (new TaskNew())->goCheck();
        $uid = TokenService::getCurrentUid();
        $data['user_id'] = $uid;
        $id = uuid();

        $dataArray = input('post.');
        ActPlanModel::checkActPlanExists($dataArray['act_plan_id']);
        $dataArray['id'] = $id;

        $ts = new TaskService();
        $ts->checkAuthority($uid,$dataArray['act_plan_id']);

        Db::startTrans();
        try {
            TaskModel::create($dataArray);
            //更新任务数
            $where['id'] = $dataArray['act_plan_id'];
            ActPlanModel::where($where)->setInc('task_num');

            $data['task_id'] = $id;
            $data['type'] = 0;
            TaskRecordModel::create($data);
            Db::commit();
        }catch (Exception $ex){
            Db::rollback();
            throw $ex;
        }

        return json(new SuccessMessage(),201);
    }

    /**
     * 编辑任务
     * 1.鉴权
     */
    public function updateTask()
    {
        $validate = new TaskUpdate();
        $validate->goCheck();
        $uid = TokenService::getCurrentUid();
        $data['user_id'] = $uid;

        $dataArray = $validate->getDataByRule(input('put.'));
        $t_obj = TaskModel::get(['id' => $dataArray['id']]);
        if (!$t_obj){
           throw new ParameterException();
        }else{
            $ts = new TaskService();
            $ts->checkAuthority($uid,$t_obj->act_plan_id);
        }

        TaskModel::update($dataArray,['id' => $dataArray['id']]);
        $data['task_id'] = $dataArray['id'];
        $data['type'] = 1;
        TaskRecordModel::create($data);

        return json(new SuccessMessage(), 201);
    }

    /**
     * 任务列表
     * @param $id
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getSummaryList($id,$page = 1, $size = 15)
    {
        (new TaskList())->goCheck();

        $uid = TokenService::getCurrentUid();
        CommunityService::checkJoinCommunityByUser($uid,$id);
        $pagingData = TaskModel::getSummaryList($id, $page, $size);
        $pagingArray = $pagingData->visible(['id','name','requirement','content','reference_time'])
            ->toArray();
        $task_obj = new TaskService();
        $data['task'] = $task_obj->checkTaskFinish($pagingArray,$uid);
        $res = ActPlanUser::get(['act_plan_id' => $id, 'user_id' => $uid]);
        $data['user_join_mode'] = null;
        if ($res){
            $data['user_join_mode'] = $res['mode'];
        }
        $res_data = ActPlanModel::checkActPlanExists($id);
        $data['mode'] = $res_data['mode'];
        $data['fee']  = $res_data['fee'];
         return [
            'data' => $data,
            'current_page' => $pagingData->currentPage()
        ];
    }

    /**
     * 任务详情
     * @param $id
     * @return mixed
     */
    public function getTaskDetail($id)
    {
        (new UUID())->goCheck();
        $uid = TokenService::getCurrentUid();
        TaskModel::checkTaskExists($id);
        TaskService::checkTaskByUser($uid,$id);
        $data = TaskModel::with('taskUser,feedback')->where(['id' => $id])->find();
        $return_data = $data->visible(['id','name','requirement','content','reference_time','task_user.user_id',
                                        'task_user.finish','task_user.create_time',
                                        'feedback.content','feedback.create_time','feedback.status','feedback.reason'])->toArray();

        return $return_data;
    }

    /**
     * GO任务
     * 1 添加定时任务
     *
     * @return array
     * @throws Exception
     */
    public function goTask(){
        (new UUID())->goCheck();
        $task_id = input('post.id');
        $uid = TokenService::getCurrentUid();

        $res = TaskModel::goTask($uid, $task_id);

        $res = $res->toArray();
        return [
            'task_user' => $res
        ];
    }

    /**
     * 普通任务加速
     * 1. 自己不能给自己加速
     * @throws ParameterException
     */
    public function accelerateTask(){
        (new AccelerateTask())->goCheck();
        $uid = TokenService::getCurrentUid();
        $data = input('post.');
        if ($uid == $data['user_id']){
            throw new ParameterException([
                'msg' => '小样儿，自己不能给自己加速哦'
            ]);
        }
        TaskAccelerateModel::accelerateTask($uid,$data);

        return json(new SuccessMessage(),201);
    }

    /**
     * 获取挑战模式下的状态
     * @return array
     * @throws ParameterException
     */
    public function getFeedbackStatus(){
        (new UUID())->goCheck();
        $task_id = input('get.id');
        $uid = TokenService::getCurrentUid();
        $mode = TaskModel::getTaskMode($task_id,$uid);
        if ($mode == 0){
            throw new ParameterException([
                'msg' => '此任务为普通模式参加'
            ]);
        }
        $res = TaskFeedbackModel::get(['user_id' => $uid, 'task_id' => $task_id]);
        if (!$res){
            return ['status' => null];
        }else{
            return ['status' => $res['status']];
        }

    }

    /**
     * 用户提交任务反馈
     *
     * 1 如果to_user_id为空，则随机选择一个备选人审核
     * 2 设置反馈有效时间为24小时内有效
     * @return \think\response\Json
     * @throws Exception
     * @throws ParameterException
     */
    public function feedback()
    {
        $validate = new Feedback();
        $validate->goCheck();
        $dataRules = input('post.');
        $uid = TokenService::getCurrentUid();
        if (isset($dataRules['status'])){
            unset($dataRules['status']);
        }
        if (!isset($dataRules['to_user_id'])){
            $task_service = new TaskService();
            $dataRules['to_user_id'] = $task_service->getRandManagerID($dataRules['task_id']);
        }
        $dataRules['user_id'] = $uid;
        TaskFeedbackModel::checkTaskFeedbackParams($dataRules,$uid);
        $feedback_service = new TaskFeedbackService();
        $feedback_service->referTaskFeedback($dataRules,$uid);
        return json(new SuccessMessage(),201);

    }

    /**
     * 其他用户给我的反馈
     * 1 待反馈或反馈失效
     * 2 更新to_look = 1
     *
     * @param int $page
     * @param int $size
     * @return array
     */
    public function feedbackByOthers($page = 1, $size = 15)
    {
        $uid = TokenService::getCurrentUid();
        $where['to_user_id'] = $uid;
        $pageData = TaskFeedbackModel::with('task,userInfo,task.actPlan,task.actPlan.community')
            ->where($where)
            ->where(function($query){
                $query->where('status',['=',0],['=',1],'or');
            })
            ->paginate($size,true,['page' => $page]);

        $data = $pageData->visible(['id','content','status','to_look','create_time','task.name','task.requirement','user_info.nickname','user_info.avatar','task.act_plan'])->toArray();
        TaskFeedbackModel::update(['to_look' => 1,'update_time' => time()],['to_user_id' => $uid]);
        return[
            'data' => $data,
            'current_page' => $pageData->currentPage()
        ];
    }

    /**
     * 反馈详情
     * @return null|static
     * @throws ParameterException
     */
    public function feedbackDetail()
    {
        (new UUID())->goCheck();
        $id = input('get.id');
        $res = TaskFeedbackModel::checkTaskFeedbackStatus($id);

        return $res->visible(['id','content']);

    }

    /**
     * 审核任务通过或不通过
     * 1 注销24小时回调
     *
     * @return \think\response\Json
     * @throws Exception
     */
    public function feedbackPassOrFail()
    {
        (new FeedbackPassOrFail())->goCheck();
        $data = input('put.');
        $uid = TokenService::getCurrentUid();
        $res = TaskFeedbackModel::get(['to_user_id' => $uid,'id' => $data['id']]);
        if (!$res){
            throw new ParameterException();
        }
        Db::startTrans();
        try{
            TaskFeedbackModel::checkTaskFeedbackStatus($data['id']);
            if ($data['pass']){
                TaskFeedbackModel::update(['status' => 2,'update_time' => time()],['id' => $data['id'],'to_user_id' => $uid]);
                $execution = new ExecutionService();
                $execution->addExecution($res['task_id'],$res['user_id'],1);
            }else{
                (new FeedbackFailReason())->goCheck();
                TaskFeedbackModel::update(['reason' => $data['reason'],'status' => 1,'update_time' => time()],['id' => $data['id'],'to_user_id' => $uid]);
            }
            CallbackModel::update(['status' => 1,'update_time' => time()],['key_id' => $data['id']]);
            Db::commit();
            return json(new SuccessMessage(),201);
        }catch (Exception $ex)
        {
            Db::rollback();
            throw $ex;
        }

    }

    public function test()
    {
        $t = new Es();
        $t->checkActPlanUserFinish('8b47815b-0f9c-0811-3a6a-accc9e4acdbd','b9d25df4-8e9e-f917-f559-4872db0b9ea6');
    }

}