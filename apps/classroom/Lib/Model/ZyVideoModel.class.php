<?php
/**
 * 课程模型 - 数据对象模型
 * @author wayne <idafoo@sina.com> 
 * @version TS3.0
 */
class ZyVideoModel extends Model
{
    var $tableName = 'zy_video';
    protected $error = '';
    public $mid = 0;

    /**
     * 获取课程列表
     * @param $limit 记录数量
     * @param $is_activity 课程是否通过审核
     */
    public function getVideosList($limit = 20, $is_activity = 1, $is_del = 0,$map = array())
    {
        $map['is_del'] = $is_del; //搜索非隐藏内容
        $map['is_activity'] = $is_activity;
        $map['type'] = 1;
        $map['uctime'] = ['GT',time()];
        $map['listingtime'] = ['LT',time()];
        $list = $this->where($map)->field('id,video_title,uid,is_activity,ctime')->order('ctime desc,id desc')->findPage($limit);
        foreach ($list['data'] as $key => $value) {
            $list['data'][$key]['user_title'] = getUserSpace($value['uid']);
        }
        return $list;
    }

    /**
     * 加载畅销榜单
     * @param $limit 记录数量
     * @param $is_activity 课程是否通过审核
     */
    public function getSellWell($limit = 17)
    {
        $where = array('is_del'=>0,'is_activity'=>1,'uctime'=>array('gt',time()),'listingtime'=>array('lt',time()));
        $order = 'video_order_count desc,ctime desc,video_collect_count desc,video_comment_count desc,video_score desc,video_question_count desc,
                 video_note_count desc,listingtime desc';
        $field = 'id,video_title,mhm_id,video_intro,cover,v_price,t_price,vip_level,is_best,endtime,starttime,limit_discount,uid,teacher_id,type';
        $list = $this->where($where)->order($order)->limit($limit)->field($field)->select();
        foreach ($list as $key => &$value){
            $value['money_data']   = getPrice ( $value, $this->mid, true, true );
        }
        return $list;
    }

    /**
     * 根据分类ID获取课程列表
     */
    public function getVideoListByIds($ids, $limit = 6)
    {
        $map['video_category'] = array('in', $ids);
        $data = $this->where($map)->findPage($limit);
        foreach ($data['data'] as $key => $vo) {
            $data['data'][$key]['vuid'] = $vo['uid'];
            $data['data'][$key]['userinfo'] = model('Follow')->getFollowStateByFids($GLOBALS['ts']['mid'], $vo['uid']);
            $data['data'][$key]['is_buy'] = D("ZyOrder", 'classroom')->isBuyVideo($GLOBALS['ts']['mid'], $vo['id']);
        }
        return $data;
    }


    /**
     * 获取课程信息
     * @param $id 课程id
     */
    public function getVideoById($id,$filed)
    {
        $map['id'] = $id;
        $data = $this->where($map)->field($filed)->find();
        $data['uid'] = !$data['uid'] ? 0 : $data['uid'];
        $data['cover_path'] = getAttachUrlByAttachId($data['cover']);
        return $data;
    }

    /**
     * 获取课程标题
     */
    public function getVideoTitleById($id)
    {
        $field = 'video_title';
        $map['id'] = $id;
        $data = $this->where($map)->field($field)->find();
        return $data['video_title'];
    }

    /**
     *
     * 获取某个用户订购的课程
     * @param integer $uid
     * @param integer|null|false $findPage
     */
    public function getBuyVideo($uid, $findPage = false)
    {
        $order = D('ZyOrder')->getTableName();
        $inSql = "SELECT video_id FROM {$order} WHERE uid={$uid}";
        $where = "is_del=0 AND uctime>" . time() . " AND is_activity=1 AND " .
            "(video_id IN($inSql) OR uid='{$uid}')";
        if (false !== $findPage) {
            return $this->where($where)->findPage($findPage);
        } else {
            return $this->where($where)->select();
        }
    }

