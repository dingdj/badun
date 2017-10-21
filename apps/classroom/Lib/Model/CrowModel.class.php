<?php
/**
 * 众筹课程模块
 * @author ezhu <ezhufrank@qq.com>
 *
 */

class CrowModel extends Model{
    
    protected $tableName = 'crowdfunding';
    
    
    /**
     * 获取众筹列表
     * @param array  $map
     * @param string $field
     * @param string $order
     * @param number $limit
     * @return array
     */
    public function getList($map,$field=true,$order,$limit=10){
        $list = $this->where($map)->field($field)->order($order)->findPage($limit);
        return $list;
    }
    
    
    /**
     * 获取众筹详细信息
     * @param array $map
     * @param string $field
     */
    public function getInfo($map=array(),$field=true){
        $data = $this->where($map)->field($field)->find();
        return $data;
    }
    
    
    
    /**
     * 是否可以众筹，如果是，返回众筹人数
     * @param int $id
     * @return boolean|number
     */
    public function isCrowing($id){
        $map = array();
        $map['id'] = array('eq',$id);
        $map['status'] = array('eq',3);
        $field = 'id,num';
        $data = $this->getInfo($map,$field);
        if(empty($data)){
            $this->error = '众筹不存在或已结束';
            return false;
        }
        $joinUser = $this->joinUser($id);
        if($data['num'] <= $joinUser){
            $this->error = '众筹结束';
            return false;
        }else{
            $user = $data['num'] - $joinUser;
            $data['joined'] = $joinUser;
            $data['left'] = $user;
            $data['averagePrice'] = $this->averagePrice($data['price'],$data['price']);
            return $data;
        }
        
    }
    
    
    /**
     * 获取参加众筹用户
     * @param unknown $joinId 众筹id
     */
    public function userList($joinId){
        if(empty($joinId)){
            return false;
        }
        $map['cid'] = array('eq',$joinId);
        $list = M('crowdfunding_user')->where($map)->select();
        return $list;
    }
    
    
    /**
     * 众筹人数
     * @param int $cid
     * @return int
     */
    public function joinUser($cid){
        $map = array();
        $map['cid'] = $cid;
        $count = M('CrowdfundingUser')->where($map)->count();
        return $count;
    }
    
    
    /**
     * 众筹平均价
     */
    public function averagePrice($price=0,$num=0){
        if(empty($price) || empty($num)){
            return false;
        }
        $averagePrice = ($price / $num);
        $averagePrice = sprintf("%.2f",substr(sprintf("%.3f", $averagePrice), 0, -1));
        return $averagePrice;
    }
    
    
    /**
     * 是否加入众筹
     * @param unknown $id
     * @param unknown $uid
     */
    public function isCrow($id,$uid){
        $map['uid'] = $uid;
        $map['id'] = $id;
        $isCrow = M('crowdfunding_user')->where($map)->find();
        return !empty($isCrow) ? 1 : 0 ;
    }
    
    
    /**
     * 众筹成功，回调函数。处理众筹情况和加入众筹用户
     */
    public function paySuccess($uid,$crow_id){
        $rst = M('crowdfunding_user')->add(array('uid'=>$uid,'cid'=>$crow_id,'ctime'=>time()));
        $crow = M('crowdfunding')->where(array('id'=>$crow_id))->find();
        $count = M('crowdfunding_user')->where(array('cid'=>$crow_id))->count();
        if($crow['num'] <= $count){// 众筹成功
            M('crowdfunding')->where(array('id'=>$crow_id))->save(array('status'=>3));
        }
        
        $s['title'] = "恭喜您成功加入众筹";
        $s['body'] = "恭喜您成功购买众筹：{$crow['title']}";
        $s['ctime'] = time();
        model('Notify')->sendMessage($s);
    }
    
    
    
    
    
    
    
}