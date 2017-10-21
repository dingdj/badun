<?php
/**
 * 套餐管理模型
 * @author Ashang <Ashang@phpzsm.com>
 * @version CY1.0
 */
class AlbumModel extends Model {


    /**
     * 获取套餐信息
     * @param $id 套餐id
     * array 返回的套餐数据
     */
    public function getAlbumById($id){
        $map['id'] = $id;
        $data = $this->where($map)->find();
        $data['listingtime'] = $data['listingtime'] ? date("Y-m-d H:i:s", $data['listingtime']) : '';
        $data['uctime']      = $data['uctime'] ? date("Y-m-d H:i:s", $data['uctime']) : '';
        return $data;
    }

    //根据套餐ID获取课程ID方法的暂留缓存
    protected static $_getVideoId = array();


    /*
     * 根据套餐ID获取课程ID
     * @param integer $id 套餐ID
     * @return string 包含课程ID的字符串，用逗号分割的，首尾都有逗号
     */
    public function getVideoId($id){
        if(!isset(self::$_getVideoId[$id])){
            $video_ids = M('album_video_link')->where('album_id='.$id)->order('id desc')->findAll();
            self::$_getVideoId[$id] = ','.implode(',' , getSubByKey($video_ids , 'video_id') ).',';
        }

        return self::$_getVideoId[$id];
    }

    //根据ID获取套餐名称
    public function getAlbumTitleById($id){
        $field = 'album_title';
        $map['id'] = $id;
        $data = $this->where($map)->field($field)->find();
        return $data['album_title'];
    }

    //根据ID获取套餐相关字段
    public function getAlbumOneInfoById($id,$field){
        $map['id'] = $id;
        $data = $this->where($map)->field($field)->find();
        return $data;
    }

    /**获取精选套餐
     * @param int $limit
     * @return mixed
     */
    public function getBestRecommend($limit=20){
        $map=array(
            'is_del'=>0,
            'is_best'=>1
        );
        $dataList=$this->where($map)->limit($limit)->select();
        return $dataList;

    }

    /**获取畅销榜单
     * @param int $limit
     * @return mixed
     */
    public function getSellWell($limit=20){
        $map=array(
            'is_del'=>0
        );
        $dataList = $this->where($map)->limit($limit)->select();
        return $dataList;
    }

    /**获取一个套餐集合的价格详细
     * @param array $list套餐集合
     * @return array
     */
    public function getAlbumMoneyList($list=array() , $uid){
        if(empty($list)) return array();
        foreach ($list as &$val){
            //$val['money_data'] = $this->getAlbumMoeny($val['album_video'] , $uid);
            if( intval($val['price']) ) {
                $price = is_admin($uid) ? 0 : $val['price'];
                $val['money_data'] = array('overplus'=>$price);
            } else {
                //获取套餐价格
                $val['money_data'] = $this->getAlbumMoeny( $val['album_video'] ,$uid);
            }
        }
        return $list;
    }

    /**获取一个套餐的价格数组
     * @param $ids课程id集合
     * @return mixed
     */
    public function getAlbumMoeny($ids , $uid){
        $_data = array();
        $oriPrice = 0;   //市场价格/原价
        $vipPrice = 0;   //vip价格
        $disPrice = 0;   //折扣价格
        $discount = 0;   //折扣
        $dis_type = 0;   //折扣类型
        $price = 0;      //价格
        //剩余需要支付的学习币
        $overplus = 0;
        //取得课程
        $data = M('ZyVideo')->where(array('id' => array('in', (string) getCsvInt($ids)),'is_del'=>0))->select();
        if($this->mid!=1){
            foreach ((array) $data as $value) {
                $prices = getPrice($value, $uid, true, true);
                $oriPrice += floatval($prices['oriPrice']);
                $vipPrice += floatval($prices['vipPrice']);
                $disPrice += floatval($prices['disPrice']);
                $discount += floatval($prices['discount']);
                $dis_type += floatval($prices['dis_type']);
                $price += floatval($prices['price']);
                if (!isBuyVideo($this->mid, $value['id']) && !is_admin($uid)) {
                    $overplus += floatval($prices['price']);
                }
            }
        }
        $_data['oriPrice'] = $oriPrice;
        $_data['vipPrice'] = $vipPrice;
        $_data['disPrice'] = $disPrice;
        $_data['discount'] = $discount;
        $_data['dis_type'] = $dis_type;
        $_data['price']    = $price;
        //剩余需要支付的学习币
        $_data['overplus'] = $overplus;
        return $_data;
    }

