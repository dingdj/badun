<?php
/**
 * 管理中心api
 * utime : 2016-03-06.
 */
class HomeApi extends Api
{
    protected $search_order_list = array(
        1 => array(
            'default' => 'video_order_count DESC,video_score DESC,video_comment_count DESC', //默认
            'saledesc' => 'video_order_count DESC', //订单量递减
            'saleasc' => 'video_order_count ASC', //订单量递增
            'scoredesc' => 'video_score DESC', //评分递减
            'scoreasc' => 'video_score ASC', //评分递增
            't_price' => 't_price ASC', //价格递增
            't_price_down' => 't_price DESC', //价格递减
            'new' => 'ctime desc',
        ),
        2 => array(
            'default' => 'video_order_count DESC,video_score DESC,video_comment_count DESC',
            'saledesc' => 'video_order_count DESC', //订单量递减
            'saleasc' => 'video_order_count ASC', //订单量递增
            'scoredesc' => 'video_score DESC',
            'scoreasc' => 'video_score ASC',
            't_price' => 't_price ASC',
            't_price_down' => 't_price DESC',
            'new' => 'ctime desc',
        ),
        3 => array(
            'defult' => 'view_count desc,collect_num desc,ctime desc',
            'new' => 'ctime desc',
            'hot' => 'view_count desc,collect_num desc',
        ),
        4 => array(
            'default' => 'reservation_count desc,review_count desc,views desc',
            'hot' => 'reservation_count desc',
            'new' => 'ctime desc',
        ),
    );
    /**
     * 会员中心问题--异步处理.
     */
    public function getwentilist()
    {
        $type = t($this->data['type']);
        $zyQuestionMod = D('ZyQuestion', 'classroom');
        $zyCollectionMod = D('ZyCollection', 'classroom');

        if ($type == 'me') {
            $map['uid'] = intval($this->mid);
            $map['parent_id'] = 0;
            $order = 'ctime DESC';

            $data = $zyQuestionMod->where($map)->order($order)->limit($this->_limit())->select();
            foreach ($data as &$value) {
                $value['qst_title'] = msubstr($value['qst_title'], 0, 15);
                $value['qst_description'] = msubstr($value['qst_description'], 0, 153);
                $value['strtime'] = friendlyDate($value['ctime']);
                $value['qcount'] = $zyQuestionMod->where(array('parent_id' => array('eq', $value['id'])))->count();
            }
        } elseif ($type == 'question') {
            $thistable = C('DB_PREFIX').'zy_question';
            $uid = $this->mid;
            //找到所有的答案
            $sql = "SELECT `id`,`parent_id` FROM {$thistable} WHERE `uid` = {$uid} and `parent_id` in(SELECT `id` FROM {$thistable} WHERE parent_id =0)";
            $data = M()->query($sql);
            $_myIds = array();
            $_myPIds = array();
            foreach ($data as $key => $value) {
                $_myIds[] = $value['id'];
                $_myPIds[] = $value['parent_id'];
            }

            $_myIds = array_unique($_myIds);
            $_myIds = $_myIds ? implode(',', $_myIds) : 0;

            $_myPIds = array_unique($_myPIds);
            $_myPIds = $_myPIds ? implode(',', $_myPIds) : 0;

            //找到所有的答案
            $data = M('ZyQuestion')->where(array('id' => array('in', (string) $_myIds)))->order('ctime desc')->limit($this->_limit())->select();
            //把答案的问题
            $_data = M('ZyQuestion')->where(array('id' => array('in', (string) $_myPIds)))->select();

            foreach ($data as  &$value) {
                $_value = array();
                foreach ($_data as $k => $v) {
                    if ($value['parent_id'] == $v['id']) {
                        $_value = $v;
                        break;
                    }
                }
                $value['wenti'] = $_value;
            }
            foreach ($data as &$value) {
                $value['wenti']['qst_title'] = msubstr($value['wenti']['qst_title'], 0, 15);
                $value['wenti']['qst_description'] = msubstr($value['wenti']['qst_description'], 0, 149);
                $value['wenti']['strtime'] = friendlyDate($value['wenti']['ctime']);
                $value['wenti']['qcount'] = M('ZyQuestion')->where(array('parent_id' => array('eq', $value['wenti']['id'])))->count();
                $value['qst_title'] = msubstr($value['qst_title'], 0, 15);
                $value['qst_description'] = msubstr($value['qst_description'], 0, 31);
                $value['qcount'] = M('ZyQuestion')->where(array('parent_id' => array('eq', $value['id'])))->count();
            }
        }
        if ($data) {
            $this->exitJson($data);
        } else {
            $this->exitJson(array(), 10016, '你还没有回答!');
        }
    }

