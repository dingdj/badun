<?php
/**
 * 直播模型 - 业务逻辑模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
use GuzzleHttp\Client;
class LiveModel extends Model{
	protected $tableName = 'zy_video';
    public $mid = 0;//当前登陆用户ID

	/**
	 * 根据条件获取所有的直播课堂信息
	 * @param $limit
	 *        	结果集数目，默认为20
	 */
	public function getLiveInfo($limit,$order,$map){
		$map = $this->getMap($map);
		$data = $this->order($order)->where($map)->findPage($limit);
		return($data);
	}

    /**
     * 根据条件获取所有的直播课堂信息
     * @param $limit
     *        	结果集数目，默认为20
     */
    public function getAllLiveInfo($limit,$order,$map){
        $data = $this->order($order)->where($map)->findPage($limit);
        return($data);
    }


	/**
	 * 根据条件查单个直播课堂信息
	 */
	public function findLiveAInfo($map,$field){
        $map = $this->getMap($map);
        $data = $this->where($map)->field($field)->find();
		return($data);
	}

    public function updateLiveInfo($map,$data){
        $data = $this->where($map)->save($data);
        return($data);
    }

    private function getMap($map = []){
        $default = [
            'type'          => 2,
            'is_activity'   => 1,
            'is_del'        => 0,
            'uctime'        => array('GT',time()),
        ];
        return array_merge($default,$map);
    }
    /**
     * 展示互动
     *————————————————————————————————————
	 * 根据条件获取所有的展示互动直播间信息
	 * @param $limit 分页
	 *        	结果集数目，默认为20
	 */
	public function getZshdLiveInfo($order,$limit,$map){
		$data = M('zy_live_zshd')->order($order)->where($map)->findPage($limit);
		return($data);
	}
	public function getZshdLiveRoomInfo($map,$field){
		$data = M('zy_live_zshd')->where($map)->find($field);
		return($data);
	}

	public function updateZshdLiveInfo($map,$data){
		$data = M('zy_live_zshd')->where($map)->save($data);
		return($data);
	}

    /**
     * 光慧
     *————————————————————————————————————
     * 根据条件获取所有的光慧直播间信息
     * @param $limit 分页
     *        	结果集数目，默认为20
     */
    public function getGhLiveInfo($order,$map,$limit){
        $data = M('zy_live_gh')->order($order)->where($map)->findPage($limit);
        return($data);
    }

    public function updateGhLiveInfo($map,$data){
        $data = M('zy_live_gh')->where($map)->save($data);
        return($data);
    }

    /**
     * @name 获取直播列表
     */
    public function getLiveList(array $map,$order = 'ctime desc',$limit = 10){
        $data = $this->getLiveInfo($limit,$order,$map);
        if($data['data']){
            $data['data'] = $this->haddleData($data['data']);
        }
        return $data;
    }

    /**
     * CC
     *————————————————————————————————————
     * 根据条件获取所有的CC直播间信息
     * @param $limit 分页
     *        	结果集数目，默认为20
     */
    public function getCcLiveInfo($order,$map,$limit){
        $data = M('zy_live_cc')->order($order)->where($map)->findPage($limit);
        return($data);
    }

    /**
     * 微吼
     *————————————————————————————————————
     * 根据条件获取所有的微吼直播间信息
     * @param $limit 分页
     *        	结果集数目，默认为20
     */
    public function getWhLiveInfo($order,$map,$limit){
        $map['type'] = 5;
        $data = M('zy_live_thirdparty')->order($order)->where($map)->findPage($limit);
        return($data);
    }

    /**
     * @name 数据解析
     * @param array $data 初始数据
     * @param boolean $has_user_info 是否需要获取用户信息
     * @param boolean $getOldPrice 是否指定获取原价
     * @return array 解析后的数据
     */
    protected function haddleData($data = array(),$has_user_info = true){
        if(!is_array($data) || empty($data)){
            return [];
        }
        $_category = M('zy_currency_category');
        foreach($data as &$v){
            //$v['price'] = getPrice($v,$this->mid);
            $v['cover'] = getCover($v['cover'] , 280 , 160);
            $has_user_info && $v['user'] = model('User')->formatForApi(array(),$v['uid'],$this->mid);
            $v['live_id'] = (int)$v['id'];
            $v['live_type'] = (int)$v['live_type'];
            //$v['score'] = (int)$v['video_score'];
            $star = M('ZyReview')->where(array('oid'=>$v['live_id']))->Avg('star');
            $star = $star ? round($star) : 100;
            $v['score'] = round($star / 20) ?:5;
            $v['live_category'] = $_category->where(['zy_currency_category_id'=>$v['cate_id']])->getField('title') ?:'';
            //获取直播所属机构
            $v['school_info'] = model('School')->getSchoolInfoById($v['mhm_id']);
            $v['teacher_id'] =  $this->getTeacher($v['live_type'],$v['live_id']);
            if($v['live_type']  ==1){
                $table = 'zy_live_zshd';
            }else if($v['live_type']  ==3){
                $table = 'zy_live_gh';
            }else if($v['live_type']  ==4){
                $table = 'zy_live_cc';
            }else if($v['live_type']  ==5){
                $live_map['type'] = 5;
                $table = 'zy_live_thirdparty';
            }
            $live_map['live_id'] = $v['live_id'];
            $live_map['is_del'] = 0;
            $live_map['is_active'] = 1;

            $live_info = M($table) -> where($live_map)-> field('startDate,invalidDate') ->select();
            $live_info_reset = reset($live_info);
            $live_info_end   = end($live_info);
            $v['beginTime'] = $live_info_reset['startDate'];
            $v['endTime'] = $live_info_end['invalidDate'];
            $v['is_buy'] = $this->isBuy($v['live_id'],$v) ? 1 : 0;
            $v['isBuy'] = $v['is_buy'];
            $v['price'] = !$v['isBuy'] ? getPrice($v,$this->mid) : $v['t_price'];
			$v['iscollect'] = D ( 'ZyCollection' ,'classroom')->isCollect ( $v['live_id'], 'zy_video', intval ( $this->mid ) );
            $v['imageurl'] = $v['cover'];
            $v['section_num'] = (int)M('zy_video_section')->where('vid='.$v['live_id'])->count();
			$v['teacher_name']  = D('ZyTeacher','classroom')->where(array('id'=>$v['teacher_id']))->getField('name') ?:'';
            $v['video_order_count'] = M('zy_order_live') -> where(array('live_id'=> $v['id'], 'is_del' => 0,'pay_status'=>3)) -> count();
            unset($v['id'],$v['uid'],$v['term'],$v['cate_id']);
        }
        return $data;
    }
	
	protected function getTeacher($type,$live_id){
		switch($type){
			case '1':
				$teacher_id = M('zy_live_zshd')->where(['live_id'=>$live_id])->order('invalidDate asc')->getField('speaker_id');
				break;
			case '3':
				$teacher_id = M('zy_live_gh')->where(['live_id'=>$live_id])->order('invalidDate asc')->getField('speaker_id');
				break;
			case '4':
				$teacher_id = M('zy_live_cc')->where(['live_id'=>$live_id])->order('invalidDate asc')->getField('speaker_id');
				break;
			case '5':
				$teacher_id = M('zy_live_thirdparty')->where(['live_id'=>$live_id,'type'=>5])->order('invalidDate asc')->getField('speaker_id');
				break;
			default:
				$teacher_id = 0;
				break;
		}
		return intval($teacher_id);
	}
	
    public function isBuy($live_id = 0,$data = []){
        // 是否已购买
		return $this->is_free($live_id,$data) || D('ZyOrderLive','classroom')->isBuyLive($this->mid,$live_id);

    }
    /**
     * @name 获取单个直播课程的详情
     * @param int $live_id 直播课程ID
     * @param boolean $has_user_info 是否需要获取用户
     * @return array 数据信息
     */
    public function getLiveInfoById($live_id = 0,$has_user_info = false){
        $data = [];
        if($live_id){
            $info[0] = $this->where(['id'=>$live_id,'is_del'=>0])->find();
            if($info[0]){
                $data = $this->haddleData($info,$has_user_info)[0];
                $data['sections'] = $this->getSections($live_id,0,$data);
            }
        }
        return $data;
    }
    /**
     * @name 检测是否为免费课程
     */
    public function is_free($vid = 0,$data = array()){
        if(empty($data)){
            $map['id'] = $vid;
            $map['type'] = 2;
            $data = $this->where($map)->find();
        }
        if($data['is_charge'] == 1 || $data['t_price'] == '0.00'){
            return true;
        }
        return false;
    }
    /**
     * @name 获取指定直播课程的课程章节信息
     * @param int $live_id 直播课程ID
     * @param int $pid 课程章节父ID  default:0 表示获取所有的章节列表
     * @return array 课程章节列表
     */
    public function getSections($live_id = 0 ,$pid = 0,$info = array(),$map = array()){
        $info = !empty($info) ? $info :$this->where('id='.$live_id)->find();
        if($info['live_type'] == 1) {//展视互动
            $map['live_id']   = $info['id'] ?:$info['live_id'];
            $map['is_del']    = 0;
            $map['is_active'] = 1;
            $data = M('zy_live_zshd')->where($map)->findAll();
            foreach($data as &$val) {
                $val['title'] = $val['subject'];
                if($val['startDate']  <= time() && $val['invalidDate']   >= time() ) {
                    $val['note'] = '直播中';
                }

                if($val['startDate']  > time()){
                    $val['note'] = '未开始';
                }

                if($val['invalidDate'] < time()){
                    $val['note'] = '已结束';
                }
                $val['section_id'] = intval($val['id']);
            }
        } elseif($info['live_type'] == 2){//三芒

        } elseif($info['live_type'] == 3){//光慧
            $map['live_id']   = $info['id'] ?:$info['live_id'];
            $map['is_del']    = 0;
            $map['is_active'] = 1;
            $data = M('zy_live_gh')->where($map)->findAll();
            foreach($data as &$val) {
                $val['beginTime'] = $val['beginTime'] / 1000;
                $val['endTime']   = $val['endTime'] / 1000;
                if($val['beginTime'] <= time() && $val['endTime']  >= time() ) {
                    $val['note'] = '直播中';
                }

                if($val['beginTime'] > time()){
                    $val['note'] = '未开始';
                }

                if($val['endTime'] < time()){
                    $val['note'] = '已结束';
                }
                $val['section_id'] = intval($val['id']);
            }
        } elseif($info['live_type'] == 4){//CC
            $map['live_id']   = $info['id'] ?:$info['live_id'];
            $map['is_del']    = 0;
            $map['is_active'] = 1;
            $data = M('zy_live_cc')->where($map)->findAll();
            foreach($data as &$val) {
                $val['title'] = $val['subject'];
                if($val['startDate']  <= time() && $val['invalidDate']   >= time() ) {
                    $val['note'] = '直播中';
                }

                if($val['startDate']  > time()){
                    $val['note'] = '未开始';
                }

                if($val['invalidDate'] < time()){
                    $val['note'] = '已结束';
                }
                $val['section_id'] = intval($val['id']);
            }
        } elseif($info['live_type'] == 5){//微吼
            $map['live_id']   = $info['id'] ?:$info['live_id'];
            $map['is_del']    = 0;
            $map['is_active'] = 1;
            $map['type']      = 5;
            $data = M('zy_live_thirdparty')->where($map)->findAll();
            foreach($data as &$val) {
                $val['title'] = $val['subject'];
                if($val['startDate']  <= time() && $val['invalidDate']   >= time() ) {
                    $val['note'] = '直播中';
                }

                if($val['startDate']  > time()){
                    $val['note'] = '未开始';
                }

                if($val['invalidDate'] < time()){
                    $val['note'] = '已结束';
                }
                $val['section_id'] = intval($val['id']);
            }
        }else {//其他
            $map['live_id']   = $info['id'];
            $map['is_del']    = 0;
            $map['is_active'] = 1;
            $data = M('zy_live_gh')->where($map)->findAll();
            foreach($data as &$val) {
                $val['beginTime'] = $val['beginTime'] / 1000;
                $val['endTime']   = $val['endTime'] / 1000;
                if($val['beginTime'] <= time() && $val['endTime']  >= time() ) {
                    $val['note'] = '直播中';
                }

                if($val['beginTime'] > time()){
                    $val['note'] = '未开始';
                }

                if($val['endTime'] < time()){
                    $val['note'] = '已结束';
                }
                $val['section_id'] = intval($val['id']);
            }
        }
        return $data ? : [];
    }
    /**
     * @name 根据直播课程章节ID获取直播播放地址信息
     * @param int | string $section_id 直播课程章节ID
     * @return array 播放地址列表数据
     */
    public function getLiveUrlBySectionId($live_id = 0,$section_id = 0){
        $map = $this->getMap(['id'=>$live_id]);
        $video = $this->where($map)->find();
        if(!$video){
            $this->error = '直播课程不存在或已被删除';
            return false;
        }
        if(!$this->isBuy($live_id)){
            $this->error = '请先购买直播课程';
            return false;
        }

        if($video){
            //获取当前章节的播放地址信息
            $data = $this->getLiveUrlBySectionData($video,$section_id);
            return $data ?:[];
        }else{
            $this->error = '未能获取到直播信息';
            return false;
        }

    }
    /**
     * @name 分析单个章节数据并获取直播地址
     * @param 单个章节的数据信息
     * @return string $url 地址
     */
    private function getLiveUrlBySectionData($data = [],$section_id = 0){
        //type: 1= zy_live_zshd 3:zy_live_gh
        $return = [];
        if($data['live_type'] == 1){
            $zshd = model('Xdata')->get('live_AdminConfig:zshdConfig');
            $res = M( 'zy_live_zshd' )->where ( 'id='. $section_id)->find ();
            if(!$res){
                $this->error = '未能获取到直播信息';
                return false;
            }
            if($res['clientJoin'] != 1){
                $this->error = '该直播不允许客户端观看';
                return false;
            }
            //如果当前直播课程ID 不在 当前模型下已经购买的课程里
            $tid = M('ZyTeacher')->where("uid=".$this->mid)->getField('id');

            $field = 'uname';
            $userInfo = model('User')->findUserInfo($this->mid,$field);
            $uname = $userInfo['uname'];
            $url = $res['studentJoinUrl']."?nickname=".$uname."&token=".$res['studentClientToken'];
            $return = [
                'live_url' => $url,
                'type' => 1,
                'body' => [
                    'uid' => (int)$this->mid,
                    'domain' => $res['studentJoinUrl'],
                    'account' => $uname,
                    'pwd'     => $res['studentClientToken'],
                    'join_pwd' => $res['studentClientToken'],
                    'number' => $res['number'],
                ]
            ];
            if($res['startDate'] >= time()){
                $this->error = '还未到直播时间';
                return false;
            }
            if($res['invalidDate'] <= time()){

                $list_url = $zshd['api_url'] . '/courseware/list?';

                $param = 'roomId=' . $res['roomid'];
                $hash = $param . '&loginName=' . $zshd['api_key'] . '&password=' . md5($zshd['api_pwd']) . '&sec=true';
                $list_url = $list_url . $hash;

                $list_live = getDataByUrl($list_url);

                $return['livePlayback'] = $list_live['coursewares'][0];
            }
        }elseif($data['live_type'] == 3){
            $res = M('zy_live_gh')->where('id=' . $section_id)->find();
            if($res['supportMobile'] != 1){
                $this->error = '该直播不允许手机观看';
                return false;
            }
    		$gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
    		if ( $res['endTime'] / 1000 >= time() ) {
    			$url = $gh_config['video_url'] . '/student/index.html?liveClassroomId='.$res['room_id'].'&customerType=taobao&customer=seition&sp=0';
    		} else {//直播结束
    			$url = $gh_config['video_url'] . '/playback/index.html?liveClassroomId='.$res['room_id'].'&customerType=taobao&customer=seition&sp=0';
    		}
            $return = [
                'live_url' => $url,
                'type' => 3,
                'body' => [
                    'uid' => (int)$this->mid,
                    'domain' => $gh_config['video_url'],
                    'account' => $res['account'],
                    'pwd'     => $res['passwd'],
                    'join_pwd' => '',
                    'number' => $res['room_id'],
                ]
            ];
        }elseif($data['live_type'] == 4){
            $cc = model('Xdata')->get('live_AdminConfig:ccConfig');
            $res = M( 'zy_live_cc' )->where ( 'id='. $section_id)->find ();
            if(!$res){
                $this->error = '未能获取到直播信息';
                return false;
            }
            if($res['clientJoin'] != 1){
                $this->error = '该直播不允许客户端观看';
                return false;
            }
            //如果当前直播课程ID 不在 当前模型下已经购买的课程里
            $tid = M('ZyTeacher')->where("uid=".$this->mid)->getField('id');

            $field = 'uname';
            $userInfo = model('User')->findUserInfo($this->mid,$field);
            $uname = $userInfo['uname'];
            $url = $res['studentJoinUrl']."?nickname=".$uname."&token=".$res['studentClientToken'];
            $return = [
                'live_url' => $url,
                'type' => 4,
                'body' => [
                    'uid' => (int)$this->mid,
                    'domain' => $res['studentJoinUrl'],
                    'account' => $uname,
                    'pwd'     => $res['studentClientToken'],
                    'join_pwd' => $res['studentClientToken'],
                    'number' => $res['number'],
                    'roomid' => $res['roomid'],
                    'userid' =>$cc['user_id'],
                    'is_live' => 1,
                ],
            ];
            if($res['startDate'] >= time()){
                $this->error = '还未到直播时间';
                return false;
            }
            if($res['invalidDate'] <= time()){

                $return['body']['is_live'] = 0;
                $return['body']['livePlayback'] = $this->getLivePlayback($section_id);
            }
        }elseif($data['live_type'] == 5){
            $wh = model('Xdata')->get('live_AdminConfig:whConfig');
            $res = M( 'zy_live_thirdparty' )->where ( 'id='. $section_id)->find ();
            if(!$res){
                $this->error = '未能获取到直播信息';
                return false;
            }
            $user_info = M('user')->where("uid={$this->mid}")->field('uname,email')->find();
            $user_info['email'] ?: $user_info['email'] = "eduline@eduline.com";
            $url = "{$res['studentJoinUrl']}?email={$user_info['email']}&name={$user_info['uname']}";

	    $login_client  = new Client();
            $login_url = 'http://e.vhall.com/api/vhallapi/v2/user/get-user-id';
            $vhall_user_id = M('ZyLoginsync')->where(array('uid'=>$this->mid))->getField('vhall_user_id');
            $vhall = $login_client->request("post",$login_url,['query' => [ 'auth_type'=>1,'account'=>'v20471089','password'=>'Zwjy123456','third_user_id'=>$vhall_user_id]]);
       	    $vhall_data = json_decode($vhall->getBody(),true);
	    $vhall_id   = 'v'.$vhall_data['data']['id'];
	    $vhall_pass = C('SECURE_CODE').$vhall_user_id;
            $return = [
                'live_url' => $url,
                'type' => 5,
                'body' => [
                    'uid' => (int)$this->mid,
                    'number' => $res['roomid'],
                    'api_key' => $wh['api_key'],
                    'appSecretKey' => $wh['appSecretKey'],
                    'is_live' => 1,
		    'aAccount'=> $vhall_id ?: 0,
                    'aPassword'=> $vhall_pass ?: 0,
                ],
            ];
            if($res['startDate'] >= time()){
                $this->error = '还未到直播时间';
                return false;
            }
            if($res['invalidDate'] <= time()){
                $return['body']['is_live'] = 0;
                $return['body']['livePlayback'] = 1;
            }
        }
        return $return;
    }
    /**
     * @name 获取我购买的直播课程列表
     */
    public function getMyLiveList($map,$count){
        $data = $this->where($map)->join("as d INNER JOIN `".C('DB_PREFIX')."zy_order_live` o ON o.live_id = d.id AND o.pay_status = 3 AND o.uid = ".$this->mid)->field('*,d.id as id')->findPage($count);
        if($data['data']){
            $data['data'] = $this->haddleData($data['data']);
        }
        return $data;
    }
    /**
     * @name 获取指定直播课程指定用户可以使用的优惠券
     */
    public function getCanuseCouponList($live_id = 0){
        if($live_id){
            $price = $this->where(['id'=>$live_id])->getField('t_price');
            $coupons = model('Coupon')->getCanuseCouponList($this->mid,[1,2]);
            if($coupons){
                //过滤全额抵消的优惠券
                foreach($coupons as $k=>$v){
                    switch($v['type']){
                        case "1":
                            //价格低于门槛价 || 至少支付0.01
                            if($v['maxprice'] != '0.00' && $price <= $v['maxprice'] || $price - $v['price'] <= 0){
                                unset($coupons[$k]);
                            }
                            break;
                        case "2":
                        default:
                            break;
                    }
                }
            }
        }
        return $coupons ? array_values($coupons):[];
    }
    /**
     * @name 搜索直播
     */
    public function getListBySearch($map,$limit = 20,$order = 'ctime desc'){
        $map['is_mount'] = 1;
        $list = $this->getLiveInfo($limit,$order,$map);
        $list['data'] = $this->haddleData($list['data'],true);
        return $list;
    }
    /**
     * @name 获取最近直播
     */
    public function getLatelyLive($limit = 10){
        $prefix = C('DB_PREFIX');
        $time = time();
        $sql  = '(SELECT '.$prefix.'zy_live_zshd.id AS sid,'.$prefix.'zy_live_zshd.startDate as stime,'.$prefix.'zy_live_zshd.live_id as live_id,IFNULL(3,1) as vtype ';
        $sql .= 'FROM '.$prefix.'zy_live_zshd WHERE startDate <='.$time.' AND invalidDate >= '.$time.' AND is_del = 0 AND is_active = 1 AND clientJoin = 1) UNION ALL ';
        $sql .= '(SELECT '.$prefix.'zy_live_cc.id AS sid,'.$prefix.'zy_live_cc.startDate AS stime,'.$prefix.'zy_live_cc.live_id as live_id,IFNULL(1,3) as vtype ';
        $sql .= 'FROM '.$prefix.'zy_live_cc WHERE startDate <= '.$time.' AND invalidDate >= '.$time.' AND  is_del = 0 AND is_active = 1 AND clientJoin = 1 ) ORDER BY stime ASC LIMIT 0,'.$limit;
        //return $sql;
        $list =  $this->query($sql);
        $res = array();
        if($list){
            static $liveInfo;
            foreach($list as $k=>$v){
                if(!$liveInfo[$v['live_id']]){
                    $info[0] = $this->where(['id'=>$v['live_id'],'is_del'=>0,'is_activity'=>1,'type'=>2,'uctime'=> array('GT',time())])->find();
                }else{
                    $info[0] = $liveInfo[$v['live_id']];
                }
                if($info[0]){
                    $data = $this->haddleData($info,false)[0];
                    unset($data['school_info']);
                    $liveInfo[$v['live_id']] = $data;
                    $data['sections'] = $this->getSections($live_id,0,$data,array('id'=>$v['sid']));
                    array_push($res,$data);
                    unset($data);
                }
            }

        }
        return $res;
    }

    /**
     * 获取指定时间段内的直播
     */
    public function getLiveByTimespan($stime = 0,$etime = 0,$limit = 10,$page = 1){
        $start = ($page - 1) * $limit;
        $prefix = C('DB_PREFIX');
        $sql  = '(SELECT '.$prefix.'zy_live_zshd.id AS sid,'.$prefix.'zy_live_zshd.startDate as stime,'.$prefix.'zy_live_zshd.live_id as live_id,IFNULL(3,1) as vtype ';
        $sql .= 'FROM '.$prefix.'zy_live_zshd WHERE startDate >= FLOOR('.$stime.'-(invalidDate-startDate)) AND (invalidDate >= '.$stime.' OR invalidDate < '.$etime.') AND is_del = 0 AND is_active = 1 AND clientJoin = 1) UNION ALL ';
        $sql .= '(SELECT '.$prefix.'zy_live_gh.id AS sid,FLOOR('.$prefix.'zy_live_gh.startDate/1000) AS stime,'.$prefix.'zy_live_gh.live_id as live_id,IFNULL(1,3) as vtype ';
        $sql .= 'FROM '.$prefix.'zy_live_gh WHERE (startDate/1000) >= ('.$stime.'-(invalidDate-startDate)/1000) AND ((invalidDate/1000) >='.$stime.' OR (invalidDate/1000) < '.$etime.') AND is_del = 0 AND is_active = 1 AND supportMobile = 1 )
                ORDER BY stime DESC LIMIT '.$start.','.$limit;
        $list =  $this->query($sql);
        $res = array();
        if($list){
            static $liveInfo;
            foreach($list as $k=>$v){
                if(!$liveInfo[$v['live_id']]){
                    $info[0] = $this->where(['id'=>$v['live_id'],'is_del'=>0,'is_activity'=>1,'type'=>2,'uctime'=> array('GT',time())])->find();
                }else{
                    $info[0] = $liveInfo[$v['live_id']];
                }
                if($info[0]){
                    $data = $this->haddleData($info,false)[0];
                    $liveInfo[$v['live_id']] = $data;
                    $data['sections'] = $this->getSections($live_id,0,$data,array('id'=>$v['sid']));
                    array_push($res,$data);
                    unset($data);
                }
            }

        }
        return $res;
    }
    /**
     * 获取指定时间段内的直播
     */
    public function getLiveByTime($time = 0,$limit = 10){
        $initial_time = strtotime(date('Y-m-d',$time));
        $where['is_del'] = 0;
        $where['is_active'] = 1;
        $end_time = $initial_time+86400;
        $where['_string'] = "(startDate < $end_time && invalidDate >= $end_time)";

        $live_zshd  = M('zy_live_zshd')->where($where)->field('live_id,startDate')->findALL()  ? : [];
        $live_cc  = M('zy_live_cc')->where($where)->field('live_id,startDate')->findALL()  ? : [];
        $where['type'] = 5;
        $live_wh  = M('zy_live_thirdparty')->where($where)->field('live_id,startDate')->findALL()  ? : [];

//        $time_gh = $time*1000;
//        $end_time_gh = $end_time*1000;
//        $where['_string'] = "(startDate < $end_time_gh && invalidDate > $time_gh)";
//        $live_gh  = M('zy_live_gh')->where($where)->field('live_id,startDate')->findALL()  ? : [];

        $live_list = array_merge($live_zshd,$live_cc,$live_wh);//,$live_gh
        $list = [];
        $map['is_del']      = 0;
        $map['is_activity'] = 1;
        $map['is_mount'] = 1;
        $map['uctime']      = array('GT',time());
        $map['listingtime'] = array('LT',time());
        if(count($live_list) != 0){
            foreach($live_list as $k=>$val){
                $map['id'] = $val['live_id'];
                if(D('ZyVideo','classroom')->where($map)->getField('id') == ''){
                    unset($live_list[$k]);
                };
            }
            if(count($live_list) == 0){
                unset($live_list);
            }
            $live_list = array_column($live_list,'startDate','live_id');
            asort($live_list);
        }
        $live_id = implode(',',array_keys($live_list));
        $map['id'] = ['in',$live_id];
        $info = D('ZyVideo','classroom')->where($map)->findPage($limit);
        $info['data'] = $this->haddleData($info['data'],false);
        foreach($info['data'] as $key=>$val){
            $info['data'][$key]['sections'] = $this->getSections($val['id'],0,$val);
            array_push($list,$info['data'][$key]);
            unset($info['data'][$key]);
        }
        return $list;
    }

    /**
     * 获取CC直播回放
     */
    public function getLivePlayback($live_id = 0){
        $cc = model('Xdata')->get('live_AdminConfig:ccConfig');
        $live_info = M('zy_live_cc')->where('id='.$live_id )->field('roomid,studentClientToken,playback_url')->find();

        $info_url  = $cc['api_url'].'/live/info?';

        $if_map['roomid']            = urlencode($live_info['roomid']);
        $if_map['userid']            = urlencode($cc['user_id']);
        $info_url    = $info_url.createHashedQueryString($if_map)[1].'&time='.time().'&hash='.createHashedQueryString($if_map)[0];

        $info_res   = getDataByUrl($info_url);

        if($info_res['result'] == "OK"){
            $playback_url = $info_res['lives'][max(array_keys($info_res['lives']))]['replayUrl']."&viewername=currency_playback&autoLogin=true&viewertoken={$live_info['studentClientToken']}";
            if(!$info_res['lives'][max(array_keys($info_res['lives']))]['replayUrl']){
                $res = 0;
            }else{
                $res = 1;
            }
            if(!$live_info['playback_url']){
                M('zy_live_cc')->where('id='.$live_id )->save(['playback_url'=>$playback_url]);
            }
        }else{
            $res = 0;
        }
        return $res;
    }
}
