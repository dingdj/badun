<?php
/**
 * 课程api
 * utime : 2016-03-06
 */
class VideoApi extends Api
{
    private $video; // 课程模型对象
    private $category; // 分类数据模型
    private $tableList = array(
        1 => 'zy_question',
        2 => 'zy_review',
        3 => 'zy_note',
    );
    public function __construct()
    {
        parent::__construct();
        $this->video    = M('ZyVideo');
        $this->category = model('VideoCategory');
    }
    protected $error  = ''; //错误信息
    protected $coupon = []; //当前优惠券信息
    /**
     * Eduline获取课程列表接口
     * 参数：
     * page 页数
     * count 每页条数
     * return   课程详情列表
     */
    public function videoList()
    {
        // 销量和评论排序
        $orders = array(
            'default'  => 'video_order_count DESC,video_score DESC,video_comment_count DESC',
            'saledesc' => 'video_order_count DESC',
            'saleasc'  => 'video_order_count ASC',
            'comtdesc' => 'video_comment_count DESC',
            'comtasc'  => 'video_comment_count Asc',
        );
        if (isset($orders[$this->data['orderBy']])) {
            $order = $orders[$this->data['orderBy']];
        } else {
            $order = $orders['default'];
        }

        $time                 = time();
        $where                = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time";
        if( $this->data['vip_id'] ){
            $where .= " AND vip_lveel = " . $this->data['vip_id'];
        }
        $this->data['cateId'] = intval($this->data['cateId']);
        if ($this->data['cateId'] > 0) {
            $idlist = implode(',', $this->category->getVideoChildCategory(intval($this->data['cateId']), 1));
            if ($idlist) {
                $where .= " AND video_category IN($idlist)";
            }

        }

        if ($this->data['pType'] == 2 || $this->data['pType'] == 1) {
            $oc = $this->data['pType'] == 2 ? '>' : '=';
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
                $ptWhere .= " OR ((is_tlimit<>1 OR starttime>{$time} OR endtime<{$time}) AND (v_price*{$mvd}/10{$oc}0) OR (v_price*{$vd}/10{$oc}0))";
            }
            // 查询价格 $oc 于0的数据，当不在限时折扣并且当前用户不是VIP的时候
            $ptWhere .= " OR ((is_tlimit<>1 OR starttime>{$time} OR endtime<{$time}) AND (0={$isVip}) AND v_price{$oc}0)";
            $where .= " AND ({$ptWhere})";
        }
        //机构的课程
        if (isset($this->data['school_id'])) {
            $where .= " AND mhm_id={$this->school_id}";
        }
        $data = $this->video->where($where)->order($order)->limit($this->_limit())->select();
        if (!$data) {
            $this->exitJson(array());
        }
        foreach ($data as &$value) {
            $value['price']       = getPrice($value, $this->mid); // 计算价格
            $value['imageurl']    = getCover($value['cover'], 280, 160);
            $value['video_score'] = round($value['video_score'] / 20); // 四舍五入
            $value['teacher_name'] = D('ZyTeacher', 'classroom')->where(array('id' => $value['teacher_id']))->getField('name');
            $value['buy_count']     = (int) D('ZyOrderCourse', 'classroom')->where(array('video_id' => $value['id']))->count();
            $value['section_count'] = (int) M('zy_video_section')->where(['vid' => $value['id'], 'pid' => ['neq', 0]])->count();
        }
        $this->exitJson($data);
    }

    /**
     * Eduline获取课程详情接口
     * 参数：
     * id  课程id
     */
    public function videoInfo()
    {
        $id        = intval($this->data['id']);
        $map['id'] = array('eq', $id);
        $data      = D('ZyVideo', 'classroom')->where($map)->find();
        if (!$data) {
            $this->exitJson(array(), 10006, '课程不存在');
        }
        // 处理数据
        $data['video_score_rate'] = D('ZyReview', 'classroom')->getCommentRate(1, $id);
        $data['video_score']      = round($data['video_score'] / 20) ?: 5; // 四舍五入
        $data['vip_level']        = intval($data['vip_level']);
        $data['reviewCount']      = D('ZyReview', 'classroom')->getReviewCount(1, $id);

        $data['video_category_name'] = getCategoryName($data['video_category'], true);
        $data['cover']               = getCover($data['cover'], 280, 160);
        $data['iscollect']           = D('ZyCollection', 'classroom')->isCollect($id, 'zy_video', intval($this->mid));
        $data['follower_count']      = (int) D('ZyCollection', 'classroom')->where(['source_table_name' => 'zy_video', 'source_id' => $id])->count() ?: 0;
        $data['section_count']       = D('ZyVideo', 'classroom')->getVideoSectionCount($id);
        $data['mzprice']             = getPrice($data, $this->mid, true, true);
        $data['price']               = $data['mzprice']['price'];
        $data['isSufficient']        = D('ZyLearnc', 'classroom')->isSufficient($this->mid, $data['mzprice']['price']);
        $data['isGetResource']       = isGetResource(1, $id, array(
            'video',
            'upload',
            'note',
            'question',
        ));
        $data['isBuy']       = D('ZyOrderCourse', 'classroom')->isBuyVideo($this->mid, $id) ? 1 : 0;
        $data['is_buy']      = $data['isBuy'];
        $data['is_play_all'] = ($data['isBuy'] || floatval ($data['mzprice']['price']) <= 0) ? 1 : 0;
        //$data ['user'] = M ( 'User' )->getUserInfo ( $data ['uid'] );
        $data['recommend_list'] = $this->recommend(array('type' => 1));
        $data['school_info']    = model('School')->getSchoolInfoById($data['mhm_id']);
        $this->exitJson($data);
    }

    /**
     * @name 推荐课程
     */
    public function recommend($map = array())
    {
        $map['is_del']      = 0;
        $map['is_activity'] = 1;
        $map['is_best']     = 1;
        $map['uctime']      = array('GT', time());
        $map['listingtime'] = array('LT', time());
        $data               = D('ZyVideo', 'classroom')->where($map)->order('endtime,starttime,limit_discount')->limit(3)->select();
        foreach ($data as $key => $val) {
            $data[$key]['price']       = getPrice($val, $this->mid); // 计算价格
            $data[$key]['imageurl']    = getCover($val['cover'], 280, 160);
            $data[$key]['video_score'] = round($val['video_score'] / 20); // 四舍五入
            $data[$key]['type']        = $val['type'];
        }
        return $data ?: [];
    }
    //获取课程目录
    public function getCatalog()
    {
        $id   = intval($this->data['id']);
        $data = D('VideoSection', 'classroom')->setTable('zy_video_section')->getNetworkList(0, $id);
        $res  = [];
        foreach ($data as &$val) {
            $val['zy_video_section_id'] = $val['id'];
            if ($val['child']) {
                $val['child'] = array_values(($val['child']));
                foreach ($val['child'] as &$v) {
                    $video_data    = M('zy_video_data')->where('status =1 and is_del=0 and id=' . $v['cid'])->find();
                    $video_address = $video_data['video_address'];
                    $url_info      = parse_url($video_address);
                    $path          = $url_info['path']; // 下载文件
                    if ($video_data['type'] == 4) {
                        $extension = substr(strrchr($path, '.'), 1);
                        // 扩展名不是pdf
                        if ($extension != 'pdf') {
                            $file         = SITE_PATH . $path;
                            $turnFileName = strstr($file, '.', true) . '.pdf';
                            if (!is_file($turnFileName)) {
                                $command = 'PATH=$PATH unoconv -l -f pdf ' . $file . '> /dev/null &';
                                exec($command);
                                //$this->exitJson((object)array(),0,'文档正在转码中');
                                $video_address = '';
                            }else{
                                // 更新扩展名
                                $video_address = str_replace($extension, 'pdf', $video_address);
                            }
                            
                        }
                        $v['video_address'] = $video_address;
                    } else {
                        $v['video_address'] = $video_address;
                    }
                    $v['type']                = $video_data['type']; //1视频，2音频，3文本，4文档
                    $v['duration']            = $video_data['duration'];
                    $v['is_shiting']          = ($v['is_free'] == '1') ? 1 : 0;
                    $v['zy_video_section_id'] = $v['id'];
                }
            }
            $res[] = $val;
        }
        $this->exitJson($res,1);
    }

    /**
     * 获取图片资源接口
     */
    public function getAttrImage()
    {
        $attid          = intval($this->data['aid']);
        $data['imgurl'] = getAttachUrlByAttachId($attid);
        $this->exitJson($data);
    }

    /**
     * 获取提问/笔记/点评
     * @param integer kztype //数据分类 1:课程;2:班级;3:线下课;4:讲师
     * @param integer kzid //课程或者班级ID
     * @param integer type //分类类型 1:提问,2:点评,3:笔记
     */
    public function render()
    {
        $kztype = intval($this->data['kztype']); //数据分类 1:课程;2:班级;3:线下课;4:讲师
        $kzid   = intval($this->data['kzid']); //课程或者班级ID
        $type   = intval($this->data['type']); //分类类型 1:提问,2:点评,3:笔记

        $stable = parse_name($this->tableList[$type], 1);
        //如果是课程的话就是=，班级就是in
        $map['oid']       = $kzid;
        $map['parent_id'] = 0;
        $map['type']      = $kztype;
        if ($type == 3) {
            //复合查询--如果是他本人就连带私密的也查出来
            if ($this->mid) {
                $where['uid']     = array('eq', $this->mid);
                $where['is_open'] = array('eq', 1);
                $where['_logic']  = 'or';
                $map['_complex']  = $where;
            } else {
                $map['is_open'] = array('eq', 1);
            }
        }
        $data      = M($stable)->where($map)->order('`ctime` DESC')->limit($this->_limit())->select();
        $zyVoteMod = D('ZyVote', 'classroom');
        foreach ($data as &$value) {
            $value['strtime']  = friendlyDate($value['ctime']);
            $value['username'] = getUserName($value['uid']);
            $value['userface'] = getUserFace($value['uid'], 's');
            $value['count']    = $this->getListCount($type, $value['id']);
            if ($type == 2) {
                $value['star'] = $value['star'] / 20;
                //判断时候已经投票了
                $value['isvote']   = $zyVoteMod->isVote($value['id'], 'zy_review', $this->mid) ? 1 : 0;
                $value['username'] = intval($value['is_secret']) ? '*****' : $value['username'];
            } else if ($type == 3) {
                $value['note_description'] = msubstr($value['note_description'], 0, 44);
                if ($value['type'] == 1) {
//是课程
                    $video_title          = M('zy_video')->where('id=' . $value['oid'] . ' and is_del=0')->getField('video_title');
                    $value['video_title'] = $video_title ? $video_title : $this->exitJson(array(), 0, '班级找不到了');
                } else {
                    $video_title          = M('album')->where('id=' . $value['oid'] . ' and is_del=0')->getField('album_title');
                    $value['video_title'] = $video_title ? $video_title : $this->exitJson(array(), 0, '班级找不到了');
                }
            }
            $value['username'] = msubstr($value['username'], 0, 8);
        }
        $this->exitJson($data);
    }

    private function getListCount($type, $id)
    {
        $stable           = parse_name($this->tableList[$type], 1);
        $map['parent_id'] = array('eq', $id);
        $count            = M($stable)->where($map)->order('`ctime` DESC')->count();
        return $count;
    }

    //取课程/班级分类
    public function getVideoGroup()
    {
        $cateId = intval($this->data['cateId']);
        $selCat = $this->category->getTreeById(0, $cateId);
        // 循环取出所有下级分类
        $datalist = array();
        foreach ($selCat['list'] as &$val) {
            $val['childlist'] = $this->category->getChildCategory($val['zy_video_category_id'], 1);
            array_push($datalist, $val);
        }
        $this->exitJson($datalist);

    }
    //获取提问评论列表
    public function questionDetail()
    {
        $pid   = intval($this->data['pid']); //获取笔记ID
        $qtype = intval($this->data['qtype']); //获取笔记类型
        $where = array(
            'parent_id' => $pid,
            'type'      => $qtype,
        );
        $data = M("zy_question")->where($where)->select();
        foreach ($data as &$val) {
            $val['userinfo'] = model('User')->getUserInfo($val['uid']);
            $val['userface'] = getUserFace($val['uid'], 'm');
        }
        $this->exitJson($data);
    }
    //添加课程/班级提问
    public function addQuestion()
    {
        $data['parent_id']       = intval($this->data['pid']);
        $data['qst_title']       = t($this->data['title']);
        $data['qst_description'] = t($this->data['content']);
        $data['type']            = intval($this->data['kztype']); //提问类型【1:课程;2:班级;】
        $data['uid']             = intval($this->mid);
        $data['oid']             = intval($this->data['kzid']); //对应的ID【班级ID/课程ID】
        $data['qst_source']      = '手机端';
        $data['ctime']           = time();

        if (!trim($data['qst_title'])) {
            $data['qst_title'] = msubstr($data['qst_description'], 0, 14);
        }
        if (!$data['uid']) {
            $this->exitJson(array(), 10015, '添加问题需要先登录');
        }
        if (!$data['qst_description']) {
            $this->exitJson(array(), 10015, '请输入问题内容');
        }
        $i = M('ZyQuestion')->add($data);
        if ($i) {
            //更改班级或课程的总提问数
            if (intval($_POST['kztype']) == 1) {
                $_data['video_question_count'] = array('exp', '`video_question_count` + 1');
                //课程
                M('ZyVideo')->where(array('id' => array('eq', $data['oid'])))->save($_data);
            } else {
                $_data['album_question_count'] = array('exp', '`album_question_count` + 1');
                //班级
                M('Album')->where(array('id' => array('eq', $data['oid'])))->save($_data);
            }
            $_data['qst_comment_count'] = array('exp', '`qst_comment_count` + 1');
            M('zy_question')->where(array('id' => array('eq', $data['parent_id'])))->save($_data);
            $this->exitJson($i);
        } else {
            $this->exitJson($i);
        }
    }

    /**
     * 编辑提问
     * @return void
     */
    public function editQuestion()
    {
        $data['id']              = intval($this->data['id']);
        $data['qst_title']       = t($this->data['title']);
        $data['qst_description'] = t($this->data['content']);

        if (!$this->mid) {
            $this->exitJson(array(), 10016, '编辑问题需要先登录');
        }
        if (!trim($data['id'])) {
            $this->exitJson(array(), 10016, '问题不存在');
        }
        if (!trim($data['qst_description'])) {
            $this->exitJson(array(), 10016, '问题内容不能为空');
        }

        $i = M('ZyQuestion')->save($data);
        if ($i === false) {
            $this->exitJson(array(), 10016, '修改失败');
        }
        $this->exitJson(true);
    }
    //获取笔记评论列表
    public function noteDetail()
    {
        $pid   = intval($this->data['pid']); //获取笔记ID
        $ntype = intval($this->data['ntype']); //获取笔记类型
        $where = array(
            'parent_id' => $pid,
            'type'      => $ntype,
        );
        $data = M("zy_note")->where($where)->select();
        foreach ($data as &$val) {
            $val['userinfo'] = model('User')->getUserInfo($val['uid']);
            $val['userface'] = getUserFace($val['uid'], 'm');
        }
        $this->exitJson($data);
    }
    //添加课程/班级笔记
    public function addNote()
    {
        $data['parent_id']        = intval($this->data['pid']);
        $data['type']             = intval($this->data['kztype']); //
        $data['uid']              = intval($this->mid);
        $data['oid']              = intval($this->data['kzid']); //对应的ID【班级ID/课程ID】
        $data['is_open']          = intval($this->data['is_open']);
        $data['note_source']      = '手机端';
        $data['note_title']       = filter_keyword(t($this->data['title']));
        $data['note_description'] = filter_keyword(t($this->data['content']));
        $data['ctime']            = time();
        if (!trim($data['note_title'])) {
            $data['note_title'] = msubstr($data['note_description'], 0, 14);
        }
        if (!$data['uid']) {
            $this->exitJson(array(), 10017, '添加笔记需要先登录');
        }
        if (!$data['oid']) {
            $this->exitJson(array(), 10017, '请选择课程或班级');
        }
        if (!$data['note_title']) {
            $this->exitJson(array(), 10017, '请输入笔记标题');
        }
        if (!$data['note_description']) {
            $this->exitJson(array(), 10017, '请输入笔记内容');
        }
        $i = M('ZyNote')->add($data);
        if ($i) {
            //更改班级或课程的总提问数
            if (intval($_POST['kztype']) == 1) {
                $_data['video_note_count'] = array('exp', '`video_note_count` + 1');
                //课程
                M('ZyVideo')->where(array('id' => array('eq', $data['oid'])))->save($_data);
            } else {
                $_data['album_note_count'] = array('exp', '`album_note_count` + 1');
                //班级
                M('Album')->where(array('id' => array('eq', $data['oid'])))->save($_data);
            }
            $_data['note_comment_count'] = array('exp', '`note_comment_count` + 1');
            M('zy_note')->where(array('id' => array('eq', $data['parent_id'])))->save($_data);
            //session('mzaddnote'.$data['oid'].$data['type'],time()+180);
            $this->exitJson($i);
        } else {
            $this->exitJson(array(), 10017, '添加失败');
        }
    }
    //编辑笔记接口
    public function editNote()
    {
        $data['id']               = intval($this->data['id']);
        $data['note_description'] = t($this->data['title']);
        $data['note_title']       = t($this->data['content']);
        if (!$this->mid) {
            $this->exitJson(array(), 10018, '编辑笔记需要先登录');
        }

        if (!trim($data['id'])) {
            $this->exitJson(array(), 10018, '笔记不存在');
        }
        if (!trim($data['note_description'])) {
            $this->exitJson(array(), 10018, '笔记内容不能为空');
        }

        $i = M('ZyNote')->save($data);
        if ($i === false) {
            $this->exitJson(array(), 10018, '修改失败');
        }
        $this->exitJson(true);
    }

    //添加点评api
    public function addReview()
    {
        //查看此人是否已经购买此课程//班级
        if (intval($this->data['kztype']) == 1) {
            //课程
            //判断是否是自己添加的课程
            if (M('zy_video')->where(['uid' => $this->mid, 'id' => $this->kzid])->count() > 0) {
                $this->exitJson(array(), 10019, '不能评价自己创建的课程');
            }
            $isbuy = D('ZyService', 'classroom')->checkVideoAccess($this->mid, intval($this->data['kzid']));
            //$isbuy = isBuyVideo($this->mid,intval($this->data['kzid']));
            //$isbuy = D('ZyOrderCourse','classroom')->isBuyVideo($this->mid,intval($this->data['kzid']));
            if (!$isbuy) {
                $this->exitJson(array(), 10019, '需要购买之后才能点评');
            }
        } else if (intval($this->data['kztype']) == 2) {
            //班级
            $isbuy = isBuyAlbum($this->mid, intval($this->data['kzid']));
            if (!$isbuy) {
                $this->exitJson(array(), 10019, '需要购买之后才能点评');
            }
        } else if (intval($this->data['kztype']) == 3) {
            //线下课
            $isbuy = M('zy_order_teacher')->where(array('uid' => $this->mid, 'video_id' => intval($this->data['kzid'])))->getField('pay_status');
            if ($isbuy == 3) {
                $this->exitJson(array(), 10019, '需要购买之后才能点评');
            }
        }

        //每个人只能点评一次
        $count = M('ZyReview')->where(array('oid' => intval($this->data['kzid']), 'parent_id' => 0, 'uid' => $this->mid, 'type' => array('eq', intval($this->data['kztype']))))->count();
        /*if($count){
        $this->exitJson( array() ,10019,'已经点评了');
        }*/

        $data['parent_id']          = 0;
        $data['star']               = intval($this->data['score']) * 20; //分数
        $data['type']               = intval($this->data['kztype']); //
        $data['uid']                = intval($this->mid);
        $data['is_secret']          = intval($this->data['is_secret']);
        $data['oid']                = intval($this->data['kzid']); //对应的ID【班级ID/课程ID】
        $data['review_source']      = '手机客户端';
        $data['review_description'] = filter_keyword(t($this->data['content']));
        $data['ctime']              = time();
        $data['tid']                = M('zy_video')->where(['id' => $this->data['kzid']])->getField('teacher_id') ?: 0;
        $data['skill']              = in_array((int) $this->data['skill'], [1, 2, 3]) ? (int) $this->data['skill'] : 1;
        $data['professional']       = in_array((int) $this->data['professional'], [1, 2, 3]) ? (int) $this->data['professional'] : 1;
        $data['attitude']           = in_array((int) $this->data['attitude'], [1, 2, 3]) ? (int) $this->data['attitude'] : 1;
        if (!$data['uid']) {
            $this->exitJson(array(), 10019, '评价需要先登录');
        }
        if (!$data['star']) {
            $this->exitJson(array(), 10019, '请给课程打分');
        }
        if (!$data['review_description']) {
            $this->exitJson(array(), 10019, '请输入评价内容');
        }
        $i = M('ZyReview')->add($data);
        if ($i) {
            //点评之后 要计算此班级的总评分
            $star = M('ZyReview')->where(array('oid' => intval($this->data['kzid']), 'parent_id' => 0, 'type' => array('eq', intval($this->data['kztype']))))->Avg('star');
            if (intval($this->data['kztype']) == 1) {
                $_data['video_score']         = intval($star);
                $_data['video_comment_count'] = array('exp', '`video_comment_count` + 1');
                //课程
                M('ZyVideo')->where(array('id' => array('eq', $data['oid'])))->save($_data);
                //操作积分
                $video_type = M('ZyVideo')->where(array('id' => array('eq', $data['oid'])))->getField('type');
                if ($video_type == 1) {
                    model('Credit')->getCreditInfo($this->mid, 7);
                } else {
                    model('Credit')->getCreditInfo($this->mid, 13);
                }
            } else if (intval($this->data['kztype']) == 2) {
                $_data['album_score']         = intval($star);
                $_data['album_comment_count'] = array('exp', '`album_comment_count` + 1');
                //班级
                M('Album')->where(array('id' => array('eq', $data['oid'])))->save($_data);
            }
            //session('mzaddreview',time()+180);
            $this->exitJson($i);
        } else {
            $this->exitJson(array(), 10019, '评价失败');
        }
    }

    //加载我购买点的课程
    public function getBuyVideosList()
    {
        $uid = intval($this->data['uid']) ? intval($this->data['uid']) : $this->mid;
        // 拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';
        $otablename = C('DB_PREFIX') . 'zy_order_course';
        // 拼接字段
        $fields = '';
        $fields .= "{$otablename}.`learn_status`,{$otablename}.`uid`,{$otablename}.`id` as `oid`,";
        $fields .= "{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_intro`,{$vtablename}.video_score,";
        $fields .= "{$vtablename}.`cover`,{$vtablename}.`video_address`,{$vtablename}.`teacher_id`,{$vtablename}.video_order_count,{$vtablename}.t_price";
        // 不是通过班级购买的
        $where = "{$otablename}.`is_del`=0 and {$otablename}.`uid`={$uid} AND {$otablename}.pay_status = 3 AND $vtablename.type= 1";

        $data = M('zy_order_course')->join("{$vtablename} on {$otablename}.`video_id`={$vtablename}.`id`")->where($where)->field($fields)->limit($this->_limit())->select();
        foreach ($data as &$val) {
            $val['price']         = $val['t_price']; //getPrice ( $val, $this->mid ); // 计算价格
            $val['cover']         = $val['imageurl']         = getCover($val['cover'], 280, 160);
            $val['type']          = 1;
            $val['video_score']   = round($val['video_score'] / 20); // 四舍五入
            $val['teacher_name']  = D('ZyTeacher', 'classroom')->where(array('id' => $val['teacher_id']))->getField('name') ?: '';
            $val['buy_count']     = (int) D('ZyOrderCourse', 'classroom')->where(array('video_id' => $val['id']))->count();
            $val['section_count'] = (int) M('zy_video_section')->where(['vid' => $val['id'], 'pid' => ['neq', 0]])->count();
        }
        $data ? $this->exitJson($data) : $this->exitJson([]);
    }
    // 我收藏的课程
    public function getCollectVideoList()
    {
        //获取购物车参数
        $vms = D('ZyVideoMerge', 'classroom')->getList($this->mid, session_id());
        //获取已购买课程id
        $buyVideos = D('zyOrder', 'classroom')->where("`uid`=" . $this->mid . " AND `is_del`=0")->field('video_id')->select();
        foreach ($buyVideos as $key => $val) {
            $buyVideos[$key] = $val['video_id'];
        }
        $limit = 9;

        $uid = intval($this->mid);
        //拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';
        $ctablename = C('DB_PREFIX') . 'zy_collection';

        $fields = '';
        $fields .= "{$ctablename}.`uid`,{$ctablename}.`collection_id` as `cid`,";

        $fields .= "{$vtablename}.*";
        //拼接条件
        $where = "{$ctablename}.`source_table_name`='zy_video' and {$ctablename}.`uid`={$uid}";
        if (isset($this->data['type']) && in_array($this->type, [1, 2])) {
            $where .= " AND {$vtablename}.type = {$this->type}";
        }
        $where .= " AND {$vtablename}.is_del = 0";
        //取数据
        $data = M('ZyCollection')->join("{$vtablename} on {$ctablename}.`source_id`={$vtablename}.`id`")->where($where)->field($fields)->limit($this->_limit())->select();
        //循环计算课程价格
        foreach ($data as &$val) {
            $val['price']         = getPrice($val, $this->mid);
            $val['imageurl']      = getCover($val['cover'], 280, 160);
            $val['video_score']   = round($val['video_score'] / 20); // 四舍五入
            $val['iscollect']     = 1;
            $val['school_info']   = model('School')->getSchoolInfoById($val['mhm_id']);
            $val['live_id']       = $val['id'];
            $val['teacher_name']  = D('ZyTeacher', 'classroom')->where(array('id' => $val['teacher_id']))->getField('name') ?: '';
            $val['buy_count']     = (int) D('ZyOrderCourse', 'classroom')->where(array('video_id' => $val['id']))->count();
            $val['section_count'] = (int) M('zy_video_section')->where(['vid' => $val['id'], 'pid' => ['neq', 0]])->count();
        }
        $data = $data ?: [];
        $this->exitJson($data);
    }

    /**
     * 删除操作
     * 1:购买的;2:收藏的;3：上传的---审核中;4:上传的---已发布
     * @param int $return
     * @return void|array
     */
    public function delalbum()
    {
        $id    = intval($this->data['id']);
        $type  = intval($this->data['type']);
        $rtype = intval($this->data['rtype']);

        if ($rtype == 1) {
            $this->delbuyalandvi($id, $type);
        } else if ($rtype == 2) {
            $this->delcollectalandvi($id, $type);
        } else if ($rtype == 3) {
            $this->delalbumorvideo($id, $type);
        } else if ($rtype == 4) {
            $this->delalbumorvideo($id, $type);
        }
    }

    /**
     * 删除购买的班级和课程 <--type   1:课程;2:班级;-->
     * @param int $return
     * @return void|array
     */
    private function delbuyalandvi($id, $type)
    {
        $map['id']      = array('eq', $id);
        $data['is_del'] = 1;
        if ($type == 1) {
            $i = M('ZyOrder')->where($map)->save($data);
        } else {
            $i = M('ZyOrderAlbum')->where($map)->save($data);
        }
        if ($i === false) {
            $this->exitJson(array(), 10016, '对不起，删除失败！');
        } else {
            $this->exitJson(true);
        }
    }

