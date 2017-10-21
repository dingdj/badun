<?php
/**
 * 分成管理模型
 * @author dengjb <dengjiebin@higher-edu.cn>
 * @version GJW2.0
 */
class ZySplitModel extends Model {

    protected $tableName = 'zy_split_balance'; //映射到分成表
    protected $flowModel = null;//分成流水模型对象

    /**
     * 模型初始化
     * @return void
     */
    public function _initialize(){
        $this->flowModel = M('zy_split_balance_flow');
    }

    //关联ID的类型描述
    protected static $relTypes = array(
        'zy_order'       => '课程订单',
        'zy_order_album' => '套餐订单',
        'zy_withdraw'    => '提现记录',
        'zy_recharge'    => '充值记录'
    );

    /**
     * 检查一个用户的余额/冻结的数量是否够支配
     * @param integer $uid 用户ID
     * @param integer $num 需要支配的数量
     * @param string $fieid balance or frozen
     * @return boolean 够支配返回true，不够支配则返回false
     */
    public function isSufficient($uid, $num, $fieid = 'balance'){
        $total = $this->where(array('uid'=>$uid))->getField($fieid);
        return $total >= $num ? true : false;
    }

    /**
     * 余额转冻结
     * @param integer $uid 用户ID
     * @param integer $num 需要冻结的数量
     * @return 如果成功则返回true，失败返回false
     */
    public function freeze($uid, $num){
        //取得用户
        $user = $this->getUser($uid);
        //检查用户的余额是否够支配
        if(!$user || $num > $user['balance']){
            return false;
        }
        //余额转冻结
        $user['balance'] -= $num;
        $user['frozen']  += $num;
        //保存并返回结果
        $result = $this->save($user);
        return $result !== true;
    }



    /**
     * 冻结转余额
     * @param integer $uid 用户ID
     * @param integer $num 需要解冻的数量
     * @return 如果成功则返回true，失败返回false
     */
    public function unfreeze($uid, $num){
        //取得用户
        $user = $this->getUser($uid);
        //检查用户的冻结是否够支配
        if(!$user || $num > $user['frozen']){
            return false;
        }
        //冻结转余额
        $user['balance'] += $num;
        $user['frozen']  -= $num;
        //保存并返回结果
        $result = $this->save($user);
        return $result !== true;
    }

    /**
     * 余额消费/扣除
     * @param integer $uid 用户ID
     * @param integer $num 需要扣除的余额数量
     * @return 如果成功则返回true，失败返回false
     */
    public function consume($uid, $num){
        //取得用户
        $user = $this->getUser($uid);
        //检查用户的余额是否够支配
        if(!$user || $num > $user['balance']){
            return false;
        }
        //余额扣除
        $user['balance'] -= $num;
        //保存并返回结果
        $result = $this->save($user);
        return $result !== true;
    }

    /**
     * 冻结扣除
     * @param integer $uid 用户ID
     * @param integer $num 需要扣除冻结的数量
     * @return 如果成功则返回true，失败返回false
     */
    public function rmfreeze($uid, $num){
        //取得用户
        $user = $this->getUser($uid);
        //检查用户的冻结是否够支配
        if(!$user || $num > $user['frozen']){
            return false;
        }
        //冻结扣除
        $user['frozen']  -= $num;
        //保存并返回结果
        $result = $this->save($user);
        return $result !== true;
    }

    /**
     * 余额充值
     * @param integer $uid 用户ID
     * @param integer $num 需要充值的数量
     * @return 如果成功则返回true，失败返回false
     */
    public function recharge($uid, $num){
        //取得用户
        $user = $this->getUser($uid);
        if(!$user) return false;

        //余额充值
        $user['balance']  += $num;
        //保存并返回结果
        $result = $this->save($user);
        return $result !== true;
    }

    /**
     * 添加分成/收益
     * @param integer $uid 用户ID
     * @param integer $num 需要添加分成/收益的数量
     * @return 如果成功则返回true，失败返回false
     */
    public function income($uid, $num){
        return $this->recharge($uid, $num);
    }

