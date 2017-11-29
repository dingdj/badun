<?php
/**
 * 云课堂点播(班级)控制器
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class VideoShareAction extends CommonAction {

    public function index(){
        $vid    = 274;
        $type   = 0;

        $chars = 'JMRZaNTUbNOXcABIdFVWXeSAYlKLMmDEGInYZfDElCFGoPQjOPQkKLRSsGIJtTUgiVqBCJrWpELMuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $share_str = $type."H";
        for ( $i = 0; $i < 4; $i++ ){
            $share_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        $share_str .= "H".$this->mid."H";
        for ( $i = 0; $i < 5; $i++ ){
            $share_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        if($type == 0){
            $share_url = U('classroom/Video/view',array('id'=>$vid,'code'=>$share_str));
        }elseif($type == 1){
            $share_url = U('classroom/Album/view',array('id'=>$vid,'code'=>$share_str));
        }elseif($type == 2){
            $share_url = U('live/Index/view',array('id'=>$vid,'code'=>$share_str));
        }
        dump($share_url);
        dump("分享成功！您的分享链接为：".$share_url);
    }

    //将课程添加到购物车
    public  function  addVideoShare(){
        if(!$this->mid){
            $this->mzError("需要登录才可以进行操作");
        }
        $vid     = intval($_POST['vid']);
        $type   = intval($_POST['type']);

        if($type == 0 || $type == 2){
            $map ['id']          = $vid;
            $map ['is_activity'] = 1;
            $map ['is_del']      = 0;
            if($type == 0){
                $map ['type']    = 1;
            }
            if($type == 2){
                $map ['type']    = 2;
            }

            $video_id = D ( 'ZyVideo' )->where ( $map )->getField('id');
            if(!$video_id){
                $this->mzError("课程不存在");
            }
        }
        if($type == 1){
            $map ['id']     = $vid;
            $map ['status'] = 1;

            $video_id = M( 'album' )->where ( $map )->getField('id');
            if(!$video_id){
                $this->mzError("班级不存在");
            }
        }
        if($type == 3){
            $map ['id']     = $vid;
            $map ['is_del']     = 0;

            $video_id = M( 'zy_topic' )->where ( $map )->getField('id');
            if(!$video_id){
                $this->mzError("资讯不存在");
            }
        }
        $chars = 'JMRZaNTUbNOXcABIdFVWXeSAYlKLMmDEGInYZfDElCFGoPQjOPQkKLRSsGIJtTUgiVqBCJrWpELMuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $share_str = $type."H";
        for ( $i = 0; $i < 4; $i++ ){
            $share_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        $share_str .= "H".$vid."H";
        for ( $i = 0; $i < 5; $i++ ){
            $share_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        if($type == 0){
            $share_url = U('classroom/Video/view',array('id'=>$vid,'code'=>$share_str));
        }elseif($type == 1){
            $share_url = U('classroom/Album/view',array('id'=>$vid,'code'=>$share_str));
        }elseif($type == 2){
            $share_url = U('live/Index/view',array('id'=>$vid,'code'=>$share_str));
        }
        elseif($type == 3){
            $share_url = U('classroom/Topic/view',array('id'=>$vid,'code'=>$share_str));
        }
        $data['uid']        = $this->mid;
        $data['video_id']   = $video_id;
        $data['type']       = $type;
        $data['ctime']      = time();
        $data['tmp_id']     = $share_str;
        $data['share_url']  = $share_url;

        $share_id = M('zy_video_share')->where(array('uid'=>$this->mid,'video_id'=>$video_id))->getField('id');
        if($share_id){
            $res = M('zy_video_share')->where('id='.$share_id)->save($data);
        }else{
            $res = M('zy_video_share')->add($data);
        }
        if($res){
            /*if($type == 0){
                $credit = M('credit_setting')->where('id=4')->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $stype = 6;
                    $note = '分享课程获得的积分';
                }
            }else if($type == 1){
                $credit = M('credit_setting')->where('id=18')->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $stype = 6;
                    $note = '分享班级获得的积分';
                }
            }else if($type == 2){
                $credit = M('credit_setting')->where('id=12')->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $stype = 6;
                    $note = '分享直播获得的积分';
                }
            }
            model('Credit')->addUserCreditRule($this->mid,$stype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);*/
            $this->ajaxReturn(null,$share_url,1);
        }else{
            $this->mzError("分享失败");
        }
    }

    public function delShare(){
        if(!intval($_POST['sid'])){
            $this->mzError('请选择要删除的分享');
        }
        $map['id'] = intval($_POST['sid']);
        $result = M('zy_video_share')->where($map)->delete();
        if($result){
            $this->mzSuccess("删除成功");
        } else {
            $this->mzError("删除失败");
        }
    }

//    兑换积分
    public function changeShare(){
        if(!$this->mid){
            $this->mzError('请先登录');
        }
        $where = "share_id = {$this->mid} AND status = 1 AND is_exchange = 0";

        $course_share_price = M('zy_split_course')->where($where)->getField('share_sum');
        $live_share_price = M('zy_split_live')->where($where)->getField('share_sum');
        $album_share_price = M('zy_split_album')->where($where)->getField('share_sum');

        $share_price = $course_share_price + $live_share_price + $album_share_price;

        if(!floatval($share_price)){
            $this->mzError('您暂无可兑换的积分');
        }
        $data['is_exchange'] = 1;
        M('zy_split_course')->where($where)->save($data);
        M('zy_split_live')->where($where)->save($data);
        M('zy_split_album')->where($where)->save($data);

        model('ZySplit')->consume($this->mid,$share_price);
        $ZySplit = model('ZySplit')->addFlow($this->mid,0,$share_price,'兑换积分：'.$share_price,'credit_user');

        $credit = model('Credit')->recharge($this->mid,$share_price);

        if($credit){
            model('Credit')->addCreditFlow($this->mid,5,$share_price,$ZySplit,'credit_user','分享收入兑换积分：'.$share_price);
            $this->mzSuccess("兑换成功");
        } else {
            $this->mzError("兑换失败");
        }
    }
}