    /**格式化套餐评分
     * @param $list
     * @return array
     */
    public function getAlbumScore($list){
        if(empty($list)) return array();
        foreach ($list as &$val){
            $val['score'] = round($val['score']/20);
        }
        return $list;
    }


    /**
     * 套餐分类
     */
    public function albumCategory(){
        $map = array();
        $map['pid'] = 0;
        $map['title'] = array('neq','');
        $data = M('zy_package_category')->where($map)->select();
        return $data;
    }


    /**
     * 获取套餐列表
     * @param array  $map
     * @param string $order
     * @return array
     */
    public function getList($map,$order = 'ctime desc',$limit=6){
        $list = $this->where($map)->order($order)->findPage($limit);
        $uid = intval($_SESSION['mid']);
        $list['time'] = array();
        foreach ($list['data'] as $key=>$val){
            $album_video = M('album_video_link')->where(array('album_id'=>$val['id']))->select();
            if($album_video){
                $vids = getSubByKey($album_video,'video_id');
                if($vids){
                    $vMap['is_del'] = 0;
                    $vMap['id'] = array('in',$vids);
                    $vidoes = M('zy_video')->where($vMap)->select();
                    $teachers = array_unique(getSubByKey($vidoes,'teacher_id'));

                    $live_zshd =M('zy_live_zshd') -> where(array('live_id'=>['in',$vids],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select() ? : [];
                    $live_gh =M('zy_live_gh') -> where(array('live_id'=>['in',$vids],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select() ? : [];
                    $live_cc =M('zy_live_cc') -> where(array('live_id'=>['in',$vids],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select() ? : [];
                    $live_list = array_merge($live_zshd,$live_cc,$live_gh);
                    $tids = array_unique(getSubByKey($live_list, 'speaker_id')) ? : [];
                    $new_teacher = array_merge($teachers,$tids);
                    if($new_teacher){
                        $tMap['id'] = array('in',$new_teacher);
                        $tMap['is_del'] = 0;
                        $list['data'][$key]['teachers'] = M('zy_teacher')->where($tMap)->select();
                    }else{
                        $list['data'][$key]['teachers'] = array();
                    }
                    $list['data'][$key]['video'] = $vidoes;
                    $oprice = 0;
                    foreach ($vidoes as $k=>$v){
                        $oprice += $v['t_price'];
                    }
                    $list['data'][$key]['oPrice'] = ($val['price'] > $oprice) ? $val['price'] : $oprice;
                    $list['data'][$key]['disPrice'] = ($oprice - $val['price']) > 0 ? ($oprice - $val['price']) : 0.00;
                }else{
                    $list['data'][$key]['video'] = array();
                }

            }else{
                $list['data'][$key]['video'] = array();
            }
            /*统计年份*/
            if(!in_array(date('Y',$val['ctime']),$list['time'])){
                $list['time'][] = date('Y',$val['ctime']);
            }
            if($uid){
                /*查看是否已购买*/
                $pay_status = M('zy_order_album')->where(array('uid' => $uid, 'album_id' => $val['id']))->getField('pay_status');
                $list['data'][$key]['isBuy'] = ($pay_status == 3) ? 1 : 0;
                /*查看是否已收藏*/
                $list['data'][$key]['isCollect'] = D('ZyCollection','classroom')->isCollect($val['id'],'album');
            }else{
                $list['data'][$key]['isBuy'] = 0;
                $list['data'][$key]['isCollect'] = 0;
            }

            unset($vidoes);
        }
        return $list;
    }

}
