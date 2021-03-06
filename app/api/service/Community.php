<?php
/**
 * Created by PhpStorm.
 * User: probe
 * Date: 2017/8/25
 * Time: 18:05
 */

namespace app\api\service;
use app\api\model\ActPlanUser as ActPlanUserModel;
use app\api\model\AuthUser;
use app\api\model\CommunityUserRecord as CommunityUserRecordModel;
use app\api\model\CommunityUser as CommunityUserModel;
use app\api\model\CommunityUserRecord;
use app\api\service\Token as TokenService;
use app\lib\enum\AllowJoinStatusEnum;
use app\lib\exception\CommunityException;
use app\lib\exception\ForbiddenException;
use app\lib\exception\ParameterException;
use app\api\model\Community as CommunityModel;
use app\api\model\ActPlan as ActPlanModel;
use app\api\model\AuthUser as AuthUserModel;

class Community
{
    /**
     * 校验、过滤重复权限值
     * @param $auth
     * @return string
     * @throws ParameterException
     */
    public static function authFilter($auth)
    {
        $str = str_replace('，', ',', $auth);
        if(empty($str))
        {
            return false;
        }
        $subject = explode(',', $str);

        foreach ($subject as $v)
        {
            $pattern = array(1,2,3,4);
            if (!in_array($v, $pattern))
            {
                throw new ParameterException(['msg' => "'".$v."'不符合规则！"]);
            }
        }
        $res = array_unique($subject);

        return implode(",", $res);
    }

    /**
     * 获取推荐社群正在行动的总人数
     * 闭包构造子查询
     * @param $data
     * @return mixed
     */
    public static function getSumActing($data)
    {

        foreach ($data as &$v){
            $act_plan_user = new ActPlanUserModel();
            $v['count'] = $act_plan_user
                ->where('finish','0')
                ->where('act_plan_id','in',function ($query) use ($v){
                    $query->table('qxd_act_plan')->where('community_id',$v['id'])->field('id');
                })
                ->count('user_id');
        }

        return $data;
    }

    /**
     * 当用户登录时，给出用户和此社群关联 (社长|管理员|成员)
     * @param $data
     * @return mixed
     */
    public static function getType($data)
    {
        $uid = TokenService::getAnyhowUid();
        foreach ($data as &$v){
            $where['community_id'] = $v['id'];
            $where['user_id'] = $uid;
            $where['delete_time'] = 0;
            $arr = CommunityUserModel::get($where);
            $v['type'] = $arr['type'];

        }
        return $data;
    }

    /**
     * 获取用户和社群的关联关系
     * 用户已经参加的社群数量
     * 用户是否加入该社群
     * 权限值
     * 是否付费
     *
     * @param $data
     * @return mixed
     * @throws ParameterException
     */
    public static function getUserStatus($data)
    {
        $uid = TokenService::getAnyhowUid();

        $community_user = CommunityUserModel::get(['user_id' => $uid, 'community_id' => $data['id']]);
        if(!$community_user){
            $data['user']['join'] = false;
            $data['user']['status'] = null;
            $data['user']['type'] = null;
            $data['user']['pay'] = null;
        }else{
            $data['user']['join'] = true;
            $data['user']['status'] = $community_user['status'];
            $data['user']['type'] = $community_user['type'];
            $data['user']['pay'] = $community_user['pay'];
        }
        $obj = new CommunityUserModel();
        $data['user']['count'] = $obj->where(['user_id' => $uid, 'status' =>['neq',1]])->count('user_id');
        $data['user']['auth'] = AuthUserModel::getAuthUserWithCommunity($uid,$data['id']);
        return $data;
    }

