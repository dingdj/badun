<?php
    /**
     * 课程订单模型
     * @author xiewei <master@xiew.net>
     * @version 1.0zy_order_course
     */
class ZyOrderLiveModel extends Model{
    
    var $tableName = 'zy_order_live'; //映射到表

    protected $albumOrder = null;

    /**
     * 取得直播课堂学习状态
     * @param integer $uid 用户UID
     * @param integer $albumId 课程ID
     * @return integer|false (课程学习状态(0:未开始,1:学习中,2:已完成))，失败返回false
     */
    public function getVideoLearnStatus($uid, $videoId){
        return $this->where(array('uid'=>$uid,'live_id'=>$videoId))->getField('learn_status');
    }


    /**
     * 取得直播课堂学习状态
     * @param integer $uid 用户UID
     * @param integer $albumId 直播课堂ID
     * @return integer|false (直播课堂学习状态(0:未开始,1:学习中,2:已完成))，失败返回false
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
     * 课程或直播课堂的学习状态
     * @param integer $uid 用户UID
     * @param integer $id 课程ID/直播课堂ID
     * @param $type 1为课程，否则为直播课堂
     * @return integer|false (课程/直播课堂学习状态(0:未开始,1:学习中,2:已完成))，失败返回false
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
     * @param integer $live_id 视频ID
     * @param integer $status 学习状态(0:未开始,1:学习中,2:已完成)
     */
    public function setLearnStatus($uid, $live_id, $status){
        return $this->where(array('uid'=>$uid,'live_id'=>$live_id))->save(array('learn_status'=>$status));
    }

    /**
     * 查询一个用户是否购买过一个直播课堂
     * @param integer $uid 用户UID
     * @param integer $albumId 直播课堂ID
     * @return integer|false 返回对应的直播课堂订单ID，如果失败则返回false
     */
    public function isBuyLive($uid, $live_id){
        $live = $this->where(array('uid'=>$uid, 'live_id'=>$live_id))->field('id,pay_status')->find();
        if($live['pay_status'] == 3 || $live['pay_status'] == 4 || $live['pay_status'] == 6){
            return $live['id'];
        }else{
            return false;
        }
    }


    /**
     * 查询一个用户是否购买过一个课程
     * @param integer $uid 用户UID
     * @param integer $albumId 课程ID
     * @return integer|false 返回对应的课程订单状态 ，如果失败则返回false
     */
    public function isBuyVideo($uid, $videoId){
        $video = $this->where(array('uid'=>$uid, 'live_id'=>$videoId))->field('id,pay_status')->find();
        if($video['pay_status'] == 3 || $video['pay_status'] == 4 || $video['pay_status'] == 6){
            return $video['id'];
        }else{
            return false;
        }
    }

}