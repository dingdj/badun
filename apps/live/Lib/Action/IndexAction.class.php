<?php

/**
 * Eduline直播首页控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload ( APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php' );
class IndexAction extends CommonAction {
    
    protected $video = null; // 课程模型对象
    protected $category = null; // 分类数据模型
    protected $base_config = array();//直播配置
    protected $zshd_config = array();//展示互动
    protected $cc_config = array();//CC
    protected $wh_config = array();//微吼

    /**
     * 初始化
     */
    public function _initialize() {
        $this->video = D ( 'ZyVideo' ,'classroom');
        $this->category = model ( 'VideoCategory' );
        $this->zshd_config =  model('Xdata')->get('live_AdminConfig:zshdConfig');
        $this->cc_config   =  model('Xdata')->get('live_AdminConfig:ccConfig');
        $this->base_config =  model('Xdata')->get('live_AdminConfig:baseConfig');
        $this->wh_config   =  model('Xdata')->get('live_AdminConfig:whConfig');
    }

    //获取服务器时间-三芒
    public function test(){
        $url = 'http://edulinedemo.3mang.com/3m/meeting/timestamp.do';
        $data = '<?xml version="1.0" encoding="UTF-8"?>
                <param>
                <siteId>edulinedemo</siteId>
                <random>'.time().'</random>
                <authId>'.md5( '8a216b7954e1432b0154e1432b1d0000'.'edulinedemo'.time() ).'</authId>
                </param>';
        $datas = request_post_xml($url , $data);
        $datas = @simplexml_load_string($datas);
        $datas = json_decode(json_encode($datas),true);
        return $datas['timestamp'];
    }

    //获取某课程详情-三芒
    function getClass(){
        $url = 'http://edulinedemo.3mang.com/3m/meeting/join_mtg.do';
        $timestamp = $this->test();
        $data = '<?xml version="1.0" encoding="UTF-8"?>
                <param>
                <siteId>edulinedemo</siteId>
                <mtgKey>631103061</mtgKey>
                <mtgTitle>edulinetest1</mtgTitle>
                <startTime>2016-05-24 13:00:00</startTime>
                <endTime>2016-05-31 15:00:00</endTime>
                <language>2</language>
                <userName>wj</userName>
                <userId>2000013</userId>
                <userType>8</userType>
                <hostPwd>12346</hostPwd>
                <meetingType>2</meetingType>
                <isPublic>1</isPublic>
                <docModule>0</docModule >
                <screenModule>0</screenModule >
                <mediaModule>0</mediaModule>
                <whiteboardModule>0</whiteboardModule>
                <recordModule>0</recordModule >
                <videoModule>0</videoModule>
                <h5Module>0</h5Module>
                <autoRecord>0</autoRecord>
                <interaction>0</interaction>
                <maxAudioChannels>1</maxAudioChannels>
                <maxVideoChannels>1</maxVideoChannels>
                <videoQuality>1</videoQuality>
                <docID>4028ac814a843f59014a8463d258000a</docID>
                <mediaID>4028ac814a843f59014a8463d258000a</mediaID>
                <backUrl></backUrl>
                <videoQuality>0</videoQuality>
                <timestamp>'.$timestamp.'</timestamp>
                <authId>'.md5('8a216b7954e1432b0154e1432b1d0000'.'edulinedemo'.'631103061'.'2000013'.'8'.$timestamp).'</authId>
                </param>';
        $datas = request_post_xml($url , $data);
        $datas = @simplexml_load_string($datas);
        $datas = json_decode(json_encode($datas),true);
        return $datas;
    }

    /**
     * Eduline直播首页方法
     * @return void
     */
    public function index() {
        //加载首页头部轮播广告位
        $ad_map = array('is_active' => 1,'display_type' => 3,'place' => 3);
        $ad_list = M('ad')->where($ad_map)->order('display_order DESC')->find();

        //序列化广告内容
        $ad_list = unserialize($ad_list['content']);

        //今日直播推荐
        $map['type'] = 2;
        $map['is_del'] = 0;
        $map['is_activity'] = 1;
//        $map['is_best'] = 1;
        $map['listingtime'] = array('lt', time());
        $map['uctime'] = array('gt', time());

        $videoData = $this->video->where($map)->order('video_order_count desc,video_score desc,video_collect_count desc')->select();
        $liveData = array();
        foreach ($videoData as $k=>$v){
            $todayLiveData = $this->getTodayLive($v['live_type'],$v['id']);
            if($todayLiveData){
                $liveData[] = $todayLiveData;
            }
        }

        $sort = array();
        foreach ($liveData as $livesort) {
            $sort[] = $livesort['startDate'];
        }
        array_multisort($sort, SORT_ASC, $liveData);

        foreach ($liveData as $live3 => $gh) {
            if (strtotime($gh['begin'])   < time() && strtotime($gh['end'])  > time()) {
                $liveData[$live3]['status'] = '直播中';
            } elseif (strtotime($gh['end']  < time())) {
                $liveData[$live3]['status'] = '已结束';
            } elseif (strtotime($gh['begin']  > time())) {
                $liveData[$live3]['status'] = '未开始';
            }

        }
        //明日直播推荐
        $tomorowmap['type'] = 2;
        $tomorowmap['is_del'] = 0;
        $tomorowmap['is_activity'] = 1;
        $tomorowmap['listingtime'] = array('lt', time()+ 86400);
        $tomorowmap['uctime'] = array('gt',  time()+ 86400);

        $videoData = $this->video->where($tomorowmap)->order('video_order_count desc,video_score desc,video_collect_count desc')->select();
        $toliveData = array();
        foreach ($videoData as $k=>$v){
            $tomorowLiveData = $this->gettomorrowLive($v['live_type'],$v['id']);
            if($tomorowLiveData){
                $toliveData[] = $tomorowLiveData;
            }
        }

        $sort = array();
        foreach ($toliveData as $livesort) {
            $sort[] = $livesort['startDate'];
        }
        array_multisort($sort, SORT_ASC, $toliveData);

//        foreach ($toliveData as $live3 => $gh) {
//            if (strtotime($gh['begin'])   < time() && strtotime($gh['end'])  > time()) {
//                $toliveData[$live3]['status'] = '直播中';
//            } elseif (strtotime($gh['end']  < time())) {
//                $toliveData[$live3]['status'] = '已结束';
//            } elseif (strtotime($gh['begin']  > time())) {
//                $toliveData[$live3]['status'] = '未开始';
//            }
//
//        }
        //后日直播推荐
        $toaftmorowmap['type'] = 2;
        $toaftmorowmap['is_del'] = 0;
        $toaftmorowmap['is_activity'] = 1;
        $toaftmorowmap['listingtime'] = array('lt', time()+ 172800);
        $toaftmorowmap['uctime'] = array('gt',  time()+ 172800);

        $toafervideoData = $this->video->where($toaftmorowmap)->order('video_order_count desc,video_score desc,video_collect_count desc')->select();
        $aftertoliveData = array();
        foreach ($toafervideoData as $k=>$v){
            $tomorowLiveData = $this->dayafttomorrow($v['live_type'],$v['id']);
            if($tomorowLiveData){
                $aftertoliveData[] = $tomorowLiveData;
            }
        }

        $sort = array();
        foreach ($aftertoliveData as $livesort) {
            $sort[] = $livesort['startDate'];
        }
        array_multisort($sort, SORT_ASC, $aftertoliveData);

//        foreach ($aftertoliveData as $live3 => $gh) {
//            if (strtotime($gh['begin'])   < time() && strtotime($gh['end'])  > time()) {
//                $aftertoliveData[$live3]['status'] = '直播中';
//            } elseif (strtotime($gh['end']  < time())) {
//                $aftertoliveData[$live3]['status'] = '已结束';
//            } elseif (strtotime($gh['begin']  > time())) {
//                $aftertoliveData[$live3]['status'] = '未开始';
//            }
//
//        }

        //精彩直播
        $this->is_pc ? $perfect_size = 16 : $perfect_size = 4;
        $perfect = $this->video->where($map)->order('best_sort asc,ctime desc')->field("id,video_title,mhm_id,cover,v_price,is_charge,
                    t_price,vip_level,is_best,listingtime,limit_discount,uid,type,live_type,str_tag,view_nums,teacher_id")->limit($perfect_size)->select();
        foreach ($perfect as $knd => $vnd) {
            //如果为管理员/机构管理员自己机构的课程 则免费
            $perfect[$knd]['mzprice'] = getPrice($vnd , $this->mid , true , true);

            $video_section_status = '';
            $video_section_end_num = 0;

            $live_info = model('Live')->liveRoom->where(array('live_id' => $vnd['id'], 'is_active' => 1, 'is_del' => 0,'type'=>$vnd['live_type']))->field('id,startDate,invalidDate')->select();

            foreach ($live_info as $live1 => $zshd) {
                if ($zshd['startDate'] < time() && $zshd['invalidDate'] > time()) {
                    $video_section_status = '直播中';
                } elseif ($zshd['invalidDate'] <= time()) {
                    $video_section_status = '已结束';
                } elseif ($zshd['startDate'] >= time()) {
                    $video_section_status = '未开始';
                }

                if ($zshd['invalidDate'] < time()) {
                    $video_section_end_num += 1;
                }
            }

            $perfect[$knd]['video_section_num'] = count($live_info);
            $perfect[$knd]['video_section_ing'] = $video_section_status;
            $perfect[$knd]['video_section_end_num'] = $video_section_end_num;
            $perfect[$knd]['video_str_tag'] = reset(array_filter(explode(',', $vnd['str_tag'])));
            $perfect[$knd]['video_str_tag'] ? : $perfect[$knd]['video_str_tag'] = "暂无标签";
        }

        //分类楼层数据
        $this->is_pc ? $live_cate_size = 4 : $live_cate_size = 4;
        $live_cate = M('zy_currency_category')->where(array('pid'=>0,'is_choice_pc'=>1))->order('sort ASC')->limit($live_cate_size)->field('zy_currency_category_id,pid,title')->findAll();
        foreach ($live_cate as $keys => $value) {
            $live_cate_data = M('zy_video')->where(array('is_mount'=>1,'type'=>2, 'fullcategorypath' => array('like', "%,{$value['zy_currency_category_id']},%"),
                'is_del' => 0, 'is_activity' => 1, 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,v_price,is_charge,
                        video_binfo,video_order_count,t_price,vip_level,is_best,listingtime,limit_discount,uid,teacher_id,type,live_type,str_tag,video_score")->order('listingtime desc,ctime desc')->limit(3)->findAll();
            foreach ($live_cate_data as $knd => $vnd) {
//                $video_section_status = '';
//                $video_section_end_num = 0;
//                if ($vnd['live_type'] == 1) {
//                    $live_info = M('zy_live_zshd')->where(array('live_id' => $vnd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,startDate,invalidDate')->select();
//                    foreach ($live_info as $live1 => $zshd) {
//                        if ($zshd['startDate'] < time() && $zshd['invalidDate'] > time()) {
//                            $video_section_status = '直播中';
//                        } elseif ($zshd['invalidDate'] < time()) {
//                            $video_section_status = '已结束';
//                        } elseif ($zshd['startDate'] > time()) {
//                            $video_section_status = '未开始';
//                        }
//
//                        if ($zshd['invalidDate'] < time()) {
//                            $video_section_end_num += 1;
//                        }
//                    }
//                } elseif ($vnd['live_type'] == 3) {
//                    $live_info = M('zy_live_gh')->where(array('live_id' => $vnd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,beginTime,endTime')->select();
//                    foreach ($live_info as $live3 => $gh) {
//                        if ($gh['beginTime'] / 1000 < time() && $gh['endTime'] / 1000 > time()) {
//                            $video_section_status = '直播中';
//                        } elseif ($gh['endTime'] / 1000 < time()) {
//                            $video_section_status = '已结束';
//                        } elseif ($gh['beginTime'] / 1000 > time()) {
//                            $video_section_status = '未开始';
//                        }
//
//                        if ($gh['endTime'] / 1000 < time()) {
//                            $video_section_end_num += 1;
//                        }
//                    }
//                }
//                $live_cate_data[$knd]['video_section_num'] = count($live_info);
//                $live_cate_data[$knd]['video_section_ing'] = $video_section_status;
//                $live_cate_data[$knd]['video_section_end_num'] = $video_section_end_num;
                $live_cate_data[$knd]['video_str_tag'] = implode( array_filter(explode(',', $vnd['str_tag'])), ' ');
                $live_cate_data[$knd]['video_intro'] = mb_substr(t($vnd['video_intro']), 0, 50, 'utf-8');
                $live_cate_data[$knd]['reviewCount'] = D ('ZyReview','classroom')->getReviewCount ( 1, intval($vnd['id']) );
                $live_cate_data[$knd]['mhm_name'] = model('School')->getSchooldStrByMap(array('id' => $vnd['mhm_id']), 'title');
//                $live_cate_data[$knd]['teacher_name'] = M('zy_teacher')->where(array('id' => $vnd['teacher_id']))->getField('name');
                $source = D ( 'ZyCollection' )->where(array('source_id'=>$vnd['id'],'source_table_name'=>'zy_video','uid'=>$this->mid))->find();
                if($source){
                    $is_collection = 1;
                }else{
                    $is_collection = 0;
                }
                $live_cate_data[$knd]['is_Collection'] = $is_collection;
            }

            $live_cate[$keys]['live_cate_data'] = $live_cate_data;
        }

        $this->assign('ad_list', $ad_list);
        $this->assign ('liveData', $liveData);
        $this->assign ('toliveData', $toliveData);
        $this->assign ('aftertoliveData', $aftertoliveData);
        $this->assign ('cateData', $live_cate);
        $this->assign ('perfect', $perfect);
        $this->display ();
    }

    protected function getTodayLive($liveType,$id){
        $where = array();
        $today = date('Ymd',time());
        $todaystart = intval(strtotime($today));
        $todayend = $todaystart + (24*60*60);
        if($liveType == 1){
            $where['is_del'] = 0;
            $where['live_id'] = $id;
            $where['is_active'] = 1;
            $where['_string'] = "(startDate > $todaystart  and startDate < $todayend)  OR  (invalidDate > $todaystart  and invalidDate < $todayend)  OR  (startDate < $todaystart and   invalidDate > $todayend ) ";
            $todayLive = M('zy_live_zshd')->where($where)->order('startDate asc')->find();
            if($todayLive){
                $todayLive['title'] = $todayLive['subject'];
                $todayLive['begin'] = date('H:i',$todayLive['startDate']);
                $todayLive['end'] = date('H:i',$todayLive['invalidDate']);

                if($todayLive['startDate'] <= $todaystart  && $todayLive['invalidDate'] >= $todayend)
                {
                    $todayLive['begin'] = date('H:i',$todaystart);
                    $todayLive['end'] = date('H:i',$todayend-1);
                }
                if($todayLive['startDate'] <= $todaystart  && $todayLive['invalidDate'] <= $todayend && $todayLive['invalidDate'] >= $todaystart)
                {
                    $todayLive['begin'] = date('H:i',$todaystart);
                    $todayLive['end'] =  date('H:i',$todayLive['invalidDate']);
                }
                if($todayLive['startDate'] >= $todaystart  && $todayLive['invalidDate'] >= $todayend)
                {
                    $todayLive['begin'] = date('H:i',$todayLive['startDate']);
                    $todayLive['end'] =  date('H:i',$todayend-1);
                }


            }
        } elseif($liveType == 3) {
            $where['is_del'] = 0;
            $where['is_active'] = 1;
            $todaystartgh = $todaystart*1000;
            $todayendgh = $todayend*1000;
            $where['_string'] = "(startDate > $todaystartgh  and startDate < $todayendgh)  OR  (invalidDate > $todaystartgh  and invalidDate < $todayendgh)   OR  (startDate < $todaystart and   invalidDate > $todayend ) ";
            $where['live_id'] = $id;
            $todayLive = M('zy_live_gh')->where($where)->order('startDate asc')->find();

            if($todayLive){
                $todayLive['begin'] = date('H:i',$todayLive['startDate']/1000);
                $todayLive['end'] = date('H:i',$todayLive['invalidDate']/1000);

                if($todayLive['startDate'] <= $todaystartgh  && $todayLive['invalidDate'] >= $todayendgh)
                {
                    $todayLive['begin'] = date('H:i',$todaystart);
                    $todayLive['end'] = date('H:i',$todayend-1);
                }

                if($todayLive['startDate'] <= $todaystartgh  && $todayLive['invalidDate'] <= $todayendgh  && $todayLive['invalidDate'] >= $todaystartgh )
                {
                    $todayLive['begin'] = date('H:i',$todaystart);
                    $todayLive['end'] =  date('H:i',$todayLive['invalidDate']/1000);
                }
                if($todayLive['startDate'] >= $todaystartgh  && $todayLive['invalidDate'] >= $todayendgh   &&  $todayLive['startDate'] <= $todayendgh )
                {
                    $todayLive['begin'] = date('H:i',$todayLive['startDate']/1000  );
                    $todayLive['end'] =  date('H:i',$todayend-1);
                }
            }
            $todayLive['startDate'] =  $todayLive['startDate']/1000;
            $todayLive['invalidDate'] =  $todayLive['invalidDate']/1000;
        }
        elseif($liveType == 4) {
            $where['is_del'] = 0;
            $where['is_active'] = 1;
            $where['_string'] = "(startDate > $todaystart  and startDate < $todayend)  OR  (invalidDate > $todaystart  and invalidDate < $todayend)   OR  (startDate < $todaystart and   invalidDate > $todayend )";
            $where['live_id'] = $id;
            $todayLive = M('zy_live_cc')->where($where)->order('startDate asc')->find();

            if($todayLive){
                $todayLive['begin'] = date('H:i',$todayLive['startDate']);
                $todayLive['end'] = date('H:i',$todayLive['invalidDate']);
                if($todayLive['startDate'] <= $todaystart  && $todayLive['invalidDate'] >= $todayend)
                {
                    $todayLive['begin'] = date('H:i',$todaystart);
                    $todayLive['end'] = date('H:i',$todayend-1);
                }
                if($todayLive['startDate'] <= $todaystart  && $todayLive['invalidDate'] <= $todayend   && $todayLive['invalidDate'] >= $todaystart)
                {
                    $todayLive['begin'] = date('H:i',$todaystart);
                    $todayLive['end'] =  date('H:i',$todayLive['invalidDate']);
                }
                if($todayLive['startDate'] >= $todaystart  && $todayLive['invalidDate'] >= $todayend  &&  $todayLive['startDate'] <= $todayend )
                {
                    $todayLive['begin'] = date('H:i',$todayLive['startDate']);
                    $todayLive['end'] =  date('H:i',$todayend-1);
                }

            }
        }

        return $todayLive;
    }



    protected function gettomorrowLive($liveType,$id){
        $where = array();
        $today = date('Ymd',time());
        $tomorrowstart = intval(strtotime($today)+ 86400);
        $tomorrowend = $tomorrowstart + (24*60*60);
        if($liveType == 1){
            $where['is_del'] = 0;
            $where['live_id'] = $id;
            $where['is_active'] = 1;
            $where['_string'] = "(startDate > $tomorrowstart  and startDate < $tomorrowend)  OR  (invalidDate > $tomorrowstart  and invalidDate < $tomorrowend)  OR  (startDate < $tomorrowstart and   invalidDate > $tomorrowend ) ";
            $tomorrowLive = M('zy_live_zshd')->where($where)->order('startDate asc')->find();
            if($tomorrowLive){
                $tomorrowLive['title'] = $tomorrowLive['subject'];
                $tomorrowLive['begin'] = date('H:i',$tomorrowLive['startDate']);
                $tomorrowLive['end'] = date('H:i',$tomorrowLive['invalidDate']);

                if($tomorrowLive['startDate'] <= $tomorrowstart  && $tomorrowLive['invalidDate'] >= $tomorrowend)
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowstart);
                    $tomorrowLive['end'] = date('H:i',$tomorrowend-1);
                }
                if($tomorrowLive['startDate'] <= $tomorrowstart  && $tomorrowLive['invalidDate'] <= $tomorrowend && $tomorrowLive['invalidDate'] >= $tomorrowstart)
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowstart);
                    $tomorrowLive['end'] =  date('H:i',$tomorrowLive['invalidDate']);
                }
                if($tomorrowLive['startDate'] >= $tomorrowstart  && $tomorrowLive['invalidDate'] >= $tomorrowend)
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowLive['startDate']);
                    $tomorrowLive['end'] =  date('H:i',$tomorrowend-1);
                }


            }
        } elseif($liveType == 3) {
            $where['is_del'] = 0;
            $where['is_active'] = 1;
            $tomorrowstartgh = $tomorrowstart*1000;
            $tomorrowendgh = $tomorrowend*1000;
            $where['_string'] = "(startDate > $tomorrowstartgh  and startDate < $tomorrowendgh)  OR  (invalidDate > $tomorrowstartgh  and invalidDate < $tomorrowendgh)   OR  (startDate < $tomorrowstartgh and   invalidDate > $tomorrowendgh ) ";
            $where['live_id'] = $id;
            $tomorrowLive = M('zy_live_gh')->where($where)->order('startDate asc')->find();

            if($tomorrowLive){
                $tomorrowLive['begin'] = date('H:i',$tomorrowLive['startDate']/1000);
                $tomorrowLive['end'] = date('H:i',$tomorrowLive['invalidDate']/1000);

                if($tomorrowLive['startDate'] <= $tomorrowstartgh  && $tomorrowLive['invalidDate'] >= $tomorrowendgh)
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowstart);
                    $tomorrowLive['end'] = date('H:i',$tomorrowend-1);
                }

                if($tomorrowLive['startDate'] <= $tomorrowstartgh  && $tomorrowLive['invalidDate'] <= $tomorrowendgh  && $tomorrowLive['invalidDate'] >= $tomorrowstartgh )
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowstart);
                    $tomorrowLive['end'] =  date('H:i',$tomorrowLive['invalidDate']);
                }
                if($tomorrowLive['startDate'] >= $tomorrowstartgh  && $tomorrowLive['invalidDate'] >= $tomorrowendgh   &&  $tomorrowLive['startDate'] <= $tomorrowendgh )
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowLive['startDate']/1000  );
                    $tomorrowLive['end'] =  date('H:i',$tomorrowend-1);
                }
            }
            $tomorrowLive['startDate'] =  $tomorrowLive['startDate']/1000;
            $tomorrowLive['invalidDate'] =  $tomorrowLive['invalidDate']/1000;
        }
        elseif($liveType == 4) {
            $where['is_del'] = 0;
            $where['is_active'] = 1;
            $where['_string'] = "(startDate > $tomorrowstart  and startDate < $tomorrowend)  OR  (invalidDate > $tomorrowstart  and invalidDate < $tomorrowend)   OR  (startDate < $tomorrowstart and   invalidDate > $tomorrowend )";
            $where['live_id'] = $id;
            $tomorrowLive = M('zy_live_cc')->where($where)->order('startDate asc')->find();

            if($tomorrowLive){
                $tomorrowLive['begin'] = date('H:i',$tomorrowLive['startDate']);
                $tomorrowLive['end'] = date('H:i',$tomorrowLive['invalidDate']);
                if($tomorrowLive['startDate'] <= $tomorrowstart  && $tomorrowLive['invalidDate'] >= $tomorrowend)
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowstart);
                    $tomorrowLive['end'] = date('H:i',$tomorrowend-1);
                }
                if($tomorrowLive['startDate'] <= $tomorrowstart  && $tomorrowLive['invalidDate'] <= $tomorrowend   && $tomorrowLive['invalidDate'] >= $tomorrowstart)
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowstart);
                    $tomorrowLive['end'] =  date('H:i',$tomorrowLive['invalidDate']);
                }
                if($tomorrowLive['startDate'] >= $tomorrowstart  && $tomorrowLive['invalidDate'] >= $tomorrowend  &&  $tomorrowLive['startDate'] <= $tomorrowend )
                {
                    $tomorrowLive['begin'] = date('H:i',$tomorrowLive['startDate']);
                    $tomorrowLive['end'] =  date('H:i',$tomorrowend-1);
                }

            }
        }

        return $tomorrowLive;
    }




    protected function dayafttomorrow($liveType,$id){
        $where = array();
        $today = date('Ymd',time());
        $dayafttomorrowstart = intval(strtotime($today)+ 172800);
        $dayafttomorrowend = $dayafttomorrowstart + (24*60*60);
        if($liveType == 1){
            $where['is_del'] = 0;
            $where['live_id'] = $id;
            $where['is_active'] = 1;
            $where['_string'] = "(startDate > $dayafttomorrowstart  and startDate < $dayafttomorrowend)  OR  (invalidDate > $dayafttomorrowstart  and invalidDate < $dayafttomorrowend)  OR  (startDate < $dayafttomorrowstart and   invalidDate > $dayafttomorrowend ) ";
            $dayafttomorrowLive = M('zy_live_zshd')->where($where)->order('startDate asc')->find();
            if($dayafttomorrowLive){
                $dayafttomorrowLive['title'] = $dayafttomorrowLive['subject'];
                $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowLive['startDate']);
                $dayafttomorrowLive['end'] = date('H:i',$dayafttomorrowLive['invalidDate']);

                if($dayafttomorrowLive['startDate'] <= $dayafttomorrowstart  && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowend)
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowstart);
                    $dayafttomorrowLive['end'] = date('H:i',$dayafttomorrowend-1);
                }
                if($dayafttomorrowLive['startDate'] <= $dayafttomorrowstart  && $dayafttomorrowLive['invalidDate'] <= $dayafttomorrowend && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowstart)
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowstart);
                    $dayafttomorrowLive['end'] =  date('H:i',$dayafttomorrowLive['invalidDate']);
                }
                if($dayafttomorrowLive['startDate'] >= $dayafttomorrowstart  && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowend)
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowLive['startDate']);
                    $dayafttomorrowLive['end'] =  date('H:i',$dayafttomorrowend-1);
                }


            }
        } elseif($liveType == 3) {
            $where['is_del'] = 0;
            $where['is_active'] = 1;
            $dayafttomorrowstartgh = $dayafttomorrowstart*1000;
            $dayafttomorrowendgh = $dayafttomorrowend*1000;
            $where['_string'] = "(startDate > $dayafttomorrowstartgh  and startDate < $dayafttomorrowendgh)  OR  (invalidDate > $dayafttomorrowstartgh  and invalidDate < $dayafttomorrowendgh)   OR  (startDate < $todaystart and   invalidDate > $todayend ) ";
            $where['live_id'] = $id;
            $dayafttomorrowLive = M('zy_live_gh')->where($where)->order('startDate asc')->find();

            if($dayafttomorrowLive){
                $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowLive['startDate']/1000);
                $dayafttomorrowLive['end'] = date('H:i',$dayafttomorrowLive['invalidDate']/1000);

                if($dayafttomorrowLive['startDate'] <= $dayafttomorrowstartgh  && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowendgh)
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowstartgh);
                    $dayafttomorrowLive['end'] = date('H:i',$dayafttomorrowendgh-1);
                }

                if($dayafttomorrowLive['startDate'] <= $dayafttomorrowstartgh  && $dayafttomorrowLive['invalidDate'] <= $dayafttomorrowendgh  && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowstartgh )
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowstartgh);
                    $dayafttomorrowLive['end'] =  date('H:i',$dayafttomorrowLive['invalidDate']/1000);
                }
                if($dayafttomorrowLive['startDate'] >= $dayafttomorrowstartgh  && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowendgh   &&  $dayafttomorrowLive['startDate'] <= $dayafttomorrowendgh )
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowLive['startDate']/1000  &&  $dayafttomorrowLive['startDate'] <= $dayafttomorrowendgh );
                    $dayafttomorrowLive['end'] =  date('H:i',$todayend-1);
                }
            }
            $dayafttomorrowLive['startDate'] =  $dayafttomorrowLive['startDate']/1000;
            $dayafttomorrowLive['invalidDate'] =  $dayafttomorrowLive['invalidDate']/1000;
        }
        elseif($liveType == 4) {
            $where['is_del'] = 0;
            $where['is_active'] = 1;
            $where['_string'] = "(startDate > $dayafttomorrowstart  and startDate < $dayafttomorrowend)  OR  (invalidDate > $dayafttomorrowstart  and invalidDate < $dayafttomorrowend)   OR  (startDate < $dayafttomorrowstart and   invalidDate > $dayafttomorrowend )";
            $where['live_id'] = $id;
            $dayafttomorrowLive = M('zy_live_cc')->where($where)->order('startDate asc')->find();

            if($dayafttomorrowLive){
                $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowLive['startDate']);
                $dayafttomorrowLive['end'] = date('H:i',$dayafttomorrowLive['invalidDate']);
                if($dayafttomorrowLive['startDate'] <= $dayafttomorrowstart  && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowend)
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowstart);
                    $dayafttomorrowLive['end'] = date('H:i',$dayafttomorrowend-1);
                }
                if($dayafttomorrowLive['startDate'] <= $dayafttomorrowstart  && $dayafttomorrowLive['invalidDate'] <= $dayafttomorrowend   && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowstart)
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowstart);
                    $dayafttomorrowLive['end'] =  date('H:i',$dayafttomorrowLive['invalidDate']);
                }
                if($dayafttomorrowLive['startDate'] >= $dayafttomorrowstart  && $dayafttomorrowLive['invalidDate'] >= $dayafttomorrowend  &&  $dayafttomorrowLive['startDate'] <= $dayafttomorrowend )
                {
                    $dayafttomorrowLive['begin'] = date('H:i',$dayafttomorrowLive['startDate']);
                    $dayafttomorrowLive['end'] =  date('H:i',$dayafttomorrowend-1);
                }

            }
        }

        return $dayafttomorrowLive;
    }

    /**
     * 取得直播列表
     * @param boolean $return 是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getList($return = false) {
//         $map['beginTime'] = array( 'elt' , time() * 1000 );
//         $map['endTime']   = array( 'egt' , time() * 1000 );
        $cateId = intval ( $_GET ['cateId'] );
        if ( $cateId > 0) {
            $map['cate_id'] = array('like' , '%,'.$cateId.',%');
        }
        if($this->base_config['live_opt'] == 1) {
            $map['is_active'] = 1;
            $map['is_del'] = 0;
            $data = M('live')->order('live_id desc')->where($map)->findPage(12);
            if ($data ['data']) {
                foreach($data ['data'] as &$val){
                    if($val['startDate']  <= time() && $val['invalidDate']   >= time() ) {
                        $val['note'] = '正在直播 '.date('m-d H:i' , $val['startDate'] );
                    }

                    if($val['startDate']  > time()){
                        $val['note'] = '即将直播 '.date('m-d H:i'  , $val['startDate'] );
                    }

                    if($val['invalidDate']   < time()){
                        $val['note'] = '直播结束';
                    }
                    $val['id'] = $val['number'];
                    $val['title'] = $val['subject'];
                }
                $this->assign ( 'listData', $data ['data'] );
                $html = $this->fetch ( 'index_list' );
            } else {
                $html = '暂无直播课程';
            }
        }else if($this->base_config['live_opt'] == 2) {
            $html = '暂无直播课程';
        }else if($this->base_config['live_opt'] == 3) {
            $data = M('zy_live')->where ( $map )->order ( 'id desc' )->findPage ( 12 );
            if ($data ['data']) {
                foreach($data ['data'] as &$val){
                    if($val['beginTime'] / 1000 <= time() && $val['endTime']  / 1000 >= time() ) {
                        $val['note'] = '正在直播 '.date('m-d H:i' , $val['beginTime'] / 1000);
                    }

                    if($val['beginTime']  / 1000 > time()){
                        $val['note'] = '即将直播 '.date('m-d H:i'  , $val['beginTime'] / 1000);
                    }

                    if($val['endTime']  / 1000 < time()){
                        $val['note'] = '直播结束';
                    }
                }
                $this->assign ( 'listData', $data ['data'] );
                $html = $this->fetch ( 'index_list' );
            } else {
                $html = '暂无直播课程';
            }
        }

        $this->assign ( 'cateId', $cateId ); // 定义分类

        $data ['data'] = $html;
        $this->assign ( 'live_opt', $this->base_config['live_opt'] );

        if ($return) {
            return $data;
        } else {
            exit( json_encode ( $data ) );
        }
    }

    public function view() {
        $this->view_info();
        $this->display ();
    }

    private function view_info(){
        $id = intval ( $_GET ['id'] );

        $liveres = M('zy_video')->where('id ='.$id) ->field('video_title,video_binfo,listingtime,uctime,is_activity,is_del')-> find();

        //设置seo详情
        $this->seo['_title'] = $liveres['video_binfo'] ? $liveres['video_title'].' — '.$liveres['video_binfo'] : $liveres['video_title'].$this->seo['_bak_title'];
        $this->seo['_meta'] = $liveres['video_binfo'] ? : $this->seo['_meta'];

        $share_url = $this->addVideoShare($id,2);
		$code = t ( $_GET ['code'] );

        if($code){
			$code = explode('@@@', $code);
			$code = $code[0];

			$video_share = M('zy_video_share')->where(array('tmp_id' => $code))->field('uid,video_id,type,share_url')->find();
            if($video_share) {
                $data['type']       = $map['type'] = $video_share['type'];
                $data['uid']        = $map['uid'] = $video_share['uid'];
                $data['use_uid']    = $this->mid ? $this->mid : 0;
                $data['video_id']   = $map['video_id'] = $video_share['video_id'];
                $data['ctime']      = time();
                $data['share_url'] = $video_share['share_url'];
                $data['tmp_id'] = $map['tmp_id'] = session_id();
                if($video_share['uid'] != $this->mid) {
                    $result = M('zy_video_share_user')->where($map)->select();
                    if ($result) {
                        M('zy_video_share_user')->where($map)->save($data);
                    } else {
                        M('zy_video_share_user')->add($data);
                    }
                }
            }

            $mhm_id = M('user')->where('uid = '.$video_share['uid'])->getField('mhm_id');
            $this_mhm_id = M('school')->where(array('id'=>$mhm_id,'status'=>1,'is_del'=>0))->getField('id') ? : 1;
            $this->assign ('this_mhm_id', $this_mhm_id );
            unset($data);
            unset($map);
        }
        $maps['id'] = $id;
        $maps['is_del'] = 0;
        $maps['is_activity'] = 1;
        $maps['type'] = 2;
        $maps['listingtime'] = array('lt', time());
        $maps['uctime'] = array('gt', time());
        $data = D('ZyVideo')->where($maps)->find ();

        //$data['video_order_count'] = M('zy_order_live') -> where(array('live_id'=> $id, 'is_del' => 0 ,'pay_status'=>3)) -> count();

        if( $liveres['uctime'] < time()  )
        {
            $this->error ( '该直播已下架,请查证该直播下架时间' );
        }
        if(  $liveres['listingtime'] >  time())
        {
            $this->error ( '该直播未上架，请查证该直播上架时间' );
        }
        if(  $liveres['is_activity'] == 0)
        {
            $this->error ( '该直播未审核' );
        }
        if(  $liveres['is_del'] == 1)
        {
            $this->error ( '该直播已被删除' );
        }

        $isAdmin = model('UserGroup')->isAdmin($this->mid);
        if (! $data && !$isAdmin) {
            $this->assign ( 'isAdmin', 1 );
            $this->error ( '直播课程不存在!' );
        }
        //如果为管理员/机构管理员自己机构的课程 则免费
        $data['mzprice'] = getPrice($data , $this->mid , true , true);

        //众筹课程不允许其他人观看
        if(!empty($data['crow_id']) && !Model('UserGroup')->isAdmin($this->mid)){
            $isJoinCrow = M('crowdfunding_user')->where(array('uid'=>$this->mid,'cid'=>$data['crow_id']))->find();
            if(!$isJoinCrow){
                $this->assign ( 'isAdmin', 1 );
                $this->error ( '你未参加此课程众筹，不允许观看!' );
            }
        }
        //总课时
        $liveData = model('Live')->liveSpeed($data['live_type'],$data['id']);
        $data['sectionNum'] = $liveData['count'];

        //添加围观人数
        D( 'ZyVideo' )->where ( $maps )->setInc('view_nums');
        // 处理数据
        $data ['video_score'] = floor ( $data ['video_score'] / 20 ); // 四舍五入
        $data ['reviewCount'] =  D ('ZyReview','classroom')->getReviewCount ( 1, intval($data['id']) );
        $data ['reviewRate'] = D ( 'ZyReview' )->getCommentRate ( 1, intval ( $data ['id'] ) );
        $data_cate = array_filter(explode(',',$data['fullcategorypath']));
        foreach ($data_cate as $cate_k=> $cate){
            $data ['video_category_name'][$cate_k]['name'] = getCategoryName ( $cate );
            $data ['video_category_name'][$cate_k]['key'] = $cate;
        }
        $data ['iscollect'] = D ( 'ZyCollection','classroom' )->isCollect ( $data ['id'], 'zy_video', intval ( $this->mid ) );
        $data ['isSufficient'] = D ( 'ZyLearnc','classroom' )->isSufficient ( $this->mid, $data['t_price'] );
        $data ['isGetResource'] = isGetResource ( 1, $data ['id'], array (
            'video',
            'upload',
            'note',
            'question'
        ) );

        //讲师信息
        $data['user'] = M('zy_teacher')->where('id ='.$data['teacher_id'])->find();
        if($data['user']){
            $count = model('UserData')->getUserData($data['user']['uid']);
            //讲师等级
            $teacher_title = M('zy_teacher_title_category')->where('zy_teacher_title_category_id='.$data['user']['title'])->find();
            if($teacher_title['cover']){
                $data['user']['teacher_title_cover'] = getCover($teacher_title['cover'],19,19);
            }
            //当前讲师关注状态
            $fans_state = M('UserFollow')->where(array('uid' => $this->mid, 'tid' => $data['user']['id']))->find();
            if ($fans_state) {
                $state = 1;
            } else {
                $state = 0;
            }
            $data['user']['fans_state'] = $state;
        }

        // 课程标签
        $data['video_str_tag'] = array_chunk ( explode ( ',', $data ['str_tag'] ), 3, false );

        //课程菜单
        $live_menu = model('Live')->liveMenu($data['live_type'],$id);

        if($live_menu){
            $live_end = $live_menu['end'];
            $live_bef = $live_menu['bef'];

            $live_end['endCount'] = $live_menu['endCount'];
            $live_end['count'] = $live_menu['count'];
            unset($live_menu['end']);
            unset($live_menu['bef']);
            unset($live_menu['endCount']);
            unset($live_menu['count']);;
        }

        //相关课程
        $fullcategorypath = M('zy_video')->where('id ='.$id)->getField('fullcategorypath');

        $sameWhere['id']            = ['neq',$id];
        $sameWhere['is_del']        = 0;
        $sameWhere['is_activity']   = 1;
        $sameWhere['listingtime']   = array('lt', time());
        $sameWhere['uctime']        = array('gt', time());
        $sameWhere['fullcategorypath'] = ['like',"%,".array_filter(explode(',',$fullcategorypath))[1].",%"];

        $sameVideo = D('ZyVideo')->where ( $sameWhere )->order('ctime desc')->limit(2)->select();
        foreach ($sameVideo as &$val) {
             $val['mzprice'] = getPrice($val , $this->mid , true , true);
        }

        //机构信息
        $mhm_id = $data['mhm_id'];
        if($mhm_id){
            //机构信息
            $mhmData =  model('School')->getSchoolInfoById($mhm_id);
            //课程数
            $mhmData['count'] = $this->video->where(array('mhm_id'=>$mhm_id))->count();
            //机构学生数量
            $student = model('Follow')->where(array('fid' => $mhmData['uid']))->count();

            $user = model('User')->where('mhm_id='.$mhm_id)->field('uid')->findALL();
            $video = M('zy_order_course')->where('mhm_id='.$mhm_id)->field('uid')->findALL();
            foreach($video as $v){
                $v = implode(',',$v);
                $list[] = $v;
            }
            foreach($user as $v){
                $v = implode(',',$v);
                $new[] = $v;
            }
            $user_count = array_merge($list,$new);
            $user_count = count(array_unique($user_count)) ? : 1;

            $mhmData['student'] = $student+$user_count;
            //当前用户关注状态
            $mhmData['state']=model('Follow')->getFollowState($this->mid,$mhmData['uid']);
            //机构域名
            if($mhmData['doadmin']){
                $mhmData['domain'] = getDomain($mhmData['doadmin']);
            }else{
                $mhmData['domain'] = U('school/School/index',array('id'=>$mhmData['school_id']));
            }
        }

        $teacher_id = $data['teacher_id'];
        //老师的其他课程
        if($teacher_id){
            $otherWhere = array();
            $otherWhere['is_del'] = 0;
            $otherWhere['teacher_id'] = $teacher_id;
            $otherWhere['id'] = array('neq',$id);
            $otherVideo = D( 'ZyVideo' )->where($otherWhere)->limit(3)->select();
            foreach ($otherVideo as &$val) {
                 $val['mzprice'] = getPrice($val , $this->mid , true , true);
            }
        }

        //课程所有评论
        $live_review = D('ZyReview','classroom')->where(array('oid'=>$id,'type'=>1))->select();
        //筛选好评，中评，差评
        $reviews = array();
        $good = 0;
        $middle = 0;
        $bad = 0;
        foreach ($live_review as $ks=>$vs){
            if($vs['star'] >= 80){
                $good += 1;
            }elseif ($vs['star']>= 40 && $vs['star'] <= 60){
                $middle += 1;
            }else{
                $bad += 1;
            }
        }
        $reviews['good'] = $good;
        $reviews['middle'] = $middle;
        $reviews['bad'] = $bad;
        $url = U('live/Index/view',array('id'=>$id));;

        //是否购买
        $data['is_buy'] = D('ZyOrderLive','classroom')->isBuyLive($this->mid ,$id );
        $data['order_count'] = M('zy_order_live')->where('live_id='.$id)->count();

        if(empty($live_bef['beginTime']))
        {
            $live_bef['beginTime'] = $data['listingtime'];
        }
        if(empty($live_end['endTime']))
        {
            $live_end['endTime'] = $data['uctime'];
        }

        $enough =  0;
        if($data['maxmannums'] < $data['video_order_count'])
        {
            $enough = 1;
        }

        //猜你喜欢
        $guess_you_like = D('ZyGuessYouLike','classroom')->getGYLData(0,$this->mid,4);

        foreach ($guess_you_like as $key=> $val){
            $mhmName = model('School')->getSchoolInfoById($val['mhm_id']);
//            $datas[$key]['mhmName'] = $mhmName['title'];
            //教师头像和简介
            $teacher = M('zy_teacher')->where(array('id'=>$val['teacher_id']))->find();
            $guess_you_like[$key]['teacherInfo']['name'] = $teacher['name'];
            $guess_you_like[$key]['teacherInfo']['inro'] = $teacher['inro'];
            $guess_you_like[$key]['teacherInfo']['head_id'] = $teacher['head_id'];
            //直播课时
            if($val['type'] == 2){
                $live_data = model('Live')->liveSpeed($val['live_type'],$val['id']);
                $guess_you_like[$key]['live']['count'] = $live_data['count'];
                $guess_you_like[$key]['live']['now'] = $live_data['now'];
            }
        }

        $follow_count = model('Follow')->getFollowCount($data['user']['uid']);
        foreach ($follow_count as $k => &$v) {
            $follow = $v['follower'];
        }
        if (!$follow) {
            $follow = '0';
        }
        $data['tevl'] = round(($live_end['endTime'] - $live_bef['beginTime']) / 86400);

		$commentSwitch = model('Xdata')->get('admin_Config:commentSwitch');
		$switch = $commentSwitch['live_switch'];

        if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            //微信分享配置
            tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'core', 'OpenSociax', 'jssdk.php')));
            $weixin = model('Xdata')->get('admin_Config:weixin');
            $jssdk = new JSSDK($weixin['appid'], $weixin['appsecret']);
            $signPackage = $jssdk->GetSignPackage();

            $this->assign('is_wx',true);
            $this->assign('signPackage', $signPackage);
        }

        $this->assign('mid',$mid);
        $this->assign('guess_you_like',$guess_you_like);
        $this->assign ( 'enough', $enough );
        $this->assign ( 'lid', $id );
        $this->assign ( 'data', $data );
        $this->assign ( 'mhmData', $mhmData );
        $this->assign ( 'url', $url );
        $this->assign ( 'othervideo', $otherVideo );
        $this->assign ( 'samevideo', $sameVideo );
        $this->assign ( 'live_menu', $live_menu );
        $this->assign ( 'live_end', $live_end );
        $this->assign ( 'live_bef', $live_bef );
        $this->assign ( 'reviews', $reviews );
        $this->assign('follow',$follow);
		$this->assign('switch',$switch);
		$this->assign('share',1);
		$this->assign('share_url',$share_url);
    }

    public function view_mount() {
        $this->view_info();
        $id = intval($_GET ['id']);
        $mid = explode('L',t($_GET['mid']))[0];
        if($mid){
            $mount = M( 'zy_video_mount')->where (['vid'=>$id,'mhm_id'=>$mid])->getField('vid');
            if(!$mount){
                $this->error("出错啦。。");
            }
        }
        $chars = 'JMRZaNTU1bNOXcABIdFVWX2eSA9YhxKhxMmDEG3InYZfDEhxCFG5oPQjOP9QkKhxR9SsGIJtTU5giVqBCJrW29pEhx0MuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $mount_url_str = '';
        for ( $i = 0; $i < 4; $i++ ){
            $mount_url_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        $this->assign('mount_str',$mid.'H'.$mount_url_str);
        $this->display ('view');
    }

    /**
     * Eduline直播首页方法
     * @return void
     */
    public function watch() {
        //dd($this->user);
        $id = intval($_GET['id']);

        if(!$this->mid){
            $this->error('请先登录');
        }
        if (! $id) {
            $this->error ( '直播课程不存在' );
        }

        $info = M('zy_video')->where('id='.$id)->find();
        $teacher_uid = M('zy_teacher')->where(array('id'=>$info['teacher_id']))->getField('uid');
        if($teacher_uid){
            $this->assign('teacher_uid',$teacher_uid);
        }
        //$this->assign('teacher_uid',$info['teacher_uid']);
        // 是否已购买
        $is_buy = M('ZyOrderLive','classroom')->isBuyLive($this->mid,$id);

        $map['live_id'] = $id;
        $map['type']    = $info['live_type'];
        $map['startDate']   = array('elt' , time() );
        $map['invalidDate'] = array('egt' , time() );
        $res = model('Live')->liveRoom->where ( $map)->order('startDate ASC')->find();
        //获取直播地址
        $zhibo_url = model('Live')->liveRoom->where(array('live_id'=>$id))->getField('zhibo_url');
        //$sql = model('Live')->liveRoom->getLastSql();dd($zhibo_url);
        if( !$res && empty($zhibo_url)) {
            $this->error ( '直播未开始或已经结束' );
        }
        if( ($this->mid != $res['speaker_id']) && !is_admin($this->mid)){
            if($info['price'] > 0 && $is_buy <= 0){
                $this->error('请先购买');
            }
            if($res['startDate'] >= time()){
                $this->error ( '还未到直播时间' );
            }
            if($res['invalidDate'] <= time() && empty($zhibo_url)){
                $this->error ( '直播已经结束' );
            }
        }
        if( $info['live_type'] == 1) {//展示互动
            $unmae = getUserName($this->mid);

            $url = $res['studentJoinUrl']."?nickname=".$unmae."&token=".$res['studentToken'];
        } else if($info['live_type'] == 2) {//三芒
            $url = $this->getClass();
            $url = $url['url'].'?param='.$url['param'];
        } else if($info['live_type'] == 3) {//光慧
            $gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
            if ( $res['invalidDate'] / 1000 >= time() ) {
                $url = $gh_config['video_url'] . '/student/index.html?liveClassroomId='.$res['room_id'].'&customerType=taobao&customer=seition&sp=0';
            } else {//直播结束
                $url = $gh_config['video_url'] . '/playback/index.html?liveClassroomId='.$res['room_id'].'&customerType=taobao&customer=seition&sp=0';
            }
        } else if($info['live_type'] == 4){//cc
            $unmae = getUserName($this->mid);

            dump($unmae);
            dump($res['studentClientToken']);
            $url = "{$res['studentJoinUrl']}&autoLogin=true&viewername={$unmae}&viewertoken={$res['studentClientToken']}";
            dump($url);
        } else if ($info['live_type'] == 5) {//微吼
            $user_info = M('user')->where("uid={$this->mid}")->field('uname,email')->find();

            $user_info['email'] ?: $user_info['email'] = "eduline@eduline.com";
            $url = "{$res['studentJoinUrl']}?email={$user_info['email']}&name={$user_info['uname']}";
        } else if ($info['live_type'] == 6) {//cc小班课
            $user_info = M('user')->where("uid={$this->mid}")->field('uname,email')->find();

            $url = "{$res['studentJoinUrl']}&autoLogin=true&username={$user_info['uname']}&password={$res['studentClientToken']}";
        }
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
         //   model('WxPay')->getWxUserInfo($_GET['code'], SITE_URL . '/live/watch/'.$id.'.html');
        }
        if($this->is_wap && strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')){
            $this->assign('is_wx',true);
        }
        //分配直播地址
        $url = $zhibo_url ? $zhibo_url : $url;
        $this->assign('url' , $url);
        $this->display();
    }

    /**
     * 教师/助教加入直播课堂-展示互动
     */
    public function doLive_login(){
        $this->display();
    }

    /**
     * 教师/助教加入直播课堂-展示互动
     */
    public function live_teacher(){
        $id = intval($_GET['id']);

        if(!$this->mid){
            $this->error('请先登录');
        }
        if (! $id) {
            $this->error ( '直播课程不存在' );
        }

        $info = M('zy_video')->where('id='.$id)->find();

        // 是否已购买
        $is_buy = M('ZyOrderLive','classroom')->isBuyLive($this->mid,$id);

        $map['live_id'] = $id;
        $map['type']    = $info['live_type'];
        $map['startDate']   = array('elt' , time() );
        $map['invalidDate'] = array('egt' , time() );
        $res = model('Live')->liveRoom->where ( $map)->order('startDate ASC')->find();

        if( !$res ) {
            $this->error ( '直播未开始或已经结束' );
        }
        if( ($this->mid != $info['teacher_id']) && !is_admin($this->mid)){
            if($info['price'] > 0 && $is_buy <= 0){
                $this->error('请先购买');
            }
            if($res['startDate'] >= time()){
                $this->error ( '还未到直播时间' );
            }
            if($res['invalidDate'] <= time()){
                $this->error ( '直播已经结束' );
            }
        }

        $teacher_info = M('zy_teacher')->where('id ='.$info['teacher_id'])->field('id,name')->find();

        if( $info['live_type'] == 1) {//展示互动
            $teacherJoinUrl = $res['teacherJoinUrl']."?nickname=".$teacher_info['name']."&token=".$res['teacherToken'];
        } else if($info['live_type'] == 2) {//三芒
            $url = $this->getClass();
            $teacherJoinUrl = $url['url'].'?param='.$url['param'];
        } else if($info['live_type'] == 3) {//光慧
            $gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
            $teacherJoinUrl = $gh_config['video_url'].'/teacher/index.html?liveClassroomId='.$res['room_id'].'&customer='.$gh_config['customer'].'&customerType=taobao&sp=0';
        } else if($info['live_type'] == 4){//cc
            $teacherJoinUrl = "{$res['teacherJoinUrl']}&publishname={$teacher_info['name']}&publishpassword={$res['teacherToken']}";
        } else if($info['live_type'] == 5){//微吼
//            $teacher_info = M('zy_teacher')->where('id ='.$res['speaker_id'])->field('id,name')->find();
            $teacherJoinUrl = $res['teacherJoinUrl'];
        } else if($info['live_type'] == 6) {//cc小班课
            $teacherJoinUrl = "{$res['teacherJoinUrl']}&autoLogin=true&username={$teacher_info['name']}&password={$res['teacherToken']}";
        }

        $this->assign('teacherJoinUrl' , $teacherJoinUrl);
        $this->display();
    }

    public function getLivePlayback(){
        $this->assign('jumpUrl','/');

        $live_info = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->field('live_id,roomid,studentClientToken,playback_url,type')->find();
        $liveInfo = model('Live')->findLiveAInfo(array('id'=>$live_info['live_id']),'id,video_title');

        $this->assign($liveInfo);

        if($live_info['type'] == 1) {
            $list_url = $this->zshd_config['api_url'] . '/courseware/list?';

            $param = 'roomId=' . $live_info['roomid'];
            $hash = $param . '&loginName=' . $this->zshd_config['api_key'] . '&password=' . md5($this->zshd_config['api_pwd']) . '&sec=true';
            $list_url = $list_url . $hash;

            $list_live = getDataByUrl($list_url);

            if ($list_live['code'] == 0) {
                if (!$list_live['coursewares'][0]['url']) {
                    $this->assign('jumpUrl', '/');
                    $this->error("该直播课时还没有回放。。");
                }
                $playback_url = $list_live['coursewares'][0]['url'] . "?nickname=currency_playback&token={$list_live['coursewares'][0]['token']}";

                if (!$live_info['playback_url']) {
                    model('Live')->liveRoom->where('id=' . intval($_GET['id']))->save(['playback_url' => $playback_url]);
                }
            } else {
                $this->error("额 服务器查询失败了。。");
            }
        //$info_url   = $this->zshd_config['api_url'].'/training/courseware/info?';
        }else if($live_info['type'] == 3){

            $playback_url = $this->gh_config['video_url'].'/playback/index.html?liveClassroomId='.$live_info['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0';
            if(!$live_info['playback_url']){
                model('Live')->liveRoom->where('id='.intval($_GET['id']) )->save(['playback_url'=>$playback_url]);
            }
        }else if($live_info['type'] == 4){

            $info_url  = $this->cc_config['api_url'].'/live/info?';

            $if_map['roomid']            = urlencode($live_info['roomid']);
            $if_map['userid']            = urlencode($this->cc_config['user_id']);
            $info_url    = $info_url.createHashedQueryString($if_map)[1].'&time='.time().'&hash='.createHashedQueryString($if_map)[0];

            $info_res   = getDataByUrl($info_url);

            if($info_res['result'] == "OK"){
                $playback_url = $info_res['lives'][max(array_keys($info_res['lives']))]['replayUrl']."&viewername=currency_playback&autoLogin=true&viewertoken={$live_info['studentClientToken']}";
                if(!$info_res['lives'][max(array_keys($info_res['lives']))]['replayUrl']){
                    $this->error("该直播课时还没有回放。。");
                }
                if(!$live_info['playback_url']){
                    model('Live')->liveRoom->where('id='.intval($_GET['id']) )->save(['playback_url'=>$playback_url]);
                }

//            $pk_url  = $this->cc_config['api_url'].'/record/download?';
//
//            $pk_map['userid']            = urlencode($this->cc_config['user_id']);
//            $pk_map['liveids']           = $info_res['lives'][max(array_keys($info_res['lives']))]['id'];
//            $pk_url    = $pk_url.createHashedQueryString($pk_map)[1].'&time='.time().'&hash='.createHashedQueryString($pk_map)[0];
//
//
//            $pk_res   = getDataByUrl($pk_url);
//            dump($pk_res);
            }else{
                $this->error("额 服务器查询失败了。。");
            }
        }else if($live_info['type'] == 5){

            $info_url  = $this->wh_config['api_url'].'/api/vhallapi/v2/record/list';

            $pl_data['webinar_id'] = $live_info['roomid'];
            $pl_data['auth_type']  = $find_data['auth_type'] = 2;
            $pl_data['app_key']    = $find_data['app_key']   = t($this->wh_config['api_key']);
            $pl_data['signed_at']  = $find_data['signed_at'] = time();
            $pl_data['sign']       = createSignQueryString($pl_data);

            $info_res   = getDataByPostUrl($info_url,$pl_data);

            $playback_url = '';
            if($info_res->code == 200){
                $playback_data = $info_res->data->lists;
                foreach($playback_data as $key => $val){
                    if($val->is_default == 1){
                        $playback_url .= $val->url;
                    }
                }
                $playback_url  = $this->wh_config['api_url']."/webinar/inituser/{$live_info['roomid']}";

                if(!$live_info['playback_url']){
                    model('Live')->liveRoom->where('id='.intval($_GET['id']) )->save(['playback_url'=>$playback_url]);
                }
            }else if($info_res->code == 10019){
                $this->error("该直播课时还没有回放。。");
            }else{
                $this->error("额 服务器查询失败了。。");
            }
        }

        if(!$_GET['ac']){
            redirect($playback_url);
        }else{
            $this->assign('playback_url',$playback_url);
            $this->display('playback_watch');
        }
    }

	//将课程添加到购物车
	public  function  addVideoShare($nvid,$ntype){

		$vid     = $nvid;
		$type   = $ntype;

		if($this->mid){
			$map ['id']          = $vid;
			$map ['is_activity'] = 1;
			$map ['is_del']      = 0;
				$map ['type']    = 2;
			$video_id = D ( 'ZyVideo' )->where ( $map )->getField('id');



            $chars = 'JMRZaNTUbNOXcABIdFVWXeSAYlKLMmDEGInYZfDElCFGoPQjOPQkKLRSsGIJtTUgiVqBCJrWpELMuFKvPTUVwQRSxCDNOyBWXzAYZ';
            $share_str = $type."H";
            for ( $i = 0; $i < 4; $i++ ){
                $share_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
            }
            $share_str .= "H".$vid."H";
            for ( $i = 0; $i < 5; $i++ ){
                $share_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
            }

                $share_url = U('live/Index/view',array('id'=>$vid,'code'=>$share_str));

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
                $share_url = U('live/Index/view',array('id'=>$vid,'code'=>$share_str.'@@@'));
            }
		else{
				$share_url =  U('live/Index/view',array('id'=>$vid));
			}

		return $share_url;
	}


}