    /**
     * 1. 判断加入社群是否存在
     * 2. 判断是否重复加入该社群
     * @param $id
     * @param $uid
     * @throws ParameterException
     */
    public static function checkCommunityUserExists($id, $uid)
    {
        $community = CommunityModel::get(['id' => $id]);
        if(!$community){
            throw new ParameterException([
                'msg' => '社群不存在,请检查ID'
            ]);
        }
        $where['community_id'] = $id;
        $where['user_id'] = $uid;
        $where['status'] = ['neq','1'];
        $community_user = CommunityUserModel::get(function ($query) use ($where){
            $query->where($where);
        });
        if($community_user){
            throw new ParameterException([
                'msg' => '已加入该社群成员，不能重复加入'
            ]);
        }
    }

    /**
     * 判断用户相关的行动是否达到上限ALLOW_JOIN_OUT
     * 不包含已退的社群
     *
     * @param $uid
     * @param $check
     * @param $count_manager
     * @throws CommunityException
     */
    public static function checkAllowJoinStatus($uid, $check = false, $count_manager = 0)
    {
        $obj = new CommunityUserModel();
        $where['user_id'] = $uid;
        $where['status'] = ['in',[0,2]];
        $count = $obj->where($where)->count('user_id');
        if ($check){
            if (($count_manager < AllowJoinStatusEnum::ALLOW_JOIN_MANAGER) && ($count == AllowJoinStatusEnum::ALLOW_JOIN_OUT)){
                return true;
            }
        }
        if($count >= AllowJoinStatusEnum::ALLOW_JOIN_OUT){
            throw new CommunityException([
                'msg' => '站住！您加入的社群数量已超过'.AllowJoinStatusEnum::ALLOW_JOIN_OUT.'个了，不可贪多啊！',
                'code' => 400
            ]);
        }
    }

    /**
     * 判断用户相关的行动是否达到上限
     * 管理+社长：ALLOW_JOIN_MANAGER
     * 不包含已退的社群
     * 允许加入数是否超过3个
     * 1.check = true 被设置管理员和转让社群的用户，拥有管理+社长 < ALLOW_JOIN_MANAGER，加入/拥有/管理社群总数上限 = ALLOW_JOIN_OUT时，对其放行。
     *
     * @param $uid
     * @param $check
     * @throws CommunityException
     */
    public static function checkManagerAllowJoinStatus($uid,$check = false)
    {
        $obj = new CommunityUserModel();
        $where['user_id'] = $uid;
        $where['status'] = ['in','0,2'];
        $where['type'] = ['in','0,1'];
        $count = $obj->where($where)->count('user_id');
        $log = LOG_PATH.'checkManager.log';
        file_put_contents($log,$obj->getLastSql().' '.$count.' '.date('Y-m-d H:i:s')."\r\n",FILE_APPEND);
        if($count >= AllowJoinStatusEnum::ALLOW_JOIN_MANAGER){
            throw new CommunityException([
                'msg' => '该用户拥有社长+管理员身份社群数量超过'.AllowJoinStatusEnum::ALLOW_JOIN_MANAGER.'个',
                'code' => 400
            ]);
        }
        self::checkAllowJoinStatus($uid,$check,$count);
    }



    /**
     * 1. 检查该社群人数是否已达上限
     * 2. 检查付费用户是否达上限
     * 二者满足其一即不然再加入社群了
     * @param $community_id
     * @throws CommunityException
     */
    public static function checkCommunityUserLimit($community_id)
    {
        $obj = new CommunityUserModel();
        $where['community_id'] = $community_id;
        $where['status'] = ['neq',1];
        $count = $obj->where($where)->count('user_id');

        $community = CommunityModel::get(['id' => $community_id]);
        if($count == $community->scale_num){
            throw new CommunityException([
                'msg' => '该社群总人数'.$community->scale_num.'上限已满',
                'code' => 400
            ]);
        }

        $where['pay'] = 1;
        $count = $obj->where($where)->count('user_id');

        if($count == $community->pay_num){
            throw new CommunityException([
                'msg' => '该社群付费总人数'.$community->pay_num.'上限已满',
                'code' => 400
            ]);
        }
    }