/**
 * 添加笔记、提问的评论
 * type 1:提问,2:点评,3:笔记
 * @return void
 */
    public function tongwen()
    {
        $types = array(
            1 => 'ZyQuestion',
            2 => 'ZyNote',
            3 => 'ZyNote',
        );
        if (!$this->mid) {
            $this->exitJson(array(), 10017, "请登陆后操作");
        }
        $rid    = intval($this->data['rid']);
        $type   = intval($this->data['type']);
        $stable = $types[$type];
        // 是否已经点击过
        $log = array('uid' => $this->mid, 'source_id' => $rid, 'source_table_name' => $stable);
        if (M('zan_logs')->where($log)->count() > 0) {
            $num = '-1';
            M('zan_logs')->where($log)->delete();
        } else {
            $num          = '+1';
            $log['ctime'] = time();
            M('zan_logs')->add($log);
        }
        if ($type == 1) {
            $data['qst_help_count'] = array('exp', 'qst_help_count' . $num);
        } else {
            $data['note_help_count'] = array('exp', 'note_help_count' . $num);
        }
        $i = M($stable)->where(array('id' => array('eq', $rid)))->save($data);
        if ($i) {
            //查出被评论人的uid和内容
            $finfo = M($stable)->where(array('id' => array('eq', $rid)))->find();
            if (empty($reply_id)) {
                $fid = $finfo['uid'];
            } else {
                $fid = $reply_id;
            }
            model('Message')->doCommentmsg($this->mid, $fid, $finfo['id'], 0, 'zy_question', 0, limitNumber($finfo['qst_description'], 30), $content);
            $this->exitJson(true);
        } else {
            $this->exitJson(array(), 10017, "对不起，操作失败！");
        }
    }

    /**
     * 处理投票
     * @return bool
     */
    public function doreviewvote()
    {
        $kztype = 5;
        $kzid   = intval($this->data['kzid']);
        $type   = intval($this->data['type']);
        $uid    = intval($this->mid);
        if ($kztype <= 0) {
            $this->exitJson(array(), 10018, '投票资源错误');
        }

        if ($uid) {
            $this->exitJson(array(), 10018, '投票需要登录');
        }
        $zyVoteMod = D('ZyVote', 'classroom');
        $stable    = $zyVoteMod->_collType[$kztype];
        if ($type > 0) {
            //取消投票
            $i = $zyVoteMod->delvote($kzid, $stable, $uid);
            if ($i) {
                $this->exitJson(array(), 0, "已取消投票");
            } else {
                $this->exitJson(array(), 10018, '取消投票失败！');
            }
        } else {
            //投票
            $i = $zyVoteMod->addvote(array(
                'uid'               => $uid,
                'source_id'         => $kzid,
                'source_table_name' => $stable,
                'ctime'             => time(),
            ));
            if ($i) {
                $this->exitJson(array(), 0, "投票成功");
            } else {
                $this->exitJson(array(), 10018, '投票失败');
            }
        }
    }

    /**
     * classroom/Public/collect
     * 收藏功能
     * 班级收藏/课程收藏/提问收藏/笔记收藏/点评收藏
     *  1=>'album',//班级收藏
     *    2=>'zy_video',//课程收藏
     * @param int $type 0:取消收藏;1:收藏;
     * @return bool
     */
    public function collect()
    {
        $zyCollectionMod = D('ZyCollection', 'classroom');
        $type            = intval($this->data['type']); //0:取消收藏;1:收藏;
        $sctype          = intval($this->data['sctype']); //班级收藏/课程收藏/提问收藏/笔记收藏/点评收藏/线下课收藏
        $source_id       = intval($this->data['source_id']); //资源ID
        if ($sctype <= 0) {
            $this->exitJson(array(), 10023, '收藏资源错误');
        }
        $data['uid']               = intval($this->mid);
        $data['source_id']         = intval($source_id);
        $data['source_table_name'] = $zyCollectionMod->_collType[$sctype];
        $data['ctime']             = time();
        if ($type) {
            $i = $zyCollectionMod->addcollection($data);
            if ($i === false) {
                $this->exitJson(array(), 10023, $zyCollectionMod->getError());
            } else {
                //操作积分
                $video_type = M('ZyVideo')->where(array('id' => $source_id))->getField('type');
                if ($video_type == 1) {
                    model('Credit')->getCreditInfo($this->mid, 3);
                } else {
                    model('Credit')->getCreditInfo($this->mid, 11);
                }
                $this->exitJson(true);
            }
        } else {
            $i = $zyCollectionMod->delcollection($data['source_id'], $data['source_table_name'], $data['uid']);
            if ($i === false) {
                $this->exitJson(array(), 10023, $zyCollectionMod->getError());
            } else {
                //操作积分
                $video_type = M('ZyVideo')->where(array('id' => $source_id))->getField('type');
                if ($video_type == 1) {
                    model('Credit')->getCreditInfo($this->mid, 49);
                } else {
                    model('Credit')->getCreditInfo($this->mid, 50);
                }
                $this->exitJson(true);
            }
        }
    }

    // 添加一个课程到购物车
    public function addVideoMerge()
    {
        if (!$this->mid) {
            $this->exitJson(array(), 10040, '请登录');
        }
        $id = intval($this->data['id']);
        if (D('zyOrder', 'classroom')->where("`video_id`=$id AND `is_del`=0 AND `uid`=" . $this->mid)->count() > 0) {
            $this->exitJson(array(), 10040, '你已经购买过了！');
        }
        if ($this->video->where("id={$id}")->count() > 0) {
            if (D('ZyVideoMerge', 'classroom')->addVideo($id, $this->mid, session_id())) {
                $this->exitJson(true);
            }
        }
        $this->exitJson(array(), 10040, '对不起，课程已在购物车中');
    }

    // 删除购物车中的课程
    public function delVideoMerges()
    {
        if (!$this->mid) {
            $this->exitJson(array(), 10041, '需要先登录');
        }

        $map             = array();
        $videoIds        = trim($this->data['videoIds'], ',');
        $map['video_id'] = array(
            'IN',
            $videoIds,
        );
        $map['uid'] = array(
            'eq',
            $this->mid,
        );
        //       if (session_id ()){
        //           $map ['tmp_id'] = session_id ();
        // }
        $rst = D('ZyVideoMerge', 'classroom')->where($map)->delete();
        if ($rst == false) {
            $this->exitJson(true);
        }
        $this->exitJson(array(), 10041, '对不起，删除失败');
    }

    /**
     * 购物车批量付款
     */
    public function buyVideos()
    {
        $post = $this->data;
        $vids = $post['vids']; // 课程id
        $vids = explode(",", $vids);
        $uid  = $this->mid;
        if (empty($vids)) {
            $this->exitJson((object) [], 0, '请勾选要提交的课程');
        }

        $total_price = 0;
        $vidsnum     = "";
        $vid         = 0;
        foreach ($vids as $key => $val) {
            $vid        = $val;
            $sql        = '(time_limit = 0 or time_limit >= ' . time() . ')';
            $pay_status = (int) M('zy_order_course')->where(array('uid' => intval($this->mid), 'video_id' => $val, '_string' => $sql))->getField('pay_status');

            if ($pay_status === 3) {
                $this->exitJson((object) [], 0, '该课程你已经购买,无需重复购买');
            } else if ($pay_status == 4){
                $this->exitJson((object) [], 0, '该课程正在申请退款');
            }

            $avideos[$val]          = D("ZyVideo", 'classroom')->getVideoById($val);
            $avideos[$val]['price'] = getPrice($avideos[$val], $uid, true, true);
            $videodata              = $videodata . D('ZyVideo', 'classroom')->getVideoTitleById($val) . ",";
            $vidsnum                = $vidsnum . $val . ",";
            if ($avideos[$val]['price']['price'] <= 0 || $avideos[$val]['is_charge'] == 1) {
                $this->addOrder($avideos[$val], $avideos[$val]['price'], array(), 'video');
            } else {
                // 当购买过之后，或者课程的创建者是当前购买者的话，价格为0
                $avideos[$val]['is_buy'] = D("ZyOrderCourse", 'classroom')->isBuyVideo($uid, $val);
                $total_price += ($avideos[$val]['is_buy'] || $avideos[$val]['uid'] == $uid) ? 0 : round($avideos[$val]['price']['price'], 2);
            }
        }

        //需要付费
        if ($total_price > 0) {
            //同步更新过期的支付订单
            model('Coupon')->checkOverdueOrder($this->mid);
            //使用优惠券
            $this->coupon_id = (int) $this->coupon_id ?: 0;
            if ($this->coupon_id) {
                $total_price = $this->useCoupon($this->coupon_id, $total_price);
                if ($total_price === false) {
                    $this->exitJson((object) [], 0, $this->error);
                }
                if($total_price === 0){
                    $coupon_id = M('coupon_user')->where(['id'=>$this->coupon_id])->getField('cid');
                    $vtype = 'zy_video';
                    $res = D('ZyVideo','classroom')->addOrder($vid,$vtype,$coupon_id);
                    if($res){
                        $order_info = $this->getSourceInfo($vid);
                        $this->exitJson(['is_free' => 1, 'order_info' => $order_info], 1, '购买成功');
                    } else {
                        $this->exitJson((object) [], 0, '购买失败');
                    }
                }
            }
            $ext_data = [
                'coupon_id' => $this->coupon_id,
                'dis_type'  => $this->coupon['type'],
                'price'     => $total_price,
            ];
            $order = D('ZyService', 'classroom')->buyOnlineVideo(intval($this->mid), $vid, $ext_data);
            if ($order === 2) {
                $this->exitJson((object) [], 0, '购买课程已经下架');
            }
            if ($order === true) {
                $pay_pass_num = date('YmdHis', time()) . mt_rand(1000, 9999) . mt_rand(1000, 9999);

                //购买订单生成成功
                $pay_id = D('ZyRecharge', 'classroom')->addRechange(array(
                    'uid'          => $this->mid,
                    'type'         => 1,
                    'money'        => $total_price,
                    'note'         => "{$this->site['site_keyword']}在线教育-购买课程：{$videodata}",
                    'pay_type'     => $this->pay_for == 'wxpay' ? 'app_wxpay' : $this->pay_for,
                    'pay_pass_num' => $pay_pass_num,
                ));
                if (!$pay_id) {
                    $this->exitJson(array(), 0, '操作异常');
                }

                $pay_data['is_free'] = 0;
                $pay_for             = in_array($this->pay_for, array('alipay', 'wxpay','lcnpay')) ? [$this->pay_for] : ['alipay', 'wxpay','lcnpay'];
                foreach ($pay_for as $p) {
                    switch ($p) {
                        case "alipay":
                            $pay_data['alipay'] = $this->alipay(array(
                                'vid'          => $vid,
                                'total_fee'    => $total_price,
                                'out_trade_no' => $pay_pass_num,
                                'vtype'        => 'zy_video',
                                'subject'      => "{$this->site['site_keyword']}在线教育-购买课程：{$videodata}",
                                'coupon_id'    => $this->coupon_id,
                            ), 'video');
                            break;
                        case "wxpay":
                            $pay_data['wxpay'] = $this->wxpay([
                                'vid'          => $vid,
                                'total_fee'    => $total_price * 100,
                                'out_trade_no' => $pay_pass_num,
                                'vtype'        => 'zy_video',
                                'subject'      => "{$this->site['site_keyword']}在线教育-购买课程：{$videodata}",
                                'coupon_id'    => $this->coupon_id,
                            ], 'video');
                            break;
                        case "lcnpay":
                            $res = $this->lcnpay([
                                'vid'          => $vid,
                                'total_fee'    => $total_price,
                                'out_trade_no' => $pay_pass_num,
                                'vtype'        => 'zy_video',
                                'subject'      => "购买课程：{$videodata}",
                                'coupon_id'    => $this->coupon_id,
                            ], 'video');
                            if($res === true){
                                $this->exitJson([], 1,"购买成功");
                            }else{
                                $this->exitJson([], 0,$res);
                            }
                            break;
                    }
                }
                $this->exitJson($pay_data, 1);

            }
            $this->exitJson((object) [], 0, '订单生成失败,请重新尝试');
        }
        $order = D('ZyService', 'classroom')->buyOnlineVideo(intval($this->mid), $vid);
        if ($order) {
            $s['uid']     = $this->mid;
            $s['is_read'] = 0;
            $s['title']   = "恭喜您购买课程成功";
            $s['body']    = "恭喜您成功购买课程：" . trim($videodata, ",");
            $s['ctime']   = time();
            model('Notify')->sendMessage($s);
            $order_info = $this->getSourceInfo($vid);
            //操作积分
            model('Credit')->getCreditInfo($this->mid, 2);
            $this->exitJson(['is_free' => 1, 'order_info' => $order_info], 1, '购买成功');
        } else {
            $this->exitJson((object) [], 0, '购买失败');
        }
    }
    private function addOrder($video, $prices, $ext_data, $type)
    {
        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id' => $video['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid' => $teacher_uid))->getField('uid');
        $data        = array(
            'uid'          => $this->mid,
            'video_id'     => $video['id'],
            'price'        => $prices['price'],
            'learn_status' => 0,
            'ctime'        => time(),
            'is_del'       => 0,
            'pay_status'   => 3,
            'time_limit'   => time() + 129600 * floatval($video['term']),
            'mhm_id'       => $video['mhm_id'],
        );
        if ($type == "video") {
            $data['muid']           = $teacher_uid;
            $data['old_price']      = $prices['oriPrice'];
            $data['discount']       = round($prices['oriPrice'] - $prices['price'], 2);
            $data['discount_type']  = 3;
            $data['order_album_id'] = 0;
            $data['order_type']     = 0;
            $data['term']           = $video['term'];
            $data['coupon_id']      = isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0;
            return M('zy_order_course')->add($data);
        } else {
            $data['tid'] = $video['teacher_id'];
            return M('zy_order_teacher')->add($data);
        }
    }
    /**
     * 微信支付
     */
    protected function wxpay_old($data)
    {
        require_once SITE_PATH . '/api/pay/wxpay/WxPay.php';
        $input        = new WxPayUnifiedOrder();
        $out_trade_no = $data['out_trade_no'] . 'h' . date('YmdHis', time()) . mt_rand(1000, 9999); //stristr
        $attr         = json_encode(array('vid' => $data['vid'], 'vtype' => $data['vtype'], 'coupon_id' => $data['coupon_id']));
        $input->SetBody($data['body']);
        $input->SetAttach($attr); //自定义数据
        $input->SetDevice_info('APP');
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($data['money']);
        $input->SetNotify_url('http://' . $_SERVER['HTTP_HOST'] . '/api/pay/wxpay/notify.php');
        $input->SetTrade_type('APP');
        $notify = new NativePay();
        $result = $notify->GetPayUrl($input);
        if (!$result['prepay_id']) {
            $this->exitJson((object) [], 0, '暂不能使用微信支付');
        }
        $input = array(
            'noncestr'  => '' . $result['nonce_str'],
            'prepayid'  => '' . $result['prepay_id'], //下单时微信服务器得到nonce_str和prepay_id参数。
            'appid'     => WxPayConfig::APPID,
            'package'   => 'Sign=WXPay',
            'partnerid' => WxPayConfig::MCHID,
            'timestamp' => time(),
        );

        $input['sign'] = $this->wxSignWithMd5($input, WxPayConfig::KEY);

        $iosPay = sprintf($this->clientpay_url, $input['appid'], $input['noncestr'], $input['partnerid'], $input['prepayid'], $input['timestamp'], $input['sign']);
        $return = [
            'ios'    => $iosPay,
            'public' => $input,
        ];
        return $return;
    }

    //微信sign加密方法
    private function wxSignWithMd5($param, $wxkey)
    {
        ksort($param);
        $sign = '';
        foreach ($param as $key => $value) {
            if ($value && $key != 'sign' && $key != 'key') {
                $sign .= $key . '=' . $value . '&';
            }
        }
        $sign .= 'key=' . $wxkey;
        $sign = strtoupper(md5($sign));

        return $sign;
    }
    /**
     * @name 获取资源信息
     */
    protected function getSourceInfo($source_id = 0)
    {
        $info = [];
        //课程订单
        $info = D('ZyVideo', 'classroom')->where(['id' => $source_id])->find();
        if ($info) {
            // 处理数据
            $info['video_score']         = round($info['video_score'] / 20); // 四舍五入
            $info['vip_level']           = intval($info['vip_level']);
            $info['reviewCount']         = D('ZyReview', 'classroom')->getReviewCount(1, intval($info['id']));
            $info['video_title']         = $info['video_title'];
            $info['video_intro']         = $info['video_intro'];
            $info['video_category_name'] = getCategoryName($info['video_category'], true);
            $info['cover']               = getCover($info['cover'], 280, 160);
            $info['iscollect']           = D('ZyCollection', 'classroom')->isCollect($info['id'], 'zy_video', intval($this->mid));
            $info['follower_count']      = (int) D('ZyCollection', 'classroom')->where(['source_table_name' => 'zy_video', 'source_id' => $source_id])->count() ?: 0;
            $info['mzprice']             = getPrice($info, $this->mid, true, true);
            $info['isSufficient']        = D('ZyLearnc', 'classroom')->isSufficient($this->mid, $info['mzprice']['price']);
            $info['isGetResource']       = isGetResource(1, $info['id'], array(
                'video',
                'upload',
                'note',
                'question',
            ));
            $info['isBuy']       = D('ZyOrder', 'classroom')->isBuyVideo($this->mid, $source_id);
            $info['is_play_all'] = ($info['isBuy'] || floatval($info['mzprice']['price']) <= 0) ? 1 : 0;
            $info['school_info'] = model('School')->getSchoolInfoById($info['mhm_id']);
        }
        return $info;
    }
    /**
     * @name 使用优惠券
     */
    private function useCoupon($coupon_id, $price)
    {
        if ($coupon_id && $price) {
            //检测优惠券是否可以使用
            $coupon = model('Coupon')->canUse($coupon_id, $this->mid);
            if (!$coupon) {
                $this->error = '该优惠券已经无法使用';
                return false;
            }
            $this->coupon = $coupon;
            //优惠券类型是否符合
            if (!in_array($coupon['type'], [1, 2, 5])) {
                $this->error = '该优惠券不能用于购买课程';
                return false;
            }
            switch ($coupon['type']) {
                case "1":
                    //价格低于门槛价 || 至少支付0.01
                    if ($coupon['maxprice'] != '0.00' && $price <= $coupon['maxprice']) {
                        $this->error = '该优惠券需要满' . $coupon['maxprice'] . '元才能使用';
                        return false;
                    }
                    if ($price <= $coupon['price']) {
                        $this->error = '所支付的金额不满足使用优惠券条件';
                        return false;
                    }
                    $price = round($price - $coupon['price'], 2);
                    break;
                case "2":
                    $price = $price * $coupon['discount'] / 10;
                    break;
                case "5":
                    $price = 0;
                    break;
                default:
                    break;
            }
            //使用优惠券
            //if(M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1)){
            return $price;
            //}
        }
        $this->error = '使用优惠券失败,请重新尝试';
        return false;
    }
    //根据内容搜索课程
    public function strSearch()
    {
        $str       = t($this->data['str']); //获取要搜索的内容
        $videolist = $this->video->where("( ( `video_title` LIKE '%{$str}%' ) OR  ( `video_intro` LIKE '%$str%' ) ) and `is_del` =0 and `is_activity`=1")->order('ctime DESC')->limit($this->_limit())->select();
        // 计算价格
        foreach ($videolist as &$value) {
            $value['price']    = getPrice($value, $this->mid, true, false);
            $value['imageurl'] = getCover($value['cover'], 280, 160);
        }
        $this->exitJson($videolist);
    }
    //根据标签搜索课程
    public function tagSearch()
    {
        $tagid                 = intval($this->data['tagid']); //获取标签id
        $where['video_tag_id'] = array('like', '%' . $tagid . '%');
        $where['is_del']       = 0;
        $videolist             = $this->video->where($where)->order('ctime DESC')->limit($this->_limit())->select();
        foreach ($videolist as &$value) {
            $value['price']    = getPrice($value, $this->mid, true, false);
            $value['imageurl'] = getCover($value['cover'], 280, 160);
        }
        $this->exitJson($videolist);
    }

    // 购物车列表
    public function merge()
    {
        if (!$this->mid) {
            $this->exitJson(array(), 10043, "请登录先，客官");
        }
        $merge_video_list = D("ZyVideoMerge", 'classroom')->getList($this->mid);
        //$merge_video_list ['total_price'] = 0;
        foreach ($merge_video_list as &$value) {
            $value['tlimit_state'] = 0; // 判断是否限时
            $value['video_info']   = D("ZyVideo", 'classroom')->getVideoById($value['video_id']);
            $value['is_buy']       = D("ZyOrder", 'classroom')->isBuyVideo($this->mid, $value['video_id']);
            $value['price']        = getPrice($value['video_info'], $this->mid);
            //$merge_video_list ['total_price'] += $value['is_buy'] ? 0 : round ( $value['price'], 2 );
            $value['legal'] = $value['video_info']['uctime'] > time() ? 1 : 0;
            if ($value['video_info']['is_tlimit'] == 1 && $value['video_info']['starttime'] <= time() && $value['video_info']['endtime'] >= time()) {
                $value['tlimit_state'] = 1;
            }
        }
        $this->exitJson($merge_video_list);
    }

    //获取免费试看时间
    public function getFreeTime()
    {
        $this->exitJson(intval(getAppConfig("video_free_time")));
    }
    /**
     * @name 获取指定课程某用户可用的优惠券
     */
    public function getCanUseCouponList()
    {
        $id             = intval($this->data['id']);
        $video_mod      = D('ZyVideo', 'classroom');
        $video_mod->mid = $this->mid;
        $list           = $video_mod->getCanuseCouponList($id,$this->canot);
        $list ? $this->exitJson($list, 1) : $this->exitJson((object) [], 0, '没有可用优惠券');
    }

    /**
     * @name 获取某用户购买的课程
     */
    public function getUserByVideos()
    {
        $tid = intval($this->teacher_id);
        if ($tid) {
            D('ZyVideo', 'classroom')->where($map)->join("as d INNER JOIN `" . C('DB_PREFIX') . "zy_order_live` o ON o.live_id = d.id AND o.pay_status = 3 AND o.uid = " . $this->mid)->field('*,d.id as id')->findPage($count);
        }
    }

    //获取线下课程筛选条件
    public function screen()
    {
        //机构列表
        $schoolMap      = array('status' => 1, 'is_del' => 0);
        $field          = 'id,title';
        $data['school'] = model('School')->where($schoolMap)->field($field)->findAll() ?: '';
        //时间筛选
        $data['time']['listingtime'] = time();
        $data['time']['uctime']      = $data['time']['listingtime'] + 86400;
        $this->exitJson($data);
    }

    /**
     * Eduline获取线下课程列表接口
     * 参数：
     * page 页数
     * count 每页条数
     * return   课程列表
     */
    public function lineClassList()
    {
        // 销量和评论排序
        $orders = array(
            'default'      => 'view_nums DESC,course_id DESC',
            'new'          => 'ctime DESC',
            't_price'      => 'course_price ASC',
            't_price_down' => 'course_price DESC',
            'hot'          => 'course_order_count DESC,view_nums DESC',
        );
        $limit = $this->count ? $this->count : 20;
        if (isset($orders[$this->data['orderBy']])) {
            $order = $orders[$this->data['orderBy']];
        } else {
            $order = $orders['default'];
        }

        $time  = time();
        $where = "is_del=0 AND is_activity=1 AND uctime>$time";
        if (isset($this->data['time'])) {
            list($listingtime, $uctime) = explode(',', $this->data['time']);
            if ($listingtime && $uctime) {
                $where .= " AND ((( $listingtime<listingtime and listingtime<$uctime) or ( $listingtime<uctime and uctime<$uctime))
                or ($listingtime<listingtime and uctime<$uctime ) or (listingtime<$listingtime and $uctime<uctime ))";
            }
        } else {
            $where .= " AND uctime>$time ";
        }

        $this->data['cateId'] = intval($this->data['cateId']);
        if ($this->data['cateId'] > 0) {
            $idlist = implode(',', $this->category->getVideoChildCategory(intval($this->data['cateId']), 1));
            if ($idlist) {
                $where .= " AND video_category IN($idlist)";
            }

        }

        //机构的课程
        if (isset($this->data['school_id'])) {
            $where .= " AND mhm_id={$this->school_id}";
        }
        $data = M('zy_teacher_course')->where($where)->order($order)->limit($limit)->select();
        if (!$data) {
            $this->exitJson([], 1, '暂时没有更多线下课程');
        }
        foreach ($data as &$value) {
            $value['t_price']      = $value['course_price'];
            $value['price']        = getPrice($value, $this->mid, true, false, 4); // 计算价格
            $value['imageurl']     = getAttachUrlByAttachId($value['cover']);
            $teacher = D('ZyTeacher','classroom')->where(array('id'=>$value['teacher_id']))->field('uid,name')->find();
            $value['teacher_name']= $teacher['name'];
            $value['teacher_uid'] = $teacher['uid'];
            $value['teach_areas']  = D('ZyTeacher')->where('id=' . $value['teacher_id'])->getField('Teach_areas');
            // 是否已购买
            $status          = M('zy_order_teacher')->where(array('uid' => $this->mid, 'video_id' => $value['course_id']))->getField('pay_status');
            $value['is_buy'] = $status ? 1 : 0;
            if ($value['uctime'] < time()) {
                $this->video->where('course_id=' . $value['course_id'])->save(array('is_del' => 1));
            }
        }
        $this->exitJson($data);
    }

    /**
     * Eduline获取线下课程详情接口
     * 参数：
     * id  课程id
     * return   课程详情
     */
    public function lineClassInfo()
    {
        $id   = intval($this->data['id']);
        $data = D('ZyLineClass', 'classroom')->getLineclassById($id);
        if (!$data) {
            $this->exitJson(array(), 10007, '线下课程不存在');
        }
        // 是否已购买
        $data['is_buy'] = 0;
        $status         = M('zy_order_teacher')->where(array('uid' => $this->mid, 'video_id' => $id))->getField('pay_status');
        if($status == 3){
            $data['is_buy'] = 1;
        }
        $this->exitJson($data);
    }

    /**
     *购买线下课
     */
    public function buyLineClass()
    {
        $post = $this->data;
        $vids = $post['vids']; // 课程id
        $vids = explode(",", $vids);
        $uid  = $this->mid;
        if (empty($vids)) {
            $this->exitJson((object) [], 0, '请选择要购买的线下课程');
        }

        $total_price = 0;
        $vid         = 0;
        foreach ($vids as $key => $val) {
            $vid        = $val;
            //判断是否为该用户的线下课程
            $teacher_id = M('zy_teacher_course')->where('course_id='.$vid)->getField('teacher_id');
            $teacher_uid = M('zy_teacher')->where('id='.$teacher_id)->getField('uid');
            if($uid == $teacher_uid){
                $this->exitJson((object) [], 0, '该课程不需要您购买');
            }
            $pay_status = (int) M('zy_order_teacher')->where(array('uid' => intval($this->mid), 'video_id' => $val))->getField('pay_status');
            if ($pay_status === 3) {
                $this->exitJson((object) [], 0, '该线下课程你已经购买,无需重复购买');
            }
            $avideos[$val]          = D('ZyLineClass', 'classroom')->getLineclassById($val);
            $avideos[$val]['price'] = getPrice($avideos[$val], $uid, true, true, 4);
            $videodata              = $avideos[$val]['course_name'];
            if ($avideos[$val]['price']['price'] <= 0 || $avideos[$val]['is_charge'] == 1) {
                $this->addOrder($avideos[$val], $avideos[$val]['price'], array(), 'teacher');
            } else {
                // 当购买过之后，或者课程的创建者是当前购买者的话，价格为0
                $avideos[$val]['is_buy'] = M('zy_order_teacher')->where(array('uid' => $uid, 'video_id' => $val, 'pay_status' => 3))->count() ?: 0;
                $total_price += ($avideos[$val]['is_buy'] || $avideos[$val]['uid'] == $uid) ? 0 : round($avideos[$val]['price']['price'], 2);
            }
        }
        //需要付费
        if ($total_price > 0) {
            //同步更新过期的支付订单
            $ext_data = [
                'price' => $total_price,
            ];
            $order = D('ZyService', 'classroom')->buyOnlineTeacher(intval($this->mid), $vid, $ext_data);
            if ($order === 2) {
                $this->exitJson((object) [], 0, '购买线下课程已经下架');
            }
            if ($order === true) {
                $pay_pass_num = date('YmdHis', time()) . mt_rand(1000, 9999) . mt_rand(1000, 9999);
                //购买订单生成成功
                $pay_id = D('ZyRecharge', 'classroom')->addRechange(array(
                    'uid'          => $this->mid,
                    'type'         => 1,
                    'money'        => $total_price,
                    'note'         => "购买课程:{$videodata}",
                    'pay_type'     => $this->pay_for == 'wxpay' ? 'app_wxpay' : $this->pay_for,
                    'pay_pass_num' => $pay_pass_num,
                ));
                if (!$pay_id) {
                    $this->exitJson(array(), 0, '操作异常');
                }

                $pay_data['is_free'] = 0;
				//$pay_for             = in_array($this->pay_for, array('alipay', 'wxpay')) ? [$this->pay_for] : ['alipay', 'wxpay'];
                $pay_for             = in_array($this->pay_for, array('alipay', 'wxpay','lcnpay')) ? [$this->pay_for] : ['alipay', 'wxpay','lcnpay'];
                foreach ($pay_for as $p) {
                    switch ($p) {
                        case "alipay":
                            $pay_data['alipay'] = $this->alipay(array(
                                'vid'          => $vid,
                                'vtype'        => 'zy_teacher',
                                'out_trade_no' => $pay_pass_num,
                                'total_fee'    => $total_price,
                                'subject'      => "购买课程:{$videodata}",
                                'coupon_id'    => $this->coupon_id,
                            ),"video");
                            break;
                        case "wxpay":
                            $pay_data['wxpay'] = $this->wxpay([
                                'subject'         => "购买课程:{$videodata}",
                                'total_fee'    => $total_price * 100,
                                'out_trade_no' => $pay_pass_num,
                                'vid'          => $vid,
                                'vtype'        => 'zy_teacher',
                                'coupon_id'    => $this->coupon_id,
                            ], 'video');
                            break;
                        case "lcnpay":
                            $res = $this->lcnpay([
                                'vid'          => $vid,
                                'total_fee'    => $total_price,
                                'out_trade_no' => $pay_pass_num,
                                'vtype'        => 'zy_teacher',
                                'subject'      => "购买课程：{$videodata}",
                                'coupon_id'    => $this->coupon_id,
                            ], 'video');
                            if($res === true){
                                $this->exitJson([], 1,"购买成功");
                            }else{
                                $this->exitJson([], 0,$res);
                            }
                            break;
                    }
				}
                $this->exitJson($pay_data, 1);
            }
            $this->exitJson((object) [], 0, '订单生成失败,请重新尝试');
        }
        $order = D('ZyService', 'classroom')->buyOnlineTeacher(intval($this->mid), $vid);
        if ($order) {
            $s['uid']     = $this->mid;
            $s['is_read'] = 0;
            $s['title']   = "恭喜您购买线下课程成功";
            $s['body']    = "恭喜您成功购买线下课程：" . trim($videodata, ",");
            $s['ctime']   = time();
            model('Notify')->sendMessage($s);
            $order_info = $data = D ( 'ZyLineClass','classroom' )->getLineclassById($vid);
            //操作积分
            model('Credit')->getCreditInfo($this->mid, 30);
            $this->exitJson(['is_free' => 1, 'order_info' => $order_info], 1, '购买成功');
        } else {
            $this->exitJson((object) [], 0, '购买失败');
        }
    }
}