    //加载我的笔记
    public function getNoteList()
    {
        $type = 'me';
        $zyNoteMod = D('ZyNote', 'classroom');
        $zyCollectionMod = D('ZyCollection', 'classroom');
        if ($type == 'me') {
            $map['uid'] = intval($_REQUEST['uid']) ? intval($_REQUEST['uid']) : $this->mid;
            $map['parent_id'] = 0;
            $order = 'ctime DESC';
            $data = $zyNoteMod->where($map)->order($order)->limit($this->_limit())->select();
            foreach ($data as &$value) {
                $value['userface'] = getUserFace($value['uid']);
                $value['uname'] = getUserName($value['uid']);
                $value['note_title'] = msubstr($value['note_title'], 0, 15);
                $value['note_description'] = msubstr($value['note_description'], 0, 150);
                $value['strtime'] = friendlyDate($value['ctime']);
                $value['qcount'] = $zyNoteMod->where(array('parent_id' => array('eq', $value['id'])))->count();
                if ($value['type'] == 1) {
                    //是课程
                    $value['video_title'] = M('zy_video')->where('id='.$value['oid'].' and is_del=0')->getField('video_title');
                } else {
                    $value['video_title'] = M('album')->where('id='.$value['oid'].' and is_del=0')->getField('album_title');
                }
            }
        }
        $this->exitJson($data);
    }

    /**
     * 会员中心点评--异步处理.
     */
    public function getReviewList()
    {
        $type = 'me';
        $zyReviewMod = D('ZyReview');
        if ($type == 'me') {
            $map['uid'] = intval($this->mid);
            $map['parent_id'] = 0;
            $order = 'ctime DESC';

            $data = $zyReviewMod->where($map)->order($order)->limit($this->_limit())->select();
            foreach ($data as &$value) {
                $value['star'] = $value['star'] / 20;
                $value['review_description'] = msubstr($value['review_description'], 0, 150);
                $value['strtime'] = friendlyDate($value['ctime']);
                $value['qcount'] = $zyReviewMod->where(array('parent_id' => array('eq', $value['id'])))->count();
                $_map['id'] = array('eq', $value['oid']);
                //找到评论的内容
                if ($value['type'] == 1) {
                    $value['title'] = M('ZyVideo')->where($_map)->getField('`video_title` as `title`');
                } else {
                    $value['title'] = M('Album')->where($_map)->getField('`album_title` as `title`');
                }
                $value['title'] = msubstr($value['title'], 0, 18);
            }
        }
        $this->exitJson($data);
    }
    //系统消息
    public function notify()
    {
        $list = D('notify_message', 'classroom')->where('uid='.$this->mid)->order('ctime desc')->limit($this->_limit())->select();
        foreach ($list as &$v) {
            if ($appname != 'public') {
                $v['app'] = model('App')->getAppByName($v['appname']);
            }
        }
        model('Notify')->setRead($this->mid);
        $this->exitJson($list);
    }

    //获取账户余额接口
    public function learnc()
    {
        $money = D('ZyLearnc', 'classroom')->getUser($this->mid);
        $credit = model('Credit')->getUserCredit($this->mid);
        $money['score'] = (int) $credit['credit']['score']['value'];
        $this->exitJson($money);
    }

    //支付记录
    public function account_pay()
    {
        $map['uid'] = $this->mid;
        $st = t($this->data['st']);
        $et = t($this->data['et']);
        if (!$st) {
            $st = '';
        }
        if (!$et) {
            $st = '';
        }

        if ($st) {
            $map['ctime'] = array('gt', $st);
        }
        if ($et) {
            $map['ctime'] = array('lt', $et);
        }
        $data = D('ZyOrder', 'classroom')->where($map)->order('ctime DESC,id DESC')->limit($this->_limit())->select();
        foreach ($data as &$val) {
            $val['title'] = D('ZyVideo', 'classroom')->getVideoTitleById($val['video_id']);
        }
        $this->exitJson($data);
    }

