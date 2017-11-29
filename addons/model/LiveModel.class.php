<?php
/**
 * 直播模型 - 业务逻辑模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class LiveModel extends Model{
	protected $tableName = 'zy_video';
	public $liveRoom = null;
    public $mid = 0;//当前登陆用户ID

    /**
     * 模型初始化
     * @return void
     */
    public function _initialize(){
        $this->liveRoom = M('zy_live_thirdparty');
        $this->mid = $_SESSION['mid'];
    }

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
        $map['type'] = 1;
        $data = $this->liveRoom->order($order)->where($map)->findPage($limit);
		return($data);
	}
	public function getZshdLiveRoomInfo($map,$field){
        $map['type'] = 1;
        $data = $this->liveRoom->where($map)->field($field)->find();
		return($data);
	}

	public function updateZshdLiveInfo($map,$data){
        $map['type'] = 1;
        $data = $this->liveRoom->where($map)->save($data);
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
        $map['type'] = 3;
        $data = $this->liveRoom->order($order)->where($map)->findPage($limit);
        return($data);
    }

    public function updateGhLiveInfo($map,$data){
        $map['type'] = 3;
        $data = $this->liveRoom->where($map)->save($data);
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
        $map['type'] = 4;
        $data = $this->liveRoom->order($order)->where($map)->findPage($limit);
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
        $data = $this->liveRoom->order($order)->where($map)->findPage($limit);
        return($data);
    }

    /**
     * CC小班课
     *————————————————————————————————————
     * 根据条件获取所有的CC直播间信息
     * @param $limit 分页
     *        	结果集数目，默认为20
     */
    public function getCcXbkLiveInfo($order,$map,$limit){
        $map['type'] = 6;
        $data = $this->liveRoom->order($order)->where($map)->findPage($limit);
        return($data);
    }

    /**
     * 根据直播id获取直播进度
     * @param $live_type 直播课时类型
     * @param $id 直播id
     * @return mixed $live_data_info now为已直播的课时数 count为总直播课时数
     */
    public function liveSpeed($live_type,$id){
        $count = 0;
        $live_data = $this->liveRoom->where(array('live_id'=>$id,'is_del'=>0,'is_active'=>1))->order('invalidDate asc')->select();

        if($live_data){
            foreach ($live_data as $item=>$value){
                if($value['invalidDate'] < time()){
                    $count = $count + 1 ;
                }
            }
        }else {
            $live_data = array();
            $count = 0;
        }

        $live_data_info['count'] = count($live_data);
        $live_data_info['now'] = $count;

        return $live_data_info;
    }

    /**
     * 根据直播id获取直播课时详情
     * @param $live_type
     * @param $id
     * @return mixed
     */
    public function liveMenu($live_type,$id)
    {
        $is_buy = D('ZyOrderLive','classroom')->isBuyLive($this->mid ,$id );

        $map['live_id']     = $id;
        $map['type']        = $live_type;
        $map['is_del']      = 0;
        $map['is_active']   = 1;
        $end_count          = 0;

        $live_data = $this->liveRoom->where($map)->order('invalidDate asc')->select();
        if($live_data){
            foreach ($live_data as $key=>$val){
                if($val['startDate'] > time()){
                    $note_time = $val['startDate']-time();
                    $note_time_str = secondsToHour($note_time,1);
                    $live_data[$key]['count_down'] = 1;
                    $live_data[$key]['note'] = "<p style='height: 20px;color: #9d9e9e;'>距开课还剩：</p><p id='countDown_{$val['id']}' data-time='{$note_time}'
                                style='height: 40px;color: #fc7272;' >{$note_time_str}</p>";
                    $live_data[$key]['notestate'] = 0;
                }elseif ($val['invalidDate'] > time() && $val['startDate'] < time()){
                    $live_data[$key]['note'] = '正在直播';
                    $live_data[$key]['notestate'] = 1;
                } elseif ($val['invalidDate'] < time()){
                    if($is_buy){
                        $live_data[$key]['note'] = "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>1,'ac'=>'in'])."' title='观看回放'>观看回放</a>";
                    }else{
                        $live_data[$key]['note'] = '已结束';
                    }
                    $end_count += 1;
                    $live_data[$key]['notestate'] = 1;
                }
                $live_data[$key]['endTime'] = $live_data[$key]['invalidDate'];
                $live_data[$key]['beginTime'] = $live_data[$key]['startDate'];
                $live_data[$key]['title'] = $live_data[$key]['subject'];
            }
            $liveCount = count($live_data);
            $end = $liveCount - 1;
            $live_data['end'] = $live_data[$end];
            $live_data['bef'] = $live_data['0'];
            $live_data['count'] = $liveCount;
            $live_data['endCount'] = $this->liveRoom->where($map)->count();
        }

        return $live_data;
    }

    /**
     * 当前时间后几天直播预告
     * @param $time 当前时间
     * @param array $map
     * @param int $day_num 天数
     * @param int $count 查询次数 至增
     * @return array
     */
    public function getLiveListByTime($time,$map = [],$day_num = 4,&$count = 0){
        $initial_time = strtotime(date('Y-m-d',$time));
        $where['is_del'] = 0;
        $where['is_active'] = 1;
        $end_time = $initial_time+86400;
        $where['_string'] = "invalidDate > $time";

        $live_list = $this->liveRoom->where($where)->field('live_id,startDate')->order('startDate ASC')->findALL()  ? : [];
        $live_count = $this->liveRoom->where(array('is_del'=>0,'is_active'=>1,'invalidDate'=>['gt',$end_time]))->count()  ? : 0;

        $list = [];
        if(count($live_list) != 0){
			
            foreach($live_list as $k=>$val){
                $map['id'] = $val['live_id'];
                $live_list[$k]['startDate'] = $initial_time;
                if(M('zy_video')->where($map)->getField('id') == ''){
                    unset($live_list[$k]);
                };
            }

            if(count($live_list) == 0){
                unset($live_list);
            }
            $live_list = i_array_column($live_list,'startDate','live_id');
            if($count < $day_num){
				
                $list[$count] = array_slice($live_list,0,4,true);
                $initial_time += 86400;
				$count += 1;
                $list = array_merge($list,$this->getLiveListByTime($initial_time,$map,$day_num,$count));
            }
        }else if($live_count > 0){
            $initial_time += 86400;
            return $this->getLiveListByTime($initial_time,$map,$day_num,$count);
        }
		
        return $list;
    }

    /**
     * 获取正在直播的直播
     * @param bool $return_type true返回直播课程id 否则返回直播课程数据
     * @param $order
     * @param $field
     * @param int $limit
     * @return array
     */
    public function getNowLive($return_type = false,$order,$field,$limit = 20){
        $where['is_del'] = 0;
        $where['is_active'] = 1;
        $time = time();
        $where['_string'] = "startDate < $time and invalidDate > $time";

        $live_room =  $this->liveRoom->where($where)->field('live_id')->select();

        $live_ids = trim(implode(',',array_unique(array_filter(getSubByKey($live_room,'live_id')))),',');

        $livemap['type']        = 2;
        $livemap['is_del']      = 0;
        $livemap['is_mount']    = 1;
        $livemap['is_activity'] = 1;
        $livemap['listingtime'] = array('lt', $time);
        $livemap['uctime']      = array('gt', $time);
        $livemap['id']          = array('in',$live_ids);

        $live_now_data =  M('zy_video')->where($livemap)->order($order)->field($field)->findPage($limit);

        return $return_type ?  $live_ids : $live_now_data;
    }

    //直播主讲教师id 老版讲师为课时的数据
    protected function teacher($live_type,$id)
    {
        $map = array();
        $map['live_id']=$id;
        $map['is_active']=1;
        $map['is_del']=0;
        $map['type']=$live_type;
        $live_data = model('Live')->liveRoom->where($map)-> field('speaker_id') ->select();
        if(!$live_data){
            $maps = array();
            $maps['live_id']=$id;
            $maps['is_del']=0;
            $maps['invalidDate']=array('gt',time());
            $live_data = model('Live')->liveRoom->where($maps)->order('invalidDate asc')->find();
        }

        return $live_data['speaker_id'];
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

            $live_map['live_id'] = $v['live_id'];
            $live_map['type'] = $v['live_type'];
            $live_map['is_del'] = 0;
            $live_map['is_active'] = 1;

            $live_info = $this->liveRoom-> where($live_map)-> field('startDate,invalidDate') ->select();
            $live_info_reset = reset($live_info);
            $live_info_end   = end($live_info);
            $v['beginTime'] = $live_info_reset['startDate'];
            $v['endTime'] = $live_info_end['invalidDate'];
            $v['is_buy'] = $this->isBuy($v['live_id'],$v) ? 1 : 0;
            $v['isBuy'] = $v['is_buy'];
            $v['price'] = getPrice($v,$this->mid);
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
        $teacher_id = $this->liveRoom->where(['live_id'=>$live_id,'type'=>$type])->order('invalidDate asc')->getField('speaker_id');

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

        $map['live_id']   = $info['id'] ?:$info['live_id'];
        $map['type']      = $info['live_type'];
        $map['is_del']    = 0;
        $map['is_active'] = 1;

        $data = $this->liveRoom->where($map)->findAll();

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
        $return = [];

        $res = $this->liveRoom->where (['id'=>$section_id,'type'=>$data['live_type']])->find ();

        if(!$res){
            $this->error = '未能获取到直播信息';
            return false;
        }

        if($data['live_type'] == 1){
            $zshd = model('Xdata')->get('live_AdminConfig:zshdConfig');

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

            $user_info = M('user')->where("uid={$this->mid}")->field('uname,email')->find();
            $user_info['email'] ?: $user_info['email'] = "eduline@eduline.com";
            $url = "{$res['studentJoinUrl']}?email={$user_info['email']}&name={$user_info['uname']}";
            $return = [
                'live_url' => $url,
                'type' => 5,
                'body' => [
                    'uid' => (int)$this->mid,
                    'number' => $res['roomid'],
                    'api_key' => $wh['api_key'],
                    'appSecretKey' => $wh['appSecretKey'],
                    'is_live' => 1,
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
        }elseif($data['live_type'] == 6){
            $cc = model('Xdata')->get('live_AdminConfig:ccConfig');

            //如果当前直播课程ID 不在 当前模型下已经购买的课程里
            $tid = M('ZyTeacher')->where("uid=".$this->mid)->getField('id');

            $field = 'uname';
            $userInfo = model('User')->findUserInfo($this->mid,$field);
            $uname = $userInfo['uname'];
            if(is_teacher($this->mid)){
                $uname = M('zy_teacher')->where(['uid'=>$this->mid])->getField('name');
                $is_teacher = 1;
            }
            $return = [
                'type' => 6,
                'body' => [
                    'uid' => (int)$this->mid,
                    'account' => $uname,
                    'is_teacher' => $is_teacher ? : 0,
                    'teacher_join_pwd' => $res['teacherToken'],
                    'teacher_join_url' => "{$res['teacherJoinUrl']}&autoLogin=true&username={$uname}&password={$res['teacherToken']}",
                    //'assistant_join_pwd' => $res['assistantToken'],
                    //'assistant_join_url' => "{$res['assistantJoinUrl']}&autoLogin=true&viewername={$uname}&viewertoken={$res['assistantToken']}",
                    'student_join_pwd' => $res['studentClientToken'],
                    'student_join_url' => "{$res['studentJoinUrl']}&autoLogin=true&username={$uname}&password={$res['studentClientToken']}",
                    //'number' => $res['number'],
                    'roomid' => $res['roomid'],
                    'userid' => $cc['user_id'],
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
        }
        return $return;
    }

    /**
     * @name 获取我购买的直播课程列表
     */
    public function getMyLiveList($map,$count){
        $data = $this->where($map)->join("as d INNER JOIN `".C('DB_PREFIX')."zy_order_live` o ON o.live_id = d.id AND o.pay_status = 3 AND o.uid = ".$this->mid)->field('*,d.id as id')->order("o.id DESC")->findPage($count);
        if($data['data']){
            $data['data'] = $this->haddleData($data['data']);
        }
        return $data;
    }
    /**
     * @name 获取指定直播课程指定用户可以使用的优惠券
     */
    public function getCanuseCouponList($live_id = 0,$canot = 0){
        if($live_id){
            $fields = $this->where(['id'=>$live_id])->field(['t_price','mhm_id'])->find();
            $price = $fields['t_price'];
            if($canot == 1){
                $coupons = model('Coupon')->getCanuseCouponList($this->mid,[1,2]);
            }else{
                $coupons = model('Coupon')->getCanuseCouponList($this->mid,[1,2],'AND c.sid = '.$fields['mhm_id']);
            }
            if($coupons){
                if(!$canot){
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
                }else{
                    foreach($coupons as $k=>$v){
                        if($v['mhm_id'] == $fields['mhm_id']){
                            switch($v['type']){
                                case "1":
                                    //价格低于门槛价 || 至少支付0.01
                                    if($v['maxprice'] != '0.00' && $price >= $v['maxprice']){
                                        unset($coupons[$k]);
                                    }
                                    break;
                                case "2":
                                    unset($coupons[$k]);
                                    break;
                                default:
                                    break;
                            }
                        }
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
//        $sql  = '(SELECT '.$prefix.'zy_live_zshd.id AS sid,'.$prefix.'zy_live_zshd.startDate as stime,'.$prefix.'zy_live_zshd.live_id as live_id,IFNULL(3,1) as vtype ';
//        $sql .= 'FROM '.$prefix.'zy_live_zshd WHERE startDate <='.$time.' AND invalidDate >= '.$time.' AND is_del = 0 AND is_active = 1 AND clientJoin = 1) UNION ALL ';
//        $sql .= '(SELECT '.$prefix.'zy_live_cc.id AS sid,'.$prefix.'zy_live_cc.startDate AS stime,'.$prefix.'zy_live_cc.live_id as live_id,IFNULL(1,3) as vtype ';
//        $sql .= 'FROM '.$prefix.'zy_live_cc WHERE startDate <= '.$time.' AND invalidDate >= '.$time.' AND  is_del = 0 AND is_active = 1 AND clientJoin = 1 ) ORDER BY stime ASC LIMIT 0,'.$limit;
//        //return $sql;
//        $list =  $this->query($sql);
        $list = $this->liveRoom->where(['startDate'=> ['elt',$time],'invalidDate'=>['egt',$time],'is_del'=>0,'is_active'=>1])->limit($limit)->select();
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
                    $data['sections'] = $this->getSections($v['live_id'],0,$data,array('id'=>$v['id']));
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

        $live_list  = $this->liveRoom->where($where)->field('live_id,startDate')->findALL()  ? : [];

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
        $live_info = $this->liveRoom->where('id='.$live_id )->field('roomid,studentClientToken,playback_url')->find();

        $info_url  = $cc['api_url'].'/live/info?';

        $if_map['roomid']            = urlencode($live_info['roomid']);
        $if_map['userid']            = urlencode($cc['user_id']);
        $info_url    = $info_url.createHashedQueryString($if_map)[1].'&time='.time().'&hash='.createHashedQueryString($if_map)[0];

        $info_res   = getDataByUrl($info_url);

        if($info_res['result'] == "OK"){
            $playback_url = $info_res['lives'][max(array_keys($info_res['lives']))]['replayUrl']."&viewername=currency_playback&autoLogin=true&viewertoken={$live_info['studentClientToken']}";
            if(!$info_res['lives'][max(array_keys($info_res['lives']))]['replayUrl']){
                $res = $info_res['lives'][max(array_keys($info_res['lives']))]['id'];
            }
            if(!$live_info['playback_url']){
                $this->liveRoom->where('id='.$live_id )->save(['playback_url'=>$playback_url]);
            }
        }else{
            $res = 0;
        }
        return $res;
    }


    public function operWhUser($mid){

        $wh_config = model('Xdata')->get('live_AdminConfig:whConfig');

        $query_data['auth_type'] = $find_data['auth_type'] = 2;
        $query_data['app_key']   = $find_data['app_key']   = t($wh_config['api_key']);
        $query_data['signed_at'] = $find_data['signed_at'] = time();

        $user_info = model('User')->where(['uid'=>$mid])->field('uid,uname,password,email,phone')->find();

        $url  = $wh_config['api_url'].'/api/vhallapi/v2/user/get-user-id';
        $query_data['third_user_id'] = $user_info['uid'];
        $query_data['sign']          = createSignQueryString($query_data);

        $find_live_user   = getDataByPostUrl($url,$query_data);
        unset($query_data['sign']);

        if($find_live_user->code != 200){//新增用户
            $url  = $wh_config['api_url'].'/api/vhallapi/v2/user/register';

            $query_data['pass']          = $user_info['password'];
            $query_data['name']          = $user_info['uname'];
            $user_info['email'] ? $query_data['email'] = $user_info['email'] : '';
            $user_info['phone'] ? $query_data['phone'] = $user_info['phone'] : '';
            $query_data['sign']          = createSignQueryString($query_data);

            $live_user_res   = getDataByPostUrl($url,$query_data);

            if($live_user_res->code == 200){
                $user_id = $live_user_res->data->user_id;
            }
        }else{
            $uri = $wh_config['api_url'].'/api/vhallapi/v2/user/update';
            $query_data['third_user_id'] = $user_info['uid'];
            $query_data['pass']          = $user_info['password'];
            $query_data['name']          = $user_info['uname'];
            $user_info['email'] ? $query_data['email'] = $user_info['email'] : '';
            $user_info['phone'] ? $query_data['phone'] = $user_info['phone'] : '';
            $query_data['sign']          = createSignQueryString($query_data);

            $live_user_res   = getDataByPostUrl($uri,$query_data);

            if($live_user_res->code == 200){
                $user_id = $live_user_res->data->user_id;
            }
        }

        return $user_id ? : 0;
    }
}