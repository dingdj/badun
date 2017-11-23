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
                    t_price,vip_level,is_best,listingtime,limit_discount,uid,type,live_type,str_tag,view_nums")->limit($perfect_size)->select();
        foreach ($perfect as $knd => $vnd) {
            //如果为管理员/机构管理员自己机构的课程 则免费
            if(is_admin($this->mid) || $vnd['is_charge'] == 1) {
                $perfect[$knd]['t_price'] = 0;
            }
            if($vnd['mhm_id'] && is_school($this->mid) == $vnd['mhm_id']){
                $perfect[$knd]['t_price'] = 0;
            }

            $video_section_status = '';
            $video_section_end_num = 0;
            if ($vnd['live_type'] == 1) {
                $live_info = M('zy_live_zshd')->where(array('live_id' => $vnd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,startDate,invalidDate')->select();
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
            } elseif ($vnd['live_type'] == 3) {
                $live_info = M('zy_live_gh')->where(array('live_id' => $vnd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,beginTime,endTime')->select();
                foreach ($live_info as $live3 => $gh) {
                    if ($gh['beginTime'] / 1000 < time() && $gh['endTime'] / 1000 > time()) {
                        $video_section_status = '直播中';
                    } elseif ($gh['endTime'] / 1000 <= time()) {
                        $video_section_status = '已结束';
                    } elseif ($gh['beginTime'] / 1000 >= time()) {
                        $video_section_status = '未开始';
                    }
                    if ($gh['endTime'] / 1000 < time()) {
                        $video_section_end_num += 1;
                    }

                }

            } elseif ($vnd['live_type'] == 4) {
                $live_info = M('zy_live_cc')->where(array('live_id' => $vnd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,beginTime,endTime')->select();
                foreach ($live_info as $live3 => $gh) {
                    if ($gh['startDate'] < time() && $gh['endTime'] > time()) {
                        $video_section_status = '直播中';
                    } elseif ($gh['invalidDate'] <= time()) {
                        $video_section_status = '已结束';
                    } elseif ($gh['startDate'] >= time()) {
                        $video_section_status = '未开始';
                    }

                    if ($gh['invalidDate'] < time()) {
                        $video_section_end_num += 1;
                    }

                }
            } elseif ($vnd['live_type'] == 5) {
                $live_info = M('zy_live_thirdparty')->where(array('live_id' => $vnd['id'], 'is_active' => 1, 'is_del' => 0,'type'=>5))->field('id,startDate,invalidDate')->select();
                foreach ($live_info as $live3 => $wh) {
                    if ($wh['startDate'] < time() && $wh['invalidDate'] > time()) {
                        $video_section_status = '直播中';
                    } elseif ($gh['invalidDate'] <= time()) {
                        $video_section_status = '已结束';
                    } elseif ($gh['startDate'] >= time()) {
                        $video_section_status = '未开始';
                    }

                    if ($gh['invalidDate'] < time()) {
                        $video_section_end_num += 1;
                    }
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
                //如果为管理员/机构管理员自己机构的课程 则免费
                if(is_admin($this->mid) || $vnd['is_charge'] == 1) {
                    $perfect[$knd]['t_price'] = 0;
                }
                if($vnd['mhm_id'] && is_school($this->mid) == $vnd['mhm_id']){
                    $perfect[$knd]['t_price'] = 0;
                }
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

		$share_url = $this->addVideoShare($id,2);
		$code = t ( $_GET ['code'] );

        M('zy_video') ->where('id ='.$id) ->setInc('view_nums');

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

        $data['video_order_count'] = M('zy_order_live') -> where(array('live_id'=> $id, 'is_del' => 0 ,'pay_status'=>3)) -> count();

        $liveres = M('zy_video')->where('id ='.$id) ->field('listingtime,uctime,is_activity,is_del')-> find();


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
        if(is_admin($this->mid) || $data['is_charge'] == 1) {
            $data['t_price'] = 0;
        }
        if(is_school($this->mid) == $data['mhm_id']){
            $data['t_price'] = 0;
        }

        //如果是讲师自己的课程 则免费
        $mid = $this -> mid;
        $tid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
        if($mid == intval($data['uid']) || $tid == $data['teacher_id'])
        {
            $data['t_price'] = 0;
        }


        if($data['live_type']  ==1)
        {
            $livetall =M('zy_live_zshd') -> where(array('live_id'=>$data['id'],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select();
        }
        if($data['live_type']  ==3)
        {
            $livetall =M('zy_live_gh') -> where(array('live_id'=>$data['id'],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select();
        }
        if($data['live_type']  ==4)
        {
            $livetall =M('zy_live_cc') -> where(array('live_id'=>$data['id'],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select();
        }
        if($data['live_type']  ==5)
        {
            $livetall =M('zy_live_thirdparty') -> where(array('live_id'=>$data['id'],'is_del'=> 0,'is_active'=>1,'type'=>5))-> field('speaker_id') ->select();
        }
        if($tid) {
            $tids = trim(implode(',', array_unique(getSubByKey($livetall, 'speaker_id'))), ',');
            $tids = "," . $tids . ',';

            $chtid = ',' . $tid . ',';

            if (strstr($tids, $chtid)) {
                $data['t_price'] = 0;
            }
        }

        $data['old_price'] =  $data['t_price'];
        if($data['is_tlimit']==1 && $data['starttime'] < time() && $data['endtime'] > time() ){
            $data['is_tlimit']=1;
        }else{
            $data['is_tlimit']=0;
        }
        //众筹课程不允许其他人观看
        if(!empty($data['crow_id']) && !Model('UserGroup')->isAdmin($this->mid)){
            $isJoinCrow = M('crowdfunding_user')->where(array('uid'=>$this->mid,'cid'=>$data['crow_id']))->find();
            if(!$isJoinCrow){
                $this->assign ( 'isAdmin', 1 );
                $this->error ( '你未参加此课程众筹，不允许观看!' );
            }
        }
        //总课时
        if($data['type'] == 1){
            $map = array();
            $map['vid'] = $data['id'];
            $map['pid'] = array('neq',0);
            $count = M('zy_video_section')->where($map)->count();
            if($count <= 0){
                $count = 1;
            }
            $data['sectionNum'] = $count;
        }elseif($data['type'] == 2){
            if($data['live_type'] == 1){
                $liveData = $this->live_data(1,$data['id']);
                $data['sectionNum'] = $liveData['count'];
            }elseif ($data['live_type'] == 3){
                $liveData = $this->live_data(3,$data['id']);
                $data['sectionNum'] = $liveData['count'];
            }elseif ($data['live_type'] == 4){
                $liveData = $this->live_data(4,$data['id']);
                $data['sectionNum'] = $liveData['count'];
            }elseif ($data['live_type'] == 5){
                $liveData = $this->live_data(5,$data['id']);
                $data['sectionNum'] = $liveData['count'];
            }
        }

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
        $teacher_id = $this->teacher($data['live_type'],$id);

        if($teacher_id){
            $data['user'] = M("zy_teacher")->where("id=".$teacher_id)->find();
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
        $live_menu = $this->live_menu($data['live_type'],$id);

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
        $sameWhere = array();
        $sameWhere['is_del'] = 0;
        $sameWhere['type'] = 2;
        $sameWhere['id'] = array('neq',$id);
        $category = M('zy_video')->where('id ='.$id)->getField('video_category');
        $sameWhere['uctime'] = array('GT',time());
        $sameWhere['video_category'] =$category;
        if(count($data['video_str_tag']['0']) > 1){
            $sameWhere['str_tag'] = array(array('LIKE',$data['video_str_tag']['0']['0']),array('LIKE',$data['video_str_tag']['0']['1']), 'or') ;
        }elseif(count($data['video_str_tag']['0']) == 1){
            $sameWhere['str_tag'] = array('LIKE',$data['video_str_tag']['0']['0']);
        }
        $sameVideo = D( 'ZyVideo' )->where ( $sameWhere )->order('ctime desc')->limit(3)->select();
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
        //老师的其他课程
        if($teacher_id){
            $otherWhere = array();
            $otherWhere['is_del'] = 0;
            $otherWhere['teacher_id'] = $teacher_id;
            $otherWhere['id'] = array('neq',$id);
            $otherVideo = D( 'ZyVideo' )->where($otherWhere)->limit(3)->select();
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
        //账号余额
        $data['balance'] = D("zyLearnc" ,'classroom' )->getUser($this->mid);
        // 是否已购买
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
        $guess_you_like = D('ZyGuessYouLike','classroom')->getGYLData(0,$this->mid,5);

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
                $live_data = $this->live_data($val['live_type'],$val['id']);
                $guess_you_like[$key]['live']['count'] = $live_data['count'];
                $guess_you_like[$key]['live']['now'] = $live_data['now'];
            }
        }

        $mid = $this -> mid;
        $tid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
        if($mid == intval($data['uid']) || $tid == $data['teacher_id'] )
        {
            $this -> assign('is_free',1);
            $data['t_price'] = 0;
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
    //直播主讲教师id
    protected function teacher($live_type,$id)
    {
        if($live_type == 1){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_zshd')->where($map)->order('startDate asc')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['invalidDate']=array('gt',time());
                $live_data = M('zy_live_zshd')->where($maps)->order('invalidDate asc')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        }elseif ($live_type == 3){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_gh')->where($map)->order('startDate asc')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['invalidDate']=array('gt',time());
                $live_data = M('zy_live_gh')->where($maps)->order('invalidDate asc')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        } elseif ($live_type == 4){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_cc')->where($map)->order('startDate asc')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['invalidDate']=array('gt',time());
                $live_data = M('zy_live_cc')->where($maps)->order('invalidDate asc')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        } elseif ($live_type == 5){
            $map = array();
            $map['live_id'] = $id;
            $map['is_del']  = 0;
            $map['type']    = 5;
            $map['startDate'] = array('gt',time());
            $live_data = M('zy_live_thirdparty')->where($map)->order('startDate asc')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['invalidDate']=array('gt',time());
                $live_data = M('zy_live_thirdparty')->where($maps)->order('invalidDate asc')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        }
        return $speaker_id;
    }
    //课程目录
    protected function live_menu($live_type,$id)
    {
        $is_buy = D('ZyOrderLive','classroom')->isBuyLive($this->mid ,$id );

        if($live_type == 1){
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['is_active']=1;
            $end_count = 0;
            $live_data = M('zy_live_zshd')->where($map)->order('invalidDate asc')->select();

            if($live_data){
                foreach ($live_data as $key=>$val){
                    if($val['startDate'] > time()){
                        $note_time = $val['startDate']-time();
                        $note_time_str = secondsToHour($note_time,1);
                        $live_data[$key]['count_down'] = 1;
                        $live_data[$key]['note'] = "<p style='height: 20px;color: #9d9e9e;'>距开课还剩：</p><p id='countDown_{$val['id']}' data-time='{$note_time}'
                                style='height: 40px;color: #fc7272;' >{$note_time_str}</p>";
                        $live_data[$key]['notestate'] = 0;
                    }elseif ($val['invalidDate'] > time() && $val['startDate'] < time()){
                        $live_data[$key]['note'] = '正在直播';
                        $live_data[$key]['notestate'] = 1;
                    } elseif ($val['invalidDate'] < time()){
                        if($is_buy){
                            $live_data[$key]['note'] = "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>1,'ac'=>'in'])."' title='观看回放'>观看回放</a>";
                        }else{
                            $live_data[$key]['note'] = '已结束';
                        }
                        $end_count += 1;
                        $live_data[$key]['notestate'] = 1;
                    }
                    $live_data[$key]['endTime'] = $live_data[$key]['invalidDate'];
                    $live_data[$key]['beginTime'] = $live_data[$key]['startDate'];
                    $live_data[$key]['title'] = $live_data[$key]['subject'];
                }
                $liveCount = count($live_data);
                $end = $liveCount - 1;
                $live_data['end'] = $live_data[$end];
                $live_data['bef'] = $live_data['0'];
                $live_data['count'] = $liveCount;
                $live_data['endCount'] = M('zy_live_zshd')->where($map)->count();
            }
        }else if ($live_type == 3){
            $end_count = 0;
            $maps['live_id']=$id;
            $maps['is_del']=0;
            $maps['is_active']=1;
            $live_data = M('zy_live_gh')->where($maps)->order('invalidDate asc')->select();

            if($live_data){
                foreach ($live_data as $key=>$val){
                    if($val['startDate']/1000 > time()){
                        $note_time = $val['startDate']-time();
                        $note_time_str = secondsToHour($note_time,1);
                        $live_data[$key]['count_down'] = 1;
                        $live_data[$key]['note'] = "<p style='height: 20px;color: #9d9e9e;'>距开课还剩：</p><p id='countDown_{$val['id']}' data-time='{$note_time}'
                                style='height: 40px;color: #fc7272;' >{$note_time_str}</p>";
                        $live_data[$key]['notestate'] = 0;
                    }elseif($val['invalidDate']/1000 > time() && $val['startDate']/1000 < time()){
                        $live_data[$key]['note'] = '正在直播';

                        $live_data[$key]['notestate'] = 1;
                    }elseif ($val['invalidDate']/1000 < time()){
                        if($is_buy){
                            $live_data[$key]['note'] = "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>3,'ac'=>'in'])."' title='观看回放'>观看回放</a>";
                        }else{
                            $live_data[$key]['note'] = '已结束';
                        }
                        $live_data[$key]['notestate'] = 1;
                        $end_count =  $end_count+ 1;
                    }

                    $live_data[$key]['invalidDate'] = substr($live_data[$key]['invalidDate'],0,10);
                    $live_data[$key]['startDate'] = substr($live_data[$key]['startDate'],0,10);
                }
                $liveCount = count($live_data);
                $end = $liveCount - 1;
                $live_data['end'] = $live_data[$end];
                $live_data['bef'] = $live_data['0'];
                $live_data['count'] = $liveCount;
                $live_data['endCount'] = $end_count;
            }
        }else if($live_type == 4){

            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['is_active']=1;
            $end_count = 0;
            $live_data = M('zy_live_cc')->where($map)->order('invalidDate asc')->select();

            if($live_data){

                foreach ($live_data as $key=>$val){
                    if(intval($val['startDate']) > time()){
                        $note_time = $val['startDate']-time();
                        $note_time_str = secondsToHour($note_time,1);
                        $live_data[$key]['count_down'] = 1;
                        $live_data[$key]['note'] = "<p style='height: 20px;color: #9d9e9e;'>距开课还剩：</p><p id='countDown_{$val['id']}' data-time='{$note_time}'
                                style='height: 40px;color: #fc7272;' >{$note_time_str}</p>";
                        $live_data[$key]['notestate'] = 0;
                    }elseif (intval($val['invalidDate']) > time() && intval($val['startDate']) < time()){
                        $live_data[$key]['note'] = '正在直播';
                        $live_data[$key]['notestate'] = 1;
                    } elseif (intval($val['invalidDate']) < time()){
                        if($is_buy){
                            $live_data[$key]['note'] = "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>4,'ac'=>'in'])."' title='观看回放'>观看回放</a>";
                        }else{
                            $live_data[$key]['note'] = '已结束';
                        }
                        $end_count += 1;
                        $live_data[$key]['notestate'] = 1;
                    }

                    $live_data[$key]['endTime'] = $live_data[$key]['invalidDate'];
                    $live_data[$key]['beginTime'] = $live_data[$key]['startDate'];
                    $live_data[$key]['title'] = $live_data[$key]['subject'];
                }

                $liveCount = count($live_data);
                $end = $liveCount - 1;
                $live_data['end'] = $live_data[$end];
                $live_data['bef'] = $live_data['0'];
                $live_data['count'] = $liveCount;
                $live_data['endCount'] = M('zy_live_cc')->where($map)->count();
            }
        }else if($live_type == 5){

            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['is_active']=1;
            $map['type']=5;
            $end_count = 0;
            $live_data = M('zy_live_thirdparty')->where($map)->order('invalidDate asc')->select();

            if($live_data){

                foreach ($live_data as $key=>$val){
                    if(intval($val['startDate']) > time()){
                        $note_time = $val['startDate']-time();
                        $note_time_str = secondsToHour($note_time,1);
                        $live_data[$key]['count_down'] = 1;
                        $live_data[$key]['note'] = "<p style='height: 20px;color: #9d9e9e;'>距开课还剩：</p><p id='countDown_{$val['id']}' data-time='{$note_time}'
                                style='height: 40px;color: #fc7272;' >{$note_time_str}</p>";
                        $live_data[$key]['notestate'] = 0;
                    }elseif (intval($val['invalidDate']) > time() && intval($val['startDate']) < time()){
                        $live_data[$key]['note'] = '正在直播';
                        $live_data[$key]['notestate'] = 1;
                    } elseif (intval($val['invalidDate']) < time()){
                        if($is_buy){
                            $live_data[$key]['note'] = "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>5,'ac'=>'in'])."' title='观看回放'>观看回放</a>";
                        }else{
                            $live_data[$key]['note'] = '已结束';
                        }
                        $end_count += 1;
                        $live_data[$key]['notestate'] = 1;
                    }

                    $live_data[$key]['endTime'] = $live_data[$key]['invalidDate'];
                    $live_data[$key]['beginTime'] = $live_data[$key]['startDate'];
                    $live_data[$key]['title'] = $live_data[$key]['subject'];
                }

                $liveCount = count($live_data);
                $end = $liveCount - 1;
                $live_data['end'] = $live_data[$end];
                $live_data['bef'] = $live_data['0'];
                $live_data['count'] = $liveCount;
                $live_data['endCount'] = M('zy_live_thirdparty')->where($map)->count();
            }
        }

        return $live_data;
    }
    /**
     * Eduline直播首页方法
     * @return void
     */
    public function watch() {
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

        if( $info['live_type'] == 1) {//展示互动
            $map['live_id'] = $id;
            $map['startDate']   = array('elt' , time() );
            $map['invalidDate'] = array('egt' , time() );
            $res = M( 'zy_live_zshd' )->where ( $map)->order('startDate ASC')->find();
            $unmae = getUserName($this->mid);
            if( !$res ) {
                $this->error ( '直播未开始或已经结束' );
            }
            if( ($this->mid != $res['speaker_id']) && !is_admin($this->mid)){
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
            $url = $res['studentJoinUrl']."?nickname=".$unmae."&token=".$res['studentToken'];
        } else if($info['live_type'] == 2) {//三芒
            $url = $this->getClass();
            $url = $url['url'].'?param='.$url['param'];
        } else if($info['live_type'] == 3) {//光慧
            $map['live_id'] = $id;
            $map['startDate'] = array('elt' , time()*1000 );
            $map['invalidDate']   = array('egt' , time()*1000 );
            $res = M('zy_live_gh')->where($map)->order('startDate ASC')->find();
            if( ($this->mid != $res['speaker_id']) && !is_admin($this->mid)){
                if($info['price'] > 0 && $is_buy <= 0){
                    $this->error('请先购买');
                }
                if($res['startDate'] / 1000 >= time()){
                    $this->error ( '还未到直播时间' );
                }
                if($res['invalidDate'] / 1000 <= time()){
                    $this->error ( '直播已经结束' );
                }
            }

            $gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
            if ( $res['invalidDate'] / 1000 >= time() ) {
                $url = $gh_config['video_url'] . '/student/index.html?liveClassroomId='.$res['room_id'].'&customerType=taobao&customer=seition&sp=0';
            } else {//直播结束
                $url = $gh_config['video_url'] . '/playback/index.html?liveClassroomId='.$res['room_id'].'&customerType=taobao&customer=seition&sp=0';
            }
        } else if($info['live_type'] == 4){//cc
            $map['live_id'] = $id;
            $map['startDate']   = array('elt' , time() );
            $map['invalidDate'] = array('egt' , time() );
            $res = M( 'zy_live_cc' )->where ( $map)->order('startDate ASC')->find();
            $unmae = getUserName($this->mid);
            if( !$res ) {
                //$this->error ( '直播未开始或已经结束' );
            }
            if( ($this->mid != $res['speaker_id']) && !is_admin($this->mid)){
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
            $url = "{$res['studentJoinUrl']}&autoLogin=true&viewername={$unmae}&viewertoken={$res['studentClientToken']}";
        } else if ($info['live_type'] == 5) {//微吼
            $map['live_id'] = $id;
            $map['startDate']   = array('elt' , time() );
            $map['invalidDate'] = array('egt' , time() );
            $map['type'] = 5;
            $res = M('zy_live_thirdparty')->where($map)->order('startDate ASC')->find();
            $user_info = M('user')->where("uid={$this->mid}")->field('uname,email')->find();
            if (!$res) {
                //$this->error('直播未开始或已经结束');
            }
            if (($this->mid != $res['speaker_id']) && !is_admin($this->mid)) {
                if ($info['price'] > 0 && $is_buy <= 0) {
                    $this->error('请先购买');
                }
                if ($res['startDate'] >= time()) {
                    $this->error('还未到直播时间');
                }
                if ($res['invalidDate'] <= time()) {
                    $this->error('直播已经结束');
                }
            }
            $user_info['email'] ?: $user_info['email'] = "eduline@eduline.com";
            $url = "{$res['studentJoinUrl']}?email={$user_info['email']}&name={$user_info['uname']}";
        }
        $url = M('zy_live_cc')->where(array('live_id'=>$id))->getField('url');
        $this->assign('url' , $url);
        $this->display();
    }


    //获取课程详情-展视互动
    function getInfo(){
        $url = 'http://cdsx.gensee.com/integration/site/training/room/info?roomId=88834851&sec=123123&loginName=link@hao.com&password=123456';
        $res = getDataByUrl($url);

    }

    //判断当前直播
    private function getLiveType(){
        $res = model('Xdata')->get('live_AdminConfig:baseConfig');
        return intval( $res['live_opt'] );
    }

    // 购买直播
    public function buyOperating() {
        if ( !$this->mid ) {
            $this->mzError ( '请先登录!' );
        }
        $id = intval ( $_POST ['id'] );
        //取得课程
        if($this->base_config['live_opt'] == 1) {
            $map['number'] = $id;
            $map['is_del'] = 0;
            $map['is_active'] = 1;
            $res = M( 'live' )->where ( $map )->find ();
            if($res['invalidDate'] < time()){
                $this->mzError ( '直播已经结束!' );
            }
            $res['title'] = $res['subject'];
        }else if($this->base_config['live_opt'] == 2) {
            $res = false;
        }else if($this->base_config['live_opt'] == 3) {
            $res = D('zy_live')->where('id='.$id)->find();
        }

        //找不到直播课程
        if ( !$res ) {
            $this->mzError ( '找不到直播课程' );
        }

        if($this->base_config['live_opt'] == 1) {
            if ( $res['invalidDate'] < time() ) {
                $this->mzError ( '直播已结束' );
            }
            $tid = M('ZyTeacher')->where("uid=".$this->mid)->getField('id');
            $live_info = M('live')->where('number='.$id)->order('live_id desc')->find();
            if($tid == $live_info['speaker']){
                $this->mzError ( '您是此直播课堂的老师，无需购买' );
            }
        }else if($this->base_config['live_opt'] == 2) {
            $this->mzError ( '直播已结束' );
        }else if($this->base_config['live_opt'] == 3) {
            if ( $res['endTime'] / 1000 < time() ) {
                $this->mzError ( '直播已结束' );
            }
        }

        $learnc = D('ZyLearnc' , 'classroom' );
        if (!$learnc->consume($this->mid ,$res['price'])) {
            $this->mzError ( '余额不足!' ); //余额扣除失败，可能原因是余额不足
        }
        //订单数据
        $data = array(
                'uid'     => $this->mid,
                'live_id' => $id,
                'price'   => $res['price'],
                'ctime'   => time(),
        );
        $id = M('zy_order_live')->add($data);
        if ( !$id ) {
            $this->mzError ( '购买失败!' );
        }
        //添加流水记录
        $learnc->addFlow($this->mid, 0, $res['price'], '购买直播课程<'.$res['title'].'>', $id, 'zy_order_live');
        // 记录购买的直播课程的ID
        session ( 'mzbugvideoid', $id );
        $this->mzSuccess ( '购买成功', 'selfhref' );
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


        if( $info['live_type'] == 1) {//展示互动
            $map['live_id'] = $id;
            $map['startDate']   = array('elt' , time() );
            $map['invalidDate'] = array('egt' , time() );
            $res = M( 'zy_live_zshd' )->where ( $map)->order('startDate ASC')->find();
            if( !$res ) {
                $this->error ( '直播未开始或已经结束' );
            }
            if( ($this->mid != $res['speaker_id']) && !is_admin($this->mid)){
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
            $teacher_info = M('zy_teacher')->where('id ='.$res['speaker_id'])->field('id,name')->find();
            $teacherJoinUrl = $res['teacherJoinUrl']."?nickname=".$teacher_info['name']."&token=".$res['teacherToken'];
        } else if($info['live_type'] == 2) {//三芒
            $url = $this->getClass();
            $teacherJoinUrl = $url['url'].'?param='.$url['param'];
        } else if($info['live_type'] == 3) {//光慧
            $map['live_id'] = $id;
            $map['startDate'] = array('elt' , time()*1000 );
            $map['invalidDate']   = array('egt' , time()*1000 );
            $res = M('zy_live_gh')->where($map)->order('startDate ASC')->find();

            if( ($this->mid != $res['speaker_id']) && !is_admin($this->mid)){
                if($info['price'] > 0 && $is_buy <= 0){
                    $this->error('请先购买');
                }
                if($res['startDate'] / 1000 >= time()){
                    $this->error ( '还未到直播时间' );
                }
                if($res['invalidDate'] / 1000 <= time()){

                    $this->error ( '直播已经结束' );
                }
            }

            $gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
            $teacherJoinUrl = $gh_config['video_url'].'/teacher/index.html?liveClassroomId='.$res['room_id'].'&customer='.$gh_config['customer'].'&customerType=taobao&sp=0';
        } else if($info['live_type'] == 4){//cc
            $map['live_id'] = $id;
            $map['startDate']   = array('elt' , time() );
            $map['invalidDate'] = array('egt' , time() );
            $res = M( 'zy_live_cc' )->where ( $map)->order('startDate ASC')->find();
            if( !$res ) {
                $this->error ( '直播未开始或已经结束' );
            }
            if( ($this->mid != $res['speaker_id']) && !is_admin($this->mid)){
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
            $teacher_info = M('zy_teacher')->where('id ='.$res['speaker_id'])->field('id,name')->find();
            $teacherJoinUrl = "{$res['teacherJoinUrl']}&publishname={$teacher_info['name']}&publishpassword={$res['teacherToken']}";
        } else if($info['live_type'] == 5){//微吼
            $map['live_id'] = $id;
            $map['type'] = 5;
            $map['startDate']   = array('elt' , time() );
            $map['invalidDate'] = array('egt' , time() );
            $res = M( 'zy_live_thirdparty' )->where ( $map)->order('startDate ASC')->find();
            if( !$res ) {
                $this->error ( '直播未开始或已经结束' );
            }
            if( ($this->mid != $res['speaker_id']) && !is_admin($this->mid)){
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
//            $teacher_info = M('zy_teacher')->where('id ='.$res['speaker_id'])->field('id,name')->find();
            $teacherJoinUrl = $res['teacherJoinUrl'];
        }

        $this->assign('teacherJoinUrl' , $teacherJoinUrl);
        $this->display();
    }

    //直播数据处理
    protected function live_data($live_type,$id)
    {
        $count = 0;
        //第三方直播类型
        if($live_type == 1){
            $live_data = M('zy_live_zshd')->where(array('live_id'=>$id,'is_del'=>0))->order('invalidDate asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['invalidDate'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else {
                $live_data = array(1);
                $count = 1;
            }
        }elseif ($live_type == 3) {
            $live_data = M('zy_live_gh')->where(array('live_id' => $id, 'is_del' => 0))->order('invalidDate asc')->select();
            if ($live_data) {
                foreach ($live_data as $item => $value) {
                    if ($value['invalidDate'] < time()) {
                        $count = $count + 1;
                    }
                }
            } else {
                $live_data = array(1);
                $count = 1;
            }
        } elseif ($live_type == 4){
            $live_data = M('zy_live_cc')->where(array('live_id'=>$id,'is_del'=>0))->order('invalidDate asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['invalidDate'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else{
                $live_data = array(1);
                $count = 1;
            }
        } elseif ($live_type == 5){
            $live_data = M('zy_live_thirdparty')->where(array('live_id'=>$id,'is_del'=>0,'type'=>$live_type))->order('invalidDate asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['invalidDate'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else{
                $live_data = array(1);
                $count = 1;
            }
        }
        $live_data_info['count'] = count($live_data);
        $live_data_info['now'] = $count;

        return $live_data_info;
    }

    public function getLivePlayback(){
        $type = $_GET['type'];
        $this->assign('jumpUrl','/');
        if($type == 1) {
            $live_info = M('zy_live_zshd')->where('id=' . intval($_GET['id']))->field('roomid,studentToken,playback_url')->find();

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
                    M('zy_live_thirdparty')->where('id=' . intval($_GET['id']))->save(['playback_url' => $playback_url]);
                }
            } else {
                $this->error("额 服务器查询失败了。。");
            }
//        $info_url   = $this->zshd_config['api_url'].'/training/courseware/info?';
        }else if($type == 3){
            $live_info = M('zy_live_gh')->where('id='.intval($_GET['id']) )->field('room_id')->find();

            $playback_url = $this->gh_config['video_url'].'/playback/index.html?liveClassroomId='.$live_info['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0';
            if(!$live_info['playback_url']){
                M('zy_live_thirdparty')->where('id='.intval($_GET['id']) )->save(['playback_url'=>$playback_url]);
            }
        }else if($type == 4){
            $live_info = M('zy_live_cc')->where('id='.intval($_GET['id']) )->field('roomid,studentClientToken,playback_url')->find();

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
                    M('zy_live_thirdparty')->where('id='.intval($_GET['id']) )->save(['playback_url'=>$playback_url]);
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
        }else if($type == 5){

            $info_url  = $this->wh_config['api_url'].'/api/vhallapi/v2/record/list';
            $live_info = M('zy_live_thirdparty')->where('id='.intval($_GET['id']) )->field('roomid')->find();

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

                if(!$live_info['playback_url']){
                    M('zy_live_thirdparty')->where('id='.intval($_GET['id']) )->save(['playback_url'=>$playback_url]);
                }
            }else if($info_res->code == 10019){
                $this->error("该直播课时还没有回放。。");
            }else{
                $this->error("额 服务器查询失败了。。");
            }
            $this->assign('header',5);
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

