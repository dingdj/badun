<?php
/**
 * 云课堂课程(视频)控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
require_once './api/qiniu/rs.php';
class VideoAction extends CommonAction
{
    protected $video           = null; // 课程模型对象
    protected $category        = null; // 分类数据模型
    protected $cc_video_config = array(); //定义cc配置

    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->video           = D('ZyVideo');
        $this->category        = model('VideoCategory');
        $this->cc_video_config = model('Xdata')->get('classroom_AdminConfig:ccyun');
    }

//    /**
    //     * 课程播放页面
    //     */
    //    public function watch() {
    //        if (! $_GET ['vid']) {
    //            $this->assign ( 'isAdmin', 1 );
    //            $this->error ( '参数错误啦' );
    //        }
    //        $map ['id'] = intval ( $_GET ['vid'] );
    //        $data = $this->video->where ( $map )->find ();
    //        $this->assign ( 'sp_config', $spark_config );
    //        $this->assign ( $data );
    //        $this->display ();
    //    }

    /**
     * 课程播放页面
     */
    public function watch()
    {
        $aid  = intval($_GET['id']);
        $data = M("ZyVideo")->where(array('id' => array('eq', $aid)))->find();

        if (!isset($data) && !$data) {
            $this->assign('isAdmin', 1);
            $this->error('课程不存在!');
        }

        $data['mzprice']    = getPrice($data, $this->mid, true, true);
        $data['isBuyVideo'] = isBuyVideo($this->mid, $data['id']) ? 1 : 0;
        //会员等级是否比当前课程所需会员等级高

        $is_colle = D('ZyCollection')->where(array('uid' => $this->mid, 'source_id' => $data['id'], 'source_table_name' => 'zy_video'))->find();
        if ($is_colle) {
            $data['is_colle'] = 1;
        } else {
            $data['is_colle'] = 0;
        }

        //判断是否是免费
        /*$is_free=0;
        if($data['is_tlimit']==1 && $data['starttime'] < time() && $data['endtime'] > time() && $data['limit_discount'] == 0.00){
        $is_free=1;
        }*/

        // 是否已购买
        $is_buy = D('ZyOrderCourse', 'classroom')->isBuyVideo($this->mid, $aid);

        $is_free = 0;
        if (floatval($data['mzprice']['price']) <= 0 || $is_buy || is_admin($this->mid)) {
            $is_free = 1;
        }
        //目录
        $menu = D('VideoSection')->setTable('zy_video_section')->getNetworkList(0, $aid);

        //获取当前播放的课时
        if ($_GET['s_id']) {
            $sid = M('zy_video_section')->where('zy_video_section_id=' . $_GET['s_id'])->find();
        } else {
            $pid = M('zy_video_section')->where('vid=' . $aid . ' and pid=0')->order('sort asc')->getField('zy_video_section_id');
            $sid = M('zy_video_section')->where('pid=' . $pid)->order('sort asc')->find();
        }

        $map          = array('uid' => $this->mid, 'vid' => $data['id'], 'sid' => $_GET['s_id'], 'is_del' => 0);
        $learn_record = M('learn_record')->where($map)->find();

        $video_data    = M('zy_video_data')->where('id=' . $sid['cid'] . ' and status=1 and is_del=0')->field('video_address,videokey,video_type,type')->find();
        $video_address = $video_data['video_address'];

        $test_time = (int) getAppConfig("video_free_time");

        //如果上传到CC服务器4CC
        if ($video_data['video_type'] == 4) {
            $this->assign("ccvideo_config", $this->cc_video_config);
            $this->assign('videokey', $video_data['videokey']);
            $this->assign('video_upload_room', $video_data['video_type']);
//            $this->assign('upload_room',getAppConfig('upload_room','basic'));//0本地 1七牛 4CC
        } else if ($video_data['video_type'] == 1) {
            // 七牛
            //域名防盗链
            Qiniu_SetKeys(getAppConfig('qiniu_AccessKey', 'qiniuyun'), getAppConfig('qiniu_SecretKey', 'qiniuyun'));
            $mod           = new Qiniu_RS_GetPolicy();
            $video_address = $mod->MakeRequest($video_address);
        } else {
            // 本地
            $secret   = $_SERVER['HTTP_HOST']; // 密钥
            $url_info = parse_url($video_address);
            $path     = $url_info['path']; // 下载文件
            if ($path) {
                if ($video_data['type'] == 4) {
                    $extension = substr(strrchr($path, '.'), 1);
                    // 扩展名不是pdf
                    if ($extension != 'pdf') {
                        $file         = SITE_PATH . $path;
                        $turnFileName = strstr($file, '.', true) . '.pdf';
                        if (!is_file($turnFileName)) {
                            $command = 'PATH=$PATH unoconv -l -f pdf ' . $file . '> /dev/null &';
                            file_put_contents('turn_cmd', $command);
                            exec($command);
                            $this->error('文档正在转码,请稍后查看');exit;
                        }
                        // 更新扩展名
                        $video_address = str_replace($extension, 'pdf', $video_address);
                    }

                } elseif ($video_data['type'] != 3) {
                    // 下载到期时间,time是当前时间,300表示300秒,也就是说从现在到300秒之内文件不过期
                    $expire = time() + 3600;
                    // 用文件路径、密钥、过期时间生成加密串
                    $md5 = base64_encode(md5($secret . $path . $expire, true));
                    $md5 = strtr($md5, '+/', '-_');
                    $md5 = str_replace('=', '', $md5);

                    $video_address = $video_address . '?m=' . $md5 . '&e=' . $expire;
                }

            }
        }

        //播放器
        $player_type = getAppConfig("player_type");
        $mhm_id      = $data['mhm_id'];
        if ($mhm_id) {
            //机构信息
            $mhmData = model('School')->getSchoolInfoById($mhm_id);
            if ($mhmData) {
                //课程数
                $mhmData['count'] = $this->video->where(array('mhm_id' => $mhm_id))->count();
                //机构学生数量
                $mhmData['student'] = model('Follow')->where(array('fid' => $mhmData['uid']))->count();
                //当前用户关注状态
                $mhmData['state'] = model('Follow')->getFollowState($this->mid, $mhmData['uid']);
                //机构域名
                if ($mhmData['doadmin']) {
                    $mhmData['domain'] = getDomain($mhmData['doadmin']);
                } else {
                    $mhmData['domain'] = U('school/School/index', array('id' => $mhmData['school_id']));
                }
                //好评度
                //机构评价（课程）
                $schoolmap['mhm_id']      = $mhm_id;
                $schoolmap['is_del']      = 0;
                $schoolmap['is_activity'] = 1;
                $videoid                  = M('zy_video')->where($schoolmap)->field('id')->select();

                $live_id        = trim(implode(',', array_unique(getSubByKey($videoid, 'id'))), ',');
                $vmap['oid']    = ['in', $live_id];
                $vmap['is_del'] = 0;

                //机构评价（讲师）
                $ostar            = M('zy_review')->where($vmap)->avg('star');
                $tidmap['mhm_id'] = $mhm_id;
                $tidmap['is_del'] = 0;
                $tids             = M('zy_teacher')->where($tidmap)->field('id')->select();
                $tid              = trim(implode(',', array_unique(getSubByKey($tids, 'id'))), ',');

                $vtmap['tid']    = ['in', $tid];
                $vtmap['is_del'] = 0;

                $tstar                     = M('zy_review')->where($vtmap)->avg('star');
                $star                      = ceil(($tstar + $ostar) / 2 / 20) * 20;
                $mhmData['favorable_rate'] = round($star, 2) . '%' ?: 0;
            } else {
                $mhmData = null;
            }
        }
        //讲师信息
        $teacher = M("zy_teacher")->where("id=" . $data["teacher_id"])->find();
        if ($teacher) {
            $data['user']               = $teacher;
            $count                      = model('UserData')->getUserData($data['user']['uid']);
            $data['user']['fans_count'] = $count['follower_count'] ?: 0;
            //当前讲师关注状态
            $fans_state = M('UserFollow')->where(array('uid' => $this->mid, 'tid' => $data['user']['id']))->find();
            if ($fans_state) {
                $state = 1;
            } else {
                $state = 0;
            }
            $data['user']['fans_state'] = $state;
            //讲师粉丝数
            $follow_count = model('Follow')->getFollowCount($data['user']['uid']);
            foreach ($follow_count as $k => &$v) {
                $follow = $v['follower'];
            }
            if (!$follow) {
                $follow = '0';
            }
            $video_count = M('zy_video')->where('is_del=0 and teacher_id=' . $teacher['uid'])->count();
        }
        //资源收藏人
        $source = D('ZyCollection')->field('uid')->where(array('source_id' => $aid, 'source_table_name' => 'zy_video'))->order('ctime desc')->limit(8)->select();
        if ($source) {
            foreach ($source as $item => $value) {
                $userInfo                            = model('User')->getUserInfo($value['uid']);
                $source[$item]['user']['uname']      = $userInfo['uname'];
                $source[$item]['user']['avatar_big'] = $userInfo['avatar_big'];
                unset($userInfo);
            }
        }
        $this->assign('mhmData', $mhmData);
        $this->assign('source', $source);
//        $balance = D("zyLearnc")->getUser($this->mid);
        $this->assign("free", $sid['is_free']);
        $this->assign("video_address", $video_address);
        $this->assign("videoid", $videoid);
        $this->assign("video_type", $video_data['type']);
        $this->assign("upload_room", $video_data['video_type']);
        $this->assign("menu", $menu);
        $this->assign("test_time", $test_time);
        $this->assign("player_type", $player_type);
//        $this->assign('balance', $balance);
        $this->assign('is_free', $is_free);
        $this->assign('vid', $data['id']);
        $this->assign('video_id', $data['video_id']);
        $this->assign('video_title', $data['video_title']);
        $this->assign('video_order_count', $data['video_order_count']);
        $this->assign('price', $data['mzprice']['price']);
        $this->assign('is_colle', $data['is_colle']);
        $this->assign('isBuyVideo', $is_buy);
        $this->assign('utime', $data['utime']);
        $this->assign('listingtime', $data['listingtime']);
        $this->assign('cover', $data['cover']);
        $this->assign("score", $data['video_score'] / 20);
        $this->assign('data', $data);
        $this->assign('aid', $aid);
        $this->assign('sid', $sid['zy_video_section_id']);
        $this->assign('type', 1);
        $this->assign('isphone', isMobile() ? 1 : 0);
        $this->assign('mzbugvideoid', session('mzbugvideoid'));
        $this->assign('mid', $this->mid);
        $this->assign('learn_record', $learn_record);
        $this->assign('follow', $follow);
        $this->assign('video_count', $video_count);
        $this->display();
    }

    /*
     *  增加学习课程积分
     */
    /*public function addWatchCredit() {
    $credit = M('credit_setting')->where('id=9')->field('id,name,score,count')->find();
    if($credit['score'] > 0){
    $type = 6;
    $note = '学习课程获得的积分';
    }
    model('Credit')->addUserCreditRule($this->mid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
    }*/

    /*
     * 记录学习记录
     */
    public function updateLearn()
    {
        $map['uid'] = intval($this->mid);
        $map['vid'] = intval($_POST['vid']);
        $map['sid'] = intval($_POST['sid']);

        $data['time']  = intval($_POST['time']);
        $data['ctime'] = time();
        $total_time    = intval($_POST['totaltime']);

        if ($this->mid) {
            $totaltime = M('learn_record')->where($map)->getField('totaltime');
            //TODO
            if ($totaltime <= $total_time) {
                $data['totaltime'] = $data['time']; //学习总时长
            }
            //$data['totaltime'] = $totaltime ? ( $totaltime + $data['time'] ) : $data['time'];
            if ($totaltime) {
                M('learn_record')->where($map)->save($data);
            } else {
                M('learn_record')->add(array_merge($map, $data));
            }
        }
    }
    /**
     * 课程详情页面
     *
     * @return void
     */
    public function view()
    {
        $this->view_info();
        $this->display();
    }

    private function view_info()
    {
        $id = intval($_GET['id']);
        $videores = M('zy_video')->where('id =' . $id)->field('video_title,video_binfo,listingtime,uctime,is_activity')->find();

        //设置seo详情
        $this->seo['_title'] = $videores['video_binfo'] ? $videores['video_title'].' — '.$videores['video_binfo'] : $videores['video_title'].$this->seo['_bak_title'];
        $this->seo['_meta'] = $videores['video_binfo'] ? : $this->seo['_meta'];

        $share_url = $this->addVideoShare($id, 0);

        $code = t($_GET['code']);

        if ($code) {
            $code = explode('@@@', $code);
            $code = $code[0];

            $video_share = M('zy_video_share')->where(array('tmp_id' => $code))->field('uid,video_id,type,share_url')->find();

            if ($video_share) {
                $data['type']      = $map['type']      = $video_share['type'];
                $data['uid']       = $map['uid']       = $video_share['uid'];
                $data['use_uid']   = $this->mid ? $this->mid : 0;
                $data['video_id']  = $map['video_id']  = $video_share['video_id'];
                $data['ctime']     = time();
                $data['share_url'] = $video_share['share_url'];
                $data['tmp_id']    = $map['tmp_id']    = session_id();
                if ($video_share['uid'] != $this->mid) {
                    $result = M('zy_video_share_user')->where($map)->select();
                    if ($result) {
                        M('zy_video_share_user')->where($map)->save($data);
                    } else {
                        M('zy_video_share_user')->add($data);
                    }
                }
            }

            $mhm_id      = M('user')->where('uid = ' . $video_share['uid'])->getField('mhm_id');
            $this_mhm_id = M('school')->where(array('id' => $mhm_id, 'status' => 1, 'is_del' => 0))->getField('id') ?: 1;
            $this->assign('this_mhm_id', $this_mhm_id);
            unset($data);
            unset($map);
        }
        $map['id']          = $id;
        $map['is_activity'] = ['in','1,5,6,7'];
        $map['is_del']      = 0;
        $map['type']        = 1;
        $map['uctime']      = array('gt', time());
        $map['listingtime'] = array('lt', time());
        if($_GET['is_look'] == 1 && (is_admin($this->mid) || is_school($this->mid))){
            unset($map['is_activity']);
            $this->assign('is_look', $_GET['is_look']);
        }

        if ($videores['uctime'] < time()) {
            $this->error('该课程已下架,请查证该课程下架时间');
        }
        if ($videores['listingtime'] > time()) {
            $this->error('该课程未上架，请查证该课程上架时间');
        }
        if ($videores['is_activity'] == 0) {
            $this->error('该课程待审核');
        }

        $data = D('ZyVideo')->where($map)->find();

        if (!$data) {
            $this->assign('isAdmin', 1);
            $this->error('课程不存在~~~~!!');
        }
        D('ZyGuessYouLike')->opTypeGYL(0, reset(array_filter(explode(',', $data['fullcategorypath']))), $this->mid);

        if ($data['is_tlimit'] == 1 && $data['starttime'] < time() && $data['endtime'] > time()) {
            $data['is_tlimit'] = 1;
        } else {
            $data['is_tlimit'] = 0;
        }
        //总课时
        if ($data['type'] == 1) {
            $smap        = array();
            $smap['vid'] = $data['id'];
            $smap['pid'] = array('neq', 0);
            $count       = M('zy_video_section')->where($smap)->count();
//            if($count <= 0){
            //                $count = 1;
            //            }
            $data['sectionNum'] = $count;
        } elseif ($data['type'] == 2) {
            if ($data['live_type'] == 1) {
                $liveData           = model('Live')->liveSpeed(1, $data['id']);
                $data['sectionNum'] = $liveData['count'];
            } elseif ($data['live_type'] == 3) {
                $liveData           = model('Live')->liveSpeed(3, $data['id']);
                $data['sectionNum'] = $liveData['count'];
            }
        }
        //课程有效期+1
        $data['valid'] = intval(round(($data['uctime'] - $data['ctime']) / 86400));
        //添加围观人数
        D('ZyVideo')->where($map)->setInc('view_nums');
        // 处理数据
        $data['video_score'] = floor($data['video_score'] / 20); // 四舍五入
        $data['video_score'] = number_format($data['video_score'], 1); // 保留小数点后一位数字
        $data['reviewCount'] = D('ZyReview')->getReviewCount(1, intval($data['id']));
        $data['reviewRate']  = D('ZyReview')->getCommentRate(1, intval($data['id']));
        $data_cate           = array_filter(explode(',', $data['fullcategorypath']));
        foreach ($data_cate as $cate_k => $cate) {
            $data['video_category_name'][$cate_k]['name'] = getCategoryName($cate);
            $pid                                          = M('zy_currency_category')->where('zy_currency_category_id = ' . $cate)->getField('pid');
            $data['video_category_name'][$cate_k]['key']  = $cate;
            if ($pid) {
                $data['video_category_name'][$cate_k]['key'] = $pid . ',' . $cate;
                $pid2                                        = M('zy_currency_category')->where('zy_currency_category_id = ' . $pid)->getField('pid');
                if ($pid2) {
                    $data['video_category_name'][$cate_k]['key'] = $pid2 . ',' . $pid . ',' . $cate;
                }
            }
        }

        $data['iscollect'] = D('ZyCollection')->isCollect($data['id'], 'zy_video', intval($this->mid));
        if ($data['type'] == 2) {
            $data['mzprice']['price']    = $data['t_price'];
            $data['mzprice']['oriPrice'] = $data['v_price'];
        } else {
            $data['mzprice'] = getPrice($data, $this->mid, true, true);
        }
        $data['isSufficient']  = D('ZyLearnc')->isSufficient($this->mid, $data['mzprice']['price']);
        $data['isGetResource'] = isGetResource(1, $data['id'], array(
            'video',
            'upload',
            'note',
            'question',
        ));
        $teacher = M("zy_teacher")->where("id=" . $data["teacher_id"])->find();
        if ($teacher) {
            $data['user'] = $teacher;
            $follow_count = model('Follow')->getFollowCount($data['user']['uid']);
            foreach ($follow_count as $k => &$v) {
                $follow = $v['follower'];
            }
            if (!$follow) {
                $follow = '0';
            }

            //当前讲师关注状态
            $fans_state = M('UserFollow')->where(array('uid' => $this->mid, 'tid' => $data['user']['id']))->find();
            if ($fans_state) {
                $state = 1;
            } else {
                $state = 0;
            }
            $data['user']['fans_state'] = $state;

            //讲师等级
            $teacher_title = M('zy_teacher_title_category')->where('zy_teacher_title_category_id=' . $data['user']['title'])->find();
            if ($teacher_title['cover']) {
                $data['user']['teacher_title_cover'] = getCover($teacher_title['cover'], 19, 19);
            }

            $video_count = M('zy_video')->where('is_del=0 and teacher_id=' . $teacher['uid'])->count();
        }
        $section = M('zy_video_section')->where(['pid' => ['neq', 0], 'vid' => $id])->field('is_free,vid')->findAll();
        foreach ($section as $k => $v) {
            if ($v['is_free'] == 1) {
                $data['free_status'] = 1;
            }
        }
        // 课程标签
        $data['video_str_tag'] = array_chunk(explode(',', $data['str_tag']), 3, false);

        $data['balance'] = D("zyLearnc")->getUser($this->mid);
        // 是否已购买
        $data['is_buy'] = D('ZyOrderCourse', 'classroom')->isBuyVideo($this->mid, $id);
        $mhm_id         = $data['mhm_id'];
        if ($mhm_id) {
            //机构信息
            $mhmData = model('School')->getSchoolInfoById($mhm_id);
            if ($mhmData) {
                //课程数
                $mhmData['count'] = $this->video->where(array('mhm_id' => $mhm_id))->count();
                //机构学生数量
                $student = model('Follow')->where(array('fid' => $mhmData['uid']))->count();

                $user  = model('User')->where('mhm_id=' . $mhm_id)->field('uid')->findALL();
                $video = M('zy_order_course')->where('mhm_id=' . $mhm_id)->field('uid')->findALL();
                foreach ($video as $v) {
                    $v      = implode(',', $v);
                    $list[] = $v;
                }
                foreach ($user as $v) {
                    $v     = implode(',', $v);
                    $new[] = $v;
                }
                $user_count = array_merge($list, $new);
                $user_count = count(array_unique($user_count)) ?: 1;

                $mhmData['student'] = $student + $user_count;
                //当前用户关注状态
                $mhmData['state'] = model('Follow')->getFollowState($this->mid, $mhmData['uid']);
                //机构域名
                if ($mhmData['doadmin']) {
                    $mhmData['domain'] = getDomain($mhmData['doadmin']);
                } else {
                    $mhmData['domain'] = U('school/School/index', array('id' => $mhmData['school_id']));
                }
                //好评度
                //机构评价（课程）
                $schoolmap['mhm_id']      = $mhm_id;
                $schoolmap['is_del']      = 0;
                $schoolmap['is_activity'] = 1;
                $videoid                  = M('zy_video')->where($schoolmap)->field('id')->select();

                $live_id        = trim(implode(',', array_unique(getSubByKey($videoid, 'id'))), ',');
                $vmap['oid']    = ['in', $live_id];
                $vmap['is_del'] = 0;

                //机构评价（讲师）
                $ostar            = M('zy_review')->where($vmap)->avg('star');
                $tidmap['mhm_id'] = $mhm_id;
                $tidmap['is_del'] = 0;
                $tids             = M('zy_teacher')->where($tidmap)->field('id')->select();
                $tid              = trim(implode(',', array_unique(getSubByKey($tids, 'id'))), ',');

                $vtmap['tid']    = ['in', $tid];
                $vtmap['is_del'] = 0;

                $tstar                     = M('zy_review')->where($vtmap)->avg('star');
                $star                      = ceil(($tstar + $ostar) / 2 / 20) * 20;
                $mhmData['favorable_rate'] = round($star, 2) . '%' ?: 0;
            } else {
                $mhmData = null;
            }
        }
        //资源收藏人
        $source = D('ZyCollection')->field('uid')->where(array('source_id' => $id, 'source_table_name' => 'zy_video'))->order('ctime desc')->limit(8)->select();
        if ($source) {
            foreach ($source as $item => $value) {
                $userInfo                            = model('User')->getUserInfo($value['uid']);
                $source[$item]['user']['uname']      = $userInfo['uname'];
                $source[$item]['user']['avatar_big'] = $userInfo['avatar_big'];
                unset($userInfo);
            }
        }
        //获取章节
        $video_section_data = D('VideoSection')->setTable('zy_video_section')->getNetworkList(0, $id);
        if(reset(reset($video_section_data)['child'])['is_activity'] != 1){
            $s_id = 0;
        }else{
            $s_id = reset(reset($video_section_data)['child'])['id'] ?: 0;
        }
        $tid                = M('zy_teacher')->where('uid =' . $this->mid)->getField('id');

        if ($tid != null && $tid == $data['teacher_id']) {
            $mybuy = 1;
        }

        if (is_school($this->mid) == $data['mhm_id'] && $data['mhm_id']) {
            $mybuy = 1;
        }

        if ($data['mhm_id'] && ($this->mid == $data['mhm_id'])) {
            $mybuy = 1;
        }

        $commentSwitch = model('Xdata')->get('admin_Config:commentSwitch');
        $switch        = $commentSwitch['course_switch'];

        $url = U('classroom/Video/view', array('id' => $id));
        if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            //微信分享配置
            tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'core', 'OpenSociax', 'jssdk.php')));
            $weixin = model('Xdata')->get('admin_Config:weixin');
            $jssdk = new JSSDK($weixin['appid'], $weixin['appsecret']);
            $signPackage = $jssdk->GetSignPackage();

            $this->assign('is_wx',true);
            $this->assign('signPackage', $signPackage);
        }

        $this->assign('video_section_data', $video_section_data);
        $this->assign('url', $url);
        $this->assign('uid', $this->mid);
        $this->assign('mybuy', $mybuy);
        $this->assign('vid', $id);
        $this->assign('mhmData', $mhmData);
        $this->assign('data', $data);
        $this->assign('s_id', $s_id);
        $this->assign('source', $source);
        $this->assign('follow', $follow);
        $this->assign('switch', $switch);
        $this->assign('share', 1);
        $this->assign('share_url', $share_url);
        $this->assign('video_count', $video_count);

    }

    /**
     * 课程详情页面
     *
     * @return void
     */
    public function view_mount()
    {
        $this->view_info();
        $id  = intval($_GET['id']);
        $mid = explode('L', t($_GET['mid']))[0];

        $chars         = 'JMRZaNTU1bNOXcABIdFVWX2eSA9YhxKhxMmDEG3InYZfDEhxCFG5oPQjOP9QkKhxR9SsGIJtTU5giVqBCJrW29pEhx0MuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $mount_url_str = '';
        for ($i = 0; $i < 4; $i++) {
            $mount_url_str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        if ($mid) {
            $mount = M('zy_video_mount')->where(['vid' => $id, 'mhm_id' => $mid])->getField('vid');
            if (!$mount) {
                $this->error("出错啦。。");
            }
            $this->assign('this_mhm_id', $mid . 'H' . $mount_url_str);
        }

        $this->assign('mount_str', $mid . 'H' . $mount_url_str);
        $this->display('view');
    }

    /**
     * 取得课程目录
     * @param int $return
     * @return void|array
     */
    public function getcatalog()
    {
        $id    = intval($_POST['id']);
        $video = D('ZyVideo')->getVideoById($id);
        $data  = D('VideoSection')->setTable('zy_video_section')->getNetworkList(0, $id);
        $this->assign('video', $video);
        $this->assign('data', $data);
        $this->assign('id', $id);
        $result = $this->fetch('_menu');
        exit(json_encode($result));
    }

    /**
     * 课程(视频)首页页面
     *
     * @return void
     */
    public function index()
    {
        $cate_id = t($_GET['cateId']);
        if ($cate_id > 0) {
            $cateId = explode(",", $cate_id);
        }
        if ($cateId) {
            $title = M("zy_currency_category")->where('zy_currency_category_id=' . end($cateId))->getField("title");
            $this->assign('title', $title);
        }
        $subject_category = M("zy_currency_category")->where('pid=0')->order('sort asc')->field("zy_currency_category_id,title")->select();
        $this->assign('selCate', $subject_category);
        if ($cateId) {
            $selCate = $this->category->getChildCategory($cateId[0]);
            $this->assign('cate', $selCate);
            $this->assign('cate_count', count($selCate));
        }
        if ($cateId[1]) {
            $selChildCate = $this->category->getChildCategory($cateId[1]);
            $this->assign('childCate', $selChildCate);
            $this->assign('childCate_count', count($selChildCate));
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);

        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort asc')->getField('id,title');
        $this->assign('vip_levels', $vip_levels);

        $orders = array(
            'default'      => 'id DESC',
            'saledesc'     => 'video_order_count DESC',
            'saleasc'      => 'video_order_count ASC',
            'scoredesc'    => 'video_score DESC',
            'scoreasc'     => 'video_score ASC',
            'new'          => 'ctime desc',
            't_price'      => 't_price ASC',
            't_price_down' => 't_price DESC',
            'best_sort'    => 'best_sort ASC',
        );
        if (isset($orders[$_GET['orderBy']])) {
            $order = $orders[$_GET['orderBy']];
        } else {
            $order = $orders['default'];
        }
        $time  = time();
        $where = "is_del=0 AND is_mount = 1 AND is_activity IN (1,5,6,7) AND uctime>$time AND listingtime<$time";
        if (isset($_GET['tid'])) {
            $tid = $_GET['tid'];
            $where .= " AND teacher_id = $tid";
        }
        if(t($_GET['vip_id'])){
            $where .= " AND vip_level = {$_GET['vip_id']}";
        }
        if ($_GET['lower']) {
            list($lower, $toper) = explode(',', $_GET['lower']);
            if ($toper && $lower >= 1) {
                $where .= " AND (t_price >= $lower AND t_price <= $toper)";
            }
        }
        if ($_GET['charge'] == 1) {
            $section = M('zy_video_section')->where('is_free=1')->field('vid')->findALL();
            $id      = trim(implode(',', getSubByKey($section, 'vid')));
            $where .= " AND ( `id` IN ($id) )";
        }
        if ($_GET['best'] == 1) {
            $where .= " AND is_best=1 ";
            $order = $orders['best_sort'];
        }
        switch ($_GET['vtype']) {
            case '1':
                $where .= " AND type=1 ";
                break;
            case '2':
                $where .= " AND type=2 ";
                break;
            default:
                break;
        }

        if($_GET['live']){
            if($_GET['vtype']){
                $where .= " AND type=2 ";
            }
            $new_live_ids = model('Live')->getNowLive(true);
            $where .= " AND id IN($new_live_ids)";
        }

        if ($_GET['videofile'] == 1) {
            $where .= " AND videofile_ids != 0";
        }
        if ($_GET['eaxm_id'] == 1) {
            $where .= " AND exam_id != 0 AND type = 1 ";
        }
        if ($cateId > 0) {
            $video_category = implode(',', $cateId);
            $where .= " AND fullcategorypath like '%,$video_category,%'";
        }
        if ($_GET['search']) {
            $search = t($_GET['search']);
            $where .= " AND (video_title like '%$search%' or video_binfo like '%$search%')";
        }
        $mhm_id = intval($_GET['mhm_id']);
        if ($mhm_id > 0) {
            $where .= " AND mhm_id = $mhm_id ";
        }
        if ($_GET['pType'] == 3 || $_GET['pType'] == 2) {
            $oc = $_GET['pType'] == 3 ? '>' : '=';
            if (vipUserType($this->mid) > 0) {
                $vd    = floatval(getAppConfig('vip_discount', 'basic', 10));
                $mvd   = floatval(getAppConfig('master_vip_discount', 'basic', 10));
                $isVip = 1;
            } else {
                $isVip = 0;
            }
            // 查询价格 $oc 于0的数据，当在限时折扣的时候
            $ptWhere = "(is_tlimit=1 AND starttime<{$time} AND endtime>{$time} AND t_price{$oc}0)";
            // 如果是VIP，那么则查询价格 $oc 于0的数据，当不在限时折扣的时候
            if ($isVip) {
                $ptWhere .= " OR ((is_tlimit<>1 OR starttime>{$time} OR endtime<{$time}) AND (is_offical=1 AND v_price*{$mvd}/10{$oc}0) OR (is_offical=0 AND v_price*{$vd}/10{$oc}0))";
            }
            // 查询价格 $oc 于0的数据，当不在限时折扣并且当前用户不是VIP的时候
            $ptWhere .= " OR ((is_tlimit<>1 OR starttime>{$time} OR endtime<{$time}) AND (0={$isVip}) AND v_price{$oc}0)";
            $where .= " AND ({$ptWhere})";
        }

        if ($this->is_pc) {
            $size = intval(getAppConfig('video_list_num', 'page', 12));
        } else {
            $size = 6;
        }
        $data = $this->video->where($where)->order($order)->findPage($size);
        if ($_GET['free']) {
            $data['data'] = $this->video->where($where)->order($order)->select();
        }

        if ($data['data']) {
            $buyVideos = D('zyOrder')->where("`uid`=" . $this->mid . " AND `is_del`=0")->field('video_id')->select();
            //机构名称
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['mhm_info'] = model('School')->where('id = ' . $v['mhm_id'])->field('id,title,doadmin')->find();
                //机构域名
                if ($data['data'][$k]['mhm_info']['doadmin']) {
                    $data['data'][$k]['mhm_info']['domain'] = getDomain($data['data'][$k]['mhm_info']['doadmin']);
                } else {
                    $data['data'][$k]['mhm_info']['domain'] = U('school/School/index', array('id' => $data['data'][$k]['mhm_info']['id']));
                }

                //教师头像和简介
                $teacher                                       = M('zy_teacher')->where(array('id' => $v['teacher_id']))->find();
                $data['data'][$k]['teacherInfo']['teacher_id'] = $teacher['id'];
                $data['data'][$k]['teacherInfo']['name']       = msubstr($teacher['name'], 0, 10, 'utf-8', true);
                $data['data'][$k]['teacherInfo']['inro']       = $teacher['inro'];
                $data['data'][$k]['teacherInfo']['head_id']    = $teacher['head_id'];

                //直播课时处理
                if ($v['type'] == 2) {
                    $live_data                         = model('Live')->liveSpeed($v['live_type'], $v['id']);
                    $data['data'][$k]['live']['now']   = $live_data['now'];
                    $data['data'][$k]['live']['count'] = $live_data['count'];
                }
            }

            foreach ($buyVideos as $key => &$val) {
                $val = $val['video_id'];
            }
            // 计算价格
            foreach ($data['data'] as $key => &$value) {
                $value['mzprice'] = getPrice($value, $this->mid, true, true,$value['type']);
            }
        }
        $this->assign('buyVideos', $buyVideos);
        $vms = D('ZyVideoMerge')->getList($this->mid, session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));

        if ($_GET['free']) {
            $tdata = array();
            foreach ($data['data'] as $k => $v) {
                if ($data['data'][$k]['mzprice']['price'] == 0) {
                    $tdata['data'][] = $data['data'][$k];
                }

            }
            if ($_GET['p']) {
                $page   = $_GET['p'];
                $p      = $page - 1;
                $pfirst = $p * 12;
            } else {
                $page   = 1;
                $pfirst = 0;
            }
            $tsdata['data']      = array_slice($tdata['data'], $pfirst, 12);
            $tdata["count"]      = count($tdata['data']);
            $tdata["totalPages"] = ceil($tdata["count"] / 12);
            $tdata["totalRows"]  = count($tdata['data']);
            $tdata["nowPage"]    = $page;
            $this->assign('listData', $tsdata['data']);
            $this->assign('data', $tdata);
        }

        if (!$_GET['free']) {
            $this->assign('listData', $data['data']);
            $this->assign('data', $data);
        }

        $this->assign('orderBy', $_GET['orderBy']); // 定义排序
        $this->assign('best', $_GET['best']); // 定义收费类型
        $this->assign('pType', $_GET['pType']); // 定义收费类型
        $this->assign('lower', $_GET['lower']); // 定义收费类型
        $this->assign('charge', $_GET['charge']);
        $this->assign('free', $_GET['free']);
        $this->assign('live', $_GET['live']);
        $this->assign('vtype', $_GET['vtype']); // 定义（直播，录像）类型
        $this->assign('videofile', $_GET['videofile']); // 课程有附件
        $this->assign('eaxm_id', $_GET['eaxm_id']); // 课程有附件
        // $this->assign ( 'mhm_id', $_GET ['mhm_id'] ); // 机构ID
        $this->assign('search', $_GET['search']); // 搜索
        $this->assign('mhm_id', $mhm_id); //机构

        //猜你喜欢
        $datas = D('ZyGuessYouLike')->getGYLData(0, $this->mid, 4);
        foreach ($datas as $key => $val) {
            $section = M('zy_video_section')->where(['pid' => ['neq', 0], 'vid' => $val['id']])->field('is_free,vid')->findAll();
            foreach ($section as $k => $v) {
                if ($v['is_free'] == 1) {
                    $datas[$key]['free_status'] = '可试听';
                }
            }
            //机构信息
            $datas[$key]['mhm_info'] = model('School')->where('id = ' . $val['mhm_id'])->field('id,title,doadmin')->find();
            //机构域名
            if ($datas[$key]['mhm_info']['doadmin']) {
                $datas[$key]['mhm_info']['domain'] = getDomain($datas[$key]['mhm_info']['doadmin']);
            } else {
                $datas[$key]['mhm_info']['domain'] = U('school/School/index', array('id' => $datas[$key]['mhm_info']['id']));
            }
            //教师头像和简介
            $teacher                               = M('zy_teacher')->where(array('id' => $val['teacher_id']))->find();
            $datas[$key]['teacherInfo']['name']    = msubstr($teacher['name'], 0, 10, 'utf-8', true);
            $datas[$key]['teacherInfo']['inro']    = $teacher['inro'];
            $datas[$key]['teacherInfo']['head_id'] = $teacher['head_id'];
            //直播课时
            if ($val['type'] == 2) {
                $live_data                    = model('Live')->liveSpeed($val['live_type'], $val['id']);
                $datas[$key]['live']['count'] = $live_data['count'];
                $datas[$key]['live']['now']   = $live_data['now'];
            }
            //如果为管理员/机构管理员自己机构的课程 则免费
            if (is_admin($this->mid) || $val['is_charge'] == 1) {
                $datas[$key]['t_price'] = 0;
            }
            if (is_school($this->mid) == $val['mhm_id'] && $val['mhm_id']) {
                $datas[$key]['t_price'] = 0;

            }
            //如果是讲师自己的课程 则免费
            $mid     = $this->mid;
            $thistid = M('zy_teacher')->where('uid =' . $mid)->getField('id');
            if ($mid == intval($datas[$key]['uid']) || $thistid == $datas[$key]['teacher_id']) {
                $datas[$key]['t_price'] = 0;
            }

            if ($thistid == $datas[$key]['teacher_id'] && $datas[$key]['teacher_id']) {
                $datas[$key]['t_price'] = 0;
            }
        }


        $this->assign('datas', $datas);
        $this->assign('mid', $this->mid);
        $this->display();
    }

    /**
     * 取得课程分类
     */
    public function getCategroy()
    {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $data = $this->category->getChildCategory($id);
        }
        if (empty($data)) {
            $data = null;
        } else {
            if ($_GET['lv'] == 0) {
                foreach ($data as $k => $v) {
                    $data[$k]['selName'] = 'country';
                    $data[$k]['lv']      = intval($_GET['lv']) + 1;
                }
            } elseif ($_GET['lv'] == 1) {
                foreach ($data as $k => $v) {
                    $data[$k]['selName'] = 'pre';
                    $data[$k]['lv']      = intval($_GET['lv']) + 1;
                }
            } elseif ($_GET['lv'] == 2) {
                foreach ($data as $k => $v) {
                    $data[$k]['selName'] = 'citys';
                    $data[$k]['lv']      = intval($_GET['lv']) + 1;
                }
            }
        }

        echo json_encode($data);
        exit;
    }

    /**
     * 取得课程列表
     *
     * @param boolean $return
     *            是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getVideoList($return = false)
    {
        $cate_id = t($_GET['cateId']);
        if ($cate_id > 0) {
            $cateId = explode(",", $cate_id);
        }
        if ($cateId) {
            $title = M("zy_currency_category")->where('zy_currency_category_id=' . end($cateId))->getField("title");
            $this->assign('title', $title);
        }
        $subject_category = M("zy_currency_category")->where('pid=0')->order('sort asc')->field("zy_currency_category_id,title")->select();
        $this->assign('selCate', $subject_category);
        if ($cateId) {
            $selCate = $this->category->getChildCategory($cateId[0]);
            $this->assign('cate', $selCate);
            $this->assign('cate_count', count($selCate));
        }
        if ($cateId[1]) {
            $selChildCate = $this->category->getChildCategory($cateId[1]);
            $this->assign('childCate', $selChildCate);
            $this->assign('childCate_count', count($selChildCate));
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);

        $orders = array(
            'default'      => 'id DESC',
            'saledesc'     => 'video_order_count DESC',
            'saleasc'      => 'video_order_count ASC',
            'scoredesc'    => 'video_score DESC',
            'scoreasc'     => 'video_score ASC',
            't_price'      => 't_price ASC',
            't_price_down' => 't_price DESC',
            'new'          => 'ctime desc',
            'best_sort'    => 'best_sort ASC',
        );
        if (isset($orders[$_GET['orderBy']])) {
            $order = $orders[$_GET['orderBy']];
        } else {
            $order = $orders['default'];
        }

        $time  = time();
        $where = "is_del=0 AND is_activity=1 AND is_mount = 1  AND uctime>$time AND listingtime<$time";
        if (isset($_GET['tid'])) {
            $tid = $_GET['tid'];
            $where .= " AND teacher_id = $tid";
        }
        if ($_GET['lower']) {
            list($lower, $toper) = explode(',', $_GET['lower']);
            if ($toper && $lower >= 1) {
                $where .= " AND (t_price >= $lower AND t_price <= $toper)";
            }
        }
        if ($_GET['charge'] == 1) {
            $section = M('zy_video_section')->where('is_free=1')->field('vid')->findALL();
            $id      = trim(implode(',', getSubByKey($section, 'vid')));
            $where .= " AND ( `id` IN ($id) )";
        }
        if ($_GET['best'] == 1) {
            $where .= " AND is_best=1 ";
            $order = $orders['best_sort'];
        }
        switch ($_GET['vtype']) {
            case '1':
                $where .= " AND type=1 ";
                break;
            case '2':
                $where .= " AND type=2 ";
                break;
            default:
                break;
        }
        if ($_GET['free']) {
            $where .= " AND is_charge = 1";
        }
        if ($_GET['videofile'] == 1) {
            $where .= " AND videofile_ids != 0";
        }
        if ($_GET['eaxm_id'] == 1) {
            $where .= " AND exam_id != 0 AND type = 1 ";
        }
        $cateId = intval($_GET['cateId']);
        if ($cateId > 0) {
            $where .= " AND fullcategorypath like '%,$cateId,%'";
        }
        if ($_GET['search']) {
            $search = t($_GET['search']);
            $where .= " AND (video_title like '%$search%' or video_binfo like '%$search%')";
        }
        $mhm_id = intval($_GET['mhm_id']);
        if ($mhm_id > 0) {
            $where .= " AND mhm_id = $mhm_id ";
        }
        if ($_GET['pType'] == 3 || $_GET['pType'] == 2) {
            $oc = $_GET['pType'] == 3 ? '>' : '=';
            if (vipUserType($this->mid) > 0) {
                $vd    = floatval(getAppConfig('vip_discount', 'basic', 10));
                $mvd   = floatval(getAppConfig('master_vip_discount', 'basic', 10));
                $isVip = 1;
            } else {
                $isVip = 0;
            }
            // 查询价格 $oc 于0的数据，当在限时折扣的时候
            $ptWhere = "(is_tlimit=1 AND starttime<{$time} AND endtime>{$time} AND t_price{$oc}0)";
            // 如果是VIP，那么则查询价格 $oc 于0的数据，当不在限时折扣的时候
            if ($isVip) {
                $ptWhere .= " OR ((is_tlimit<>1 OR starttime>{$time} OR endtime<{$time}) AND (is_offical=1 AND v_price*{$mvd}/10{$oc}0) OR (is_offical=0 AND v_price*{$vd}/10{$oc}0))";
            }
            // 查询价格 $oc 于0的数据，当不在限时折扣并且当前用户不是VIP的时候
            $ptWhere .= " OR ((is_tlimit<>1 OR starttime>{$time} OR endtime<{$time}) AND (0={$isVip}) AND v_price{$oc}0)";
            $where .= " AND ({$ptWhere})";
        }
        $size = 6;
        $data = $this->video->where($where)->order($order)->findPage($size);
        if ($data['data']) {
            $buyVideos = D('zyOrder')->where("`uid`=" . $this->mid . " AND `is_del`=0")->field('video_id')->select();
            //机构名称
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['mhm_info'] = model('School')->where('id = ' . $v['mhm_id'])->field('id,title,doadmin')->find();
                //机构域名
                if ($data['data'][$k]['mhm_info']['doadmin']) {
                    $data['data'][$k]['mhm_info']['domain'] = getDomain($data['data'][$k]['mhm_info']['doadmin']);
                } else {
                    $data['data'][$k]['mhm_info']['domain'] = U('school/School/index', array('id' => $data['data'][$k]['mhm_info']['id']));
                }

                //教师头像和简介
                $teacher                                       = M('zy_teacher')->where(array('id' => $v['teacher_id']))->find();
                $data['data'][$k]['teacherInfo']['teacher_id'] = $teacher['id'];
                $data['data'][$k]['teacherInfo']['name']       = $teacher['name'];
                $data['data'][$k]['teacherInfo']['inro']       = $teacher['inro'];
                $data['data'][$k]['teacherInfo']['head_id']    = $teacher['head_id'];

                //直播课时处理
                if ($v['type'] == 2) {
                    $live_data                         = model('Live')->liveSpeed($v['live_type'], $v['id']);
                    $data['data'][$k]['live']['now']   = $live_data['now'];
                    $data['data'][$k]['live']['count'] = $live_data['count'];

                    //$data['data'][$k]['mzprice']['price'] = $data['data'][$k]['t_price'];
                    /*//如果为管理员/机构管理员自己机构的课程 则免费
                    if (is_admin($this->mid) || $v['is_charge'] == 1) {
                        $data['data'][$k]['mzprice']['price'] = 0;
                    }
                    if (is_school($this->mid) == $v['mhm_id'] && $v['mhm_id']) {
                        $data['data'][$k]['mzprice']['price'] = 0;

                    }
                    //如果是讲师自己的课程 则免费
                    $mid     = $this->mid;
                    $thistid = M('zy_teacher')->where('uid =' . $mid)->getField('id');
                    if ($mid == intval($data['data'][$k]['uid']) || $thistid == $data['data'][$k]['teacher_id']) {
                        $data['data'][$k]['mzprice']['price'] = 0;
                    }

                    if ($thistid == $data['data'][$k]['teacher_id'] && $data['data'][$k]['teacher_id'] != null) {
                        $data['data'][$k]['mzprice']['price'] = 0;
                    }*/
                }
                
                // 计算价格
                $data['data'][$k]['mzprice'] = getPrice($v,$this->mid,true,true);

            }
            foreach ($buyVideos as $key => &$val) {
                $val = $val['video_id'];
            }
            /*$buyVideos = D ( 'zyOrder' )->where ( "`uid`=" . $this->mid . " AND `is_del`=0" )->field ( 'video_id' )->select ();
            //机构名称
            foreach ($data['data'] as $k=>$v) {
            $mhmName = model('School')->getSchoolInfoById($v['mhm_id']);
            $data['data'][$k]['mhmName'] = $mhmName['title'];
            }*/

            $this->assign('listData', $data['data']);

            $html         = $this->fetch('ajax_video');
            $data['data'] = $html;
        }
        if ($return) {
            return $data;
        } else {
            echo json_encode($data);
            exit();
        }
    }

    // 添加一个课程到课程列表
    public function addVideoMerge()
    {
        if (!$this->mid) {
            $this->mzError('需要先登录');
        }
        $id = intval($_GET['id']);
        if (D('zyOrder')->where("`video_id`=$id AND `is_del`=0 AND `uid`=" . $this->mid)->count() > 0) {
            $this->mzError('你已经购买');
        }
        if ($this->video->where("id={$id}")->count() > 0) {
            if (D('ZyVideoMerge')->addVideo($id, $this->mid, session_id())) {
                $this->ajaxReturn(true, '', true);
            }
        }
        $this->ajaxReturn(false, '', false);
    }

    // 删除一个课程从课程列表
    public function delVideoMerge()
    {
        $id = intval($_GET['id']);
        if (D('zyOrder')->where("`video_id`=$id AND `is_del`=0 AND `uid`=" . $this->mid)->count() > 0) {
            $this->mzError('你已经购买');
        }
        if (D('ZyVideoMerge')->delVideo($id, $this->mid, session_id())) {
            $this->ajaxReturn(true, '', true);
        }
        $this->ajaxReturn(false, '', false);
    }

    // 删除购物车中的课程
    public function delVideoMerges()
    {
        if (!$this->mid) {
            $this->mzError('请先登录');
        }

        $map             = array();
        $map['video_id'] = array(
            'IN',
            $_POST['videoIds'],
        );
        $map['uid'] = array(
            'eq',
            $this->mid,
        );
        if (session_id()) {
            $map['tmp_id'] = session_id();
        }

        $rst = model('ZyVideoMerge')->where($map)->delete();
        if ($rst !== false) {
            $this->ajaxReturn(true, '', true);
        }
        $this->ajaxReturn(false, '', false);
    }

    // 购物车
    public function merge()
    {
        if (!$this->mid) {
            $this->assign('isAdmin', 1);
            $this->error("请登录先，客官!");
        }
        import(session_id(), $this->mid);
        $merge_video_list['data']        = D("ZyVideoMerge")->getList($this->mid, session_id());
        $merge_video_list['total_price'] = 0;
        foreach ($merge_video_list['data'] as $key => $value) {
            $merge_video_list['data'][$key]['tlimit_state'] = 0; // 判断是否限时
            $merge_video_list['data'][$key]['video_info']   = D("ZyVideo")->getVideoById($value['video_id']);
            $merge_video_list['data'][$key]['is_buy']       = D("ZyOrder")->isBuyVideo($this->mid, $value['video_id']);
            $merge_video_list['data'][$key]['price']        = getPrice($merge_video_list['data'][$key]['video_info'], $this->mid);
            $merge_video_list['total_price'] += $merge_video_list['data'][$key]['is_buy'] ? 0 : round($merge_video_list['data'][$key]['price'], 2);
            $merge_video_list['data'][$key]['legal'] = $merge_video_list['data'][$key]['video_info']['uctime'] > time() ? 1 : 0;
            if ($merge_video_list['data'][$key]['video_info']['is_tlimit'] == 1 && $merge_video_list['data'][$key]['video_info']['starttime'] <= time() && $merge_video_list['data'][$key]['video_info']['endtime'] >= time()) {
                $merge_video_list['data'][$key]['tlimit_state'] = 1;
            }
        }
        $user_info = D("ZyLearnc", "classroom")->getUser($this->mid);
        $this->assign('user_info', $user_info);
        $this->assign('merge_video_list', $merge_video_list);
        $this->display();
    }

    /**
     * 批量购买课程
     */
    public function buyVideos()
    {
        $post  = $_POST;
        $price = floatval($post['price']); // 总价
        $vids  = $post['vids']; // 课程id
        $uid   = $this->mid;
        if (empty($vids)) {
            $this->error('请勾选要提交的课程');
        }

        $total_price = 0;
        $vidsnum     = "";
        foreach ($vids as $key => $val) {
            $avideos[$val]          = D("ZyVideo")->getVideoById($val);
            $avideos[$val]['price'] = getPrice($avideos[$val], $uid, true, true);
            $videodata              = $videodata . D('ZyVideo')->getVideoTitleById($val) . ",";
            $vidsnum                = $vidsnum . $val . ",";
            // 价格为0的/限时免费的 不加入购物记录
            if ($avideos[$val]['price']['price'] == 0) {
                unset($avideos[$val]);
                continue;
            }

            // 当购买过之后，或者课程的创建者是当前购买者的话，价格为0
            $avideos[$val]['is_buy'] = D("ZyOrder")->isBuyVideo($uid, $val);
            $total_price += ($avideos[$val]['is_buy'] || $avideos[$val]['uid'] == $uid) ? 0 : round($avideos[$val]['price']['price'], 2);
        }
        // 前台post的价格和后台计算的价格不相等，防止篡改价格
        if (bccomp($total_price, $price) != 0) {
            $this->error('亲，可不要随便改价格哦，我们会发现的!');
        }
        // 获取$uid的学币数量
        if (!D('ZyLearnc')->isSufficient($uid, $total_price, 'balance')) {
            $this->error('可支配的学币不足');
        }
        if (!D("ZyLearnc")->consume($uid, $total_price)) {
            $this->error('合并付款失败，请稍后再试');
        }
        // 添加消费记录
        D('ZyLearnc')->addFlows($this->mid, 0, $total_price, $avideos, 'zy_order_video');
        // 添加每个课程的订单数量
        $vidsnum = trim($vidsnum, ",");
        $sql     = "update `" . C("DB_PREFIX") . "zy_video`  set video_order_count=video_order_count+1 where `id` in($vidsnum)";
        M()->query($sql);
        // 添加课程购买记录
        $time = time();
        foreach ($avideos as $key => $val) {
            $insert_value .= "('" . $this->mid . "','" . $val['uid'] . "','" . $val['id'] . "','" . $val['v_price'] . "','" . ($val['price']['discount'] / 10) . "','" . $val['price']['dis_type'] . "','" . $val['price']['price'] . "','0'," . $time . ",0),";
        }
        $query = "INSERT INTO " . C("DB_PREFIX") . "zy_order (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`learn_status`,`ctime`,`is_del`) VALUE " . trim($insert_value, ',');

        $rst             = M()->query($query);
        $map['video_id'] = array(
            'IN',
            $vids,
        );
        $map['uid'] = array(
            'eq',
            $uid,
        );
        $rst = M('zyVideoMerge')->where($map)->delete();
        if ($rst) {
            $s['uid']     = $this->mid;
            $s['is_read'] = 0;
            $s['title']   = "恭喜您购买课程成功";
            $s['body']    = "恭喜您成功购买如下课程：" . trim($videodata, ",");
            $s['ctime']   = time();
            model('Notify')->sendMessage($s);
            $this->success('购买成功');
        } else {
            $this->error('购买失败');
        }
    }

    /**
     * 批量购买课程
     */
    public function delVideos()
    {
        if (!$this->mid) {
            $this->error('请先登录');
        }

        $map             = array();
        $post            = $_POST;
        $map['video_id'] = array(
            'IN',
            $post['vids'],
        );
        $map['uid'] = array(
            'eq',
            $this->mid,
        );
        $rst = M('zyVideoMerge')->where($map)->delete();
        $rst !== false ? $this->success('删除成功') : $this->error('删除失败');
    }

    //
    public function doAddVideo()
    {
        $post = $_POST;
        if (empty($post['video_id'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '课程所包含的视频id有误',
            )));
        }

        if (empty($post['video_title'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '课程标题为空',
            )));
        }

        if (empty($post['video_binfo'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '课程简介为空',
            )));
        }

        if (empty($post['video_tag'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '课程标签为空',
            )));
        }

        if (empty($post['v_price'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '课程价格为空',
            )));
        } else if (floatval($post['v_price']) > 1000 || floatval($post['v_price']) < 0) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '课程价格不符合规定',
            )));
        }
        if (empty($post['cover'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '请上传封面',
            )));
        }

        if (empty($post['video_category'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '课程分类不能为空',
            )));
        }

        if (empty($post['uctime'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '下架时间不能为空',
            )));
        } else if (strtotime($post['uctime']) < time()) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '下架时间不能小于当前时间',
            )));
        }
        $fullcategorypath         = array();
        $fullcategorypath         = explode(',', $post['video_category']);
        $data['fullcategorypath'] = t($post['video_category']);
        $category                 = array_pop($fullcategorypath);
        $category                 = $category == '0' ? array_pop($fullcategorypath) : $category;
        $this->assign('isAdmin', 1);
        if (empty($category)) {
            $this->error('您还没选择课程分类');
        }

        $video_tag              = explode(',', $post['video_tag']);
        $data['video_title']    = t($post['video_title']);
        $data['video_binfo']    = t($post['video_binfo']);
        $data['v_price']        = $post['v_price'];
        $data['cover']          = intval($post['cover']);
        $data['video_category'] = $category;
        $data['videofile_ids']  = isset($post['video_course_ids']) ? intval($post['video_course_ids']) : 0; // 课件id
        $data['listingtime']    = strtotime($post['listingtime']);
        $data['uctime']         = strtotime($post['uctime']);
        $data['uid']            = $this->mid;
        $data['ctime']          = time();
        if ($post['id']) {
            $result = M('zy_video')->where('id=' . $post['id'])->data($data)->save();
        } else {
            $result = M('zy_video')->data($data)->add();
        }
        if ($result) {
            unset($data);
            if ($post['id']) {
                model('Tag')->setAppName('classroom')->setAppTable('zy_video')->deleteSourceTag($post['id']);
                $tag_reslut = model('Tag')->setAppName('classroom')->setAppTable('zy_video')->addAppTags($post['id'], $video_tag);
            } else {
                $tag_reslut = model('Tag')->setAppName('classroom')->setAppTable('zy_video')->addAppTags($result, $video_tag);
            }
            $tag_reslut      = model('Tag')->setAppName('classroom')->setAppTable('zy_video')->addAppTags($result, $video_tag);
            $data['str_tag'] = implode(',', getSubByKey($tag_reslut, 'name'));
            $data['tag_id']  = ',' . implode(',', getSubByKey($tag_reslut, 'tag_id')) . ',';
            $map['id']       = $post['id'] ? $post['id'] : $result;
            M('zy_video')->where($map)->data($data)->save();
            exit(json_encode(array(
                'status' => '1',
                'info'   => '操作成功，等待审核',
            )));
        } else {
            exit(json_encode(array(
                'status' => '0',
                'info'   => '服务器繁忙，请稍后提交',
            )));
        }
    }
    // 购买课程
    /*
     * 1:可以直接观看，用户为管理员，限时免费,价格为0，已经购买过了
     * 2:找不到课程
     * 3:余额扣除失败，可能原因是余额不足
     * 4:购买记录/订单，添加失败
     */
    public function buyOperating()
    {
        if (!$this->mid) {
            $this->mzError('请先登录!');
        }

        $vid = intval($_POST['id']);
        $i   = D('ZyService')->buyVideo(intval($this->mid), $vid);
        if ($i === true) {
            // 记录购买的课程的ID
            session('mzbugvideoid', $vid);
            $this->mzSuccess('购买成功', 'selfhref');
        }
        if ($i === 1) {
            $this->mzError('该课程你不需要购买!');
        } else if ($i === 2) {
            $this->mzError('找不到课程!');
        } else if ($i === 3) {
            $this->mzError('余额不足!');
        } else if ($i === 4) {
            $this->mzError('购买失败!');
        }
    }
    /*
     * 清除上一次购买的课程iD
     */
    public function cleansession()
    {
        session('mzbugvideoid', null);
        echo '';
        exit();
    }

    //下载附件的方法
    public function down()
    {
        //判断课件是否存在
        $id         = intval($_GET['id']);
        $attach_id  = M('zy_video')->where('id = ' . $id . ' and is_del=0')->getField('videofile_ids');
        $attachInfo = model('Attach')->getAttachById($attach_id);
        $file_path  = UPLOAD_PATH . '/' . $attachInfo['save_path'] . $attachInfo['save_name'];

        if (file_exists($file_path) && is_file($file_path)) {
            $file = getAttachUrlByAttachId($attach_id);
            header("location:" . $file);
        } else {
            $this->error('该课程暂无课件下载');
        }
    }

    public function addVideoShare($nvid, $ntype)
    {

        $vid  = $nvid;
        $type = $ntype;
        if ($this->mid) {
            $map['id']          = $vid;
            $map['is_activity'] = 1;
            $map['is_del']      = 0;
            $map['type']        = 1;

            $video_id = D('ZyVideo')->where($map)->getField('id');

            $chars     = 'JMRZaNTUbNOXcABIdFVWXeSAYlKLMmDEGInYZfDElCFGoPQjOPQkKLRSsGIJtTUgiVqBCJrWpELMuFKvPTUVwQRSxCDNOyBWXzAYZ';
            $share_str = $type . "H";
            for ($i = 0; $i < 4; $i++) {
                $share_str .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            $share_str .= "H" . $vid . "H";
            for ($i = 0; $i < 5; $i++) {
                $share_str .= $chars[mt_rand(0, strlen($chars) - 1)];
            }

//            $share_url = U('classroom/Video/view', array('id' => $vid, 'code' => $share_str));
            //
            //            $data['uid'] = $this->mid;
            //            $data['video_id'] = $video_id;
            //            $data['type'] = $type;
            //            $data['ctime'] = time();
            //            $data['tmp_id'] = $share_str;
            //            $data['share_url'] = $share_url;
            //
            //            $share_id = M('zy_video_share')->where(array('uid' => $this->mid, 'video_id' => $video_id))->getField('id');
            //            if ($share_id) {
            //                $res = M('zy_video_share')->where('id=' . $share_id)->save($data);
            //            } else {
            //                $res = M('zy_video_share')->add($data);
            //            }
            $share_url = U('classroom/Video/view', array('id' => $vid, 'code' => $share_str . '@@@'));
        } else {
            $share_url = U('classroom/Video/view', array('id' => $vid));
        }
        return $share_url;
    }

}
