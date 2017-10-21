<?php
/**
 * 问答评论模型
 * By:Ashang
 * Date: 14-10-13
 * Time: 下午8:43
 */
class ZyWendaCommentModel extends Model{

    var $tableName = 'zy_wenda_comment'; //映射到点评表
    /**根据type取最新或者最赞的一条回答
     * @param $id
     * @param $type
     */
    public function getNowWenda($id,$type){
        $id=intval($id);//问答id
        $type=intval($type);//类型 1,最新的回答 2最赞的回答
        $where=array(
            'is_del'=>0,
            'wid'=>$id
        );
        if($type==1){
          $order="ctime DESC";
        }else{
          $orde="help_count DESC";
        }
        $nowd=$this->where($where)->order($order)->find();
        return $nowd;
    }

    /**设置回复评论量+1
     * @param $id
     */
    public function addCommentCount($id){
        $id=intval($id);
        if(!empty($id)){
            //$this->where("id=$id")->setInc('wd_browse_count',1);
            $this->query('UPDATE `'.C('DB_PREFIX').'zy_wenda_comment` SET `comment_count`=`comment_count`+1 WHERE `id`='.$id);
        }
    }
     /**设置回复评论量+1
     * @param $id
     */
    public function reductionCommentCount($id){
        $id=intval($id);
        if(!empty($id)){
            //$this->where("id=$id")->setInc('wd_browse_count',1);
            $this->query('UPDATE `'.C('DB_PREFIX').'zy_wenda_comment` SET `comment_count`=`comment_count`-1 WHERE `id`='.$id);
        }
    }
    /**设置回复赞+1
     * @param $id
     */
    public function addCommentZan($id){
        $id=intval($id);
        if(!empty($id)){
            //$this->where("id=$id")->setInc('wd_browse_count',1);
            $this->query('UPDATE `'.C('DB_PREFIX').'zy_wenda_comment` SET `help_count`=`help_count`+1 WHERE `id`='.$id);
        }
    }
    public function getListForId($map = array(),$limit=20,$field="*",$order = "id DESC"){
        $user = M('zy_wenda_comment');
        // 查询数据
        $list = $this->where ( $map )->order ( $order )->field ( $field )->findPage ( $limit );
        echo "ghghg";
        dump($list);
        return $list?$list:false;
    }

/**
 * 删除問答回復
 * @param array|int $ids 点评ID
 * @return array 操作状态【1:删除成功;100003:要删除的ID不合法;false:删除失败】
 */
public function doDeletewendacomment($ids){
    $myIds = array();
    if(is_array($ids)){
        $_ids = implode(',',$ids);
        $myIds = array_merge($myIds,$ids);
    }else{
        $_ids = intval($ids);
        $myIds[] = $_ids;
    }
    if(!trim($_ids)){
        return array('status'=>100003);
    }
    //先找到问题和评论和回复的所有ID
    $this->_getPids($_ids,$myIds);
    $myIds = $myIds?implode(',',$myIds):0;
    //开始删除提问
    $i = $this->where(array('id'=>array('in',(string)$myIds)))->delete();
    if($i === false){
        return false;
    }else{
        return array('status'=>1);
    }
}
}
?>