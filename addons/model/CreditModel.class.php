<?php
/**
 * 积分模型 - 数据对象模型
 * @example
 * $credit = model('Credit')->setUserCredit($uid,'weibo_demo');
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
class CreditModel extends Model {
	
	// 所有设置的值
	var $info;
	var $creditType;
	private $type = 'experience'; // 等级的图标类型
	
	/**
	 * +----------------------------------------------------------
	 * 架构函数
	 * +----------------------------------------------------------
	 * 
	 * @author melec制作
	 * @access public
	 *         +----------------------------------------------------------
	 */
	public function __construct() {
		if (($this->creditType = F ( '_service_credit_type' )) === false) {
			$this->creditType = M ( 'credit_type' )->order ( 'id ASC' )->findAll ();
			F ( '_service_credit_type', $this->creditType );
		}
	}
	
	/**
	 * 获取积分设置信息
	 * 
	 * @return array 积分设置信息
	 */
	public function getSetData() {
		if (($data = model ( 'Cache' )->get ( 'credit_set' )) == false) {
			$data = model ( 'Xdata' )->get ( 'admin_Credit:set' );
			model ( 'Cache' )->set ( 'credit_set', $data );
		}
		
		return $data;
	}
	
	/**
	 * 获取用户积分
	 *
	 * 返回积分值的数据结构
	 * <code>
	 * array(
	 * 'score' =>array(
	 * 'credit'=>'1',
	 * 'alias' =>'积分',
	 * ),
	 * 'experience'=>array(
	 * 'credit'=>'2',
	 * 'alias' =>'经验',
	 * ),
	 * '类型' =>array(
	 * 'credit'=>'值',
	 * 'alias' =>'名称',
	 * ),
	 * )
	 * </code>
	 *
	 * @param int $uid        	
	 * @return boolean array
	 */
	public function getUserCredit($uid) {
		if (empty ( $uid ))
			return false;

		$userCredit = S('getUserCredit_'.$uid);
		if($userCredit!=false){
			return $userCredit;
		}

		$userCreditInfo = M ( 'credit_user' )->where ( "uid={$uid}" )->find (); // 用户积分
		
		if(!$userCreditInfo){
			$data['uid'] = $uid;
			M('credit_user')->add($data);// 用户积分
		}

		foreach ( $this->creditType as $v ) {
			$userCredit ['credit'] [$v ['name']] = array (
					'value' => intval ( $userCreditInfo [$v ['name']] ),
					'alias' => $v ['alias'] 
			);
		}
		
		$userCredit ['creditType'] = $this->getTypeList ();
		
		// 获取积分等级规则
		$level = $this->getLevel ();
		$data = $userCredit ['credit'] [$this->type] ['value'];
		
		foreach ( $level as $k => $v ) {
			if ($data >= $v ['start'] && $data <= $v ['end']) {
				$userCredit ['level'] = $v;
				$userCredit ['level'] ['level_type'] = $this->type;
				$userCredit ['level'] ['nextNeed'] = $v ['end'] - $data;
				$userCredit ['level'] ['nextName'] = $level [$k + 1] ['name'];
				if (is_numeric($userCredit ['level'] ['image'])) {
					$userCredit ['level'] ['src'] = getImageUrlByAttachId($userCredit['level']['image']);
				} else {
					$userCredit ['level'] ['src'] = THEME_PUBLIC_URL . '/image/level/' . $userCredit ['level'] ['image'];
				}
				break;
			}
			if ($data > $v ['end'] && ! isset ( $level [$k + 1] )) {
				$userCredit ['level'] = $v;
				$userCredit ['level'] ['nextNeed'] = '';
				$userCredit ['level'] ['nextName'] = '';
				if (is_numeric($userCredit ['level'] ['image'])) {
					$userCredit ['level'] ['src'] = getImageUrlByAttachId($userCredit['level']['image']);
				} else {
					$userCredit ['level'] ['src'] = THEME_PUBLIC_URL . '/image/level/' . $userCredit ['level'] ['image'];
				}
				break;
			}
		}
		S('getUserCredit_'.$uid, $userCredit, 604800);  //缓存一周
		return $userCredit;
	}

    /**
     * 获取用户积分（new）
     */
    /**
     * @param mixed $uid
     */
    public function getUserCreditInfo($uid)
    {
        return M('credit_user')->where('uid = '.$uid)->find();
    }
	
	/**
	 * 获取积分类型列表
	 * 
	 * @param string $return
	 *        	返回类型，默认为has
	 * @return [type] [description]
	 */
	public function getTypeList() {
		$arr = array ();
		foreach ( $this->creditType as $value ) {
			$arr [$value['name']] = $value ['alias'];
		}
		
		return $arr;
	}
	
	/**
	 * 获取积分等级规则
	 * 
	 * @return array 积分等级规则信息
	 */
	public function getLevel() {
		$data = model ( 'Xdata' )->get ( 'admin_Credit:level' );
		if (! $data) {
			$file = ADDON_PATH . '/lang/zh-cn/creditlevel.php';
			$data = include ($file);
			model ( 'Xdata' )->put ( 'admin_Credit:level', $data );
		}
		
		return $data;
	}
	
	/**
	 * 添加任务积分
	 * 
	 * @param int $exp        	
	 * @param int $score        	
	 * @param int $uid        	
	 */
	public function addTaskCredit($exp, $score, $uid) {
		// 加积分
		D ( 'credit_user' )->setInc ( 'experience', 'uid=' . $uid, $exp );
		D ( 'credit_user' )->setInc ( 'score', 'uid=' . $uid, $score );
		
		$this->cleanCache($uid);
	}
	
	/**
	 * TS2兼容方法：获取积分类型列表
	 * 
	 * @return array 积分类型列表
	 */
	public function getCreditType() {
		return $this->creditType;
	}
	
	/**
	 * TS2兼容方法：设置用户积分
	 * 操作用户积分
	 *
	 * @param int $uid
	 *        	用户ID
	 * @param array|string $action
	 *        	系统设定的积分规则的名称
	 *        	或临时定义的一个积分规则数组，例如array('score'=>-4,'experience'=>3)即socre减4点，experience加三点
	 * @param string|int $type
	 *        	reset:按照操作的值直接重设积分值，整型：作为操作的系数，-1可实现增减倒置
	 * @return Object
	 */
	public function setUserCredit($uid, $action, $type = 1) {
		if (! $uid) {
			$this->info = false;
			return $this;
		}
		if (is_array ( $action )) {
			$creditSet = $action;
		} else {
			// 获取配置规则
			$credit_ruls = $this->getCreditRules ();
			foreach ( $credit_ruls as $v )
				if ($v ['name'] == $action)
					$creditSet = $v;
		}
		if (! $creditSet) {
			$this->info = '积分规则不存在';
			return $this;
		}
		$creditUserDao = M ( 'credit_user' );
		$creditUser = $creditUserDao->where ( "uid={$uid}" )->find (); // 用户积分
		                                                              // 积分计算
		if ($type == 'reset') {
			foreach ( $this->creditType as $v ) {
				$creditUser [$v ['name']] = $creditSet [$v ['name']];
			}
		} else {
			$type = intval ( $type );
			foreach ( $this->creditType as $v ) {
				$creditUser [$v ['name']] = $creditUser [$v ['name']] + ($type * $creditSet [$v ['name']]);
			}
		}
		$creditUser ['uid'] || $creditUser ['uid'] = $uid;
		// $res = $creditUserDao->save ( $creditUser ) || $res = $creditUserDao->add ( $creditUser ); // 首次进行积分计算的用户则为插入积分信息
		if($creditUserDao->where('uid='.$creditUser['uid'])->count()){
			$map['id'] = $creditUser['id'];
			$map['uid'] = $creditUser['uid'];
			unset($creditUser['id']);unset($creditUser['uid']);
			$res = $creditUserDao->where($map)->save ( $creditUser );
		}else{
			$res = $creditUserDao->add ( $creditUser );
		}                                  
		// 用户进行积分操作后，登录用户的缓存将修改
		$this->cleanCache($uid);
		// $userLoginInfo = S('S_userInfo_'.$uid);
		// if(!empty($userLoginInfo)) {
		// $userLoginInfo['credit']['score']['credit'] = $creditUser['score'];
		// $userLoginInfo['credit']['experience']['credit'] = $creditUser['experience'];
		// S('S_userInfo_'.$uid, $userLoginInfo);
		// }
		if ($res) {
			$this->info = $creditSet ['info'];
			return $this;
		} else {
			$this->info = false;
			return $this;
		}
	}
	
	/**
	 * 获取积分操作结果
	 *
	 * return string
	 */
	public function getInfo() {
		return $this->info;
	}
	
	/**
	 * 获取所有系统积分规则
	 */
	public function getCreditRules() {
		if (($res = F ( '_service_credit_rules' )) === false) {
			$res = M ( 'credit_setting' )->order ( 'type ASC' )->findAll ();
			F ( '_service_credit_rules', $res );
		}
		return $res;
	}

	
	/**
	 * 保存积分等级规则
	 * @param array $d 修改的积分等级规则
	 * @return void
	 */
	public function saveCreditLevel($d) {
		$data = $this->getLevel();
		$data[$d['level'] - 1]['name'] = $d['name'];
		$data[$d['level'] - 1]['image'] = $d['image'];
		$data[$d['level'] - 1]['start'] = $d['start'];
		$data[$d['level'] - 1]['end'] = $d['end'];
		model('Xdata')->put('admin_Credit:level', $data);
		
		//清除用户缓存
		$users = model('User')->field('uid')->findAll();
		foreach($users as $user){
			$this->cleanCache($user['uid']);
		}
	}
	/**
	 * 清除用户积分缓存
	 * @return void
	 */
	public function cleanCache($uid) {
		S ( 'S_userInfo_' . $uid, null );
		S('getUserCredit_'.$uid, NULL);
	}

	/**
	 * 根据条件获取用户积分
	 * @param $map 		查询条件
	 * @param $ordert 	排序规则
	 * @param $limit 	分页数量
	 *
	 * @return $res		用户积分列表
	 */
	public function getGreditUser($map,$ordert,$limit){
		$res = M('credit_user')->where($map)->order($ordert)->findPage($limit);
		return $res;
	}

	/**
	 * 更新字段
	 * @param $map  条件
	 * @param $data 数据
	 */
	public function saveCreditUser($map,$data){
		$res = M('credit_user')->where($map)->save($data);
		return $res;
	}

    /**
     * 余额兑换/充值
     * @param integer $uid 用户ID
     * @param integer $num 需要充值/兑换的数量
     * @return 如果成功则返回true，失败返回false
     */
    public function recharge($uid, $num){
        //取得用户
        $user = $this->getUserCreditInfo($uid);
        if(!$user) return false;
        $this->cleanCache($uid);

        //余额充值
        $user['score']  += $num;
        if($user['score'] < 0){
            $user['score'] = 0;
        }
        //保存并返回结果
        $result = M('credit_user')->where("uid = {$uid}")->save($user);
        return $result !== true;
    }

    /**
     * 余额扣除/消费
     * @param integer $uid 用户ID
     * @param integer $num 需要扣除/消费的数量
     * @return 如果成功则返回true，失败返回false
     */
    public function rmfreeze($uid, $num){
        //取得用户
        $user = $this->getUserCreditInfo($uid);
        if(!$user) return false;
        $this->cleanCache($uid);

        //余额充值
        $user['score']  -= $num;
        //保存并返回结果
        $result = M('credit_user')->where("uid = {$uid}")->save($user);
        return $result !== true;
    }

	/**
	 * 添加一条流水记录
	 * @param integer $uid 用户ID
	 * @param integer $type 流水类型(0:消费,1:充值,2:冻结,3:解冻,4:冻结扣除,6:操作增加,7:操作减少)
	 * @param integer $num 变动数量
	 * @param string $note 业务备注
	 * @param integer $relId 关联ID
	 * @param string $relType 关联类型
	 * @return 如果成功则返回true，失败返回false
	 */
	public function addCreditFlow($uid, $type, $num, $relId = 0, $relType = '', $note = ''){
		//取得用户
		$user = $this->getUserCreditInfo($uid);
		if(!$user) return false;
		$data['uid']      = $uid;
		$data['type']     = $type;
		$data['num']      = $num;
        $data['rel_id']   = $relId;
        $data['rel_type'] = $relType;
        $data['note']     = $note;
        $data['ctime']    = time();
		$data['balance']  = $user['score'];
		return M('credit_user_flow')->add($data);
	}

    /**
     * 添加多条流水记录 暂未用 如需用请自行调试
     * @param integer $uid 用户ID
     * @param integer $type 流水类型(0:消费,1:充值,2:冻结,3:解冻,4:冻结扣除,5:分成收入)
     * @param integer $num 变动数量
     * @param array   $data 多条数据参数
     * @param string $relType 关联类型
     * @return 如果成功则返回true，失败返回false
     */
    public function addCreditFlows($uid, $type, $num, $data, $relType = ''){
        //取得用户
        $user = $this->getUserCreditInfo($uid);
        if(!$user) return false;
        $time = time();
        $insert_value = '';
        foreach($data as $key=>$val){
            $insert_value .= "('" . $uid . "','" . $type . "','" . $num . "','" .  $val['video_title'] . "','" . $val['id'] . "','" . $relType . "','" . $time . "','" . $user['balance'] . "'),";
        }
        $query = "INSERT INTO " . C("DB_PREFIX") . "zy_learncoin_flow (`uid`,`type`,`num`,`note`,`rel_id`,`rel_type`,`ctime`,`balance`) VALUE " . trim($insert_value, ',');
        return M('credit_user_flow')->execute($query)? true : false;
    }

	/**
	 * 积分规则模型---增加积分
	 * @param integer $uid 用户ID
	 * @param integer $type 流水类型(6:增加,7:减少)
	 * @param array   $relId 积分规则关联ID
	 * @param string $relType 积分类型
	 * @param integer $num 增加积分数量
	 * @param integer $mostNums 每天积分上限
	 * @param integer $note 流水记录
	 * @return 如果成功则返回true，失败返回false
	 */
	public function addUserCreditRule($uid,$type,$relId,$relType = '',$num,$mostNums,$note = ''){
        $credit = M('credit_user')->where('uid='.$uid)->getField('score');
        if($num < 0 && $credit <= 0 ){
            return false;
        }
		$today = strtotime(date('Y-m-d', time()));
		$end = $today + 24 * 60 * 60;
		$where = "uid=$uid AND ctime >$today AND ctime <$end AND rel_id=$relId ";
        $flow_count = M('credit_user_flow')->where($where)->count();
        if($mostNums == 0){
            $result = $this->recharge($uid,$num);
        }else if($flow_count < $mostNums){
            $result = $this->recharge($uid,$num);
        }
		if($result){
			$num = abs($num);
			$this->addCreditFlow($uid, $type, $num, $relId, $relType, $note);
		}
		return $result !== true;
	}

	/*
	 * 获取积分规则
	 */
	public function getCreditInfo($uid,$id){
		$credit = M('credit_setting')->where('id='.$id)->field('id,name,alias,score,count')->find();
		if($credit['score'] > 0){
			$type = 6;
			$note = $credit['alias'].'获得的积分';
		}else if($credit['score'] < 0){
            $type = 7;
            $note = $credit['alias'].'扣除的积分';
        }
        $this->addUserCreditRule($uid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
	}

	/**
	 * 记录积分操作事件--适用每日单次事件
	 * @param integer $uid 用户ID
	 * @param string $type 增加积分所触发事件
	 * @param integer $num 增加积分数量
	 * @return 如果成功则返回true，失败返回false
	 */
	public function addCreditType($uid,$type,$num){
		$today = strtotime(date('Y-m-d', time()));
		$end = $today + 24 * 60 * 60;
		$where = "uid=$uid";
		$user_type = M('credit_user')->where($where)->field('type,ctime')->find();
		$new_type = explode(',',$user_type['type']);
		if(in_array($type,$new_type)){
			if($today>$user_type['ctime']){
				/*if($type == $user_type['type']){
                    $data['type'] = '';
                }else{
                    $new = preg_replace("/,?\b{$type}\b/","", $user_type['type']);
                    $data['type'] = $new;
                }*/
				$data['type'] = '';
				$res = M('credit_user')->where($where)->save($data);
				if($res){
					M('credit_user')->where($where)->setInc('count');
					$this->addCreditType($uid,$type,$num);
				}
			}
		}else{
			if($user_type['type']){
				$types = $user_type['type'].','.$type;
				$user['type'] = $types;
			}else{
				$user['type'] = $type;
			}
			$res = M('credit_user')->where($where)->save($user);
			if($res){
				$result = $this->recharge($uid,$num);
			}
		}
		return $result !== true;
	}

	/**
	 * 记录积分操作事件--适用每日多次事件
	 * @param integer $uid 用户ID
	 * @param integer $count 触发事件次数
	 * @param integer $num 增加积分数量
	 * @return 如果成功则返回true，失败返回false
	 */
	public function addCreditTypeNums($uid,$count,$num){
		$today = strtotime(date('Y-m-d', time()));
		$end = $today + 24 * 60 * 60;
		$where = "uid=$uid AND ctime >$today AND ctime <$end";
		$user_count = M('credit_user')->where($where)->getField('count');
		if($user_count < $count){
			$res = $this->recharge($uid,$num);
			if($res == true){
				$result = M('credit_user')->where("uid = {$uid}")->setInc('count');
			}
		}else{
			$time = time();
			$tomorrow = $end + 24 * 60 * 60;
			if($end <$time && $time <$tomorrow){
				$user['count'] = 0;
				$result = M('credit_user')->where("uid = {$uid}")->save($user);
			}
		}
		return $result !== true;
	}

}