
<?php
/**
 * 在线统计模型 - 业务逻辑模型
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
class OnlineModel{

	private $today = 0;						// 今日日期字符串
	private $todayTimestamp = 0;			// 今日0点的时间戳
	private $check_point = 0;				// 查询在线起始时间点的时间戳
	private $check_step = 1200;				// 检查在线用户步长，10分钟检查一次
	private $stats_step = 1800;				// 统计在线用户步长，30分钟
	/**
	 * 初始化方法，数据库配置、连接初始化
	 * @return void
	 */
	public function __construct() {
		$dbconfig = array();
		$_config = C('ONLINE_DB');
		$dbconfig['DB_TYPE'] = isset($_config['DB_TYPE']) ? $_config['DB_TYPE'] : C('DB_TYPE');
		$dbconfig['DB_HOST'] = isset($_config['DB_HOST']) ? $_config['DB_HOST'] : C('DB_HOST');
		$dbconfig['DB_NAME'] = isset($_config['DB_NAME']) ? $_config['DB_NAME'] : C('DB_NAME');
		$dbconfig['DB_USER'] = isset($_config['DB_USER']) ? $_config['DB_USER'] : C('DB_USER');
		$dbconfig['DB_PWD'] = isset($_config['DB_PWD']) ? $_config['DB_PWD'] : C('DB_PWD');
		$dbconfig['DB_PORT'] = isset($_config['DB_PORT']) ? $_config['DB_PORT'] : C('DB_PORT');
		$dbconfig['DB_PREFIX'] = isset($_config['DB_PREFIX']) ? $_config['DB_PREFIX'] : C('DB_PREFIX');
		$dbconfig['DB_CHARSET'] = isset($_config['DB_CHARSET']) ? $_config['DB_CHARSET'] : C('DB_CHARSET');

		$db_pwd = $dbconfig['DB_PWD'];

		if($dbconfig['DB_ENCRYPT'] == 1) {
			if($db_pwd != '') {
				require_once(SITE_PATH.'/addons/library/CryptDES.php');
				$crypt = new CryptDES;
				$db_pwd = (string)$crypt->decrypt($db_pwd);
			}
		}
		// 重设Service的数据连接信息
		$connection = array(
							'dbms' => $dbconfig['DB_TYPE'],
							'hostname' => $dbconfig['DB_HOST'],
							'hostport' => $dbconfig['DB_PORT'],
							'database' => $dbconfig['DB_NAME'],
							'username' => $dbconfig['DB_USER'],
							'password' => $db_pwd,
						);
		// 实例化Online数据库连接
		$this->odb = new Db($connection);
		$this->today = date('Y-m-d');
		$this->todayTimestamp = strtotime(date('Y-m-d'));	
	}

	/**
	 * 获取统计列表
	 * @param string $where 查询条件
	 * @param integer $limit 结果集数目，默认为30
	 * @return array 统计列表数据
	 */
	public function getStatsList($where = '1',$limit = 30) {
		$p = $_REQUEST['p'] ? $_REQUEST['p'] : 1;
		$start = ($p - 1) * $limit;
		$sqlCount = "SELECT COUNT(1) as count FROM ".C('DB_PREFIX')."online_stats WHERE {$where} ";
		if($count = $this->odb->query($sqlCount)) {
			$count = $count[0]['count'];
		} else {
			$count = 0;
		}

		$sql = "SELECT * FROM ".C('DB_PREFIX')."online_stats WHERE {$where} LIMIT $start,$limit";

		$data = $this->odb->query($sql);

		$p = new Page($count, $limit);
		$output['count'] =$count;
		$output['totalPages'] = $p->totalPages;
		$output['totalRows'] = $p->totalRows;
		$output['nowPage'] = $p->nowPage;
		$output['html'] = $p->show();
		$output['data'] = $data;

		return $output;
	}

	/**
	 * 执行统计
	 * @return void
	 */
	public function dostatus() {
		// 获取最大ID
		$sql = 'SELECT MAX(id) AS max_id FROM '.C('DB_PREFIX').'online_logs WHERE statsed = 0';
		$max_id = $this->odb->query($sql);
		$max_id = @$max_id['0']['max_id'];
		if(empty($max_id)) {
			return false;
		} else {
			$sql = "UPDATE ".C('DB_PREFIX')."online_logs SET statsed = 1 WHERE id < {$max_id}";
			$this->odb->execute($sql);
		}
		// 开始统计
		// TODO:需要计划任务支持移动上一日数据到备份表，现在在每次统计之后备份今天之前的数据到备份表
		// 从logs累计总的用户数，总的游客数到stats表
		$userDataSql = "SELECT COUNT(1) AS pv, COUNT(DISTINCT uid) AS pu, COUNT(distinct(ip)) AS guestpu, `day`, isGuest
						FROM `".C('DB_PREFIX')."online_logs`
						WHERE id <= {$max_id}
						GROUP BY day, isGuest";

		$userData = $this->odb->query($userDataSql);

		if(!empty($userData)) {
			$upData = array();
			foreach($userData as $v) {
				if($v['isGuest'] == 0) {
					// 注册用户
					$upData[$v['day']]['total_users'] = $v['pu'];
					$upData[$v['day']]['total_pageviews'] += $v['pv'];
				} else {
					// 游客
					$upData[$v['day']]['total_guests'] = $v['guestpu'];
					$upData[$v['day']]['total_pageviews'] += $v['pv'];
				}
			}
			foreach($upData as $k=>$v) {
				$sql = "SELECT id FROM ".C('DB_PREFIX')."online_stats WHERE day = '{$k}'";
				$issetRow  = $this->odb->query($sql);
				if(empty($issetRow)) {
					$sql = "INSERT INTO ".C('DB_PREFIX')."online_stats (`day`,`total_users`,`total_guests`,`total_pageviews`) 
							 VALUES ('{$k}','{$v['total_users']}','{$v['total_guests']}','{$v['total_pageviews']}')";
				} else {
					$sql = " UPDATE ".C('DB_PREFIX')."online_stats 
							 SET total_users = '{$v['total_users']}',
							 total_guests = '{$v['total_guests']}',
							 total_pageviews = {$v['total_pageviews']}
							 WHERE day = '{$k}'";
				}
				$this->odb->execute($sql);
			}
		}

		// 从online表统计在线用户到most_onine_user表
		$this->checkOnline();
		// 将logs表中今天之前的的数据移动到bak表
		$sql = "INSERT INTO ".C('DB_PREFIX')."online_logs_bak SELECT * FROM `".C('DB_PREFIX')."online_logs` WHERE day <='".date('Y-m-d', strtotime('-1 day'))."'";
		$this->odb->execute($sql);
		// 删除logs表中今天之前的数据删除
		$sql = " DELETE FROM `".C('DB_PREFIX')."online_logs` WHERE day <='".date('Y-m-d', strtotime('-1 day'))."'";
		// 统计结束
		$this->odb->execute($sql);
	}

	/**
	 * 在线用户检查及入库
	 * @return void
	 */
	public function checkOnline() {
		$startTime = time() - $this->stats_step;
		$day = date('Y-m-d');
		// 今日统计数据
		$sql = "SELECT * FROM ".C('DB_PREFIX')."online_stats WHERE day ='{$day}'";
		$dayData =  $this->odb->query($sql);

		if(!empty($dayData)) {
			// 在线注册用户
			$sql = "SELECT COUNT(1) AS pu FROM ".C('DB_PREFIX')."online WHERE uid !=0 AND activeTime  >= {$startTime}";
			$onlineData = $this->odb->query($sql);

			$set = array();
			if($onlineData && $onlineData[0]['pu'] > 0 && $onlineData[0]['pu'] > $dayData[0]['most_online_users']) {
				$set[] = 'most_online_users = '.$onlineData[0]['pu'];
			}
			// 在线游客
			$sql = "SELECT COUNT(ip) AS pu FROM ".C('DB_PREFIX')."online WHERE uid = 0 AND activeTime >= {$startTime}";
			$onlineGuestData = $this->odb->query($sql);
			if($onlineGuestData && $onlineGuestData[0]['pu'] > 0 && $onlineGuestData[0]['pu'] > $dayData[0]['most_online_guests']) {
				$set[] = ' most_online_guests = '.$onlineGuestData[0]['pu'];
			}
			$mostUser = intval($onlineData[0]['pu']) + intval($onlineGuestData[0]['pu']);
			if(empty($mostUser)) {
				$mostUser = 1;
			}
			if($mostUser >= $dayData[0]['most_online']) {
				$set[] = ' most_online = '.$mostUser;
				$set[] = ' most_online_time  ='.time();
			}

			if(!empty($set)) {
				$sql = " UPDATE ".C('DB_PREFIX')."online_stats SET ".implode(',', $set)." WHERE day = '{$day}'";
				$this->odb->execute($sql);
			}
		}
	}

	/**
	 * 获取指定用户最后操作的IP地址信息
	 * @param array $uids 指定用户ID数组
	 * @return array 指定用户最后操作的IP地址信息
	 */
	public function getLastOnlineInfo($uids) {
		$map['uid'] = array('IN', $uids);
		$data = D()->table(C('DB_PREFIX').'online')->where($map)->getHashList('uid', 'ip');
		return $data;
	}

	/**
	 * 获取指定用户的操作日志 - 分页型
	 * @param integer $uid 用户ID
	 * @param array $map 查询条件
	 * @param integer $count 结果集数目，默认为20
	 * @param string $order 排序条件，默认为day DESC
	 * @return array 指定用户的操作日志 - 分页型
	 */
	public function getUserOperatingList($uid, $map, $count = 20, $order = 'id DESC') {
		$map['uid'] = $uid;
		$data = D()->table(C('DB_PREFIX').'online_logs_bak')->where($map)->order($order)->findPage($count);
		return $data;
	}

	/**
	 * 获取数据看板列表
	 * @param string $where 查询条件
	 * @return array 统计列表数据
	 *
	 */
	public function getDataCount($where,$mhm_id,$time) {
		$total = [];
        if($mhm_id){
            $tid = D('ZyTeacher','classroom')->getSchoolAllTeacher($mhm_id);
            $new_tid = implode(',',$tid);
            $vid = D('ZyVideo','classroom')->getSchoolAllVideo($mhm_id);
            $new_vid = implode(',',$vid);
            if($where){
                $whereOrder = $where."AND mhm_id= {$mhm_id}";
                $whereReview = $where."AND oid IN ({$new_vid}) ";
                $whereTeacher = $where."AND tid IN ({$new_tid}) ";
            }else{
                $whereOrder = " mhm_id= '{$mhm_id}' ";
                $whereTeacher = " tid IN ({$new_tid}) ";
                $whereReview = " oid IN ({$new_vid}) ";
            }
        }else{
            $whereOrder = $whereTeacher = $whereReview = $where;
        }
		//订单数
		$videoCount = M('zy_order_course')->where($whereOrder)->count();
		$liveCount = M('zy_order_live')->where($whereOrder)->count();
		$albumCount = M('zy_order_album')->where($whereOrder)->count();
		$teacherCount = M('zy_order_teacher')->where($whereTeacher)->count();
		//商品订单
        if(!$mhm_id){
            $goodsCount = M('goods_order')->where($where)->count();
        }
        //订单总数
        $total['order'] = $videoCount + $liveCount + $albumCount + $teacherCount + $goodsCount ?: 0;


        //获取学员数
        $userCount = M('user')->where($whereOrder)->count();
        $total['user'] = $userCount  ?: 0;

        //课程数
        if($time){
            $whereOrder = 'listingtime >'.$time.' AND type = 1 AND is_activity=1 AND is_del=0 AND mhm_id='.$mhm_id.' ';
        }else{
            $whereOrder = 'type = 1 AND is_activity=1 AND is_del=0 AND mhm_id='.$mhm_id.' ';
        }
        $courseCount = M('zy_video')->where($whereOrder)->count();
        $total['video'] = $courseCount  ?: 0;

		//评论数
		$reviewCount = M('zy_review')->where($whereReview)->count();
        $total['review'] = $reviewCount  ?: 0;

		//获取提问数
		$commentCount = M('zy_question')->where($whereReview)->count();
        $total['question'] = $commentCount  ?: 0;

        return $total;
	}

	/**
	 * 获取学员活跃列表
	 * @param string $where 查询条件
	 * @return array 统计列表数据
	 *
	 */
	public function getStudentCount($where = '1',$mhm_id) {
		$total = [];
        if($mhm_id){
            $whereOrder = $where."AND mhm_id= {$mhm_id}";
            $tid = D('ZyTeacher','classroom')->getSchoolAllTeacher($mhm_id);
            $new_tid = implode(',',$tid);
            $whereTeacher = $where."AND tid IN ({$new_tid}) ";
            $uid = model('school')->getSchoolAllUserById($mhm_id);
            $new_uid = implode(',',$uid);
            $whereReview = $where."AND uid IN ({$new_uid}) ";
        }else{
            $whereOrder = $whereTeacher = $whereReview = $where;
        }
		//获取提问数
		$commentcount = M('zy_question')->where($whereReview)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		if(!empty($commentcount)){
			$total = $this->haddleCountData([],$commentcount);
		}
		//订单数
		$videoCount = M('zy_order_course')->where($whereOrder)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		if(!empty($videoCount)){
			$total = $this->haddleCountData($total,$videoCount);
		}
		$liveCount = M('zy_order_live')->where($whereOrder)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		if(!empty($liveCount)){
			$total = $this->haddleCountData($total,$liveCount);
		}
		$albumCount = M('zy_order_album')->where($whereOrder)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		if(!empty($albumCount)){
			$total = $this->haddleCountData($total,$albumCount);
		}
		$teacherCount = M('zy_order_teacher')->where($whereTeacher)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		if(!empty($teacherCount)){
			$total = $this->haddleCountData($total,$teacherCount);
		}
		//评论
		$reviewCount = M('zy_review')->where($whereReview)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		if(!empty($reviewCount)){
			$total = $this->haddleCountData($total,$reviewCount);
		}
		//问答
		$wendaCount = M('zy_wenda')->where($whereReview)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		if(!empty($wendaCount)){
			$total = $this->haddleCountData($total,$wendaCount);
		}
		//商品订单
        if(!$mhm_id){
            $goodsCount = M('goods_order')->where($where)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
            if(!empty($goodsCount)){
                $total = $this->haddleCountData($total,$goodsCount);
            }
        }
		krsort($total);
		if(!empty($total)){
			$res = json_encode([
				'x' => array_keys($total),
				'y' => array_values($total),
			]);
			return $res;
		}
	}
	/**
	  * @name 处理统计数据
	  * @param $total_data 已有统计数据
	  * @param $data 需要组合的数据
	  */
	protected function haddleCountData($total_data,$data){
		foreach($data as $k => $v){
			if(array_key_exists($v['d_time'],$total_data)){
				$total_data[$v['d_time']] += $v['d_count'];
			}else{
				$total_data[$v['d_time']] = (int)$v['d_count'];
			}
		}
		return $total_data;
	}
	/**
	  * @name 处理统计数据 -- 合并数组，以二维数组保存数据
	  * @param $total_data 已有统计数据
	  * @param $data 需要组合的数据
	  */
	protected function mergeCountData(){
		$data = func_get_args();
		$return = [];
		$times = [];
		//合并数据数组key
		foreach($data as $k=>$v){
			$times = array_unique(array_merge($times,array_keys($v)));
			$return[$k] = $v;
		}
		unset($data);
		foreach($times as $k=>$v){
			foreach($return as &$item){
				if(array_key_exists($v,$item)){
					continue;
				}
				//数据填充
				$item[$v] = 0;
				krsort($item);
			}
		}
		unset($item);//手动释放
		//return $this->is_multiArrayEmpty($return) ? array() : $return;
		return $return;
	}
	/**
	 * 获取课程订单列表
	 * @param string $where 查询条件
	 * @return array 统计列表数据
	 *
	 */
