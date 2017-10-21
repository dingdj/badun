<?php
/**
 * 机构模型 - 数据对象模型
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */
class SchoolModel extends Model {
	protected $tableName = 'school';
    
    public $mid = 0;
    /**
     * @name 获取机构列表
     */
    public function getList(array $map,$limit = 10,$order = ''){
        $map['is_del'] = 0;
        $map['status'] = 1;
        $data = $this->where($map)->order($order)->findPage($limit);
        if($data['data']){
            $data['data'] = $this->haddleData($data['data']);
        }
        return $data;
    }
    /**
     * @name 获取我关注的机构
     */
    public function getFollowList($uid = 0,$keyword = ''){
        $map['title'] = array('like','%'.h($keyword).'%');
        $map['status'] = 1;
        $data = $this->where($map)->join('u INNER JOIN (SELECT fid FROM `'.C('DB_PREFIX').'user_follow` WHERE `uid` = '.$uid.') AS f ON u.uid = f.fid')->findPage();
        if($data['data']){
            $data['data'] = $this->haddleData($data['data']);
        }
        return $data;
    }
    /**
     * @name 获取单个机构的详情
     */
    public function getSchoolInfoById($id = 0){
        $info = [];
        if($id){
            $data = $this->where(array('id'=>$id,'status'=>1,'is_del'=>0))->find();
            //获取机构自定义机构与教师的分成比例
            $school_and_teacher  = array_filter(explode(':',$data['school_and_teacher']));
            if(is_array($school_and_teacher) && empty($school_and_teacher) === 'false'){
                $data['sat_school']  = floatval($school_and_teacher[0]);
                $data['sat_teacher'] = floatval($school_and_teacher[1]);
            }
            if($data){
                $user = model('User')->formatForApi(array(),$data['uid']);
                $data = $this->haddleData(array($data));
                $info = $data[0];
                $info['user_info'] = $user;
            }
        }
        return $info ?:[];
        
    }
    /**
     * @name 数据解析
     */
    protected function haddleData($data = array()){
        if(!is_array($data) || empty($data)){
            return [];
        }
        foreach($data as &$v){
            //重置值
            $v['school_id'] = (int)$v['id'];
            $v['uid'] = (int)$v['uid'];
            $v['logo_id'] = (int)$v['logo'];
            $v['cover_id'] = (int)$v['cover'];
            $v['logo'] = getCover($v['logo']);
            $v['pc_cover'] = $v['cover'];
            $v['cover'] = getCover($v['cover']);
            $v['status'] = (int)$v['status'];
            $count = model('UserData')->getUserData($v['uid']);
            $v['count']['follower_count'] = $count['follower_count'] ?:0;//关注人数
            $v['count']['video_count'] = (int)M('zy_video')->where(['mhm_id'=>$v['school_id']])->count() ?:0;//课程数量
            $v['count']['learn_count'] = (int)M('zy_order_course')->where(['mhm_id'=>$v['school_id']])->count() ?:0;//学习次数
			$v['count']['learn_count'] += (int)M('zy_order_live')->where(['mhm_id'=>$v['school_id']])->count() ?:0;//学习次数
            //$userInfo = model('User')->getUserInfo($v['uid']);
            //$v['location'] = $userInfo['location']?:'';//地区
            $v['location'] = preg_replace('# #', '',$v['location'].$v['address']);
            //计算评分: 机构下所有讲师所有课程的综合评分
            $sql = '(SELECT id FROM `'.C('DB_PREFIX').'zy_teacher` where mhm_id = '.$v['school_id'].') AS t ';
            //$review = M('zy_teacher_review')->join('r INNER JOIN '.$sql.'ON t.id = r.tid')->field(['SUM(star) as star','COUNT(*) as tatal_count'])->find();
            $star =  M('zy_review')->join('r INNER JOIN '.$sql.'ON t.id = r.tid')->avg('star');
            $v['count']['comment_score'] =  $star ? round($star) : 100;
            $v['count']['comment_star'] = $star ? (round($v['count']['comment_score']/20) ?:5): 5;//默认5颗星
            $v['count']['view_count'] = (int)$v['view_count'];
            $v['follow_state'] = model('Follow')->getFollowState($this->mid , $v['uid']);
            $_count = M('zy_review')->join('r INNER JOIN '.$sql.'ON t.id = r.tid')->count();
            if($_count == 0){
                $v['count']['comment_rate'] = '100%';
            }else{
//                $star_all = M('zy_review')->join('r INNER JOIN '.$sql.'ON t.id = r.tid')->sum('star');
                $schoolmap['mhm_id'] = $v['id'];
                $schoolmap['is_del'] = 0;
                $schoolmap['is_activity'] = 1;
                $videoid = M('zy_video') -> where($schoolmap) -> field('id')->select();
                $live_id = trim(implode(',',array_unique(getSubByKey($videoid,'id'))),',');
                $tidmap['mhm_id'] = $v['id'];
                $tidmap['is_del'] = 0;
                $tids = M('zy_teacher') -> where($tidmap) -> field('id')->select();
                $tid = trim(implode(',',array_unique(getSubByKey($tids,'id'))),',');
                $vtmap['tid'] = ['in',$tid];
                $vtmap['is_del'] = 0;
                $vmap['oid'] = ['in',$live_id];
                $vmap['is_del'] = 0;
                $tstar =   M('zy_review')->where($vtmap)->avg('star');
                $ostar =  M('zy_review')->where($vmap)->avg('star');
                $tsstar = ceil(($tstar + $ostar)/2/20) * 20;
                $v['count']['comment_rate'] = round($tsstar,2).'%' ? : 0 .'%';
            }
            $school_vip = M('school_vip')->where(['id'=>$v['school_vip']])->find();
            $v['school_vip_name'] = $school_vip['title'] ?: '普通机构';
            if($school_vip['cover']){
                $v['school_vip_cover'] = getCover($school_vip['cover'],19,19);
            }
            unset($v['id'],$v['fid'],$v['cuid'],$v['address'],$v['view_count']);
        }
        return $data;
    }
    /**
     * @name 根据uid获得其机构的名称
     */
    public function getSchooldTitleByUid($uid = 0){
            $title = $this->where(array('uid'=>$uid))->getField('title');
            return $title ?:'';
    }
    /**
     * @name 根据条件获得其机构的某个字段
     */
    public function getSchooldStrByMap($map,$field){
            $school_str = $this->where($map)->getField($field);
            return $school_str;
    }
    /**
     * @name 根据条件获得其机构的字段
     */
    public function getSchoolFindStrByMap($map,$field){
        $school_str = $this->where($map)->field($field)->find();
        return $school_str;
    }
    
