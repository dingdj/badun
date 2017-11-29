<?php
/**
 * 班级订单模型
 * @author xiewei <master@xiew.net>
 * @version 1.0
 */
class ZyOrderAlbumModel extends Model{

    var $tableName = 'zy_order_album'; //映射到表

    static protected $albumIds = array();

    /**
     * 通过班级订单ID取得班级ID
     * @param integer $id 要查询的订单编号ID
     * return mixed 成功时返回班级ID，失败时返回false
     */
    public function getAlbumIdById($id){
        if(!isset(self::$albumIds[$id])){
            self::$albumIds[$id] = self::$albumIds[$id] = $this->where(array('id'=>$id))->getField('album_id');
            if(!self::$albumIds[$id]) self::$albumIds[$id] = false;
        }
        return self::$albumIds[$id];
    }


    /**
     * 取得班级订单ID，根据用户ID和班级ID
     * @param integer $uid 用户UID
     * @param integer $albumId 班级ID
     * @return integer|false 返回对应的班级订单ID，如果失败则返回false
     */
    public function getAlbumOrderId($uid, $albumId){
        $id = $this->where(array('uid'=>$uid, 'album_id'=>$albumId))->getField('id');
        return $id ? $id : false;
    }

    /**
     * 查询一个用户是否购买过一个班级
     * @param integer $uid 用户UID
     * @param integer $albumId 班级ID
     * @return integer|false 返回对应的班级订单状态 ，如果失败则返回false
     *         $data['is_buy'] = D('ZyOrderAlbum')->isBuyAlbum($this->mid ,$id );
     */
    public function isBuyAlbum($uid, $album_id){
        $album = $this->where(array('uid'=>$uid, 'album_id'=>$album_id))->field('id,pay_status')->find();
        if($album['pay_status'] == 3 || $album['pay_status'] == 4 || $album['pay_status'] == 6){
            return $album['id'];
        }else{
            return false;
        }
    }
}