    /**
     * @name 意见反馈
     */
    public function feedback()
    {
        $data = [
            'uid' => (int) $this->mid,
            'content' => filter_keywordh($this->content),
            'ctime' => time(),
            'contact_way' => $this->way,
            'type' => 0,
        ];
        if (!$data['content']) {
            $this->exitJson((object) [], 0, '反馈意见内容不能为空');
        }
        if (M('suggest')->add($data)) {
            $this->exitJson(['count' => 1], 1, '已收到意见反馈');
        }
        $this->exitJson(['count' => 0], 0, '已收到意见反馈');
    }

    /**
     * @name 获取通用的分类
     */
    public function getCateList()
    {
        $pid = (int) $this->id ?: 0;
        $cate_list = model('CategoryTree')->setTable('zy_currency_category')->getNetworkList($pid);
        $list = $this->parseCateList($cate_list,0);
        if ($list) {
            $this->exitJson($list, 1);
        }
        $this->exitJson([], 0, '没有更多分类了');
    }
    /**
     * @name 获取推荐的分类
     */
    public function getRecCateList()
    {
        $limit = (int) $this->count ?: 20;
        $cate_list = model('CategoryTree')->setTable('zy_currency_category')->getAllCategory(['pid' => array('neq',0), 'is_h5_and_app' => 1], $limit);
        $list = $this->parseCateList($cate_list);
        if ($list) {
            $this->exitJson($list, 1);
        }
        $this->exitJson([], 0, '没有更多分类了');
    }
    /** 分类数据解析 **/
    public function parseCateList($list,$pid = false)
    {
        $data = [];
        if ($list) {
            foreach ($list as $v) {
                $item['id'] = $v['id'];
                $item['title'] = $v['title'];
                $childs = $this->parseCateList($v['child'],($pid === false) ? false : $v['id']);
                if ($childs) {
                    $item['childs'] = $childs;
                }
                $item['icon'] = getCover($v['middle_ids']);
                $data[] = $item;
            }
            $pid !== false && array_unshift($data,['id'=>$pid,'title'=>'全部','icon'=>'']);
        }

        return (array) $data ?: [];
    }

    /**
     * @name 获取广告图
     */
    public function getAdvert()
    {
        $place_list = [19 => 'app_goods_banner', 20 => 'app_home',22=>'app_ad'];
        if (!$this->place) {
            $this->exitJson((object) [], 0, '请指定广告位置');
        }
        $place = array_search($this->place, $place_list);
        if (!$place) {
            $this->exitJson((object) [], 0, '请指定有效的广告配置');
        }
        $map = [
            'is_active' => 1,
            'display_type' => 3,
            'place' => $place,
        ];
        if ($info = M('ad')->where($map)->find()) {
            $info['content'] = unserialize($info['content']);
            foreach ($info['content'] as &$v) {
                $v['banner'] = getCover($v['banner'], 'auto', 'auto', false);
            }
            $this->exitJson($info['content'], 1);
        }
        $this->exitJson((object) [], 0, '该位置暂时没有广告');
    }


    /**
     * @name 获取首页所有分类
     */
    public function indexAllReCate(){
        $limit = 5;
        $cate_list = model('CategoryTree')->setTable('zy_currency_category')->getAllCategory(['pid' => array('eq',0)], $limit);
        $list = $this->parseCateList($cate_list);
        $list = $list ?: [];
        $this->exitJson($list, 1);
    }

    /**
     * @name 获取首页直播大厅
     */
    public function indexNewLive(){
        $list = model('Live')->getLatelyLive(5);
        $list = $list ?: [];
        $this->exitJson($list, 1);
    }

    /**
     * @name 获取首页推荐讲师
     */
    public function indexReTeacher(){
        $list = M()->query('SELECT `id`,`name`,`head_id` FROM `'.C('DB_PREFIX').'zy_teacher` WHERE is_del=0 and is_best=1 order by `best_sort` ASC  LIMIT 3');
        if (!$list) {
            $list = M()->query('SELECT `id`,`name`,`head_id` FROM `'.C('DB_PREFIX').'zy_teacher` WHERE is_del=0 order by `ctime` DESC  LIMIT 3');
        }
        if ($list) {
            foreach ($list as &$val) {
                $val['headimg'] = getCover($val['head_id'], 80, 80);
            }
        }
        $list = $list ?: [];
        $this->exitJson($list, 1);
    }

    /**
    * @name 获取首页推荐机构
    */
    public function indexReSchool(){
        $map['is_best'] = 1;
        $order = 'best_sort ASC';
        $list = model('School')->getList($map, 4 , $order);
        $list = $list ?: [];
        $this->exitJson($list, 1);
    }