    /**
     * @name 获取某个用户的机构状态信息
     * @param int $uid 用户UID
     * @return int -1:未申请 0:未通过审核 1:已通过  2:被禁用
     */
    public function getStatusByUid($uid = 0){
        $status = $this->where(['uid'=>$uid,'is_del'=>0])->getField('status');
        if($status === null){
            $status = -1;
        }
        return intval($status);
    }
    /**
     * @name 添加机构浏览量
     */
    public function addViewCount($school_id = 0 ,$count = 1){
        $res = $this->where(['id'=>$school_id])->setInc('view_count',$count);
        return $res ? $this->getViewCount($school_id) : 0;
    }
    
    /**
     * @name 获取机构的浏览量
     */
    public function getViewCount($school_id = 0){
        return (int)$this->where(['id'=>$school_id])->getField('view_count');
    }
    
    /**
     * @name 获取某用户申请的机构ID
     */
    public function getSchoolByUid($uid = 0){
        $school_id = $this->where(['uid'=>$uid])->getField('id');
        return (int)$school_id;
    }
    
    /**
     * @name 搜索
     */
    public function getListBySearch($map,$limit,$order){
        return $this->getList($map,$limit,$order);
    }
    
    /**
     * 获取当前机构某一个月每天排课的课程数量
     */
    public function getMonthsCourseCount($school_id = 0,$timespan = 0){
        $timespan = $timespan ?: time();
        $beginThismonth = strtotime(date('Y-m-01', $timespan));
        $endThismonth = strtotime(date('Y-m-01', $timespan) . ' +1 month -1 day');
        $map = [
            'beginTime' => array('between',array($beginThismonth,$endThismonth)),
            'is_del'    => 0,
            'is_activity' => 1,
            'mhm_id'    => $school_id
        ];
        return M('arrange_course')->where($map)->field(['FROM_UNIXTIME(beginTime,"%Y-%m-%d")as day','count(*) as count'])->group('day')->order('beginTime desc')->select();
    }
    /**
     * 获取所有机构 用于下拉菜单
     */
    public function getAllSchol($map,$field){
        $map['is_del'] = 0;
        $map['status'] = 1;
        $data = $this->where($map)->getField($field);

        return $data;
    }

    /**
     * @name 获取某机构下的所有用户
     * @$school_id  机构ID
     * @return  array  用户ID集合
     */
    public function getSchoolAllUserById($school_id){
        $map = array('mhm_id'=>$school_id,'is_del'=>0,'is_audit'=>1,'is_active'=>1);
        $uid = M('User')->where($map)->field('uid')->findALL();
        foreach($uid as $k=>&$v){
            $new_uid[] = implode(',',$v);
        }
        return $new_uid;
    }

}