    /**
     * 根据行动计划id判断该用户是否参加对应的社群
     * @param $uid
     * @param $act_plan_id
     * @return bool
     * @throws ParameterException
     */
    public static function checkJoinCommunityByUser($uid, $act_plan_id)
    {
        $data = ActPlanModel::checkActPlanExists($act_plan_id);

        $community_id = $data['community_id'];

        $res = CommunityUserModel::get(['user_id' => $uid, 'community_id' => $community_id]);
        if(!$res){
            throw new ParameterException([
                'msg' => '该用户还未参加此行动计划的社群'
            ]);
        }else
            return true;
    }

    /**
     * 判断用户是否有权限
     * 1.社长或管理员
     * 2.付费用户
     * @param $where
     * @return bool
     * @throws ForbiddenException
     * @throws ParameterException
     */
    public function checkAuthority($where)
    {
        $res = CommunityUserModel::get($where);
        if (!$res){
            throw new ParameterException([
                'msg' => '还未参加该社群'
            ]);
        }
        if (($res->type != 2) || ($res->pay == 1)){
            return true;
        }else{
            throw new ForbiddenException([
                'msg' => '你是普通用户，没有此权限！'
            ]);
        }
    }

    /**
     * 检查此社群管理员权限
     * 1.如果是社长直接放行
     * @param $uid
     * @param $community_id
     * @param $subject
     * @return bool
     * @throws ForbiddenException
     */
    public function checkManagerAuthority($uid,$community_id,$subject)
    {
        $where['to_user_id'] = $uid;
        $where['community_id'] = $community_id;
        $where['delete_time'] = 0;

        $cu_obj = CommunityUserModel::get(['community_id' => $community_id, 'user_id' => $uid]);
        if ($cu_obj->type ==0){
            return true;
        }
        $res = AuthUserModel::get($where);
        if(!$res){
            throw new ForbiddenException();
        }else{
            $pattern = explode(',', $res->auth);
            foreach ($subject as $v){
                if (!in_array($v, $pattern))
                {
                    throw new ForbiddenException();
                }
            }
        }

    }

    /**
     * 检查此社群管理员权限
     * 1.如果是社长直接放行
     * 2.将不在权限内结果false返回
     *
     * @param $uid
     * @param $community_id
     * @param $subject
     * @return bool
     */
    public function checkNewManagerAuthority($uid,$community_id,$subject)
    {
        $where['to_user_id'] = $uid;
        $where['community_id'] = $community_id;
        $where['delete_time'] = 0;

        $cu_obj = CommunityUserModel::get(['community_id' => $community_id, 'user_id' => $uid]);
        if ($cu_obj->type ==0){
            return true;
        }
        $res = AuthUserModel::get($where);
        if(!$res){
            return false;
        }else{
            $pattern = explode(',', $res->auth);
            foreach ($subject as $v){
                if (!in_array($v, $pattern))
                {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 检查是否是社长
     * @param $community_id
     * @param $uid
     * @throws ForbiddenException
     */
    public function checkPresident($community_id,$uid)
    {
        $res = CommunityUserModel::get(['community_id' => $community_id, 'user_id' => $uid]);
        if ($res->type !=0){
            throw new ForbiddenException([
                'msg' => '你不是社长，没有此权限！'
            ]);
        }
    }

    /**
     *判断用户最近一次退出社群是否为付费用户
     * 1 需要去用户加入社群备份里面去查找
     *
     * @param $community_id
     * @param $uid
     * @return int
     */
    public static function getPayLastJoinCommunity($community_id,$uid)
    {
        $where['community_id'] = $community_id;
        $where['user_id'] = $uid;
        $pay = CommunityUserRecordModel::where($where)
            ->field('pay')
            ->order('create_time DESC')
            ->select()->toArray();
        if ($pay){
            return $pay[0]['pay'];
        }else{
            return 0;
        }
    }
}