    /**
     * 添加一条流水记录
     * @param integer $uid 用户ID
     * @param integer $type 流水类型(0:消费,1:充值,2:冻结,3:解冻,4:冻结扣除,5:分成收入)
     * @param integer $num 变动数量
     * @param string $note 业务备注
     * @param integer $relId 关联ID
     * @param string $relType 关联类型
     * @return 如果成功则返回true，失败返回false
     */
    public function addFlow($uid, $type, $num, $note = '', $relId = 0, $relType = ''){
        //取得用户
        $user = $this->getUser($uid);
        if(!$user) return false;
        $data['uid']      = $uid;
        $data['type']     = $type;
        $data['num']      = $num;
        $data['balance']  = $user['balance'];
        $data['rel_id']   = $relId;
        $data['rel_type'] = $relType;
        $data['note']     = $note;
        $data['ctime']    = time();
        return $this->flowModel()->add($data)?  : false;
    }

    /**
     * 添加多条流水记录
     * @param integer $uid 用户ID
     * @param integer $type 流水类型(0:扣除,1:增加,2:冻结,3:解冻,4:冻结扣除,5:分成收入)
     * @param integer $num 变动数量
     * @param array   $data 多条数据参数
     * @param string $relType 关联类型
     * @return 如果成功则返回true，失败返回false
     */
    public function addOldFlows($uid, $type, $num, $data, $relType = ''){
        //取得用户
        $user = $this->getUser($uid);
        if(!$user) return false;
        $time = time();
        $insert_value = '';
        foreach($data as $key=>$val){
            $insert_value .= "('" . $uid . "','" . $type . "','" . $num . "',' 购买课程<" .  $val['video_title'] . "> ','" . $val['id'] . "','" . $relType . "','" . $time . "','" . $user['balance'] . "'),";
        }
        $query = "INSERT INTO " . C("DB_PREFIX") . "zy_split_balance_flow (`uid`,`type`,`num`,`note`,`rel_id`,`rel_type`,`ctime`,`balance`) VALUE " . trim($insert_value, ',');
        return M('zy_split_balance_flow')->query($query)? true : false;
    }

