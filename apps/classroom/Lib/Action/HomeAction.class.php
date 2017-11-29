<?php
tsload(APPS_PATH . '/classroom/Lib/Action/UserAction.class.php');
class HomeAction extends UserAction
{
    /**get
     * 初始化
     * @return void
     */
    public function _initialize()
    {
        $this->assign("user_show_type", 'user');

        parent::_initialize();
    }

    /**
     * 内容管理--问答
     * @return void
     */
    public function wenda()
    {
        $mid = $this->mid;

        if (!$_GET['tab']) {
            $map['is_del'] = array('EQ', 0);
            $map['uid']    = array('EQ', $mid);

            $wddata = M('zy_wenda')->where($map)->order('ctime DESC')->findPage(9);
            $this->assign("wendadata", $wddata);
        } else if ($_GET['tab'] == 1) {
            $an_map['d.is_del'] = array('EQ', 0);
            $an_map['d.uid']    = array('EQ', $mid);

            $andata = M("zy_wenda_comment d")->join("`" . C('DB_PREFIX') . "zy_wenda` w ON w.id = d.wid")
                ->field('w.id,w.uid,w.wd_description,w.wd_comment_count,w.wd_help_count,d.uid as duid,d.description,
                d.ctime,d.id as commentid')->where($an_map)->order('ctime DESC')->findPage(9);
            $this->assign("andata", $andata);
        }

        $this->display();
    }

    /**
     * 会员中心问题--列表处理
     * @return void
     */
    public function wenti()
    {
        $limit           = 9;
        $zyQuestionMod   = D('ZyQuestion');
        $zyCollectionMod = D('ZyCollection');

        if (!$_GET['tab']) {
            $map['uid']       = intval($this->mid);
            $map['parent_id'] = 0;
            $order            = 'ctime DESC';

            $wenti_data = $zyQuestionMod->where($map)->order($order)->findPage($limit);
            foreach ($wenti_data['data'] as $key => &$value) {
                $value['qst_title']       = msubstr($value['qst_title'], 0, 15);
                $value['qst_description'] = msubstr($value['qst_description'], 0, 153);
                $value['strtime']         = friendlyDate($value['ctime']);
                $value['qcount']          = $zyQuestionMod->where(array('parent_id' => array('eq', $value['id'])))->count();
                if ($value['type'] == 1) {
                    $value['course_title'] = M('zy_video')->where('id=' . $value['oid'])->getField('video_title');
                }
                if ($value['type'] == 2) {
                    $value['course_title'] = M('album')->where('id=' . $value['oid'])->getField('album_title');

                }
            }
            $this->assign("wenti_data", $wenti_data);
        } else if ($_GET['tab'] == 1) {
            $huif_data = $zyQuestionMod->myAnswer($limit, intval($this->mid));
            foreach ($huif_data['data'] as $key => &$value) {
                $value['wenti']['qst_title']       = msubstr($value['wenti']['qst_title'], 0, 15);
                $value['wenti']['qst_description'] = msubstr($value['wenti']['qst_description'], 0, 149);
                $value['wenti']['strtime']         = friendlyDate($value['wenti']['ctime']);
                $value['wenti']['qcount']          = $zyQuestionMod->where(array('parent_id' => array('eq', $value['wenti']['id'])))->count();
                $value['qst_title']                = msubstr($value['qst_title'], 0, 15);
                $value['qst_description']          = msubstr($value['qst_description'], 0, 31);
                $value['qcount']                   = $zyQuestionMod->where(array('parent_id' => array('eq', $value['id'])))->count();
                if ($value['type'] == 1) {
                    $value['course_title'] = M('zy_video')->where('id=' . $value['oid'])->getField('video_title');
                }
                if ($value['type'] == 2) {
                    $value['course_title'] = M('album')->where('id=' . $value['oid'])->getField('album_title');
                }
            }
            $this->assign("huif_data", $huif_data);
        }
        $this->display();
        //以前的收藏的提问 好像
        $data = $zyCollectionMod->myCollection('zy_question', $limit, intval($this->mid));
        foreach ($data['data'] as $key => &$value) {
            $value['qst_title']       = msubstr($value['qst_title'], 0, 15);
            $value['qst_description'] = msubstr($value['qst_description'], 0, 153);
            $value['strtim  e']       = friendlyDate($value['ctime']);
            $value['qcount']          = $zyQuestionMod->where(array('parent_id' => array('eq', $value['id'])))->count();
        }
    }

    /**
     * 会员中心笔记--列表处理
     * @return void
     */
    public function note()
    {
        $limit = 9;

        $zyNoteMod       = D('ZyNote');
        $zyCollectionMod = D('ZyCollection');

        $map['uid']       = intval($this->mid);
        $map['parent_id'] = 0;
        $order            = 'ctime DESC';
        $data             = $zyNoteMod->where($map)->order($order)->findPage($limit);

        foreach ($data['data'] as $key => &$value) {
            $value['note_title']       = msubstr($value['note_title'], 0, 15);
            $value['note_description'] = msubstr($value['note_description'], 0, 150);
            $value['strtime']          = friendlyDate($value['ctime']);
            $value['qcount']           = $zyNoteMod->where(array('parent_id' => array('eq', $value['id'])))->count();
        }

        $this->assign("notedata", $data);
        $this->display();
    }

    /**
     * 会员中心订单--列表处理
     * @return void
     */
    public function order()
    {
        if (!$this->is_pc) {
            $limit   = 6;
            $orderby = t($_GET['orderby']);
            if ($orderby) {
                if ($orderby != 0) {
                    $map['pay_status'] = $orderby;
                }
            }
            $map['uid']    = intval($this->mid);
            $map['is_del'] = intval(0);
            $order         = 'ctime DESC';

            $table      = 'zy_order_course';
            $ordertype  = 'course';
            $check_type = 'zy_video';
            if ($_GET['ordertype'] == 'course') {
                $table = 'zy_order_course';
            } else if ($_GET['ordertype'] == 'live') {
                $ordertype  = 'live';
                $table      = 'zy_order_live';
                $check_type = 'zy_live';
            } else if ($_GET['ordertype'] == 'teacher') {
                $ordertype  = 'teacher';
                $table      = 'zy_order_teacher';
                $check_type = 'zy_teacher';
            }

            $data = M($table)->where($map)->order($order)->findPage($limit);

            if ($data['data']) {
                foreach ($data['data'] as $key => &$val) {
                    //取得课程信息
                    if ($table == "zy_order_course") {

                        $video = M('zy_video')->where('id =' . $val['video_id'])->field("id,uid,cover,video_binfo,video_title,mhm_id,teacher_id,v_price,t_price,vip_level,
                    endtime,starttime,limit_discount,uid,teacher_id")->find();

                        $val['video_name']  = msubstr($video['video_title'], 0, 20);
                        $val['cover']       = $video['cover'];
                        $val['video_binfo'] = msubstr($video['video_binfo'], 0, 45);
                        //价格和折扣
                        $val['old_price'] = $video['v_price'];

                        $val['ctime']   = $val['ctime'];
                        $val['mzprice'] = getPrice($video, $this->mid, true, true);

                        //如果是通过班级购买的课程显示为0元购买
                        $order_info = M('zy_order_course')->where(['video_id' => $val['video_id'], 'uid' => $this->mid])->field('order_album_id')->find();
                        if ($order_info['order_album_id']) {
                            $val['price']              = '0.00';
                            $val['order_album_status'] = 1;
                        }
                    }
                    if ($table == "zy_order_live") {
                        //取得课程信息
                        $val['cover']            = M('zy_video')->where('id =' . $val['live_id'])->getField('cover');
                        $video_binfo = M('zy_video')->where('id =' . $val['live_id'])->getField("video_binfo");
                        $val['video_binfo']      = msubstr($video_binfo, 0, 45);
                        $playtype                = '2';
                        $t_price                 = M('zy_video')->where('id =' . $val['live_id'])->getField("t_price");
                        $val['mzprice']['price'] = $t_price;

                        //如果为管理员/机构管理员自己机构的课程 则免费
                        if (is_admin($this->mid) || $val['is_charge'] == 1) {
                            $val['mzprice']['price'] = 0;
                        }
                        if (is_school($this->mid) == $data['data'][$key]['mhm_id'] && $data['data'][$key]['mhm_id']) {
                            $val['mzprice']['price'] = 0;
                        }

                        $val['live_type'] = M('zy_video')->where('id =' . $val['live_id'])->getField("live_type");
                        $val['cuid']      = M('zy_video')->where('id =' . $val['live_id'])->getField("uid");
                        $val['ctid']      = M('zy_video')->where('id =' . $val['live_id'])->getField("teacher_id");

                        //如果是讲师自己的课程 则免费
                        $mid = $this->mid;
                        $tid = M('zy_teacher')->where('uid =' . $mid)->getField('id');
                        if ($mid == intval($val['cuid']) || $tid == $val['ctid']) {
                            $val['mzprice']['price'] = 0;
                        }

                        $val['old_price'] = M('zy_video')->where('id =' . $val['live_id'])->getField("v_price");
                        //取得直播名称
                        $val['video_name'] = getVideoNameForID($val['live_id']);

                        //如果是通过班级购买的课程显示为0元购买
                        $order_info = M('zy_order_live')->where(['live_id' => $val['live_id'], 'uid' => $this->mid])->field('order_album_id')->find();
                        if ($order_info['order_album_id']) {
                            $val['price']              = '0.00';
                            $val['order_album_status'] = 1;
                        }
                    }

                    //取得线下课程信息
                    if ($table == "zy_order_teacher") {

                        $video              = M('zy_teacher_course')->where('course_id =' . $val['video_id'])->find();
                        $val['video_name']  = msubstr($video['course_name'], 0, 20);
                        $val['cover']       = $video['cover'];
                        $val['video_binfo'] = msubstr($video['course_binfo'], 0, 45);
                        //价格和折扣
                        $val['old_price'] = $video['course_price'];
                        $val['ctime']     = $val['ctime'];
                        $video['t_price'] = $video['course_price'];
                        $val['mzprice']   = getPrice($video, $this->mid, true, true, 4);
                    }
                }
            }
            $this->assign("orderby", $orderby);
            $this->assign("check_type", $check_type);
            $this->assign("ordertype", $ordertype);
            $this->assign("uid", $this->mid);
            $this->assign("data", $data);
        }
        $this->display();
    }

    /**
     * 我的分享--列表处理
     * @return void
     */
    public function share()
    {

        if (!$this->is_pc) {

            $video_share = M('zy_video_share')->where(array('uid' => $this->mid))->order('ctime desc')->findPage(9);
            foreach ($video_share['data'] as $key => $val) {
                if ($val['type'] == 0 || $val['type'] == 2) {
                    $video_info                         = M('zy_video')->where(array('id' => $val['video_id']))->field('video_title')->find();
                    $video_share['data'][$key]['title'] = $video_info['video_title'];
                }
                if ($val['type'] == 1) {
                    $video_info                         = M('album')->where(array('id' => $val['video_id']))->field('album_title')->find();
                    $video_share['data'][$key]['title'] = $video_info['album_title'];
                }
            }

            $this->assign('data', $video_share);
            $data['data'] = $this->fetch('share_list');
            $this->display();
            exit;
        }

        $tab = intval($_GET['tab']);

        $tpls = array('share', 'share_money');
        if (!isset($tpls[$tab])) {
            $tab = 0;
        }

        if ($tpls[$tab] == 'share') {
            $video_share = M('zy_video_share')->where(array('uid' => $this->mid))->order('ctime desc')->findPage(10);
            foreach ($video_share['data'] as $key => $val) {
                if ($val['type'] == 0 || $val['type'] == 2) {
                    $video_info                         = M('zy_video')->where(array('id' => $val['video_id']))->field('video_title')->find();
                    $video_share['data'][$key]['title'] = $video_info['video_title'];
                }
                if ($val['type'] == 1) {
                    $video_info                         = M('album')->where(array('id' => $val['video_id']))->field('album_title')->find();
                    $video_share['data'][$key]['title'] = $video_info['album_title'];
                }
            }
            $this->assign('video_share', $video_share);
        } else if ($tpls[$tab] == 'share_money') {
            $where = "share_id = {$this->mid} AND status = 1 AND is_exchange = 0";

            $course_share_price = M('zy_split_course')->where($where)->sum('share_sum');
            $live_share_price   = M('zy_split_live')->where($where)->sum('share_sum');
            $album_share_price  = M('zy_split_album')->where($where)->sum('share_sum');

            $share_price = $course_share_price + $live_share_price + $album_share_price;

            $this->assign('share_price', $share_price);
        }

        $this->assign('tab', $tab);
        $this->display($tpls[$tab]);
    }

    public function group()
    {
        $map['uid']    = $this->mid;
        $map['is_del'] = 0;

        $data = M('group')->where($map)->order('ctime desc')->findpage(9);
        foreach ($data['data'] as $key => $val) {
            $data['data'][$key]['logo'] = $this->logo_path_to_url($val['logo']);
            $data['data'][$key]['cid0'] = M('group_category')->where('id =' . $val['cid0'])->getField('title');
        }
        //把数据传入模板
        $this->assign('listData', $data);
        $this->assign('group_data', $data['data']);
        $this->assign('mid', $this->mid);
        $this->display();
    }

    /**
     * 异步加载我购买的课程
     * @return void
     */
    public function getcgroup()
    {
        $map['uid']    = $this->mid;
        $map['is_del'] = 0;

        $data = M('group')->where($map)->order('ctime desc')->findpage(9);
        foreach ($data['data'] as $key => $val) {
            $data['data'][$key]['cid0'] = M('group_category')->where('id =' . $val['cid0'])->getField('title');
        }

        //把数据传入模板
        $this->assign('data', $data['data']);
        //取得数据
        $data['data'] = $this->fetch('_mygroup');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步完成我的约课
     * @return void
     */
    public function delgroup()
    {
        $res = M("group")->where("id=" . intval($_POST["id"]))->data(array("is_del" => 1))->save();

        if ($res) {
            $credit = M('credit_setting')->where(array('id' => 44, 'is_open' => 1))->field('id,name,score,count')->find();
            if ($credit['score'] < 0) {
                $dtype = 7;
                $note  = '解散小组扣除的积分';
            }
            model('Credit')->addUserCreditRule($this->mid, $dtype, $credit['id'], $credit['name'], $credit['score'], $credit['count'], $note);

            exit(json_encode(array('status' => '1', 'info' => '删除成功')));
        } else {
            exit(json_encode(array('status' => '0', 'info' => '删除失败')));
        }
    }

    public function getshare()
    {

        if ($_GET['type'] != 'income') {

            $data = M('zy_video_share')->where(array('uid' => $this->mid))->order('ctime desc')->findPage(9);
            foreach ($data['data'] as $key => $val) {
                if ($val['type'] == 0 || $val['type'] == 2) {
                    $video_info                  = M('zy_video')->where(array('id' => $val['video_id']))->field('video_title')->find();
                    $data['data'][$key]['title'] = $video_info['video_title'];
                }
                if ($val['type'] == 1) {
                    $video_info                  = M('album')->where(array('id' => $val['video_id']))->field('album_title')->find();
                    $data['data'][$key]['title'] = $video_info['album_title'];
                }
            }

            $this->assign('data', $data);
            $data['data'] = $this->fetch('share_list');
            echo json_encode($data);
            exit();
        } else {
            $where = "share_id = {$this->mid} AND status = 1 AND is_exchange = 0";

            $course_share_price = M('zy_split_course')->where($where)->sum('share_sum');
            $live_share_price   = M('zy_split_live')->where($where)->sum('share_sum');
            $album_share_price  = M('zy_split_album')->where($where)->sum('share_sum');

            $share_price = $course_share_price + $live_share_price + $album_share_price;

            $data['share_price'] = $share_price;
            $data['income']      = 1;
            echo json_encode($data);

        }

    }

    /**
     * 会员中心我的关注--列表处理
     * @return void
     */
    public function follow()
    {
        $limit = 10;
        $order = 'ctime DESC';

        if (!$_GET['tab']) {
            $map['uid']   = intval($this->mid);
            $map['tid']   = array('gt', '0');
            $teacher_data = model('Follow')->where($map)->order($order)->findPage($limit);
            foreach ($teacher_data['data'] as $key => &$value) {
                $teacher          = D('ZyTeacher')->getTeacherInfo($value['tid']);
                $value['name']    = $teacher['name'];
                $value['title']   = M('zy_teacher_title_category')->where('zy_teacher_title_category_id=' . $teacher['title'])->getField('title') ?: '普通讲师';
                $value['head_id'] = $teacher['head_id'];
                $value['inro']    = mb_substr($teacher['inro'], 0, 20, 'utf-8') . "...";
                $value['strtime'] = friendlyDate($value['ctime']);
            }
            $this->assign("teacher_data", $teacher_data);
        } else if ($_GET['tab'] == 1) {
            //拼接两个表名
            $follow = C('DB_PREFIX') . 'user_follow';
            $school = C('DB_PREFIX') . 'school';
            //拼接字段
            $fields = "{$school}.`title`,{$school}.`uid`,{$school}.`id`,{$school}.`doadmin`,{$school}.`info`,{$school}.`cover`,";
            $fields .= "{$follow}.`ctime`,{$follow}.`follow_id`";

            $where       = "{$school}.`is_del`=0 and {$school}.`status`=1 and {$follow}.`uid`={$this->mid}";
            $school_data = model('School')->join("{$follow} on {$school}.`uid`={$follow}.`fid`")->where($where)->field($fields)->order('ctime desc')->findPage($limit);
            foreach ($school_data['data'] as $key => &$value) {
                $school_data['data'][$key]['title']  = msubstr($value['title'], 0, 20, 'utf-8', true);
                $school_data['data'][$key]['info']   = msubstr($value['info'], 0, 25, 'utf-8', true);
                $school_data['data'][$key]['domain'] = getDomain($value['doadmin'], $value['id']);
            }
            /*$school_fid = model('Follow')->where(['uid' => $this->mid])->order($order)->getField('fid,follow_id');
            foreach ($school_fid as $key => $val) {
            if (!is_school($val)) {
            unset($school_fid[$key]);
            }
            }
            $school_data = model('Follow')->where(['follow_id' => ['in', $school_fid]])->order($order)->findPage($limit);
            foreach ($school_data['data'] as $key => &$value) {
            if (is_school($value['fid'])) {
            $school = M('school')->where('uid = ' . $value['fid'])->field('id,title,doadmin,info,logo')->find();
            $school_data['data'][$key]['title'] = $school['title'];
            $school_data['data'][$key]['info'] = msubstr($school['info'], 0, 25);
            $school_data['data'][$key]['logo'] = $school['logo'];
            if ($school['doadmin'] && $school['doadmin'] != 'www') {
            $school_data['data'][$key]['domain'] = getDomain($school['doadmin']);
            } else {
            $school_data['data'][$key]['domain'] = U('school/School/index', array('id' => $school['id']));
            }
            } else {
            unset($school_data['data'][$key]);
            ksort($school_data['data']);
            }
            }*/
            $this->assign("school_data", $school_data);
        }

        $this->display();
    }

    public function collect()
    {
        $limit = 9;

        $map['uid'] = $school_map['uid'] = $teacher_map['uid'] = $this->mid;
        if (!$_GET['tab']) {
            $map['source_table_name'] = "zy_topic";
            $topic_data               = M('zy_collection')->where($map)->findPage($limit);
            foreach ($topic_data['data'] as $key => &$value) {
                $data['data'][$key]['topictitle']         = M('zy_topic')->where('id = ' . $value['source_id'])->getField('title');
                $topic_data['data'][$key]['topictitle']   = msubstr($topic_data['data'][$key]['topictitle'], 0, 18);
                $topic_data['data'][$key]['topicdesc']    = M('zy_topic')->where('id = ' . $value['source_id'])->getField('desc');
                $topic_data['data'][$key]['topicdesc']    = msubstr($topic_data['data'][$key]['topicdesc'], 0, 18);
                $topic_data['data'][$key]['dateline']     = M('zy_topic')->where('id = ' . $value['source_id'])->getField('dateline');
                $commentmap['row_id']                     = $value['source_id'];
                $commentmap['table']                      = 'zy_topic';
                $commentmap['is_del']                     = '0';
                $topic_data['data'][$key]['commentcount'] = M('comment')->where($commentmap)->count();
            }
            $this->assign("topic_data", $topic_data);
        } else if ($_GET['tab'] == 1) {
            $school_map['source_table_name'] = 'school';
            $school_data                     = M('zy_collection')->where($school_map)->findPage($limit);
            foreach ($school_data['data'] as $key => &$value) {
                $school                             = M('school')->where('id = ' . $value['source_id'])->field('id,title,doadmin,info,logo')->find();
                $school_data['data'][$key]['title'] = $school['title'];
                $school_data['data'][$key]['info']  = msubstr($school['info'], 0, 25);
                $school_data['data'][$key]['logo']  = $school['logo'];
                if ($school['doadmin'] && $school['doadmin'] != 'www') {
                    $school_data['data'][$key]['domain'] = getDomain($school['doadmin']);
                } else {
                    $school_data['data'][$key]['domain'] = U('school/School/index', array('id' => $school['id']));
                }
            }
            $this->assign("school_data", $school_data);
        } else if ($_GET['tab'] == 2) {
            $teacher_map['source_table_name'] = 'zy_teacher';
            $teacher_data                     = M('zy_collection')->where($teacher_map)->findPage($limit);
            foreach ($teacher_data['data'] as $key => &$value) {
                $teacher                               = M('zy_teacher')->where('id = ' . $value['source_id'])->field('id,name,title,inro,head_id')->find();
                $teacher_data['data'][$key]['tid']     = $teacher['id'];
                $teacher_data['data'][$key]['name']    = $teacher['name'];
                $teacher_data['data'][$key]['title']   = M('zy_teacher_title_category')->where('zy_teacher_title_category_id=' . $teacher['title'])->getField('title') ?: '普通讲师';
                $teacher_data['data'][$key]['inro']    = msubstr($teacher['inro'], 0, 25);
                $teacher_data['data'][$key]['head_id'] = $teacher['head_id'];
            }
            $this->assign("teacher_data", $teacher_data);
        }
        $this->display();
    }

    /**
     * 会员中心学习次数--列表处理
     * @return void
     */
    public function learn()
    {
        $limit = 9;
        $uid   = intval($this->mid);
        $map   = array('uid' => $uid, 'is_del' => 0);
        $order = 'ctime DESC';

        $data = M('learn_record')->where($map)->order($order)->findPage($limit);

        foreach ($data['data'] as $key => &$value) {
            $video_section_info           = M('zy_video_section')->where(array('zy_video_section_id' => $value['sid']))->field('pid,title')->find();
            $value['video_chapter_title'] = M('zy_video_section')->where(array('zy_video_section_id' => $video_section_info['pid']))->getField('title');
            $value['video_section_title'] = $video_section_info['title'];
            $value['video_title']         = D('ZyVideo')->getVideoTitleById($value['vid']);
            $value['strtime']             = friendlyDate($value['ctime']);
            $value['time']                = secondsToHour($value['time']);
        }

        $this->assign("data", $data['data']);
        $this->assign("learndata", $data);
        $this->display();
    }

    /**
     * 会员中心点评--列表处理
     * @return void
     */
    public function review()
    {
        $limit       = 9;
        $zyReviewMod = D('ZyReview');

        $map['uid']       = intval($this->mid);
        $map['parent_id'] = 0;
        $order            = 'ctime DESC';

        $data = $zyReviewMod->where($map)->order($order)->findPage($limit);
        foreach ($data['data'] as $key => &$value) {
            $value['star']               = $value['star'] / 20;
            $value['review_description'] = msubstr($value['review_description'], 0, 150);
            $value['strtime']            = friendlyDate($value['ctime']);
            $value['qcount']             = $zyReviewMod->where(array('parent_id' => array('eq', $value['id'])))->count();

            $_map['id'] = array('eq', $value['oid']);
            //找到评论的内容
            if ($value['type'] == 1) {
                $value['title']      = M('ZyVideo')->where($_map)->getField('`video_title` as `title`');
                $value['video_type'] = M('ZyVideo')->where($_map)->getField('type');
                $value['_src']       = U('classroom/Video/view', 'id=' . $value['oid']);
            } else {
                $value['title'] = M('Album')->where($_map)->getField('`album_title` as `title`');
                $value['_src']  = U('classroom/Album/view', 'id=' . $value['oid']);
            }
            $value['title'] = msubstr($value['title'], 0, 18);
        }
        $this->assign("reviewdata", $data);
        $this->display();
    }

    /**
     * 会员中心点评--异步处理
     * @return void
     */
    public function getreviewlist()
    {
        $limit       = 9;
        $type        = t($_GET['type']);
        $zyReviewMod = D('ZyReview');

        if ($type == 'me') {
            $map['uid']       = intval($this->mid);
            $map['parent_id'] = 0;
            $order            = 'ctime DESC';

            $data = $zyReviewMod->where($map)->order($order)->findPage($limit);
            foreach ($data['data'] as $key => &$value) {
                $value['star']               = $value['star'] / 20;
                $value['review_description'] = msubstr($value['review_description'], 0, 150);
                $value['strtime']            = friendlyDate($value['ctime']);
                $value['qcount']             = $zyReviewMod->where(array('parent_id' => array('eq', $value['id'])))->count();

                $_map['id'] = array('eq', $value['oid']);
                //找到评论的内容
                if ($value['type'] == 1) {
                    $value['title'] = M('ZyVideo')->where($_map)->getField('`video_title` as `title`');
                    $value['_src']  = U('classroom/Video/view', 'id=' . $value['oid']);
                } else {
                    $value['title'] = M('Album')->where($_map)->getField('`album_title` as `title`');
                    $value['_src']  = U('classroom/Album/view', 'id=' . $value['oid']);
                }
                $value['title'] = msubstr($value['title'], 0, 18);
            }
        }
        $this->assign("data", $data);
        $data['data'] = $this->fetch('review_list');
        echo json_encode($data);
        exit;
    }

    /**
     * 会员中心班级--列表处理
     * @return void
     */
    public function album()
    {
        $limit = 9;
        $uid   = intval($this->mid);
        //拼接两个表名
        $atablename = C('DB_PREFIX') . 'album';

        if (!$_GET['tab']) {
            $otablename = C('DB_PREFIX') . 'zy_order_album';
            //拼接字段
            $fields = "{$otablename}.`uid`,{$otablename}.`id` as `oid`,";
            $fields .= "{$atablename}.`id`,{$atablename}.`album_title`,{$atablename}.`album_category`,{$atablename}.`cover`,{$atablename}.`album_intro`,";
            $fields .= "{$atablename}.`cover`, {$atablename}.`price`";
            //不是通过班级购买的
            $where = "{$otablename}.`is_del`=0 and {$otablename}.`uid`={$uid}";

            $buy_data = M('ZyOrderAlbum')->join("{$atablename} on {$otablename}.`album_id`={$atablename}.`id`")->where($where)->field($fields)->findPage($limit);
            foreach ($buy_data['data'] as $key => &$val) {
                $val['album_order_count'] = M('zy_order_album')->where(array('album_id' => $val['id'], 'is_del' => 0, 'pay_status' => 3))->count();
            }
            $this->assign('buy_album_data', $buy_data);
        } else if ($_GET['tab'] == 1) {
            $ctablename = C('DB_PREFIX') . 'zy_collection';
            //拼接字段
            $c_fields = "{$ctablename}.`uid`,{$ctablename}.`collection_id` as `cid`,";
            $c_fields .= "{$atablename}.`id`,{$atablename}.`album_title`,{$atablename}.`album_category`,{$atablename}.`cover`,{$atablename}.`album_intro`,";
            $c_fields .= "{$atablename}.`cover`, {$atablename}.`price`";
            //拼接字段
            $c_where = "{$ctablename}.`source_table_name` = 'album' and {$ctablename}.`uid`={$uid}";

            $col_album_data = M('ZyCollection')->join("{$atablename} on {$ctablename}.`source_id`={$atablename}.`id`")->where($c_where)->field($c_fields)->findPage($limit);
            foreach ($col_album_data['data'] as $key => &$val) {
                $val['album_order_count'] = M('zy_order_album')->where(array('album_id' => $val['id'], 'is_del' => 0, 'pay_status' => 3))->count();
            }
            $this->assign('col_album_data', $col_album_data);
        }

        $this->display();
    }

    /**
     * 会员中心课程--列表处理
     * @return void
     */
    public function video()
    {
        $uid   = $this->mid;
        $limit = 9;
        //拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';

        if (!$_GET['tab']) {
            $order_course = C('DB_PREFIX') . 'zy_order_course';
            //拼接字段
            $fields = "{$order_course}.`learn_status`,{$order_course}.`uid`,{$order_course}.`id` as `oid`,";
            $fields .= "{$vtablename}.`teacher_id`,{$vtablename}.`mhm_id`,{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_binfo`,";
            $fields .= "{$vtablename}.`cover`,{$vtablename}.`video_order_count`,{$vtablename}.`ctime`,{$vtablename}.`t_price`";
            //不是通过班级购买的
            //$where     = "{$order_course}.`is_del`=0 and {$order_course}.`order_album_id`=0 and {$order_course}.`uid`={$uid}";
            $where      = "{$order_course}.`is_del`=0 and {$order_course}.`pay_status`=3 and {$order_course}.`uid`={$uid}";
            $video_data = M('zy_order_course')->join("{$vtablename} on {$order_course}.`video_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);
            foreach ($video_data['data'] as &$val) {
                $val['teacher_neme'] = M('zy_teacher')->where(['id' => $val['teacher_id']])->getField('name');
                $school_info         = M('school')->where(['id' => $val['mhm_id']])->field('title,doadmin')->find();
                $val['school_title'] = $school_info['title'];
                $val['school_url']   = getDomain($school_info['doadmin'], $val['mhm_id']);
            }
            $this->assign("video_data", $video_data);
        } else if ($_GET['tab'] == 1) {
            //拼接两个表名
            $ctablename = C('DB_PREFIX') . 'zy_collection';

            $fields = "{$ctablename}.`uid`,{$ctablename}.`collection_id` as `cid`,";
            $fields .= "{$vtablename}.*";
            //拼接条件
            $c_where = "{$ctablename}.`source_table_name`='zy_video' and {$ctablename}.`uid`={$uid} and {$vtablename}.`type`=1";
            //取数据
            $merge_video_data = M('ZyCollection')->join("{$vtablename} on {$ctablename}.`source_id`={$vtablename}.`id`")->where($c_where)->field($fields)->findPage($limit);
            //循环计算课程价格
            foreach ($merge_video_data['data'] as $key => &$val) {
                $val['teacher_neme']                     = M('zy_teacher')->where(['id' => $val['teacher_id']])->getField('name');
                $val['school_title']                     = M('school')->where(['id' => $val['mhm_id']])->getField('title');
                $merge_video_data['data'][$key]['money'] = $val['t_price'];
                if ($val['type'] == 2) {
                    unset($merge_video_data['data'][$key]);
                }

            }
            $this->assign('merge_video_data', $merge_video_data);
        }
        $this->display();
    }

    /**
     * 我发起的众筹
     */
    public function crow()
    {
        $this->assign('mid', $this->mid);
        $this->display();
    }

    /**
     * 会员中心课程--列表处理
     * @return void
     */
    public function live()
    {
        $uid   = intval($this->mid);
        $limit = 9;
        //拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';
        if (!$_GET['tab']) {
            $otablename = C('DB_PREFIX') . 'zy_order_live';
            //拼接字段
            $fields = "{$otablename}.`learn_status`,{$otablename}.`uid`,{$otablename}.`id` as `oid`,{$otablename}.`live_id`,";
            $fields .= "{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_intro`,";
            $fields .= "{$vtablename}.`cover`,{$vtablename}.video_order_count,{$vtablename}.`t_price`,{$vtablename}.`mhm_id`";
            //不是通过班级购买的
            $where = "{$otablename}.`is_del`=0 and {$otablename}.`pay_status`=3 and {$otablename}.`uid`={$uid}";
            $data  = M('zy_order_live')->join("{$vtablename} on {$otablename}.`live_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);

            //循环计算课程价格
            foreach ($data['data'] as $key => &$val) {
                $data['data'][$key]['money']       = $val['t_price'];
                $data['data'][$key]['order_count'] = M('zy_order_live')->where(['live_id' => $val['live_id'], 'is_del' => 0, 'pay_status' => 3])->count();

                $school                          = model('School')->where('id=' . $val['mhm_id'])->field('doadmin,title')->find();
                $data['data'][$key]['mhm_title'] = $school['title'];
                if (!$school['doadmin']) {
                    $data['data'][$key]['domain'] = U('school/School/index', array('id' => $val['mhm_id']));
                } else {
                    $data['data'][$key]['domain'] = getDomain($school['doadmin']);
                }
            }
            $vms = D('ZyVideoMerge')->getList(intval($this->mid), session_id());
            $this->assign('vms', getSubByKey($vms, 'live_id'));
            $this->assign('listData', $data);
            $this->assign('data', $data['data']);
        } else if ($_GET['tab'] == 1) {
            //拼接两个表名
            $ctablename = C('DB_PREFIX') . 'zy_collection';

            $fields = "{$ctablename}.`uid`,{$ctablename}.`collection_id` as `cid`,";
            $fields .= "{$vtablename}.*";
            //拼接条件
            $c_where = "{$ctablename}.`source_table_name`='zy_video' and {$ctablename}.`uid`={$uid} and {$vtablename}.`type`=2";
            //取数据
            $merge_live_data = M('ZyCollection')->join("{$vtablename} on {$ctablename}.`source_id`={$vtablename}.`id`")->where($c_where)->field($fields)->findPage($limit);
            //循环计算课程价格
            foreach ($merge_live_data['data'] as $key => &$val) {
                $merge_live_data['data'][$key]['money']       = $val['t_price'];
                $merge_live_data['data'][$key]['order_count'] = M('zy_order_live')->where(['live_id' => $val['id'], 'is_del' => 0, 'pay_status' => 3])->count();

                $school                                     = model('School')->where('id=' . $val['mhm_id'])->field('doadmin,title')->find();
                $merge_live_data['data'][$key]['mhm_title'] = $school['title'];
                if (!$school['doadmin']) {
                    $merge_live_data['data'][$key]['domain'] = U('school/School/index', array('id' => $val['mhm_id']));
                } else {
                    $merge_live_data['data'][$key]['domain'] = getDomain($school['doadmin']);
                }
                if ($val['type'] == 1) {
                    unset($merge_live_data['data'][$key]);
                }
            }
            $this->assign('live_data', $merge_live_data);
            $this->assign('merge_live_data', $merge_live_data['data']);
        }

        $this->assign('mid', $this->mid);
        $this->display();
    }

    /**
     * 会员中心课程--线下课处理
     * @return void
     */
    public function course()
    {
        $tab   = $_GET['tab'];
        $limit = 9;
        if (!$tab) {
            $map['uid']          = $this->mid;
            $map['pay_status']   = 3;
            $map['learn_status'] = ['neq', 2];
            $map['is_del']       = 0;
            $data                = M('zy_order_teacher')->where($map)->findPage($limit);
            foreach ($data["data"] as $key => $value) {
                $data["data"][$key]['course_name'] = M('zy_teacher_course')->where('course_id =' . $value['video_id'])->getField('course_name');
                $data["data"][$key]['tname']       = M('zy_teacher')->where('id =' . $value['tid'])->getField('name');
                $data["data"][$key]['tuid']        = M('zy_teacher')->where('id =' . $value['tid'])->getField('uid');
                $data["data"][$key]['tphone']      = M('user')->where('uid =' . $data["data"][$key]['tuid'])->getField('phone');
                $data["data"][$key]['temail']      = M('user')->where('uid =' . $data["data"][$key]['tuid'])->getField('email');

                $data["data"][$key]['pay_status'] = "已支付";
                $data["data"][$key]['phone']      = M('user')->where('uid =' . $value['uid'])->getField('phone');
                $data["data"][$key]['email']      = M('user')->where('uid =' . $value['uid'])->getField('email');

            }
        } else {
            $data = D('ZyCollection')->myCollection('zy_teacher_course', $limit, intval($this->mid));
            foreach ($data["data"] as $key => $value) {
                $value['t_price']                   = $value['course_price'];
                $value['uid']                       = $value['course_uid'];
                $data["data"][$key]['price']        = getPrice($value, $this->mid, true, false, 4);
                $data["data"][$key]['teacher_name'] = D('ZyTeacher')->getTeacherStrByMap(array('id' => $value['teacher_id']), 'name');
            }
        }
        $this->assign('mid', $this->mid);
        $this->assign('data', $data['data']);
        $this->display();
    }

    /**
     * 会员中心课程--排课处理
     * @return void
     */
    public function ArrCourse()
    {

        $this->display();
    }

    /**
     * 会员中心课程--排课数据
     * @return void
     */
    public function ArrCoursedata()
    {
        $mid    = $this->mid;
        $school = M('school')->where('uid= ' . $mid)->getField('id');
        $data   = array('video_title', 'start');
        $start  = t($_GET['start']);
        $end    = t($_GET['end']);
        if (!empty($start && $end)) {
            $map['start'] = array('BETWEEN', array($start, $end));
        }
        $map['mhm_id']      = $school;
        $map['is_del']      = 0;
        $map['is_activity'] = 1;
        $res                = M('arrange_course')->where($map)->field($data)->select();

        foreach ($res as $key => $val) {
            $tdata[$key]['title']  = $val['video_title'];
            $tdata[$key]['start']  = $val['start'];
            $tdata[$key]['end']    = $val['start'] + 3600;
            $tdata[$key]['allDay'] = false;

        }

        echo json_encode($tdata);
    }

    /**
     * 会员中心课程--添加排课处理
     * @return void
     */
    public function addArrCourse()
    {
        if ($_POST) {
            if (empty($_POST['start'])) {
                $this->error("排课时间不能为空");
            }
            if (empty($_POST['course'])) {
                $this->error("排课课程不能为空");
            }
            if (empty($_POST['maxmannums'])) {
                $this->error("申请并发不能为空");
            }
            if (empty($_POST['reset'])) {
                $this->error("未获得剩余并发");
            }
            $map['uid']          = $this->mid;
            $map['is_del']       = 0;
            $map['pay_status']   = 3;
            $map['stime']        = array('LT', strtotime(t($_POST['start'])));
            $map['etime']        = array('GT', strtotime(t($_POST['start'])) + 3600);
            $res                 = M('zy_order_concurrent')->where($map)->Field('connums')->select();
            $tdata['maxmannums'] = 0;
            foreach ($res as $val) {
                $tdata['maxmannums'] = $tdata['maxmannums'] + $val['connums'];
            }
            if (!$res) {
                $this->error("对不起，请购买并发数目");
            }

            $result['start']       = $data["listingtime"]       = strtotime(t($_POST['start']));
            $data["uctime"]        = strtotime(t($_POST['start'])) + 3600;
            $result['video_title'] = $data["video_title"] = t($_POST['course']);
            $result["maxmannums"]  = $data["maxmannums"]  = intval(t($_POST['maxmannums']));
            $data["notice"]        = t($_POST['notice']);
            $result['is_activity'] = $data["is_activity"] = 0;
            $result['is_del']      = $data["is_del"]      = 0;
            $data["ctime"]         = time();
            $data["notice"]        = t($_POST['notice']);
            $data['type']          = 2;
            $mid                   = $this->mid;
            $data['uid']           = $mid;
            $shoolId               = M('school')->where('uid =' . $mid)->getField('id');
            $result['mhm_id']      = $data['mhm_id']      = $shoolId;
            if (!$shoolId) {
                $this->error('你不是机构管理员');
            }
            $nums['maxmannums'] = 0;
            if ($res) {
                $ever['start']  = strtotime(t($_POST['start']));
                $ever['mhm_id'] = $shoolId;
                $total          = M('arrange_course')->where($ever)->Field('maxmannums')->select();
                if ($total) {
                    foreach ($total as $val) {
                        $nums['maxmannums'] = $nums['maxmannums'] + $val['maxmannums'];
                    }
                }
                if ($tdata['maxmannums'] < intval(t($_POST['maxmannums'] + $nums['maxmannums']))) {
                    $this->error("对不起，你购买的并发数量不足");
                }
            }
            if (!is_numeric($_POST['maxmannums'])) {
                $this->error('并发必须为数字');
            }
            $reset = explode("/", $_POST['reset']);
            if ($data["maxmannums"] > $reset[0]) {
                $this->error('没有足够的并发');
            }
            if (!is_numeric($_POST['maxmannums'])) {
                $this->error('并发必须为数字');
            }

            $video  = M('zy_video');
            $course = M('arrange_course');
            $video->startTrans();
            $res                 = $video->add($data);
            $result['course_id'] = $res;
            $tres                = $course->add($result);
            if (!$res) {
                $video->rollback();
                $this->error('申请失败!');
            }
            if (!$tres) {
                $video->rollback();
                $this->error('申请失败');
            }
            $video->commit(); //成功则提交
            $this->success('申请成功');
        }

        $this->display();
    }

    /**
     * 会员中心课程--添加排课数据的展示
     * @return void
     */
    public function addArrCoursedata()
    {

        $timehour = date("Y-m-d H:0:0", time());
        $start    = strtotime($timehour) + 3600;
        $start    = (t($_GET['start']) < time()) ? $start : t($_GET['start']);

        $end  = t($_GET['end']);
        $nums = M('concurrent')->where('id = 1')->getField('Concurrent_nums');

        if (!empty($start && $end)) {
            $map['start'] = array('BETWEEN', array($start, $end));
        }
        $serth              = array('start', 'maxmannums');
        $map['is_del']      = 0;
        $map['is_activity'] = 1;
        $res                = M('arrange_course')->where($map)->field($serth)->select();
        if (!$res) {
            $res = array();
        }
        $times = intval(($end - $start) / 3600);

        if ($times > 0) {
            for ($i = 0; $i < $times; $i++) {
                $timedada[$i]['start']      = $start + $i * 3600;
                $timedada[$i]['maxmannums'] = 0;
            }
            $res = array_merge($timedada, $res);
            $j   = 0;
            foreach ($res as $k => $val) {
                $tdata[$j]['title'] = $res[$j]['maxmannums'];
                $tdata[$j]['start'] = $res[$j]['start'];
                for ($i = $j + 1; $i < count($res); $i++) {
                    if ($val['start'] == $res[$i]['start']) {
                        $tdata[$j]['title']    = $tdata[$j]['title'] + $res[$i]['maxmannums'];
                        $res[$i]['maxmannums'] = -1000;
                    }
                }
                $j++;
            }
            $data = array();
            $i    = 0;
            foreach ($tdata as $k => $val) {
                if ($val['title'] < 0) {
                    unset($tdata[$k]);
                }
                if ($val['title'] >= 0 && $i < 168) {
                    $data[$i]['title']  = strval($nums - $val['title']) . "/" . $nums;
                    $data[$i]['start']  = strval($val['start']);
                    $data[$i]['end']    = strval($val['start'] + 3600);
                    $data[$i]['allDay'] = false;
                    $i++;
                }
            }
            echo json_encode($data);
        }
    }

    /**
     * 会员中心课程--约课(教师)
     * @return void
     */
    public function teacher_course()
    {
        $this->assign('mid', $this->mid);
        $this->display();
    }

    /**
     * 问答中心--我的问题
     * @return void
     */
    public function getWenda()
    {
        $map           = array();
        $mid           = $this->mid;
        $map['is_del'] = array('EQ', 0);
        $map['uid']    = array('EQ', $mid);

        if ($_GET['p'] && !$_POST['type']) {
            $this->assign('p', $_GET['p']);
            $this->assign('wendatype', 'getWenda');
            $this->display('wenda');
        }

        $data = M('zy_wenda')->where($map)->order('ctime DESC')->findPage(9);
        $this->assign("data", $data['data']);
        $this->assign("wendadata", $data);
        $this->assign("type", $_POST['type']);

        if ($this->is_pc) {
            $data['data'] = $this->fetch('wenda_list');
        } else {
            $data['data'] = $this->fetch('ajax_wendaList');
        }
        echo json_encode($data);
    }

    /**
     * 问答中心--我的回答
     * @return void
     */
    public function getAnswer()
    {
        $map             = array();
        $mid             = $this->mid;
        $map['d.is_del'] = array('EQ', 0);
        $map['d.uid']    = array('EQ', $mid);

        if ($_GET['p'] && !$_POST['type']) {
            $this->assign('p', $_GET['p']);
            $this->assign('wendatype', 'getAnswer');
            $this->display('wenda');
        }

        $data = M("zy_wenda_comment d")->join("`" . C('DB_PREFIX') . "zy_wenda` w ON w.id = d.wid")->field('w.id,w.uid,w.wd_description,w.wd_comment_count,w.wd_help_count,d.uid as duid,d.description,d.ctime,d.id as commentid')->where($map)->findPage(9);
        $this->assign("data", $data['data']);
        $this->assign("wendadata", $data);
        $this->assign("type", $_POST['type']);

        if ($this->pc) {
            $data['data'] = $this->fetch('wenda_list');
        } else {
            $data['data'] = $this->fetch('ajax_wendaList');
        }
        echo json_encode($data);

    }

    /**
     * 会员中心问题--异步处理
     * @return void
     */
    public function getwentilist()
    {
        $limit           = 9;
        $type            = t($_POST['type']);
        $zyQuestionMod   = D('ZyQuestion');
        $zyCollectionMod = D('ZyCollection');

        if ($_GET['p'] && !$_POST['type']) {
            $this->assign('p', $_GET['p']);
            $this->display('wenti');
        }

        if ($type == 'me') {
            $map['uid']       = intval($this->mid);
            $map['parent_id'] = 0;
            $order            = 'ctime DESC';

            $data = $zyQuestionMod->where($map)->order($order)->findPage($limit);
            foreach ($data['data'] as $key => &$value) {
                $value['qst_title']       = msubstr($value['qst_title'], 0, 15);
                $value['qst_description'] = msubstr($value['qst_description'], 0, 153);
                $value['strtime']         = friendlyDate($value['ctime']);
                $value['qcount']          = $zyQuestionMod->where(array('parent_id' => array('eq', $value['id'])))->count();
                if ($value['type'] == 1) {
                    $value['course_title'] = M('zy_video')->where('id=' . $value['oid'])->getField('video_title');
                }
                if ($value['type'] == 2) {
                    $value['course_title'] = M('album')->where('id=' . $value['oid'])->getField('album_title');

                }
            }
        } else if ($type == 'question') {
            $data = $zyQuestionMod->myAnswer($limit, intval($this->mid));

            foreach ($data['data'] as $key => &$value) {
                $value['wenti']['qst_title']       = msubstr($value['wenti']['qst_title'], 0, 15);
                $value['wenti']['qst_description'] = msubstr($value['wenti']['qst_description'], 0, 149);
                $value['wenti']['strtime']         = friendlyDate($value['wenti']['ctime']);
                $value['wenti']['qcount']          = $zyQuestionMod->where(array('parent_id' => array('eq', $value['wenti']['id'])))->count();

                $value['qst_title']       = msubstr($value['qst_title'], 0, 15);
                $value['qst_description'] = msubstr($value['qst_description'], 0, 31);
                $value['qcount']          = $zyQuestionMod->where(array('parent_id' => array('eq', $value['id'])))->count();
                if ($value['type'] == 1) {
                    $value['course_title'] = M('zy_video')->where('id=' . $value['oid'])->getField('video_title');
                }
                if ($value['type'] == 2) {
                    $value['course_title'] = M('album')->where('id=' . $value['oid'])->getField('album_title');
                }
            }
        } else if ($type == 'collect') {
            $data = $zyCollectionMod->myCollection('zy_question', $limit, intval($this->mid));
            foreach ($data['data'] as $key => &$value) {
                $value['qst_title']       = msubstr($value['qst_title'], 0, 15);
                $value['qst_description'] = msubstr($value['qst_description'], 0, 153);
                $value['strtim  e']       = friendlyDate($value['ctime']);
                $value['qcount']          = $zyQuestionMod->where(array('parent_id' => array('eq', $value['id'])))->count();
            }
        }
        $this->assign("data", $data['data']);
        $this->assign("wentidata", $data);
        $this->assign("type", $type);

        if ($this->is_pc) {
            $data['data'] = $this->fetch('wenti_list');
        } else {
            $data['data'] = $this->fetch('ajax_wentiList');
        }
        echo json_encode($data);
        exit;
    }

    /**
     * 会员中心笔记--异步处理
     * @return void
     */
    public function getnotelist()
    {
        $limit = 9;
        $type  = t($_POST['type']);

        if ($_GET['p'] && !$_POST['type']) {
            $this->assign('p', $_GET['p']);
            $this->display('note');
        }

        $zyNoteMod       = D('ZyNote');
        $zyCollectionMod = D('ZyCollection');

        if ($type == 'me') {
            $map['uid']       = intval($this->mid);
            $map['parent_id'] = 0;
            $order            = 'ctime DESC';
            $data             = $zyNoteMod->where($map)->order($order)->findPage($limit);

            foreach ($data['data'] as $key => &$value) {
                $value['note_title']       = msubstr($value['note_title'], 0, 15);
                $value['note_description'] = msubstr($value['note_description'], 0, 150);
                $value['strtime']          = friendlyDate($value['ctime']);
                $value['qcount']           = $zyNoteMod->where(array('parent_id' => array('eq', $value['id'])))->count();
            }
        } else if ($type == 'collect') {
            $data = $zyCollectionMod->myCollection('zy_note', $limit, intval($this->mid));

            foreach ($data['data'] as $key => &$value) {
                $value['note_title']       = msubstr($value['note_title'], 0, 15);
                $value['note_description'] = msubstr($value['note_description'], 0, 150);
                $value['strtime']          = friendlyDate($value['ctime']);
                $value['qcount']           = $zyNoteMod->where(array('parent_id' => array('eq', $value['id'])))->count();
            }
        }

        $this->assign("data", $data['data']);
        $this->assign("notedata", $data);
        $this->assign("type", $type);
        if ($this->is_pc) {
            $data['data'] = $this->fetch('note_list');
        } else {
            $data['data'] = $this->fetch('ajax_noteList');
        }
        echo json_encode($data);
        exit;
    }

    /**
     * 会员中心订单--异步处理
     * @return void
     */
    public function getOrderlist($return = false)
    {
        $type    = t($_GET['type']);
        $orderby = t($_GET['orderby']);
        if ($orderby) {
            if ($orderby != 0) {
                $map['pay_status'] = $orderby;
            }
        }
        $map['uid']    = intval($this->mid);
        $map['is_del'] = intval(0);
        if ($type == 'course') {
            $table = "zy_order_course";
            $this->assign("check_type", 'zy_video');
        } elseif ($type == 'album') {
            $table = "zy_order_album";
            $this->assign("check_type", 'zy_album');
        } elseif ($type == 'teacher') {
            $table = "zy_order_teacher";
            $this->assign("check_type", 'zy_teacher');
        } elseif ($type == 'live') {
            $table = "zy_order_live";
            $this->assign("check_type", 'zy_live');
        } elseif ($type == 'connum') {
            $table = "zy_order_concurrent";
        }
        $order = 'ctime DESC';
//        $size = intval ( getAppConfig ( 'video_list_num', 'page', 6 ) );
        $playtype = "";

        if ($this->is_pc) {
            $size = 10;
        } else {
            $size = 6;
        }
        $data = M($table)->where($map)->order($order)->findPage($size);

        $this->assign('pagecount', $data['totalPages']);

        if ($data['data']) {
            foreach ($data['data'] as $key => &$val) {
                //折扣类型
                $discount_type = array('无折扣', '会员折扣', '限时优惠');
                if ($table == "zy_order_course") {
                    //取得课程信息
                    $video = M('zy_video')->where('id =' . $val['video_id'])->field("id,uid,cover,video_binfo,video_title,mhm_id,teacher_id,v_price,t_price,vip_level,
                    endtime,starttime,limit_discount,uid,teacher_id")->find();

                    $val['mzprice']     = getPrice($video, $this->mid, true, true);
                    $val['video_name']  = msubstr($video['video_title'], 0, 20);
                    $val['cover']       = $video['cover'];
                    $val['video_binfo'] = msubstr($video['video_binfo'], 0, 45);
//                    $val['old_price'] = $video['v_price'];
                    $playtype = '1';

                    //如果是通过班级购买的课程显示为0元购买
                    $order_info = M('zy_order_course')->where(['video_id' => $val['video_id'], 'uid' => $this->mid])->field('order_album_id')->find();
                    if ($order_info['order_album_id']) {
                        $val['price']              = '0.00';
                        $val['order_album_status'] = 1;
                    }
                }
                if ($table == "zy_order_live") {
                    //取得课程信息
                    $val['cover'] = M('zy_video')->where('id =' . $val['live_id'])->getField('cover');
                    $playtype     = '2';

                    $t_price = M('zy_video')->where('id =' . $val['live_id'])->getField("t_price");

                    $val['mzprice']['price'] = $t_price;

                    //如果为管理员/机构管理员自己机构的课程 则免费
                    if (is_admin($this->mid) || $val['is_charge'] == 1) {
                        $val['mzprice']['price'] = 0;
                    }
//
                    if (is_school($this->mid) == $data['data'][$key]['mhm_id'] && $data['data'][$key]['mhm_id']) {

                        $val['mzprice']['price'] = 0;
                    }

                    $val['live_type'] = M('zy_video')->where('id =' . $val['live_id'])->getField("live_type");
                    $val['cuid']      = M('zy_video')->where('id =' . $val['live_id'])->getField("uid");
                    $val['ctid']      = M('zy_video')->where('id =' . $val['live_id'])->getField("teacher_id");

                    //如果是讲师自己的课程 则免费
                    $mid = $this->mid;
                    $tid = M('zy_teacher')->where('uid =' . $mid)->getField('id');
                    if ($mid == intval($val['cuid']) || $tid == $val['ctid']) {
                        $val['mzprice']['price'] = 0;
                    }

//                    $val['old_price'] = M('zy_video')->where('id =' . $val['live_id'])->getField("v_price");;

                    //取得直播名称
                    $val['video_name'] = getVideoNameForID($val['live_id']);

                    //如果是通过班级购买的课程显示为0元购买
                    $order_info = M('zy_order_live')->where(['live_id' => $val['live_id'], 'uid' => $this->mid])->field('order_album_id')->find();
                    if ($order_info['order_album_id']) {
                        $val['price']              = '0.00';
                        $val['order_album_status'] = 1;
                    }
                }
                if ($table == "zy_order_album") {
                    $val['cover'] = M('album')->where('id =' . $val['album_id'])->getField('cover');
                    $playtype     = '3';
                    //取得专辑名称
                    $val['video_name'] = getAlbumNameForID($val['album_id']);
                }
                //取得线下课程信息
                if ($table == "zy_order_teacher") {

                    $video              = M('zy_teacher_course')->where('course_id =' . $val['video_id'])->find();
                    $val['video_name']  = msubstr($video['course_name'], 0, 20);
                    $val['cover']       = $video['cover'];
                    $val['video_binfo'] = msubstr($video['course_binfo'], 0, 45);
                    //价格和折扣
                    $val['old_price'] = $video['course_price'];
                    $val['ctime']     = $val['ctime'];
                    $video['t_price'] = $video['course_price'];
                    $val['mzprice']   = getPrice($video, $this->mid, true, true, 4);
                    $playtype         = '4';
                }
                if ($table == "zy_order_concurrent") {
                    $val['cover']      = M('album')->where('album_id =' . $val['album_id'])->getField('cover');
                    $playtype          = '5';
                    $val['video_name'] = "并发量购买";
                }

                //价格和折扣
                $val['old_price'] = $val['old_price'];
                $val['uname']     = getUserSpace($val['uid'], null, '_blank');
                $val['price']     = $val['price'];
                $val['connums']   = $val['connums'];
                $val['ctime']     = $val['ctime'];

                $val['discount_type'] = $discount_type[$val['discount_type']];
                if ($val['discount_type'] > 0) {
                    $val['discount'] = $val['discount'];
                } else {
                    $val['discount'] = '0';
                }
                $val['strtime'] = friendlyDate($val['ctime']);
            }
            if ($data['html'] == null) {
                $data['html'] = ' ';
            }
            $this->assign("data", $data['data']);
            $this->assign("playtype", $playtype);
            if ($this->is_pc) {
                $html = $this->fetch('order_list');
            } else {
                $html = $this->fetch('ajax_order');
            }
        } else {
            $html = '暂无此类订单。。';
            if ($data['html'] == null) {
                $data['html'] = '';
            }
        }

        $data['data'] = $html;
        if ($return) {

            return $data;
        } else {
            echo json_encode($data);
            exit();
        }
    }

    /**
     *操作订单
     */
    public function operatOrder()
    {

        $ordertype = $_POST['ordertype'];
        if ($ordertype) {
            if ($ordertype == 'course') {
                $table = "zy_order_course";

            } elseif ($ordertype == 'album') {
                $table = "zy_order_album";

            } elseif ($ordertype == 'meeting') {
                $table = "zy_order_teacher";

            } elseif ($ordertype == 'live') {
                $table = "zy_order_live";
            } elseif ($ordertype == 'connum') {
                $table = "zy_order_concurrent";
            }
        }
        $id   = intval($_POST['order_id']);
        $type = t($_POST['type']);
        if (!$id) {
            $this->ajaxReturn(null, "参数错误", 0);
        }

        if ($type == 'cancel') {
            $data['pay_status'] = 2;
            $info               = '取消付款';
        }
        if ($type == 'del') {
            $data['is_del'] = 1;
            $info           = '删除订单';
        }
        if ($type == 'refund') {
            $data['pay_status'] = 4;
            $info               = '申请退款已提交，';
        }

        $res = M($table)->where(array('id' => $id))->save($data);
        if ($res) {
            $info = $info . "成功";
            if ($type == 'refund') {
                $info .= "请等待审核";
            }
            $this->ajaxReturn(null, $info, 1);
        } else {
            $this->ajaxReturn(null, $info . "失败", 0);
        }
    }

    /**
     * 会员中心我的关注--异步处理
     * @return void
     */
    public function getFollowlist()
    {
        $limit = 40;
        $type  = t($_POST['type']);
        if ($type == 'me') {
            $map['uid'] = intval($this->mid);
            $map['tid'] = array(gt, '0');
            $order      = 'ctime DESC';
            $data       = model('Follow')->where($map)->order($order)->findPage($limit);
            foreach ($data['data'] as $key => &$value) {
                $teacher          = D('ZyTeacher')->getTeacherInfo($value['tid']);
                $value['name']    = $teacher['name'];
                $value['title']   = M('zy_teacher_title_category')->where('zy_teacher_title_category_id=' . $teacher['title'])->getField('title') ?: '普通讲师';
                $value['head_id'] = $teacher['head_id'];
                $value['inro']    = mb_substr($teacher['inro'], 0, 20, 'utf-8') . "...";
                $value['strtime'] = friendlyDate($value['ctime']);
            }
        } else {
            $map['uid'] = intval($this->mid);
            $order      = 'ctime DESC';
            $data       = model('Follow')->where($map)->order($order)->findPage($limit);
            foreach ($data['data'] as $key => &$value) {
                if (is_school($value['fid'])) {
                    $school                      = M('school')->where('uid = ' . $value['fid'])->field('id,title,doadmin,info,logo')->find();
                    $data['data'][$key]['title'] = $school['title'];
                    $data['data'][$key]['info']  = msubstr($school['info'], 0, 25);
                    $data['data'][$key]['logo']  = $school['logo'];
                    if ($school['doadmin'] && $school['doadmin'] != 'www') {
                        $data['data'][$key]['domain'] = getDomain($school['doadmin']);
                    } else {
                        $data['data'][$key]['domain'] = U('school/School/index', array('id' => $school['id']));
                    }
                } else {
                    unset($data['data'][$key]);
                    ksort($data['data']);
                }
            }
        }
        $this->assign("data", $data['data']);
        $this->assign("type", $type);

        $data['data'] = $this->fetch('follow_list');
        echo json_encode($data);
        exit;
    }

    public function getTopiclist()
    {

        $type  = t($_POST['type']);
        $limit = 9;
        if ($type == "me") {
            $map['uid']               = $this->mid;
            $map['source_table_name'] = "zy_topic";
            $data                     = M('zy_collection')->where($map)->findPage($limit);
            foreach ($data['data'] as $key => &$value) {
                $data['data'][$key]['topictitle']   = M('zy_topic')->where('id = ' . $value['source_id'])->getField('title');
                $data['data'][$key]['topictitle']   = msubstr($data['data'][$key]['topictitle'], 0, 18);
                $data['data'][$key]['topicdesc']    = M('zy_topic')->where('id = ' . $value['source_id'])->getField('desc');
                $data['data'][$key]['topicdesc']    = msubstr($data['data'][$key]['topicdesc'], 0, 18);
                $data['data'][$key]['dateline']     = M('zy_topic')->where('id = ' . $value['source_id'])->getField('dateline');
                $commentmap['row_id']               = $value['source_id'];
                $commentmap['table']                = 'zy_topic';
                $commentmap['is_del']               = '0';
                $data['data'][$key]['commentcount'] = M('comment')->where($commentmap)->count();
            }
        } else if ($type == "school") {
            $map['uid']               = $this->mid;
            $map['source_table_name'] = 'school';
            $data                     = M('zy_collection')->where($map)->findPage($limit);

            foreach ($data['data'] as $key => &$value) {
                $school                      = M('school')->where('id = ' . $value['source_id'])->field('id,title,doadmin,info,logo')->find();
                $data['data'][$key]['title'] = $school['title'];
                $data['data'][$key]['info']  = msubstr($school['info'], 0, 25);
                $data['data'][$key]['logo']  = $school['logo'];
                if ($school['doadmin'] && $school['doadmin'] != 'www') {
                    $data['data'][$key]['domain'] = getDomain($school['doadmin']);
                } else {
                    $data['data'][$key]['domain'] = U('school/School/index', array('id' => $school['id']));
                }
            }
        } else if ($type == "teacher") {
            $map['uid']               = $this->mid;
            $map['source_table_name'] = 'zy_teacher';
            $data                     = M('zy_collection')->where($map)->findPage($limit);

            foreach ($data['data'] as $key => &$value) {
                $teacher                       = M('zy_teacher')->where('id = ' . $value['source_id'])->field('id,name,title,inro,head_id')->find();
                $data['data'][$key]['tid']     = $teacher['id'];
                $data['data'][$key]['name']    = $teacher['name'];
                $data['data'][$key]['title']   = M('zy_teacher_title_category')->where('zy_teacher_title_category_id=' . $teacher['title'])->getField('title') ?: '普通讲师';
                $data['data'][$key]['inro']    = msubstr($teacher['inro'], 0, 25);
                $data['data'][$key]['head_id'] = $teacher['head_id'];
            }
        }
        $this->assign("data", $data['data']);
        $this->assign("type", $type);

        $data['data'] = $this->fetch('collect_list');
        echo json_encode($data);
        exit;
    }

    /*
     *取消收藏
     */
    public function delCollect()
    {
        $map['collection_id']     = intval($_POST['id']);
        $tableName                = $_POST['tableName'];
        $map['source_table_name'] = $tableName;
        $source_id                = M('zy_collection')->where($map)->getField('source_id');
        $res                      = M('zy_collection')->where($map)->delete();
        if ($res) {
            M('school')->where(array('id' => $source_id))->setDec('collect_num');
            if ($tableName == 'zy_teacher') {
                $credit = M('credit_setting')->where(array('id' => 55, 'is_open' => 1))->field('id,name,score,count')->find();
                if ($credit['score'] < 0) {
                    $ctype = 7;
                    $note  = '取消收藏讲师扣除的积分';
                }
            } else if ($tableName == 'school') {
                $credit = M('credit_setting')->where(array('id' => 51, 'is_open' => 1))->field('id,name,score,count')->find();
                if ($credit['score'] < 0) {
                    $ctype = 7;
                    $note  = '取消收藏机构扣除的积分';
                }
            }
            model('Credit')->addUserCreditRule($this->mid, $ctype, $credit['id'], $credit['name'], $credit['score'], $credit['count'], $note);
            echo 200;
            exit;
        } else {
            echo 500;
            exit;
        }
    }

    /**
     * 会员中心学习记录--异步处理
     * @return void
     */
    public function getlearnlist()
    {
        $limit = 9;
        $uid   = intval($this->mid);
        $map   = array('uid' => $uid, 'is_del' => 0);
        $order = 'ctime DESC';

        $data = M('learn_record')->where($map)->order($order)->findPage($limit);

        foreach ($data['data'] as $key => &$value) {
            $video_section_info           = M('zy_video_section')->where(array('zy_video_section_id' => $value['sid']))->field('pid,title')->find();
            $value['video_chapter_title'] = M('zy_video_section')->where(array('zy_video_section_id' => $video_section_info['pid']))->getField('title');
            $value['video_section_title'] = $video_section_info['title'];
            $value['video_title']         = D('ZyVideo')->getVideoTitleById($value['vid']);
            $value['strtime']             = friendlyDate($value['ctime']);
            $value['time']                = secondsToHour($value['time']);
        }

        $this->assign("data", $data);
        $data['data'] = $this->fetch('learn_list');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步加载我购买的课程
     * @return void
     */
    public function getbuyvideoslist()
    {
        $limit = intval($_POST['limit']);
        $uid   = intval($this->mid);
        $limit = 9;
        //拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';
        $otablename = C('DB_PREFIX') . 'zy_order_course';
        //拼接字段
        $fields = '';
        $fields .= "{$otablename}.`uid`,{$otablename}.`id` as `oid`,";
        $fields .= "{$vtablename}.`video_title`,{$vtablename}.`id`,{$vtablename}.`video_intro`,";
        $fields .= "{$vtablename}.`cover`,{$vtablename}.video_order_count,{$vtablename}.`t_price`";
        //不是通过班级购买的

        $where = "{$otablename}.`is_del`=0 and {$otablename}.`pay_status`=3 and {$otablename}.`uid`={$uid}";
        $data  = M('zy_order_course')->join("{$vtablename} on {$otablename}.`video_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);

        //循环计算课程价格
        foreach ($data['data'] as $key => &$val) {
            $data['data'][$key]['money'] = $val['t_price'];
        }

        $vms = D('ZyVideoMerge')->getList(intval($this->mid), session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        $data['count'] = 10;
        //把数据传入模板
        $this->assign('data', $data['data']);
        //取得数据
        $data['data'] = $this->fetch('_video_my_buy');
        echo json_encode($data);
        exit;
    }

    /**
     * 众筹列表
     */
    public function crowList()
    {
        $type  = intval($_GET['type']);
        $where = '';
        $limit = 12;
        $order = 'ctime desc';
        if ($type == 1) {
            //我发起的
            $map['uid'] = $this->mid;
            $data       = M('crowdfunding')->where($map)->order($order)->findPage($limit);
        } else {
            //拼接两个表名
            $crow     = C('DB_PREFIX') . 'crowdfunding';
            $crowUser = C('DB_PREFIX') . 'crowdfunding_user';
            //拼接字段
            $fields = '';
            $fields .= "{$crow}.`id`,{$crow}.`cover`,{$crow}.`title`,{$crow}.`uid`,{$crow}.`id` as `crow_id`,{$crow}.`price`,{$crow}.`num`,";
            $fields .= "{$crowUser}.`cid`";
            //参与众筹的
            $where = "{$crowUser}.`uid`={$this->mid}";
            $data  = M('crowdfunding_user')->join(" LEFT JOIN {$crow} on {$crow}.`id`={$crowUser}.`cid`")->where($where)->field($fields)->findPage($limit);
        }
        $this->assign('data', $data['data']);
        $data['data'] = $this->fetch('ajaxCrow');
        exit(json_encode($data));

    }

    /**
     * 异步加载我收藏的课程
     * @return void
     */
    public function getcollectvideolist()
    {
        //获取购物车参数
        $vms = D('ZyVideoMerge')->getList($this->mid, session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        //获取已购买课程id
        $buyVideos = M('zy_order_course')->where("`uid`=" . $this->mid . " AND `is_del`=0")->field('video_id')->select();
        foreach ($buyVideos as $key => $val) {
            $buyVideos[$key] = $val['video_id'];
        }
        $this->assign('buyVideos', $buyVideos);
        $limit = 9;
        $uid   = intval($this->mid);
        //拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';
        $ctablename = C('DB_PREFIX') . 'zy_collection';

        $fields = '';
        $fields .= "{$ctablename}.`uid`,{$ctablename}.`collection_id` as `cid`,";
        $fields .= "{$vtablename}.*";
        //拼接条件
        $where = "{$ctablename}.`source_table_name`='zy_video' and {$ctablename}.`uid`={$uid}";
        //取数据
        $data = M('ZyCollection')->join("{$vtablename} on {$ctablename}.`source_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);
        //循环计算课程价格
        foreach ($data['data'] as $key => &$val) {
            $data['data'][$key]['money'] = $val['t_price'];
            if ($val['type'] == 2) {
                unset($data['data'][$key]);
            }

        }
        $vms = D('ZyVideoMerge')->getList(intval($this->mid), session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        //把数据传入模板
        $this->assign('data', $data['data']);
        //取得数据
        $data['data'] = $this->fetch('_video_my_collect');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步加载我上传的课程
     * @return void
     */
    public function getupvideoslist()
    {
        $map['uid']  = $this->mid;
        $map['type'] = 1;
        $limit       = 9;
        $data        = M('zyvideo')->where($map)->findPage($limit);
        //循环计算课程价格
        foreach ($data['data'] as $key => &$val) {
            $data['data'][$key]['money'] = $val['t_price'];
        }
        //把数据传入模板
        $this->assign('data', $data['data']);
        //取得数据
        $data['data'] = $this->fetch('_video_my_upload');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步加载我购买的班级
     * @return void
     */
    public function getbuyalbumslist()
    {
        $limit = 9;
        $uid   = intval($this->mid);
        //拼接两个表名
        $atablename = C('DB_PREFIX') . 'album';
        $otablename = C('DB_PREFIX') . 'zy_order_album';
        //拼接字段
        $fields = '';
        $fields .= "{$otablename}.`uid`,{$otablename}.`id` as `oid`,";
        $fields .= "{$atablename}.`id`,{$atablename}.`album_title`,{$atablename}.`album_category`,{$atablename}.`album_intro`,";
        $fields .= "{$atablename}.`cover`, {$atablename}.`price`";
        //不是通过班级购买的
        $where = "{$otablename}.`is_del`=0 and {$otablename}.`uid`={$uid}";

        $data = M('ZyOrderAlbum')->join("{$atablename} on {$otablename}.`album_id`={$atablename}.`id`")->where($where)->field($fields)->findPage($limit);
        foreach ($data['data'] as $key => $val) {
            $maps['is_del']                = 0;
            $maps['album_id']              = $val['id'];
            $maps['pay_status']            = 3;
            $data['data'][$key]['buy_num'] = M('zy_order_album')->where($maps)->count();
        }
        //把数据传入模板
        $this->assign('data', $data['data']);

        //取得数据
        $data['data'] = $this->fetch('_album_my_buy');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步加载我收藏的班级
     * @return void
     */
    public function getcollectalbumslist()
    {
        $limit = 9;
        $uid   = intval($this->mid);
        //拼接两个表名
        $atablename = C('DB_PREFIX') . 'album';
        $ctablename = C('DB_PREFIX') . 'zy_collection';
        //拼接字段
        $fields = '';
        $fields .= "{$ctablename}.`uid`,{$ctablename}.`collection_id` as `cid`,";
        $fields .= "{$atablename}.`id`,{$atablename}.`album_title`,{$atablename}.`album_category`,{$atablename}.`album_intro`,";
        $fields .= "{$atablename}.`cover`, {$atablename}.`price`";
        //拼接字段
        $where = "{$ctablename}.`source_table_name` = 'album' and {$ctablename}.`uid`={$uid}";

        $data = M('ZyCollection')->join("{$atablename} on {$ctablename}.`source_id`={$atablename}.`id`")->where($where)->field($fields)->findPage($limit);
        foreach ($data['data'] as $key => $val) {
            $maps['is_del']                = 0;
            $maps['album_id']              = $val['id'];
            $maps['pay_status']            = 3;
            $data['data'][$key]['buy_num'] = M('zy_order_album')->where($maps)->count();
        }

        //把数据传入模板
        $this->assign('data', $data['data']);

        //取得数据
        $data['data'] = $this->fetch('_album_my_collect');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步加载我的约课
     * @return void
     */
    public function getbuycourselist()
    {

        $limit      = 9;
        $map['uid'] = $this->mid;
        if ($_POST['ordertype'] == 1) {
            $map['pay_status']   = 3;
            $map['learn_status'] = ['neq', 2];
        } else if ($_POST['ordertype'] == 2) {
            $map['learn_status'] = 2;
        }
        $map['is_del'] = 0;
        $data          = M('zy_order_teacher')->where($map)->findPage($limit);
        foreach ($data["data"] as $key => $value) {
            $data["data"][$key]['course_name'] = M('zy_teacher_course')->where('course_id =' . $value['video_id'])->getField('course_name');
            $data["data"][$key]['tname']       = M('zy_teacher')->where('id =' . $value['tid'])->getField('name');
            $data["data"][$key]['tuid']        = M('zy_teacher')->where('id =' . $value['tid'])->getField('uid');
            $data["data"][$key]['tphone']      = M('user')->where('uid =' . $data["data"][$key]['tuid'])->getField('phone');
            $data["data"][$key]['temail']      = M('user')->where('uid =' . $data["data"][$key]['tuid'])->getField('email');

            /*if ($value['pay_status'] == 1) {
            $data["data"][$key]['pay_status'] = "未支付";
            }
            if ($value['pay_status'] == 2) {
            $data["data"][$key]['pay_status'] = "已取消";
            }
            if ($value['pay_status'] == 3) {
            $data["data"][$key]['pay_status'] = "已支付";
            }*/
            $data["data"][$key]['pay_status'] = "已支付";
            $data["data"][$key]['phone']      = M('user')->where('uid =' . $value['uid'])->getField('phone');
            $data["data"][$key]['email']      = M('user')->where('uid =' . $value['uid'])->getField('email');

        }
        //把数据传入模板
        $this->assign('data', $data['data']);
        //取得数据
        $data['data'] = $this->fetch('_course_my');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步加载我的约课(教师)
     * @return void
     */
    public function getTeachercourselist()
    {
        $limit      = 9;
        $tid        = M("zy_teacher")->where("uid=" . $this->mid)->getField('id');
        $map['tid'] = $tid;
        if ($_POST['ordertype'] == 1) {
            $map['pay_status']   = 3;
            $map['learn_status'] = ['neq', 2];
        } else if ($_POST['ordertype'] == 2) {
            $map['learn_status'] = 2;
        }
        $map['is_del'] = 0;
        $data          = M('zy_order_teacher')->where($map)->findPage($limit);

        foreach ($data["data"] as $key => $value) {
            $data["data"][$key]['course_name']  = M('zy_teacher_course')->where('course_id =' . $value['video_id'])->getField('course_name');
            $data["data"][$key]['student_name'] = M('user')->where('uid =' . $value['uid'])->getField('uname');

            /*if ($value['pay_status'] == 1) {
            $data["data"][$key]['pay_status'] = "未支付";
            }
            if ($value['pay_status'] == 2) {
            $data["data"][$key]['pay_status'] = "已取消";
            }
            if ($value['pay_status'] == 3) {
            $data["data"][$key]['pay_status'] = "已支付";
            }*/
            $data["data"][$key]['pay_status'] = "已支付";
            $data["data"][$key]['phone']      = M('user')->where('uid =' . $value['uid'])->getField('phone');
            $data["data"][$key]['email']      = M('user')->where('uid =' . $value['uid'])->getField('email');

        }
        //把数据传入模板
        $this->assign('data', $data['data']);
        //取得数据
        $data['data'] = $this->fetch('_teacher_course');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步完成我的约课
     * @return void
     */
    public function delCourse()
    {
        $res = M("zy_order_course")->where("id=" . intval($_POST["id"]))->data(array("is_del" => 1))->save();
        if ($res) {
            exit(json_encode(array('status' => '1', 'info' => '删除成功')));
        } else {
            exit(json_encode(array('status' => '0', 'info' => '删除失败')));
        }
    }

    /**
     * 异步加载我购买的直播
     * @return void
     */
    public function getbuyliveslist()
    {

        $uid   = intval($this->mid);
        $limit = 9;
        //拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';
        $otablename = C('DB_PREFIX') . 'zy_order_live';
        //拼接字段
        $fields = '';
        $fields .= "{$otablename}.`learn_status`,{$otablename}.`uid`,{$otablename}.`id` as `oid`,";
        $fields .= "{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_intro`,";
        $fields .= "{$vtablename}.`cover`,{$vtablename}.video_order_count,{$vtablename}.`t_price`";
        //不是通过班级购买的
        $where = "{$otablename}.`is_del`=0 and {$otablename}.`pay_status`=3 and {$otablename}.`uid`={$uid}";
        $data  = M('zy_order_live')->join("{$vtablename} on {$otablename}.`live_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);

        //循环计算课程价格
        foreach ($data['data'] as $key => &$val) {
            $data['data'][$key]['money'] = $val['t_price'];
        }
        $vms = D('ZyVideoMerge')->getList(intval($this->mid), session_id());
        $this->assign('vms', getSubByKey($vms, 'live_id'));
        //把数据传入模板
        $this->assign('data', $data['data']);
        //取得数据
        $data['data'] = $this->fetch('_live_my_buy');
        echo json_encode($data);
        exit;
    }

    /**
     * 异步加载我收藏的直播
     * @return void
     */
    public function getcollectlivelist()
    {
        //获取购物车参数
        $vms = D('ZyVideoMerge')->getList($this->mid, session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        //获取已购买课程id
        $buyVideos = M('zy_order_live')->where("`uid`=" . $this->mid . " AND `is_del`=0")->field('video_id')->select();
        foreach ($buyVideos as $key => $val) {
            $buyVideos[$key] = $val['video_id'];
        }
        $this->assign('buyVideos', $buyVideos);
        $limit = 9;
        $uid   = intval($this->mid);
        //拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';
        $ctablename = C('DB_PREFIX') . 'zy_collection';

        $fields = '';
        $fields .= "{$ctablename}.`uid`,{$ctablename}.`collection_id` as `cid`,";
        $fields .= "{$vtablename}.*";
        //拼接条件
        $where = "{$ctablename}.`source_table_name`='zy_video' and {$ctablename}.`uid`={$uid} and {$vtablename}.`type`=2";
        //取数据
        $data = M('ZyCollection')->join("{$vtablename} on {$ctablename}.`source_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);
        //循环计算课程价格
        foreach ($data['data'] as $key => &$val) {
            $data['data'][$key]['money'] = $val['t_price'];
        }

        $vms = D('ZyVideoMerge')->getList(intval($this->mid), session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        //把数据传入模板
        $this->assign('data', $data['data']);
        //取得数据
        $data['data'] = $this->fetch('_live_my_collect');
        echo json_encode($data);
        exit;
    }

    public function delorder()
    {

        $type = t($_POST['type']);

        $map['id']      = t($_POST['ids']);
        $data['is_del'] = intval(1);
        if ($type == 'course') {
            $table = "zy_order_course";

        } elseif ($type == 'album') {
            $table = "zy_order_album";

        } elseif ($type == 'meeting') {
            $table = "zy_order_teacher";

        } elseif ($type == 'live') {
            $table = "zy_order_live";
        } elseif ($type == 'connum') {
            $table = "zy_order_concurrent";
        }
        $data = M($table)->where($map)->save($data);
        if ($data) {
            $this->mzSuccess("删除成功");
        } else {
            $this->mzError("删除失败");
        }
    }

    public function delCancel()
    {
        $type = t($_POST['type']);

        $map['id']          = t($_POST['ids']);
        $data['pay_status'] = 2;
        if ($type == 'course') {
            $table = "zy_order_course";

        } elseif ($type == 'album') {
            $table = "zy_order_album";

        } elseif ($type == 'meeting') {
            $table = "zy_order_teacher";

        } elseif ($type == 'live') {
            $table = "zy_order_live";
        } elseif ($type == 'connum') {
            $table = "zy_order_concurrent";
        }
        $data      = M($table)->where($map)->save($data);
        $coupon_id = M($table)->where($map)->getField('coupon_id');
        if ($data) {
            if ($coupon_id) {
                M('coupon_user')->where(['id' => $coupon_id])->setField('status', 0);
            }
            $this->mzSuccess("取消成功");
        } else {
            $this->mzError("取消失败");
        }
    }

    public function getrejectinfo()
    {

        $id   = $_POST['id'];
        $type = $_POST['type'];

        if ($type == 'zy_video') {
            $table = "zy_order_course";

        } elseif ($type == 'zy_album') {
            $table = "zy_order_album";

        } elseif ($type == 'zy_live') {
            $table = "zy_order_live";
        }

        $res = M($table)->where('id =' . $id)->getField('reject_info');

        if ($res) {
            $this->ajaxReturn(null, $res, 1);
        } else {
            $this->mzError("未知错误");
        }

    }

    public function withdraw()
    {
        $type               = t($_POST['type']);
        $map['id']          = t($_POST['ids']);
        $data['pay_status'] = 3;

        if ($type == 'course') {
            $table = "zy_order_course";
        } elseif ($type == 'album') {
            $table = "zy_order_album";
        } elseif ($type == 'meeting') {
            $table = "zy_order_teacher";
        } elseif ($type == 'live') {
            $table = "zy_order_live";
        } elseif ($type == 'connum') {
            $table = "zy_order_concurrent";
        }

        $data = M($table)->where($map)->save($data);

        if ($data) {
            $this->mzSuccess("取消申请成功");
        } else {
            $this->mzError("取消申请失败");
        }
    }

    /**
     * 申请退款理由窗口
     * @return void
     */
    public function applyForRefundBox()
    {
        $id   = intval($_GET['id']);
        $from = t($_GET['from']);
        $this->assign('id', $id);
        $type = t($_GET['type']);

        if ($type == 'course') {
            $table = "zy_order_course";
        } elseif ($type == 'album') {
            $table = "zy_order_album";
        } elseif ($type == 'live') {
            $table = "zy_order_live";
        }
        $order_info = M($table)->where(['id' => $id])->field('price,rel_id')->find();
        $pay_into   = M('zy_recharge')->where(['pay_pass_num' => $order_info['rel_id']])->field('pay_type,money')->find();

        if($pay_into['pay_type'] == 'alipay') {
            $pay_type = '支付宝';
        } else if ($pay_into['pay_type'] == 'wxpay' || $pay_into['pay_type'] == 'app_wxpay') {
            $pay_type = '微信';
        } else if ($pay_into['pay_type'] == 'unionpay') {
            $pay_type = '银联';
        } else if ($pay_into['pay_type'] == 'lcnpay') {
            $pay_type = '余额';
        }
        $this->assign('price', $pay_into['money']);
        $this->assign('pay_type', $pay_type);
        $this->assign('type', $type);
        $this->assign('refundConfig', model('Xdata')->get('admin_Config:refundConfig'));
        if ($from == 'h5') {
            $this->ajaxReturn(null, $this->fetch('refund_box'));
        }
        $this->display();
    }

    /**
     * 申请退款处理
     */
    public function doApplyFR()
    {
        $refundConfig = model('Xdata')->get('admin_Config:refundConfig');

        $map['id'] = t($_POST['id']);

        $order_type = t($_POST['order_type']); //0点播1班级2直播
        if ($order_type == 'course') {
            $data['refund_type'] = "0";
            $table               = "zy_order_course";
            $field               = ',order_album_id';
        } elseif ($order_type == 'album') {
            $data['refund_type'] = "1";
            $table               = "zy_order_album";
        } elseif ($order_type == 'live') {
            $data['refund_type'] = "2";
            $table               = "zy_order_live";
        }

        $order_info = M($table)->where($map)->field('ptime' . $field)->find();
        if ($order_type == 'course' || $order_type == 'live') {
            if ($order_info['order_album_id']) {
                $this->mzError("课程通过班级购买，请通过班级退款");
            }
        }

        $order_ptime = time() - $order_info['ptime'];
        $refund_time = $refundConfig['refund_numday'] * 86400;
        if ($order_ptime > $refund_time) {
            $this->mzError("该课程已超过{$refundConfig['refund_numday']}天退款有效期");
        }

        //判断退款、重新退款成功返回状态
        if (intval($_POST['status'])) {
            $this->mzSuccess("");
        }

        $data['refund_reason'] = t($_POST['refund_reason']); //退款原因
        $data['refund_note']   = t($_POST['refund_note']); //退款说明
        $data['voucher']       = t($_POST['voucher']); //退款凭证图片
        $data['order_id']      = $map['id'];
        $data['refund_status'] = 0;
        $data['ctime']         = time();

        $refund_info = M('zy_order_refund')->where(['order_id'=>$data['order_id'],'refund_type'=>$data['refund_type']])->field('id,refund_status')->find();

        if($refund_info['id']){
            $res = M('zy_order_refund')->where(['id'=>intval($refund_info['id'])])->save($data);
        }else{
            $res = M('zy_order_refund')->add($data);
        }

        if ($res) {
            $ret = M($table)->where($map)->save(['pay_status' => 4]);
            if ($ret) {
                $this->mzSuccess("申请成功");
            } else {
                M($table)->where($map)->save(['pay_status' => 3]);
                $this->mzError("申请失败");
            }
        } else {
            $this->mzError("未知错误");
        }
    }

    /**
     * 确认约课完成方法
     * @return void
     */
    public function saveFinished()
    {
        $id                  = intval($_POST['id']);
        $map['learn_status'] = t($_POST['type']);

        $res = M('zy_order_teacher')->where('id=' . $id)->save($map);
        if ($res) {
            exit(json_encode(array('status' => '1', 'info' => '确认成功')));
        } else {
            exit(json_encode(array('status' => '0', 'info' => '确认失败')));
        }
    }

    /**
     * 根据群组Logo的保存路径获取Logo的URL
     *
     * @param string $save_path 群组Logo的保存路径
     * @return string 群组Logo的URL. 给定路径不存在时, 返回默认的群组Logo的URL地址.
     */
    public function logo_path_to_url($save_path, $width = 186, $height = 186)
    {
        $path = getImageUrl($save_path, $width, $height, true);
        if ($save_path != 'default.png') {
            return $path;
        } else {
            return SITE_URL . '/apps/group/_static/images/default.png';
        }

    }

    /**
     * 考试
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-19
     * @return [type] [description]
     */
    public function exams()
    {
        $map['uid'] = $this->mid;
        switch ($_GET['tab']) {
            case '2':
                // 考试记录
                $map['progress']   = -1;
                $map['exams_mode'] = 2;
                $list              = D("ExamsUser", 'exams')->getExamsUserPageList($map);
                break;
            case '3':
                // 错题记录
                // 进度为100%的试卷
                $map['progress'] = 100;
                // 错题数大于0
                $map['wrong_count'] = ['gt', 0];
                $map['exams_mode']  = ['in','1,2,3'];
                $list               = D("ExamsUser", 'exams')->getExamsUserPageList($map);
                break;
            case '4':
                // 题目收藏
                $list = D("ZyCollection",'classroom')->where(['source_table_name'=>'exams_question','uid'=>$this->mid])->findpage();
                if($list['data']){
                    $mod = D("ExamsQuestion",'exams');
                    foreach ($list['data'] as  &$value) {
                        $value['question_info'] =$mod->getQuestionById($value['source_id']);
                    }
                }
                break;
            default:
                // 练习记录
                $map['progress']   = -1;
                $map['exams_mode'] = ['in','1,3'];
                $list              = D("ExamsUser", 'exams')->getExamsUserPageList($map);
                break;
        }
        $this->assign('list', $list);
        if(isAjax()){
            $html = $this->fetch('exams_w3g_ajax');
            echo json_encode(['status'=>1,'data'=>['html'=>$html]]);exit;
        }
        
        // 获取练习记录
        $this->display();
    }

}