    /**
    * @name 获取首页精选课程
    */
    public function indexHotCourse(){
        $map ['is_best']     = 1;
        $map ['is_del']      = 0;
        $map['uctime']       = array('GT',time());
        $map['listingtime']  = array('LT',time());
        $bestVideo  = D('ZyVideo')->where($map)->order('ctime desc')->limit(4)->findAll();
        foreach ($bestVideo as &$val) {
            //获取课程的最新价格
            $val['mzprice'] = getPrice ( $val, $this->mid, true, true );
            $val['price']   = $val['mzprice']['price'];
            $val['cover'] = getCover($val['cover'], 280, 160);
        }
        $bestVideo = $bestVideo ?: [];
        $this->exitJson($bestVideo, 1);
    }

    /**
    * @name 获取首页最新课程
    */
    public function indexNewCourse(){
        $map ['is_del']      = 0;
        $map ['is_activity'] = 1;
        $map['uctime']       = array('GT',time());
        $map['listingtime']  = array('LT',time());
        $video  = D('ZyVideo')->where($map)->order('ctime desc','id desc')->limit(4)->findAll();
        foreach ($video as &$val) {
            //获取课程的最新价格
            $val['mzprice'] = getPrice ( $val, $this->mid, true, true );
            $val['price']   = $val['mzprice']['price'];
            $val['cover'] = getCover($val['cover'], 280, 160);
        }
        $video = $video ?: [];
        $this->exitJson($video, 1);
    }

    //TODO临时
    public function index()
    {
        $list = [
            'video' => [],
            'school' => [],
            'like' => [],
            'teacher' => [],
            'live' => [],
        ];
        //推送到APP的课程
        $video_mode = D('ZyVideo', 'classroom');
        $video_mode->mid = $this->mid;
        $video = $video_mode->cateVideo('APP', array('is_choice_pc' => 1), 20);
        if ($video) {
            $list['video'] = $video;
        }
        //猜你喜欢
        $youLike = D('ZyGuessYouLike', 'classroom')->getGYLData(2, $this->mid, 4);
        if (!$youLikeModel) {
            $map = [
                'is_del' => 0,
                'is_activity' => 1,
                'uctime' => ['gt', time()],
                'listingtime' => ['lt', time()],
            ];
            $youLike = D('ZyVideo', 'classroom')->where($map)->order('endtime,starttime,limit_discount')->limit(4)->select();

            if ($youLike) {
                foreach ($youLike  as &$value) {
                    $value['price'] = getPrice($value, $this->mid);// 计算价格
                     $value['imageurl'] = getCover($value['cover'], 280, 160);
                    $value['video_score'] = round($value['video_score'] / 20); // 四舍五入
                     if ($value['type'] == 2) {
                         $value['live_id'] = (int) $value['id'];
                     }
                }
            }
        }
        $list['like'] = $youLike ?: [];
        //入住机构
        $school = model('School')->getList('', 4);
        $school['data'] && $list['school'] = $school['data'];
        $time = time();
        $beVideos = M()->query('SELECT zv.`id`, zv.`teacher_id`,zv.`video_title`,zt.`name`,zt.`inro`,zt.`head_id` FROM `'.C('DB_PREFIX').'zy_video` zv,`'.C('DB_PREFIX')."zy_teacher` zt WHERE zv.teacher_id=zt.id AND zt.id AND zt.is_del=0 and zv.`is_del`=0 AND `is_activity`=1 AND `uctime`>'$time' AND `listingtime`<'$time' and teacher_id >0 GROUP BY `teacher_id` ORDER BY `video_order_count` DESC ,`id` DESC  LIMIT 3");
        if (!$beVideos) {
            $beVideos = M()->query('SELECT `id` as teacher_id,`type`,`name`,`inro`,`label`,`head_id` FROM `'.C('DB_PREFIX').'zy_teacher` WHERE is_del=0 order by `id` DESC  LIMIT 3');
        }
        if ($beVideos) {
            foreach ($beVideos as $k => $val) {
                $beVideos[$k]['headimg'] = getCover($val['head_id'], 80, 80);
                $beVideos[$k]['label'] = $val['label'] ?: '';
                $beVideos[$k]['type'] = ($val['type'] == 1) ? 1 : 2;
                $beVideos[$k]['id'] = $val['teacher_id'];
                if ($val['type'] == 1) {
                    $beVideos[$k]['live_id'] = (int) $val['id'];
                }
            }
        }
        $live = model('Live')->getLatelyLive(5);
        if ($live) {
            $list['live'] = $live;
        }
        $list['teacher'] = $beVideos ?: [];
        $this->exitJson($list, 1);
    }

