<?php
    /**
     * 课程订单模型
     * @author xiewei <master@xiew.net>
     * @version 1.0zy_order_course
     */
class ZyOrderCourseModel extends Model{
    
    var $tableName = 'zy_order_course'; //映射到表

    protected $albumOrder = null;

    /**
     * 模型初始化
     * @return void
     */
    public function _initialize(){
        $this->albumOrder = D('ZyOrderAlbum','classroom');
    }
    /**
     * 通过教师id获取教师姓名
     */
    public function teacherId($id){
        $teacher_info=M("zy_teacher")->where("id=".$id)->find();
        return $teacher_info["name"];
    }
    public function courseId($id){
        $course_info=M("zy_teacher_course")->where("course_id=".$id)->find();
        return $course_info["course_name"];
    }
    public function teacherWay($id){
        if($id==1){
            return "线上授课";
        }else if($id==2){
            return "线下授课";
        }else{
            return "线上/线下均可";
        }
    }

    /**
     * 通过班级取得某个用户的课程订单列表
     * @param integer $id 班级订单ID，
     * @param string $vl 该班级包含的全部课程的id列表，逗号分割值
     * @param boolean $useVidKey 返回数组是否使用video_id作为键名
     * @return array 返回包含该用户订购某个班级下面的课程订单列表
     */
    public function getAlbumOrderList($id, $vl = null, $useVidKey = false){
        $where = "order_album_id = '$id'";
        //设置了$vl 那么将单独购买的课程也查询出来
        $vl = $vl?getCsvInt($vl, 0, true):false;
        if($vl){
            $where .= " OR video_id IN($vl)";
        }

        $data = $this->where($where)->order('order_album_id DESC,id')->select();
        if(!$data) return array();
        if($useVidKey){
            $array = array();
            foreach($data as $val){
                $array[$val['video_id']] = $val;
            }
            return $array;
        }
        return $data;
    }

    /**
     * 取得班级学习状态
     * @param integer $uid 用户UID
     * @param integer $albumId 课程ID
     * @return integer|false (课程学习状态(0:未开始,1:学习中,2:已完成))，失败返回false
     */
    public function getVideoLearnStatus($uid, $videoId){
        return $this->where(array('uid'=>$uid,'video_id'=>$videoId))->getField('learn_status');
    }

    /**
     * 取得班级学习状态
     * @param integer $uid 用户UID
     * @param integer $albumId 班级ID
     * @return integer|false (班级学习状态(0:未开始,1:学习中,2:已完成))，失败返回false
     */
    public function getAlbumLearnStatus($uid, $albumId){
        $id = $this->albumOrder->getAlbumOrderId($uid, $albumId);
        if(!$id) return false;
        $array = $this->where("order_album_id='$id' AND learn_status IN(0,1,2)")
            ->field('distinct learn_status')->select();
        if(!$array) return false;
        $count = count($array);
        //状态次数
        $status = array(0,0,0);
        foreach($array as $val){
            $status[$val['learn_status']] += 1;
        }
        if($status[0] == $count){//全部没开始
            return 0;
        }elseif($status[2] == $count){//全部学习完成
            return 2;
        }else{//各种状态都有
            return 1;
        }
    }

    /**
     * 课程或班级的学习状态
     * @param integer $uid 用户UID
     * @param integer $id 课程ID/班级ID
     * @param $type 1为课程，否则为班级
     * @return integer|false (课程/班级学习状态(0:未开始,1:学习中,2:已完成))，失败返回false
     */
    public function getLearnStatus($uid, $id, $type){
        if($type == 1){
            return $this->getVideoLearnStatus($uid, $id);
        }else{
            return $this->getAlbumLearnStatus($uid, $id);
        }
    }

    /**
     * 设置课程学习状态
     * @param integer $uid 用户ID
     * @param integer $video_id 视频ID
     * @param integer $status 学习状态(0:未开始,1:学习中,2:已完成)
     */
    public function setLearnStatus($uid, $video_id, $status){
        return $this->where(array('uid'=>$uid,'video_id'=>$video_id))->save(array('learn_status'=>$status));
    }

    /**
     * 查询一个用户是否购买过一个班级
     * @param integer $uid 用户UID
     * @param integer $albumId 班级ID
     * @return integer|false 返回对应的班级订单ID，如果失败则返回false
     */
    public function isBuyAlbum($uid, $albumId){
        $video = $this->where(array('uid'=>$uid, 'order_album_id'=>$albumId,'order_type'=>1))->field('id,pay_status')->find();
        if($video['pay_status'] == 3){
            return $video['id'];
        }else{
            return false;
        }
//        return $this->albumOrder->getAlbumOrderId($uid, $albumId);
    }

    /**
     * 查询一个用户是否购买过一个课程
     * @param integer $uid 用户UID
     * @param integer $albumId 课程ID
     * @return integer|false 返回对应的课程订单状态 ，如果失败则返回false
     */
    public function isBuyVideo($uid, $videoId){
        $video = $this->where(array('uid'=>$uid, 'video_id'=>$videoId))->field('id,pay_status')->find();
        if($video['pay_status'] == 3 || $video['pay_status'] == 4 || $video['pay_status'] == 6){
            return $video['id'];
        }else{
            return false;
        }
    }

}