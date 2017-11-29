<?php
/**
 * 猜你喜欢管理模型
 * @author link
 * @version GJW2.0
 */
class ZyGuessYouLikeModel extends Model {

    var $tableName = 'zy_guess_you_like'; //映射到表

    /**
     *记录猜你喜欢的类型及分类id
     * @param $type 0点播1班级2直播3讲师
     * @param $cate_id 分类全路径第一个id极好的
     * @param $uid 用户id
     * @return 状态 ture为成功
     */
    public function opTypeGYL($type,$cate_id,$uid){
        if($uid){
            $guess_info = $this->where(array('type'=>$type,'uid'=>$uid))->field('uid,type')->find();

            $data['uid']     = $map['uid'] = $uid;
            $data['cate_id'] = $cate_id;
            $data['ctime']   = time();
            $data['tmp_id']  = $map['tmp_id'] = session_id();
            $data['type']    = $map['type'] = $type;

            if(!$guess_info) {
                $res = $this->add($data);
            }else{
                $res = $this->where($map)->save($data);
            }
        }else{
            session('[start]');
            session('gyl_cate_id',$cate_id);
            session('gyl_type',$type);
        }

        return $res ?  true : false;
    }

    /**
     * @param $type 类型 0点播1班级2直播3讲师
     * @param $uid 用户id
     * @return $data
     */
    public function getGYLData($type,$uid,$limit){
        if(!$uid){
            $guess_cate_id = session('gyl_cate_id');
            $type = session('gyl_type');
        }else{
            $guess_cate_id = $this->where(array('tmp_id'=>session_id(),'type'=>$type,'uid'=>$uid))->getField('cate_id');
        }
        //0 2 为直播 其他的自己加
        if($type == 0 || $type == 2 ){
            $map['is_del']      = 0;
            $map['is_activity'] = 1;
            $map['fullcategorypath'] = array('like',"%,{$guess_cate_id},%");
            $map['uctime']      = array('GT',time());
            $map['listingtime'] = array('LT',time());

            $data = M('zy_video')->where($map)->order('video_collect_count desc,video_comment_count desc,video_question_count desc,
                video_note_count desc,video_score desc,video_order_count desc,listingtime desc')->field("id,video_title,mhm_id,video_intro,cover,v_price,
                t_price,vip_level,is_charge,endtime,starttime,limit_discount,uid,teacher_id,type")->limit($limit)->select();
            if(!$data){
                unset($map['fullcategorypath']);
                $data = M('zy_video')->where($map)->order('video_collect_count desc,video_comment_count desc,video_question_count desc,
                video_note_count desc,video_score desc,video_order_count desc,listingtime desc')->field("id,video_title,mhm_id,video_intro,cover,v_price,
                t_price,vip_level,is_charge,endtime,starttime,limit_discount,uid,teacher_id,type")->limit($limit)->select();
            }
            foreach ($data as $key => $val){
                $data[$key]['video_intro']  = mb_substr(t($val['video_intro']),0,50,'utf-8' );
                $data[$key]['mhmName']      = model('School')->getSchooldStrByMap(array('id'=>$val['mhm_id']),'title');
                if($val['type'] == 1){
                    $data[$key]['money_data']   = getPrice ( $val, $this->mid, true, true );
                }else{
                    $data[$key]['money_data']['oriPrice'] = $val['t_price'];
                    $data[$key]['money_data']['price'] = $val['t_price'];
                }
            }
        }else if($type == 3){
            $map['is_del']      = 0;
            $map['fullcategorypath'] = array('like',"%,{$guess_cate_id},%");

            $data = M('zy_teacher')->where($map)->order('course_count desc,reservation_count desc,review_count desc,views desc')
                ->limit($limit)->select();
        }

        return $data;
    }
}