    /**
     * 添加多条流水记录 并给分成用户加钱
     * @param integer $uid 购买用户ID
     * @param integer $vid 购买课程id
     * @param integer $type 流水类型(0:扣除,1:增加,2:冻结,3:解冻,4:冻结扣除,5:分成收入)
     * @param string $relType 关联类型
     * @param integer $rel_id 关联id 第三方购买记录id
     * @return 如果成功则返回true，失败返回false
     */
    public function addVideoFlows( $map, $type, $relType = ''){
        if($relType == 'zy_video_order' || $relType == 'zy_order_course'){
            $split_model = M('zy_split_course');
            $field = 'id,pid,platform_sum,oschool_uid,ouats_ouschool_sum,sid,school_sum,mount_school_id,mount_school_sum,share_id,share_sum,note';
        }else if($relType == 'zy_album_order'){
            $field = 'id,pid,platform_sum,oschool_uid,ouats_ouschool_sum,sid,school_sum,share_id,share_sum,note';
            $split_model = M('zy_split_album');
        }else if($relType == 'zy_live_order'){
            $field = 'id,pid,platform_sum,oschool_uid,ouats_ouschool_sum,sid,school_sum,mount_school_id,mount_school_sum,share_id,share_sum,note';
            $split_model = M('zy_split_live');
        }else if($relType == 'zy_teacher_order'){
            $field = 'id,pid,platform_sum,oschool_uid,ouats_ouschool_sum,sid,school_sum,note';
            $split_model = M('zy_split_teacher');
        }

        $data = $split_model->where($map)->field($field)->find();
        $note = stristr($data['note'],'。',true);
        $rel_id = $data['id'];

        unset($data['id']);
        unset($data['note']);

        if(!$data){
            return false;
        }

        //如果平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，销毁购买用户机构管理员、课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $data['oschool_uid'] && $data['pid'] == $data['sid']) {
            $data['platform_sum'] = round($data['platform_sum'] + $data['ouats_ouschool_sum'] + $data['school_sum'], 2);
            unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
            unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            unset($data['sid']);                //课程所属机构分成的管理员用户id
            unset($data['school_sum']);         //课程所属机构获得的金额
            //如果购买用户的所属机构管理员和平台管理员相同 销毁购买用户的所属机构管理员的金额 直接加到平台管理员账户上
        }else if($data['oschool_uid'] == $data['pid']){
            $data['platform_sum'] += $data['ouats_ouschool_sum'];
            unset($data['oschool_uid']);
            unset($data['ouats_ouschool_sum']);
            //如果购买用户的所属机构管理员和课程所属机构管理员相同 销毁购买用户的所属机构管理员的金额的金额 直接加到课程所属机构管理员账户上
        }else if($data['oschool_uid'] == $data['sid']){
            $data['school_sum'] += $data['ouats_ouschool_sum'];
            unset($data['oschool_uid']);
            unset($data['ouats_ouschool_sum']);
            //如果购买用户的所属机构管理员和挂载课程机构管理员相同 销毁买用户的所属机构管理员的金额的金额的金额 直接加到挂载课程机构管理员账户上
        }else if($data['oschool_uid'] == $data['mount_school_id']){
            $data['mount_school_sum'] += $data['mount_school_sum'];
            unset($data['oschool_uid']);
            unset($data['ouats_ouschool_sum']);
            //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到机构管理员账户上
        }else if($data['pid'] == $data['sid']) {
            $data['school_sum'] += $data['school_sum'];
            unset($data['sid']);
            unset($data['school_sum']);
        }

//        //如果平台管理员和机构管理员相同 销毁机构的金额 直接加到平台管理员账户上
//        if($data['pid'] == $data['sid']){
//            $data['platform_sum'] += $data['school_sum'];
//            unset($data['sid']);
//            unset($data['school_sum']);
//            //如果机构管理员和教师相同 销毁教师的金额 直接加到机构管理员账户上
//        }else if($data['sid'] == $data['st_id']){
//            $data['school_sum'] += $data['school_teacher_sum'];
//            unset($data['st_id']);
//            unset($data['school_teacher_sum']);
//            //如果平台管理员、机构管理员、教师都相同 销毁机构管理员、教师的金额 直接加到平台管理员账户上
//        }else if($data['pid'] == $data['sid'] && $data['sid'] == $data['st_id']){
//            $data['platform_sum'] = $data['platform_sum'] + $data['school_sum'] + $data['school_teacher_sum'];
//            unset($data['sid']);
//            unset($data['school_sum']);
//            unset($data['st_id']);
//            unset($data['school_teacher_sum']);
//        }

        if($data['pid']){
            $flow_uid[0] = $data['pid'];
        }
        if($data['oschool_uid']){
            $flow_uid[1] = $data['oschool_uid'];
        }
        if($data['sid']){
            $flow_uid[2] = $data['sid'];
        }
        if($data['mount_school_id']){
            $flow_uid[3] = $data['mount_school_id'];
        }
        //分享者自己操作使用
//        if($data['share_id']){
//            $flow_uid[4] = $data['share_id'];
//        }

        if($data['platform_sum'] && $data['platform_sum'] != '0.00'){
            $flow_num[0] = $data['platform_sum'];
        }
        if($data['ouats_ouschool_sum'] && $data['ouats_ouschool_sum'] != '0.00'){
            $flow_num[1] = $data['ouats_ouschool_sum'];
        }
        if($data['school_sum'] && $data['school_sum'] != '0.00'){
            $flow_num[2] = $data['school_sum'];
        }
        if($data['mount_school_sum'] && $data['mount_school_sum'] != '0.00'){
            $flow_num[3] = $data['mount_school_sum'];
        }
        //分享者自己操作使用
//        if($data['share_sum'] && $data['share_sum'] != '0.00'){
//            $flow_num[4] = $data['share_sum'];
//        }

//        if($data['st_id']){
//            $flow_st_id = trim($data['st_id'],',');
//            $flow_teacher = array_filter(explode(',',$flow_st_id ));
//            $flow_st_num = count($flow_teacher);
//            //如果讲师有多个用户
//            if($flow_st_num > 1){
//                foreach ($flow_teacher as $key => $val){
//                    $flow_uid[$key+3] = $val;
//                }
//            }else{
//                $flow_uid[3] = trim($data['st_id'],',');
//            }
//        }
//        if($data['school_teacher_sum']){
//            //如果讲师有多个用户
//            if($flow_st_num > 1){
//                foreach ($flow_teacher as $key => $val){
//                    $flow_num[$key+3] = $data['school_teacher_sum'];
//                }
//            }else {
//                $flow_num[3] = $data['school_teacher_sum'];
//            }
//        }