    /**
     * guess_you_like
     */
    public function guessYouLike()
    {
        $map['is_del']      = 0;
        $map['is_activity'] = 1;
        $map['uctime']      = array('GT',time());
        $map['listingtime'] = array('LT',time());

        $data = $this->where($map)->order('video_collect_count desc,video_comment_count desc,video_question_count desc,
                video_note_count desc,video_score desc,video_order_count desc,listingtime desc')->field("id,video_title,mhm_id,video_intro,cover,v_price,
                t_price,vip_level,is_best,endtime,starttime,limit_discount,uid,teacher_id,type")->limit(5)->select();
        foreach ($data as $key => $val){
            $data[$key]['video_intro']  = mb_substr(t($val['video_intro']),0,50,'utf-8' );
            $data[$key]['mhmName']      = model('School')->getSchooldStrByMap(array('id'=>$val['mhm_id']),'title');
            $data[$key]['money_data']   = getPrice ( $val, $this->mid, true, true );
        }
        return $data;
    }
    /**
     * @name 获取分类下的video数据
     * @param string $port 数据所显示的位置 PC APP 
     * @param array $map 分类的查询条件
     * @param int $limit 分类的查询的数量
     * @return array 数据集信息
     */
    /**
     * @param $port 端口类型 PC APP
     * @param array $map 搜索条件
     * @param $limit 分页
     * @return array
     */
    public function cateVideo($port,$map = array(),$limit,$city){
        $map['pid'] = 0;
        $result = M('zy_currency_category')->where($map)->order('sort ASC')->limit($limit)->field('zy_currency_category_id,pid,title,middle_ids')->findAll();
        $cate_and_video = [];
        if($result) {
            if ($port == 1) {

                //加载首页分类课程等数据头部轮播广告位
                $ad_cate_map = array('is_active' => 1, 'display_type' => 3, 'place' => 8);
                $ad_cate_list = M('ad')->where($ad_cate_map)->order('display_order DESC')->find();

                //序列化广告内容
                $ad_cate_list = unserialize($ad_cate_list['content']);
                foreach ($result as $keys => $value) {
                    $cate_id = $value['zy_currency_category_id'];
                    $cate_and_video[$cate_id]['id'] = $value['zy_currency_category_id'];
                    $cate_and_video[$cate_id]['pid'] = $value['pid'];
                    $cate_and_video[$cate_id]['icon'] = 'tit0' . ($keys + 1);
                    $cate_and_video[$cate_id]['img_class'] = 'indextit_img' . ($keys + 1);
                    $cate_and_video[$cate_id]['title'] = $value['title'];
                    $cate_and_video[$cate_id]['middle_ids'] = $value['middle_ids'];

                    //楼层广告
                    foreach ($ad_cate_list as $kacl => $vacl) {
                        if ($keys == $kacl) {
                            $cate_and_video[$cate_id]['advertisement_id'] = $vacl['banner'];
                            $cate_and_video[$cate_id]['advertisement_url'] = $vacl['bannerurl'];
                        }
                    }

                    //推荐
                    $re_free_data = $this->where(array('is_mount'=>1,'is_cete_floor' => 1, 'fullcategorypath' => array('like', "%,{$value['zy_currency_category_id']},%"),
                        'is_del' => 0, 'is_activity' => 1, '_string' => ' (is_charge = 1)  OR ( t_price = 0) ', 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,
                            view_nums,type")->order('view_nums desc,video_order_count desc,video_collect_count desc,video_comment_count desc,video_question_count desc,video_note_count desc,
                                video_score desc')->limit(10)->findAll();
                    foreach ($re_free_data as $krfd => $vrfd) {
                        $re_free_data[$krfd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vrfd['mhm_id']), 'title');
                    }

                    $cate_and_video[$cate_id]['re']['re_free_data'] = $re_free_data;

                    $re_charge_data = $this->where(array('is_mount'=>1,'is_cete_floor' => 1, 'fullcategorypath' => array('like', "%,{$value['zy_currency_category_id']},%"),
                        'is_del' => 0, 'is_activity' => 1, 'is_charge' => 0, 't_price' => array('neq', 0), 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,
                            view_nums,type")->order('view_nums desc,video_order_count desc,video_collect_count desc,video_comment_count desc,video_question_count desc,video_note_count desc,
                                video_score desc')->limit(10)->findAll();
                    foreach ($re_charge_data as $krcd => $vrcd) {
                        $re_charge_data[$krcd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vrcd['mhm_id']), 'title');
                    }

                    $cate_and_video[$cate_id]['re']['re_charge_data'] = $re_charge_data;

                    $re_cate_data = $this->where(array('is_mount'=>1,'is_cete_floor' => 1, 'fullcategorypath' => array('like', "%,{$value['zy_currency_category_id']},%"),
                        'is_del' => 0, 'is_activity' => 1, 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,v_price,
                        t_price,vip_level,is_best,listingtime,limit_discount,uid,teacher_id,type,live_type,str_tag")->order('cete_floor_sort asc')->limit(6)->findAll();
                    foreach ($re_cate_data as $krd => $vrd) {
                        if ($vrd['type'] == 2) {
                            $video_section_status = '';
                            $video_section_end_num = 0;
                            if ($vrd['live_type'] == 1) {
                                $live_info = M('zy_live_zshd')->where(array('live_id' => $vrd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,startDate,invalidDate')->select();
                                foreach ($live_info as $live1 => $zshd) {
                                    if ($zshd['startDate'] < time() && $zshd['invalidDate'] > time()) {
                                        $video_section_status = '直播中';
                                    } elseif ($zshd['invalidDate'] < time()) {
                                        $video_section_status = '已结束';
                                    } elseif ($zshd['startDate'] > time()) {
                                        $video_section_status = '未开始';
                                    }

                                    if ($zshd['invalidDate'] < time()) {
                                        $video_section_end_num += 1;
                                    }
                                }
                            } elseif ($vrd['live_type'] == 3) {
                                $live_info = M('zy_live_gh')->where(array('live_id' => $vrd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,beginTime,endTime')->select();
                                foreach ($live_info as $live3 => $gh) {
                                    if ($gh['beginTime'] / 1000 < time() && $gh['endTime'] / 1000 > time()) {
                                        $video_section_status = '直播中';
                                    } elseif ($gh['endTime'] / 1000 < time()) {
                                        $video_section_status = '已结束';
                                    } elseif ($gh['beginTime'] / 1000 > time()) {
                                        $video_section_status = '未开始';
                                    }

                                    if ($gh['endTime'] / 1000 < time()) {
                                        $video_section_end_num += 1;
                                    }
                                }
                            }
                            $re_cate_data[$krd]['video_section_num'] = count($live_info);
                            $re_cate_data[$krd]['video_section_ing'] = $video_section_status;
                            $re_cate_data[$krd]['video_section_end_num'] = $video_section_end_num;
                            $re_cate_data[$krd]['money_data']['oriPrice'] = $vrd['v_price'];
                            $re_cate_data[$krd]['money_data']['price'] = $vrd['t_price'];
                        } else {
                            $re_cate_data[$krd]['video_section_num'] = M('zy_video_section')->where(array('vid' => $vrd['id'], 'pid' => array('neq', 0)))->field('id')->count();
                            $re_cate_data[$krd]['video_str_tag'] = reset(array_filter(explode(',', $vrd['str_tag'])));
                            $re_cate_data[$krd]['money_data'] = getPrice($vrd, $this->mid, true, true);
                        }
//                        $re_cate_data[$krd]['video_intro'] = mb_substr(t($vrd['video_intro']), 0, 50, 'utf-8');
                        $re_cate_data[$krd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vrd['mhm_id']), 'title');
                    }

                    $cate_and_video[$cate_id]['re']['re_cate_data'] = $re_cate_data;

                    //最新
                    $new_free_data = $this->where(array('is_mount'=>1,'is_cete_floor' => 1, 'fullcategorypath' => array('like', "%,{$value['zy_currency_category_id']},%"),
                        'is_del' => 0, 'is_activity' => 1, '_string' => ' (is_charge = 1)  OR ( t_price = 0) ', 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,
                            view_nums,type")->order('listingtime desc,ctime desc,view_nums desc,video_order_count desc,video_collect_count desc,video_comment_count desc,video_question_count desc,video_note_count desc,
                                video_score desc')->limit(10)->findAll();
                    foreach ($new_free_data as $knfd => $vnfd) {
                        $re_free_data[$knfd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vnfd['mhm_id']), 'title');
                    }

                    $cate_and_video[$cate_id]['new']['new_free_data'] = $new_free_data;

                    $new_charge_data = $this->where(array('is_mount'=>1,'is_cete_floor' => 1, 'fullcategorypath' => array('like', "%,{$value['zy_currency_category_id']},%"),
                        'is_del' => 0, 'is_activity' => 1, 'is_charge' => 0, 't_price' => array('neq', 0), 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,
                            view_nums,type")->order('listingtime desc,ctime desc,view_nums desc,video_order_count desc,video_collect_count desc,video_comment_count desc,video_question_count desc,video_note_count desc,
                                video_score desc')->limit(10)->findAll();
                    foreach ($new_charge_data as $kncd => $vncd) {
                        $re_charge_data[$kncd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vncd['mhm_id']), 'title');
                    }

                    $cate_and_video[$cate_id]['new']['new_charge_data'] = $new_charge_data;

                    $new_cate_data = $this->where(array('is_mount'=>1,'is_cete_floor' => 1, 'fullcategorypath' => array('like', "%,{$value['zy_currency_category_id']},%"),
                        'is_del' => 0, 'is_activity' => 1, 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,v_price,
                        t_price,vip_level,is_best,listingtime,limit_discount,uid,teacher_id,type,live_type,str_tag")->order('listingtime desc,ctime desc')->limit(6)->findAll();
                    foreach ($new_cate_data as $knd => $vnd) {
                        if ($vnd['type'] == 2) {
                            $video_section_status = '';
                            $video_section_end_num = 0;
                            if ($vnd['live_type'] == 1) {
                                $live_info = M('zy_live_zshd')->where(array('live_id' => $vnd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,startDate,invalidDate')->select();
                                foreach ($live_info as $live1 => $zshd) {
                                    if ($zshd['startDate'] < time() && $zshd['invalidDate'] > time()) {
                                        $video_section_status = '直播中';
                                    } elseif ($zshd['invalidDate'] < time()) {
                                        $video_section_status = '已结束';
                                    } elseif ($zshd['startDate'] > time()) {
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
                                    } elseif ($gh['endTime'] / 1000 < time()) {
                                        $video_section_status = '已结束';
                                    } elseif ($gh['beginTime'] / 1000 > time()) {
                                        $video_section_status = '未开始';
                                    }

                                    if ($gh['endTime'] / 1000 < time()) {
                                        $video_section_end_num += 1;
                                    }
                                }
                            }
                            $new_cate_data[$knd]['video_section_num'] = count($live_info);
                            $new_cate_data[$knd]['video_section_ing'] = $video_section_status;
                            $new_cate_data[$knd]['video_section_end_num'] = $video_section_end_num;
                            $new_cate_data[$knd]['money_data']['oriPrice'] = $vnd['v_price'];
                            $new_cate_data[$knd]['money_data']['price'] = $vnd['t_price'];
                        } else {
                            $new_cate_data[$knd]['video_section_num'] = M('zy_video_section')->where(array('vid' => $vnd['id'], 'pid' => array('neq', 0)))->field('id')->count();
                            $new_cate_data[$knd]['video_str_tag'] = reset(array_filter(explode(',', $vnd['str_tag'])));
                            $new_cate_data[$knd]['money_data'] = getPrice($vnd, $this->mid, true, true);
                        }
//                        $new_cate_data[$knd]['video_intro'] = mb_substr(t($vnd['video_intro']), 0, 50, 'utf-8');
                        $new_cate_data[$knd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vnd['mhm_id']), 'title');
                    }

                    $cate_and_video[$cate_id]['new']['new_cate_data'] = $new_cate_data;

                    //机构
                    $get_cete_school_list = model('School')->where(array('city'=>$city,'is_cete_floor' => 1, 'fullcategorypath' => array('like',
                        "%,{$value['zy_currency_category_id']},%")))->limit(10)->order('cete_floor_sort asc,id')->field('id,title,doadmin,cover')->select();

                    $cate_and_video[$cate_id]['cete_school_list'] = $get_cete_school_list;

                    //二级分类
                    $child = M('zy_currency_category')->where("pid={$cate_id} and is_choice_pc = 1")->field('zy_currency_category_id,pid,title,middle_ids')->order('sort ASC')->limit(9)->findAll();

                    $cate_and_video[$cate_id]['child'] = $child;
                    foreach ($cate_and_video[$cate_id]['child'] as $key => $val) {
                        if ($val['is_choice_ranking'] == 1) {
                            $free_data = $this->where(array('is_mount'=>1,'fullcategorypath' => array('like', "%,{$val['zy_currency_category_id']},%"),
                                'is_del' => 0, 'is_activity' => 1, '_string' => ' (is_charge = 1)  OR ( t_price = 0) ', 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,
                            view_nums,type")->order('view_nums desc,video_order_count desc,video_collect_count desc,video_comment_count desc,video_question_count desc,video_note_count desc,
                                video_score desc')->limit(10)->findAll();
                            foreach ($free_data as $kfd => $vfd) {
                                $free_data[$kfd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vfd['mhm_id']), 'title');
                            }

                            $cate_and_video[$cate_id]['child'][$key]['free_data'] = $free_data;

                            $charge_data = $this->where(array('is_mount'=>1,'fullcategorypath' => array('like', "%,{$val['zy_currency_category_id']},%"),
                                'is_del' => 0, 'is_activity' => 1, 'is_charge' => 0, 't_price' => array('neq', 0), 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,
                            view_nums,type")->order('view_nums desc,video_order_count desc,video_collect_count desc,video_comment_count desc,video_question_count desc,video_note_count desc,
                                video_score desc')->limit(10)->findAll();
                            foreach ($charge_data as $kcd => $vcd) {
                                $charge_data[$kcd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vcd['mhm_id']), 'title');
                            }

                            $cate_and_video[$cate_id]['child'][$key]['charge_data'] = $charge_data;

                            $cate_data = $this->where(array('is_mount'=>1,'fullcategorypath' => array('like', "%,{$val['zy_currency_category_id']},%"),
                                'is_del' => 0, 'is_activity' => 1, 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,v_price,
                                t_price,vip_level,is_best,listingtime,limit_discount,uid,teacher_id,type,live_type,str_tag")->order('cete_floor_sort asc')
                                ->limit(6)->findAll();
                            foreach ($cate_data as $kd => $vd) {
                                if ($vd['type'] == 2) {
                                    $video_section_status = '';
                                    $video_section_end_num = 0;
                                    if ($vd['live_type'] == 1) {
                                        $live_info = M('zy_live_zshd')->where(array('live_id' => $vd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,startDate,invalidDate')->select();
                                        foreach ($live_info as $live1 => $zshd) {
                                            if ($zshd['startDate'] < time() && $zshd['invalidDate'] > time()) {
                                                $video_section_status = '直播中';
                                            } elseif ($zshd['invalidDate'] < time()) {
                                                $video_section_status = '已结束';
                                            } elseif ($zshd['startDate'] > time()) {
                                                $video_section_status = '未开始';
                                            }

                                            if ($zshd['invalidDate'] < time()) {
                                                $video_section_end_num += 1;
                                            }
                                        }
                                    } elseif ($vd['live_type'] == 3) {
                                        $live_info = M('zy_live_gh')->where(array('live_id' => $vd['id'], 'is_active' => 1, 'is_del' => 0))->field('id,beginTime,endTime')->select();
                                        foreach ($live_info as $live3 => $gh) {
                                            if ($gh['beginTime'] / 1000 < time() && $gh['endTime'] / 1000 > time()) {
                                                $video_section_status = '直播中';
                                            } elseif ($gh['endTime'] / 1000 < time()) {
                                                $video_section_status = '已结束';
                                            } elseif ($gh['beginTime'] / 1000 > time()) {
                                                $video_section_status = '未开始';
                                            }

                                            if ($gh['endTime'] / 1000 < time()) {
                                                $video_section_end_num += 1;
                                            }
                                        }
                                    }
                                    $cate_data[$kd]['video_section_num'] = count($live_info);
                                    $cate_data[$kd]['video_section_ing'] = $video_section_status;
                                    $cate_data[$kd]['video_section_end_num'] = $video_section_end_num;
                                    $cate_data[$kd]['money_data']['oriPrice'] = $vd['v_price'];
                                    $cate_data[$kd]['money_data']['price'] = $vd['t_price'];
                                } else {
                                    $cate_data[$kd]['video_section_num'] = M('zy_video_section')->where(array('vid' => $vd['id'], 'pid' => array('neq', 0)))->field('id')->count();
                                    $cate_data[$kd]['video_str_tag'] = reset(array_filter(explode(',', $vd['str_tag'])));
                                    $cate_data[$kd]['money_data'] = getPrice($vd, $this->mid, true, true);
                                }
//                                $cate_data[$kd]['video_intro'] = mb_substr(t($vd['video_intro']), 0, 50, 'utf-8');
                                $cate_data[$kd]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vd['mhm_id']), 'title');
                            }
                        } else {
                            $cate_data = $this->where(array('is_mount'=>1,'fullcategorypath' => array('like', "%,{$val['zy_currency_category_id']},%"),
                                'is_del' => 0, 'is_activity' => 1, 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,video_intro,cover,v_price,
                                t_price,vip_level,is_best,listingtime,limit_discount,uid,teacher_id,type,str_tag")->order('cete_floor_sort asc')->limit(8)->findAll();
                            foreach ($cate_data as $kd2 => $vd2) {
                                if ($vd2['type'] == 2) {
                                    $video_section_status = '';
                                    $video_section_end_num = 0;
                                    if ($vd2['live_type'] == 1) {
                                        $live_info = M('zy_live_zshd')->where(array('live_id' => $vd2['id'], 'is_active' => 1, 'is_del' => 0))->field('id,startDate,invalidDate')->select();
                                        foreach ($live_info as $live1 => $zshd) {
                                            if ($zshd['startDate'] < time() && $zshd['invalidDate'] > time()) {
                                                $video_section_status = '直播中';
                                            } elseif ($zshd['invalidDate'] < time()) {
                                                $video_section_status = '已结束';
                                            } elseif ($zshd['startDate'] > time()) {
                                                $video_section_status = '未开始';
                                            }

                                            if ($zshd['invalidDate'] < time()) {
                                                $video_section_end_num += 1;
                                            }
                                        }
                                    } elseif ($vd2['live_type'] == 3) {
                                        $live_info = M('zy_live_gh')->where(array('live_id' => $vd2['id'], 'is_active' => 1, 'is_del' => 0))->field('id,beginTime,endTime')->select();
                                        foreach ($live_info as $live3 => $gh) {
                                            if ($gh['beginTime'] / 1000 < time() && $gh['endTime'] / 1000 > time()) {
                                                $video_section_status = '直播中';
                                            } elseif ($gh['endTime'] / 1000 < time()) {
                                                $video_section_status = '已结束';
                                            } elseif ($gh['beginTime'] / 1000 > time()) {
                                                $video_section_status = '未开始';
                                            }

                                            if ($gh['endTime'] / 1000 < time()) {
                                                $video_section_end_num += 1;
                                            }
                                        }
                                    }
                                    $cate_data[$kd2]['video_section_num'] = count($live_info);
                                    $cate_data[$kd2]['video_section_ing'] = $video_section_status;
                                    $cate_data[$kd2]['video_section_end_num'] = $video_section_end_num;
                                    $cate_data[$kd2]['money_data']['oriPrice'] = $vd2['v_price'];
                                    $cate_data[$kd2]['money_data']['price'] = $vd2['t_price'];
                                } else {
                                    $cate_data[$kd2]['video_section_num'] = M('zy_video_section')->where(array('vid' => $vd2['id'], 'pid' => array('neq', 0)))->field('id')->count();
                                    $cate_data[$kd2]['video_str_tag'] = reset(array_filter(explode(',', $vd2['str_tag'])));
                                    $cate_data[$kd2]['money_data'] = getPrice($vd2, $this->mid, true, true);
                                }
//                                $cate_data[$kd2]['video_intro'] = mb_substr(t($vd2['video_intro']), 0, 50, 'utf-8');
                                $cate_data[$kd2]['mhmName'] = model('School')->getSchooldStrByMap(array('id' => $vd2['mhm_id']), 'title');
                            }
                        }

                        $cate_and_video[$cate_id]['child'][$key]['cate_data'] = $cate_data;
                    }
                }

                return $cate_and_video;
            } else if ($port == 2) {
                foreach ($result as $keys => $value) {
                    $cate_id = $value['zy_currency_category_id'];
                    $cate_and_video[$cate_id]['id'] = $value['zy_currency_category_id'];
                    $cate_and_video[$cate_id]['title'] = $value['title'];

                    //推荐
                    $h5_re_cate_data = $this->where(array('is_mount'=>1,'is_cete_floor' => 1, 'fullcategorypath' => array('like', "%,{$value['zy_currency_category_id']},%"),
                        'is_del' => 0, 'is_activity' => 1, 'uctime' => array('gt', time()), 'listingtime' => array('lt', time())))->field("id,video_title,mhm_id,cover,v_price,
                        t_price,vip_level,is_best,listingtime,limit_discount,uid,teacher_id,type,str_tag")->order('cete_floor_sort asc')->limit(4)->findAll();
                    foreach ($h5_re_cate_data as $hkrcd => $hvrcd) {
                        $h5_re_cate_data[$hkrcd]['money_data'] = getPrice($hvrcd, $this->mid, true, true);
                    }

                    $cate_and_video[$cate_id]['re_cate_data'] = $h5_re_cate_data;
                }

                return $cate_and_video;
            }else{
                $prefix = C('DB_PREFIX');
                foreach($result as $k=>&$cate_video_list){
                    $cate_video_list['icon'] = getCover($cate_video_list['middle_ids'],80,80);
                    //$sql = '(SELECT zy_currency_category_id FROM '.$prefix.'zy_currency_category WHERE pid IN ('.$cate_video_list['zy_currency_category_id'].') OR zy_currency_category_id="'.$cate_video_list['zy_currency_category_id'].'") AS c';
                    $where = array(
                        'is_del'        => 0,
                        'is_activity'   => 1,
                        'uctime'        => array('gt',time()),
                        'listingtime'   => array('lt',time()),
						'is_cete_floor' => 1,
						'is_mount' 		=> 1,
						'fullcategorypath' => array('like', "%,{$cate_video_list['zy_currency_category_id']},%")
                    );
                    //$cate_video_list['video_list'] = $this->where($where)->join('AS v INNER JOIN '.$sql.' ON c.zy_currency_category_id = v.video_category')->order('v.cete_floor_sort asc')->limit(4)->select();
					$cate_video_list['video_list'] = $this->where($where)->order('cete_floor_sort asc')->limit(4)->select();
                    if($cate_video_list['video_list']){
                        foreach($cate_video_list['video_list'] as $key=>$video){
                            $cate_video_list['video_list'][$key]['price'] = getPrice ( $video, $this->mid );// 计算价格
                            $cate_video_list['video_list'][$key]['imageurl']    = getCover($video['cover'] , 280 , 160);
                            $cate_video_list['video_list'][$key]['video_score'] = round ( $video['video_score'] / 20 ); // 四舍五入
                        }
                    }
                }
                return $result;
            }
        }
    }
    /**
     * @name 获取某课程的章节数
     */
    public function getVideoSectionCount($vid = 0){
        return (int)M('zy_video_section')->where(['vid'=>$vid,'pid'=>['gt',0]])->count();
    }
    /**
     * @name 获取指定课程指定用户可以使用的优惠券
     */
    public function getCanuseCouponList($video_id = 0,$canot = 0){
        if($video_id){
            $fields = $this->where(['id'=>$video_id])->field(['t_price','mhm_id'])->find();
            $price = $fields['t_price'];
            if($canot == 1){
                $coupons = model('Coupon')->getCanuseCouponList($this->mid,[1,2]);
            }else{
                $coupons = model('Coupon')->getCanuseCouponList($this->mid,[1,2],'AND c.sid = '.$fields['mhm_id']);
            }
            if($coupons){
                if(!$canot){
                    //过滤全额抵消的优惠券
                    foreach($coupons as $k=>$v){
                        switch($v['type']){
                            case "1":
                                //价格低于门槛价 || 至少支付0.01
                                if($v['maxprice'] != '0.00' && $price <= $v['maxprice'] || $price - $v['price'] <= 0){
                                    unset($coupons[$k]);
                                }
                                break;
                            case "2":
                            default:
                                break;
                        }
                    }
                }else{
                    foreach($coupons as $k=>$v){
                        if($v['mhm_id'] == $fields['mhm_id']){
                            switch($v['type']){
                                case "1":
                                    //价格低于门槛价 || 至少支付0.01
                                    if($v['maxprice'] != '0.00' && $price >= $v['maxprice']){
                                        unset($coupons[$k]);
                                    }
                                    break;
                                case "2":
                                    unset($coupons[$k]);
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }
            }
        }
        return $coupons ? array_values($coupons):[];
    }
    /**
     * @name 搜索
     */
    public function getListBySearch($map,$limit,$order = ''){
        $map['is_del'] = 0; //搜索非隐藏内容
        $map['is_activity'] = 1;
        $map['is_mount'] = 1;
        $map['type'] = 1;
        $map['uctime'] = ['GT',time()];
        $map['listingtime'] = ['LT',time()];
        $list = $this->where($map)->order($order)->findPage($limit);
        if($list['data']){
            foreach($list['data'] as &$val){
                $val['price']       = getPrice ( $val, $this->mid );// 计算价格
                $val['imageurl']    = getCover($val['cover'] , 280 , 160);
                $val['video_score'] = round ( $val['video_score'] / 20 ); // 四舍五入
                $val['teacher_name']  = D('ZyTeacher','classroom')->where(array('id'=>$val['teacher_id']))->getField('name');
                $val['buy_count'] = (int)D('ZyOrderCourse','classroom')->where(array('video_id'=>$val['id']))->count();
                $val['section_count'] = (int)M('zy_video_section')->where(['vid'=>$val['id'],'pid'=>['neq',0]])->count();
            }
        }
        return $list;
    }

    /**
     * @name 获取某机构下的所有课程
     * @$school_id  机构ID
     * @return  array  课程ID集合
     */
    public function getSchoolAllVideo($school_id){
        $map = array('mhm_id'=>$school_id,'is_del'=>0,'type'=>1,'is_activity'=>1);
        $vid = $this->where($map)->field('id')->findALL();
        foreach($vid as $k=>&$v){
            $new_vid[] = implode(',',$v);
        }
        return $new_vid;
    }

    /**
     * @name 课程加入看单功能
     * @$video_id  机构ID
     * @$vtype  课程类型
     * @$coupon_id  卡券ID
     * @return  array  课程ID集合
     */
    public function addOrder($video_id, $vtype, $coupon_id)
    {
        $mid = $this -> mid;
        //取得课程
        $filed = "id,uid,video_title,mhm_id,teacher_id,v_price,t_price,term,live_type";
        $video = $this->getVideoById($video_id,$filed);

        $is_buy = D('ZyOrderCourse','classroom')->isBuyVideo($this->mid ,$video_id );
        if($is_buy){
            return false;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id' => $video['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid' => $teacher_uid))->getField('uid');

        //购买用户机构id
        $mhuid = M('user')->where('uid = '.$this->mid)->getField('mhm_id');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

        //取得价格
        if($vtype == 'zy_video') {
            $prices = getPrice($video, $this->mid, false, true);
        }else{
            $prices = getPrice($video, $this->mid, false, true, 2);
        }

        $data = array(
            'uid' => $mid,
            'old_price' => $prices['oriPrice'],//10
            'discount' => round($prices['oriPrice'] - $prices['price'], 2),
            'discount_type' => 3,
            'price' => 0,
            'coupon_id' => $coupon_id,
            'order_album_id' => 0,
            'learn_status' => 0,
            'ctime' => time(),
            'order_type' => 0,
            'is_del' => 0,
            'pay_status' => 3,
            'term' => $video['term'],
            'time_limit' => time() + 129600 * floatval($video['term']),
            'mhm_id' => $video['mhm_id'],
            'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
            'rel_id'        => 0,
        );
        if($vtype == 'zy_video') {
            $data['video_id'] = $video_id;
            $data['muid'] = $teacher_uid;
            $order_id = M('zy_order_course')->where(array('uid'=>$this->mid,'video_id'=>$video['id']))->getField('id');
            if($order_id){
                $id = M('zy_order_course')->where(array('uid'=>$this->mid,'video_id'=>$video['id']))->save($data);
            }else{
                $id = M('zy_order_course')->add($data);
            }
        }else{
            $data['live_id'] = $video_id;
            $order_id = D('ZyOrderLive')->where(array('uid'=>$this->mid,'live_id'=>$video_id))->getField('id');
            if($order_id){
                $id = D('ZyOrderLive')->where(array('uid'=>$this->mid,'live_id'=>$video_id))->save($data);
            }else{
                $id = D('ZyOrderLive')->add($data);
            }
        }

        if ($id) {
            if($coupon_id){
                $data['status'] = 1;
                M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->save($data);
            }
            M('zy_video')->where(array('id' => $video['id']))->setInc('video_order_count');
            return true;
        }
        return false;
    }
}