    /**
     * @name 全站搜索
     */
    public function search()
    {
        //联查所有资源
        $table_list = [
            1 => [
                    'keyword' => 'video_title',
                    'mod' => array('ZyVideo', 'classroom'),
                    'cate_id' => 'fullcategorypath',
            ],
            2 => [
                    'keyword' => 'video_title',
                    'mod' => 'Live',
                    'cate_id' => 'fullcategorypath',
            ],
            3 => [
                    'keyword' => 'title',
                    'mod' => 'School',
                    'cate_id' => 'school_category',
            ],
            4 => [
                    'keyword' => 'name',
                    'mod' => array('ZyTeacher', 'classroom'),
                    'cate_id' => 'teacher_category',
            ],
        ];
        $sql = '';
        $list = [];
        if (!array_key_exists($this->type, $table_list)) {
            foreach ($table_list as $k => $table) {
                $data = $this->getSearchData($table);
                if ($data['data'] && $data['gtLastPage'] === false) {
                    $list[] = [
                        'type' => intval($k),
                        'list' => $data['data'],
                    ];
                } else {
                    $list[] = ['type' => intval($k), 'list' => []];
                }
            }
        } else {
            $table = $table_list[$this->type];
            $data = $this->getSearchData($table);
            if ($data['data'] && $data['gtLastPage'] === false) {
                $list[] = [
                    'type' => (int) $this->type,
                    'list' => $data['data'],
                ];
            } else {
                $list[] = ['type' => (int) $this->type, 'list' => []];
            }
        }
        $this->exitJson($list, 1);
    }
    /**
     * @name 查询并获取搜索的数据
     */
    private function getSearchData($tableInfo)
    {
        $mod = (is_string($tableInfo['mod'])) ? model($tableInfo['mod']) : D($tableInfo['mod'][0], $tableInfo['mod'][1]);
        if (method_exists($mod, 'getListBySearch')) {
            $mod->mid = $this->mid;
            $_map = $map = [];
            $this->keyword && $_map['keyword'] = array('like', '%'.h($this->keyword).'%');
            $this->cate_id && $_map['cate_id'] = array('like', '%,'.intval($this->cate_id).',%');
            $this->type && $_map['type'] = intval($this->type);
            $in_map = array_intersect_key($_map, $tableInfo);
            if (!empty($in_map)) {
                foreach ($in_map as $k => $v) {
                    $map[$tableInfo[$k]] = $v;//重置查询条件
                }
            }
            $order = $this->search_order_list[$this->type][$this->order] ?: '';

            return $mod->getListBySearch($map, $this->count, $order);
        }

        return [];
    }

    /**
     * @name 获取地区
     */
    public function getArea()
    {
        $area_mod = model('CategoryTree')->setTable('area');
        $list = $area_mod->getAllCategory(array('pid' => 0), 0);
        $aliasList = M('area')->where('(title like "%市辖区%" or title like "县%" or title LIKE "区%" or title LIKE "市%") AND (area_id % 100 = 0)')->field('area_id,pid')->select();
        $aliasList = array_column($aliasList, 'pid', 'area_id');
        $data = [];
        foreach ($list as $v) {
            $v['area_id'] = $v['id'];
            $where = in_array($v['id'], $aliasList) ? array('area_id' => $v['id']) : array('pid' => $v['id']);
            $v['letter'] = getFirstLetter($v['title']);
            $child = $area_mod->getAllCategory($where);
            foreach ($child as $k => $c) {
                $v['child'][] = [
                    'area_id' => $c['id'],
                    'title' => $c['title'],
                    'letter' => getFirstLetter($c['title']),
                ];
            }
            unset($v['pid'], $v['id']);
            array_push($data, $v);
        }
        $this->exitJson($data, 1);
    }
    /**
     * 获取热门搜索关键字.
     *
     * @author martinsun <syh@sunyonghong.com>
     * @datetime 2017-02-16T10:07:23+080
     *
     * @version  1.0
     *
     * @return json
     */
    public function getHotKeyword()
    {
        $limit = intval($this->count) ?: 10;
        $search_keywords = M('search_keywords')->where('is_del = 0')->order('sort asc')->field('id,sk_name,sk_url,sk_text,is_color')->limit($limit)->select();
        $this->exitJson($search_keywords, 1);
    }
}