        if($type == 0){
            foreach ($flow_uid as $key => $val) {
                foreach ($flow_num as $k => $v) {
                    if($key == $k) {
                        $balance = M('zy_split_balance')->where(array('uid'=>$flow_uid[$k]))->getField('balance');
                        $list[$k]['uid']     = $flow_uid[$k];//变动用户id
                        $list[$k]['num']     =  $flow_num[$k];//变动数量
                        $list[$k]['balance'] = $balance - $flow_num[$k];//变动后余额
                    }
                }
            }
        }elseif($type == 5){
            foreach ($flow_uid as $key => $val) {
                foreach ($flow_num as $k => $v) {
                    if($key == $k) {
                        $balance = M('zy_split_balance')->where(array('uid'=>$flow_uid[$k]))->getField('balance');
                        $list[$k]['uid']     = $flow_uid[$k];//变动用户id
                        $list[$k]['num']     =  $flow_num[$k];//变动数量
                        $list[$k]['balance'] = $balance + $flow_num[$k];//变动后余额
                    }
                }
            }
        }

        $value = "";
        foreach ($list as $key => $val){
            $value .= "('".$val['uid']."','".$val['balance']."','".time()."'),";
        }
        $sql = "replace into ".C('DB_PREFIX')."zy_split_balance (`uid`,`balance`,`ltime`) values " . trim($value, ',');
        $res = M('zy_split_balance')->execute($sql)? true : false;
        if($res){
            $time = time();
            $insert_value = '';
            foreach($list as $key=>$val){
                $balance = M('zy_split_balance')->where(array('uid'=>$val['uid']))->getField('balance');
                $insert_value .= "('" . $val['uid'] . "','" . $type . "','" . $val['num'] . "','" .  $note . "','" . $rel_id . "','" . $relType . "','" . $time . "','" . $balance . "'),";
            }
            $query = "INSERT INTO " . C("DB_PREFIX") . "zy_split_balance_flow (`uid`,`type`,`num`,`note`,`rel_id`,`rel_type`,`ctime`,`balance`) VALUE " . trim($insert_value, ',');
            return M('zy_split_balance_flow')->execute($query)? true : false;
        }
    }

    /**
     * 取得一个用户的扩展信息
     * @param integer $uid 用户UID
     * @return array
     */
    public function getUser($uid){
        return $this->where(array('uid'=>$uid))->find();
    }

    /**
     * 初始化一个用户的扩展信息
     * @param integer $uid 用户UID
     * @param array $data 初始化数据
     * @param boolean $isForce 是否强制初始化，强制初始化将删除之前的信息，谨慎操作
     * @return boolean 成功返回true或失败返回false
     */
    public function initUser($uid, $data = null, $isForce = false){
        if($isForce){
            if(false === $this->delUser($uid)) return false;
        }
        if($isForce || !$this->userExists($uid)){
            static $init = array(
                'balance' => 0,
                'frozen'  => 0,
            );
            $init['uid'] = $uid;
            $init['ltime'] = time();
            if(is_array($data)){
                $data = array_merge($init, $data);
            }else{
                $data = $init;
            }
            return $this->add($data)? true : false;
        }
        return true;
    }

    /**
     * 查询用户扩展信息是否存在
     * @param integer $uid 用户UID
     * @return boolean 存在返回true，不存在返回false
     */
    public function userExists($uid){
        return $this->where(array('uid'=>$uid))->count()>0?true:false;
    }

    /**
     * 删除用户扩展信息，不可恢复，谨慎操作
     * @param integer $uid 用户UID
     * @return boolean 成功返回true，失败返回false
     */
    public function delUser($uid){
        return $this->where(array('uid'=>$uid))->delete()!==false ? true : false;
    }

    /**
     * 取得rel_type 的描述
     * @return array
     */
    public function getRelTypes(){
        return self::$relTypes;
    }

    /**
     * 取得流水记录模型对象
     * return Model
     */
    public function flowModel(){
        return $this->flowModel;
    }

}