//	public function getVipCount($where = '1',$mhm_id) {
//		$resturn = [];
//		$total = [];
//		$video = [];
//		$paid = [];
//        if($mhm_id){
//            $whereOrder = $where."AND mhm_id= {$mhm_id}";
//        }else{
//            $whereOrder = $where;
//        }
//		//课程订单
//		$videoCount = M('zy_order_course')->where($whereOrder)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
////		$total = $this->haddleCountData([],$videoCount);
//		if(!empty($videoCount)){
//			$resturn['video'] = json_encode([
//				'x' => getSubByKey($videoCount,'d_time'),
//				'y' => getSubByKey($videoCount,'d_count')
//			]);
//		}
//		//课程购买
//		$paidCount = M('zy_order_course')->where($whereOrder .' AND (pay_status = 3)')->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
//		$nopaidCount = M('zy_order_course')->where($whereOrder .' AND (pay_status IN (1,2))')->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
//        $yData = $this->mergeCountData($this->haddleCountData([],$paidCount),$this->haddleCountData([],$nopaidCount));
//		$resturn['paid'] =  json_encode([
//			'x' => array_keys($yData[0]),
//			'y' => [
//				array_values($yData[0]),
//				array_values($yData[1]),
//			],
//		]);
//		return $resturn;
//	}

	/**
	 * 获取订单收益列表
	 * @param string $where 查询条件
	 * @return array 统计列表数据
	 *
	 */
	public function getVipCount($where = '1',$mhm_id) {
        $return = [];
		if($mhm_id){
			$whereOrder = $where."AND mhm_id= {$mhm_id}";
		}else{
			$whereOrder = $where;
		}
        $whereOrder .= " AND pay_status=3 AND is_del=0 ";
		//课程订单
        $whereVideo = $whereOrder." AND order_album_id=0 ";
		$videoCount = M('zy_order_course')->where($whereVideo)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','sum(price) as d_count'])->group('d_time')->order('ctime desc')->select();
        //直播订单
		$liveCount = M('zy_order_live')->where($whereVideo)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','sum(price) as d_count'])->group('d_time')->order('ctime desc')->select();
		//班级订单
		$albumCount = M('zy_order_album')->where($whereOrder)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','sum(price) as d_count'])->group('d_time')->order('ctime desc')->select();
		//数据组装
		$yData = $this->mergeCountData($this->haddleDate([],$videoCount),$this->haddleDate([],$liveCount),$this->haddleDate([],$albumCount));

		$return['data'] =  json_encode([
			'x' => array_keys($yData[0]),
			'y' => [
				array_values($yData[0]),
				array_values($yData[1]),
				array_values($yData[2]),
			],
		]);

		return $return;
	}
    protected function haddleDate($total_data,$data){
        foreach($data as $k => $v){
            if(array_key_exists($v['d_time'],$total_data)){
                $total_data[$v['d_time']] += $v['d_count'];
            }else{
                $total_data[$v['d_time']] = $v['d_count'];
            }
        }
        return $total_data;
    }
	/**
	 * 获取注册列表
	 * @param string $where 查询条件
	 * @return array 统计列表数据
	 *
	 */
	public function getRegCount($where = '1') {
		$resturn = [];
		//注册数量
		$regCount = M('user')->where($where)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		if(!empty($regCount)){
			$regCount = json_encode([
				'x' => getSubByKey($regCount,'d_time'),
				'y' => getSubByKey($regCount,'d_count')
			]);
		}
		$resturn['regCount'] = $regCount ?:(object)[];
		//会员开通
		$vipCount = M('credit_user')->where('vip_type <> 0')->count();
		$novipCount = M('credit_user')->where('vip_type = 0')->count();
		$resturn['vip'] =  json_encode([
			'x' => $vipCount,
			'y' => $novipCount
		]);
		return $resturn;
	}

	/**
	 * 获取订单列表
	 * @param string $where 查询条件
	 * @return array 统计列表数据
	 *
	 */
	public function getOrderCount($where = '1',$mhm_id) {
		$return = [];
        if($mhm_id){
            $whereOrder = $where."AND mhm_id= {$mhm_id}";
            $tid = D('ZyTeacher','classroom')->getSchoolAllTeacher($mhm_id);
            $new_tid = implode(',',$tid);
            $whereTeacher = $where."AND tid IN ({$new_tid}) ";
        }else{
            $whereOrder = $whereTeacher = $where;
        }
		//课程订单
		$videoCount = M('zy_order_course')->where($whereOrder)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		//直播订单
		$liveCount = M('zy_order_live')->where($whereOrder)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		//班级订单
		$albumCount = M('zy_order_album')->where($whereOrder)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		//约课订单
		$teacherCount = M('zy_order_teacher')->where($whereTeacher)->field(['FROM_UNIXTIME(ctime,"%Y-%m-%d")as d_time','count(*) as d_count'])->group('d_time')->order('ctime desc')->select();
		//数据组装
		$yData = $this->mergeCountData($this->haddleCountData([],$videoCount),$this->haddleCountData([],$liveCount),$this->haddleCountData([],$albumCount),$this->haddleCountData([],$teacherCount));

		$return['data'] =  json_encode([
			'x' => array_keys($yData[0]),
			'y' => [
				array_values($yData[0]),
				array_values($yData[1]),
				array_values($yData[2]),
				array_values($yData[3]),
			],
		]);
		return $return;
	}

	/**
	  * @name 递归检测数组是否为空
	  */
	public function is_multiArrayEmpty($multiarray) { 
    if(is_array($multiarray) and !empty($multiarray)){ 
        $tmp = array_shift($multiarray); 
            if(!$this->is_multiArrayEmpty($multiarray) or !$this->is_multiArrayEmpty($tmp)){ 
                return false; 
            } 
            return true; 
    } 
    if(empty($multiarray)){ 
        return true; 
    } 
    return false; 
} 
	
}

	