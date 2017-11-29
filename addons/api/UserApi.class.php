<?php
/**
 * 用户信息api
 * utime : 2016-03-06
 */
class UserApi extends Api
{

    /**
     * 按用户UID或昵称返回用户资料，同时也将返回用户的最新发布的微博
     *
     */
    public function show()
    {
        $this->user_id = (!$this->user_id) ? $this->mid : $this->user_id;
        //用户基本信息
        if (!$this->user_id && !$this->user_name) {
            $this->exitJson(array(), 10003, "没有用户uid");
        }
        if (!$this->user_id) {
            $data          = model('User')->getUserInfoByName($this->user_name);
            $this->user_id = $data['uid'];
        } else {
            $data = model('User')->getUserInfo($this->user_id);
        }
        if (empty($data)) {
            $this->exitJson(array(), 10004, "用户不存在");
        }
        $data['sex'] = $data['sex'] == 1 ? '男' : '女';
        if (!$data['intro'] || $data['intro'] == 'null') {
            $data['intro'] = '';
        }

        $data['profile'] = model('UserProfile')->getUserProfileForApi($this->user_id);

        $profileHash              = model('UserProfile')->getUserProfileSetting();
        $data['profile']['email'] = array('name' => '邮箱', 'value' => $data['email']);
        foreach (UserProfileModel::$sysProfile as $k) {
            if (!isset($data['profile'][$k])) {
                $data['profile'][$k] = array('name' => $profileHash[$k]['field_name'], 'value' => '');
            }
        }
        //用户统计信息
        $defaultCount = array('following_count' => 0, 'follower_count' => 0, 'feed_count' => 0, 'favorite_count' => 0, 'unread_atme' => 0, 'weibo_count' => 0);
        $count        = model('UserData')->getUserData($this->user_id);
        if (empty($count)) {
            $count = array();
        }
        $data['count_info'] = array_merge($defaultCount, $count);
        //用户标签
        $data['user_tag'] = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($this->user_id);
        $data['user_tag'] = empty($data['user_tag']) ? '' : implode('、', $data['user_tag']);
        //关注情况
        $followState          = model('Follow')->getFollowState($this->mid, $this->user_id);
        $data['follow_state'] = $followState;
        //最后一条微博
        $lastFeed          = model('Feed')->getLastFeed($this->user_id);
        $data['last_feed'] = $lastFeed;
        // 判断用户是否被登录用户收藏通讯录
        $data['isMyContact'] = 0;
        if ($this->user_id != $this->mid) {
            $cmap['uid']         = $this->mid;
            $cmap['contact_uid'] = $this->user_id;
            $myCount             = D('Contact', 'contact')->where($cmap)->count();
            if ($myCount == 1) {
                $data['isMyContact'] = 1;
            }
        }
        $this->exitJson($data);
    }

    /**
     * 上传头像 API
     * 传入的头像变量 $_FILES['filedata']
     */
    public function upload_face()
    {
        $_FILES['Filedata'] = $_FILES['filedata'] = $_FILES['face'];
        $dAvatar            = model('Avatar');
        $dAvatar->init($this->mid); // 初始化Model用户id
        $res = $dAvatar->upload(true);
        if ($res['status'] == 1) {
            $data['picurl']   = $res['data']['picurl'];
            $data['picwidth'] = $res['data']['picwidth'];
            $scaling          = 5;
            $data['w']        = $res['data']['picwidth'] * $scaling;
            $data['h']        = $res['data']['picheight'] * $scaling;
            $data['x1']       = $data['y1']       = 0;
            $data['x2']       = $data['w'];
            $data['y2']       = $data['h'];
            $r                = $dAvatar->dosave($data);
            unset($r['status']);
            $r = $r['data'];
            $this->exitJson($r);
        } else {
            $this->exitJson(array(), 10008, '不是有效的图片格式，请重新选择照片上传');
        }
    }

    /**
     *    关注一个用户
     */
    public function follow_create()
    {
        $user_id = intval($this->data["user_id"]);
        if ((!$this->mid) || (!$user_id)) {
            $this->exitJson(array(), 10008, '关注失败!');
        }
        $f_mod = model('Follow');
        if ($this->mid == intval($this->data["user_id"])) {
            $this->exitJson(array(), 10008, '自己不能关注自己!');
        }

        $res = $f_mod->getFollowState($this->mid, $this->user_id);
        if ($res['following'] == 1) {
            $this->exitJson($res);
        } else {
            $r = $f_mod->doFollow($this->mid, $this->data["user_id"]);
            if ($r) {
                $res = $f_mod->getFollowState($this->mid, $this->user_id);
                $this->exitJson($res);
            } else {
                $this->exitJson(array(), 10008, $f_mod->getError());
            }
        }
    }

    /**
     * 取消关注
     */
    public function follow_destroy()
    {
        if (!$this->mid || !$this->user_id) {
            $this->exitJson(array());
        }

        $r = model('Follow')->unFollow($this->mid, $this->user_id);
        if (!$r) {
            $this->exitJson(model('Follow')->getFollowState($this->mid, $this->user_id));
        }
        $this->exitJson($r);
    }

    /**
     * 用户粉丝列表
     */
    public function user_followers()
    {
        $this->user_id = !$this->user_id ? $this->mid : $this->user_id;
        // 清空新粉丝提醒数字
        if ($this->user_id == $this->mid) {
            $udata = model('UserData')->getUserData($this->mid);
            $udata['new_folower_count'] > 0 && model('UserData')->setKeyValue($this->mid, 'new_folower_count', 0);
        }
        $res = model('Follow')->getFollowerListForApi($this->mid, $this->user_id, $this->since_id, $this->max_id, $this->count, $this->page);
        if ($res) {
            $this->exitJson($res);
        } else {
            $this->exitJson(array(), 10016, '你还没有用户粉丝!');
        }
    }

    /**
     * 获取用户关注的人列表
     */
    public function user_following()
    {
        $this->user_id = !$this->user_id ? $this->mid : $this->user_id;
        $res           = model('Follow')->getFollowingListForApi($this->mid, $this->user_id, $this->since_id, $this->max_id, $this->count, $this->page);
        if ($res) {
            $this->exitJson($res);
        } else {
            $this->exitJson(array(), 10016, '没有关注我的人!');
        }
    }

    /**
     * 获取用户的朋友列表
     *
     */
    public function user_friends()
    {
        $this->user_id = !$this->user_id ? $this->mid : $this->user_id;
        return model('Follow')->getFriendsForApi($this->mid, $this->user_id, $this->since_id, $this->max_id, $this->count, $this->page);
    }

    // 按名字搜索用户
    public function wap_search_user()
    {
        $key          = t($this->data['key']);
        $map['uname'] = array('LIKE', $key);
        $userlist     = M('user')->where($map)->findAll();
        return $userlist;
    }

    /**
     * 获取用户相关信息
     * @param array $uids 用户ID数组
     * @return array 用户相关数组
     */
    public function getUserInfos($uids, $data, $type = 'basic')
    {
        // 获取用户基本信息
        $userInfos    = model('User')->getUserInfoByUids($uids);
        $userDataInfo = model('UserData')->getUserKeyDataByUids('follower_count', $uids);

        if ($type == 'all') {
            // 获取关注信息
            $followStatusInfo = model('Follow')->getFollowStateByFids($GLOBALS['ts']['mid'], $uids);
            // 获取用户组信息
            $userGroupInfo = model('UserGroupLink')->getUserGroupData($uids);
        }

        // 组装数据
        foreach ($data as &$value) {
            $value              = array_merge($value, $userInfos[$value['uid']]);
            $value['user_data'] = $userDataInfo[$value['uid']];
            if ($type == 'all') {
                $value['follow_state'] = $followStatusInfo[$value['uid']];
                $value['user_group']   = $userGroupInfo[$value['uid']];
            }
        }

        return $data;
    }
    // 按标签搜索用户
    public function search_by_tag()
    {
        $limit                         = 20;
        $this->data['limit'] && $limit = intval($this->data['limit']);
        $tagid                         = intval($this->data['tagid']);
        if (!$tagid) {
            return 0;
        }
        $data         = model('UserCategory')->getUidsByCid($tagid, array(), $limit);
        $data['data'] = $this->getUserInfos(getSubByKey($data['data'], 'uid'), $data['data'], 'basic');
        return $data['data'] ? $data : 0;
    }

    // 按地区搜索用户
    public function search_by_area($value = '')
    {
        $this->data['p']               = $this->data['page']               = $this->page;
        $limit                         = 20;
        $this->data['limit'] && $limit = intval($this->data['limit']);
        $areaid                        = intval($this->data['areaid']);
        if (!$areaid && $this->data['areaname']) {
            $amap['title'] = t($this->data['areaname']);
            $areaid        = D('area')->where($amap)->getField('area_id');
        }
        if (!$areaid) {
            return 0;
        }

        $pid1  = model('Area')->where('area_id=' . $areaid)->getField('pid');
        $level = 1;
        if ($pid1 != 0) {
            $level = $level + 1;
            $pid2  = model('Area')->where('area_id=' . $pid1)->getField('pid');
            if ($pid2 != 0) {
                $level = $level + 1;
            }
        }
        switch ($level) {
            case 1:
                $map['province'] = $areaid;
                break;
            case 2:
                $map['city'] = $areaid;
                break;
            case 3:
                $map['area'] = $areaid;
                break;
        }

        $map['is_del']    = 0;
        $map['is_active'] = 1;
        $map['is_audit']  = 1;
        $map['is_init']   = 1;

        $data         = D('user')->where($map)->field('uid')->order("uid desc")->findPage($limit);
        $data['data'] = $this->getUserInfos(getSubByKey($data['data'], 'uid'), $data['data'], 'basic');

        return $data['data'] ? $data : 0;
    }

    // 按认证分类搜索用户
    public function search_by_verify_category($value = '')
    {
        $limit                         = 20;
        $this->data['limit'] && $limit = intval($this->data['limit']);
        $verifyid                      = intval($this->data['verifyid']);
        if (!$verifyid && $this->data['verifyname']) {
            $amap['title'] = t($this->data['verifyname']);
            $verifyid      = D('user_verify_category')->where($amap)->getField('user_verified_category_id');
        }
        if (!$verifyid) {
            return 0;
        }
        $maps['user_verified_category_id'] = $verifyid;
        $maps['verified']                  = 1;
        $data                              = D('user_verified')->where($maps)->field('uid, info AS verify_info')->findPage($limit);
        $data['data']                      = $this->getUserInfos(getSubByKey($data['data'], 'uid'), $data['data'], 'basic');
        return $data['data'] ? $data : 0;
    }

    // 按官方推荐分类搜索用户
    public function search_by_uesr_category($value = '')
    {
        $limit                         = 20;
        $this->data['limit'] && $limit = intval($this->data['limit']);
        $cateid                        = intval($this->data['cateid']);
        if (!$cateid && $this->data['catename']) {
            $amap['title'] = t($this->data['catename']);
            $cateid        = D('user_official_category')->where($amap)->getField('user_official_category_id');
        }
        if (!$cateid) {
            return 0;
        }
        $maps['user_official_category_id'] = $cateid;
        $data                              = D('user_official')->where($maps)->field('uid, info AS verify_info')->findPage($limit);
        $data['data']                      = $this->getUserInfos(getSubByKey($data['data'], 'uid'), $data['data'], 'basic');
        return $data['data'] ? $data : 0;
    }

    public function get_user_category()
    {
        $type = t($this->data['type']);
        switch ($type) {
            //地区分类 最多只列出二级
            case 'area':
                $category = model('CategoryTree')->setTable('area')->getNetworkList();
                break;

            //认证分类 最多只列出二级
            case 'verify_category':
                $category = model('UserGroup')->where('is_authenticate=1')->findAll();
                foreach ($category as $k => $v) {
                    $category[$k]['child'] = D('user_verified_category')->where('pid=' . $v['user_group_id'])->findAll();
                }
                break;

            //推荐分类 最多只列出二级
            case 'user_category':
                $category = model('CategoryTree')->setTable('user_official_category')->getNetworkList();
                break;

            //标签 tag 最多只列出二级
            default:
                $category = model('UserCategory')->getNetworkList();
                break;
        }
        return $category;
    }
    /**
     * 粉丝最多
     * @return Ambigous <number, 返回新的一维数组, boolean, multitype:Ambigous <array, string> >
     */
    public function get_user_follower()
    {
        $limit                         = 20;
        $this->data['limit'] && $limit = intval($this->data['limit']);
        $page                          = $this->data['page'] ? intval($this->data['page']) : 1;
        $limit                         = ($page - 1) * $limit . ', ' . $limit;

        $followermap['key'] = 'follower_count';
        $followeruids       = model('UserData')->where($followermap)->field('uid')->order('`value`+0 desc,uid')->limit($limit)->findAll();
        $followeruids       = $this->getUserInfos(getSubByKey($followeruids, 'uid'), $followeruids, 'basic');
        return $followeruids ? $followeruids : 0;
    }

    // 按地理位置搜索邻居
    public function neighbors()
    {
        //经度latitude
        //纬度longitude
        //距离distance
        $latitude  = floatval($this->data['latitude']);
        $longitude = floatval($this->data['longitude']);
        //根据经度、纬度查询周边用户 1度是 111 公里
        //根据ts_mobile_user 表查找，经度和纬度在一个范围内。
        //latitude < ($latitude + 1) AND latitude > ($latitude - 1)
        //longitude < ($longitude + 1) AND longitude > ($longitude - 1)
        $limit                         = 20;
        $this->data['limit'] && $limit = intval($this->data['limit']);
        $map['last_latitude']          = array('between', ($latitude - 1) . ',' . ($latitude + 1));
        $map['last_longitude']         = array('between', ($longitude - 1) . ',' . ($longitude + 1));

        $data         = D('mobile_user')->where($map)->field('uid')->findpage($limit);
        $data['data'] = $this->getUserInfos(getSubByKey($data['data'], 'uid'), $data['data'], 'basic');
        return $data['data'] ? $data : 0;
    }

    // 记录用户的最后活动位置
    public function checkin()
    {
        $latitude  = floatval($this->data['latitude']);
        $longitude = floatval($this->data['longitude']);
        //记录用户的UID、经度、纬度、checkin_time、checkin_count
        //如果没有记录则写入，如果有记录则更新传过来的字段包括：sex\nickname\infomation（用于对周边人进行搜索）
        $checkin_count          = D('mobile_user')->where('uid=' . $this->mid)->getField('checkin_count');
        $data['last_latitude']  = $latitude;
        $data['last_longitude'] = $longitude;
        $data['last_checkin']   = time();

        if ($checkin_count) {
            $data['checkin_count'] = $checkin_count + 1;
            $res                   = D('mobile_user')->where('uid=' . $this->mid)->save($data);
        } else {

            $user               = model('User')->where('uid=' . $this->mid)->field('uname,intro,sex')->find();
            $data['nickname']   = $user['uname'];
            $data['infomation'] = $user['intro'];
            $data['sex']        = $user['sex'];

            $data['checkin_count'] = 1;
            $data['uid']           = $this->mid;
            $res                   = D('mobile_user')->add($data);
        }
        return $res ? 1 : 0;
    }

    /**
     * 修改登录用户帐号密码操作
     * @return json 返回操作后的JSON信息数据
     */
    public function doModifyPassword()
    {
        $_POST['oldpassword'] = t($this->data['oldpassword']); //原密码
        $_POST['password']    = t($this->data['password']); //新密码
        $_POST['repassword']  = t($this->data['repassword']); //确认新密码
        // 验证信息
        if ($_POST['oldpassword'] === '') {
            $this->exitJson(array(), 10026, '请填写原始密码');
        }
        if ($_POST['password'] === '') {
            $this->exitJson(array(), 10026, '请填写新密码');
        }
        if ($_POST['repassword'] === '') {
            $this->exitJson(array(), 10026, '请填写确认密码');
        }
        if ($_POST['password'] != $_POST['repassword']) {
            $this->exitJson(array(), 10026, L('PUBLIC_PASSWORD_UNSIMILAR')); // 新密码与确认密码不一致
        }
        if (strlen($_POST['password']) < 6) {
            $this->exitJson(array(), 10026, '密码太短了，最少6位');
        }
        if (strlen($_POST['password']) > 15) {
            $this->exitJson(array(), 10026, '密码太长了，最多15位');
        }
        if ($_POST['password'] == $_POST['oldpassword']) {
            $this->exitJson(array(), 10026, L('PUBLIC_PASSWORD_SAME')); // 新密码与旧密码相同
        }

        $user_model = model('User');
        $map['uid'] = $this->mid;
        $user_info  = $user_model->where($map)->find();

        if ($user_info['password'] == $user_model->encryptPassword($_POST['oldpassword'], $user_info['login_salt'])) {
            $data['login_salt'] = rand(11111, 99999);
            $data['password']   = $user_model->encryptPassword($_POST['password'], $data['login_salt']);
            $res                = $user_model->where("`uid`={$this->mid}")->save($data);
            if ($res) {
                $this->exitJson(true);
            } else {
                $this->exitJson(array(), 10026, L('PUBLIC_PASSWORD_MODIFY_FAIL'));
            }
        } else {
            $this->exitJson(array(), 10026, L('PUBLIC_ORIGINAL_PASSWORD_ERROR')); // 原始密码错误
        }
    }

    //用户账户余额(余额，收入，积分，当前绑定的银行卡信息）
    public function accountInfo(){
        $account['learn'] = D('ZyLearnc','classroom')->where(['uid'=>$this->mid])->getField('balance') ? : 0;
        $account['split'] = D('ZySplit','classroom')->where(['uid'=>$this->mid])->getField('balance') ? : 0;
        $account['score'] = M('credit_user')->where('uid = '.$this->mid)->getField('score') ? : 0;

//        $card = D('ZyBcard','classroom')->getUserOnly($this->mid);
//        $account['card_info'] = $card ? "{$card['accounttype']}".substr($card['account'],-4) : "";

        $this->exitJson($account,1);
    }

    //用户账户余额
    public function account(){
        $learncoin_info = M('zy_learncoin')->where('uid = '.$this->mid)->find();

        //选择模版
        $tab = intval($_GET['tab']);
        $tpls = array('index','integral_list');
        if(!isset($tpls[$tab])) $tab = 0;
        $method = 'account_'.$tpls[$tab];
        if(method_exists($this, $method)){
            $data_info = $this->$method();
        }

        if($tab == 0) {
            $config['learncoin_info'] = $learncoin_info;
            //读取系统设置的支付方式&支付配置
            $split_score = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $rechange_default = array_filter(explode("\n", $split_score['rechange_default']));
            foreach($rechange_default as &$val) {
                $rechange_arr = array_filter(explode('=>', $val));
                $val_arr['rechange'] = trim($rechange_arr[0]) ? : 0;
                $val_arr['give'] = trim($rechange_arr[1]) ? : 0;
                $val = $val_arr;
            }
            $config['rechange_default'] = $rechange_default;
            $payConfig = model('Xdata')->get("admin_Config:payConfig");
            $config['pay'] = $payConfig['pay'];
            $config['pay_note'] = "1元人民币=1余额";

            if (!$config) {
                $this->exitJson(array(), 0, "暂时没有相关信息");
            } else {
                $this->exitJson($config,1);
            }
        }

        if($tab == 1) {
            if (!$data_info) {
                $this->exitJson(array(), 0, "暂时没有相关流水");
            }

            $data['balance'] = $learncoin_info['balance'] ? : 0;
            $data = array_merge($data, $data_info);

            $this->exitJson($data,1);
        }
    }

    //余额流水记录
    protected function account_integral_list(){
        $map = array('uid'=>$this->mid);  //获取用户id

        $limit = intval($this->limit) ? : 5;
        $st    = t($this->st) ? : 0;
        $et    = t($this->et) ? : 0;

        if($this->st){
            $map['ctime'][] = array('gt', $st);
        }
        if($this->et){
            $map['ctime'][] = array('lt', $et);
        }
        $data = D('ZyLearnc','classroom')->flowModel->where($map)->order('ctime DESC,id DESC')->findPage($limit);
        foreach($data['data'] as $key=>$value){
            switch ($value['type']){
                case 0:$data['data'][$key]['type'] = "消费";break;
                case 1:$data['data'][$key]['type'] = "充值";break;
                case 2:$data['data'][$key]['type'] = "冻结";break;
                case 3:$data['data'][$key]['type'] = "解冻";break;
                case 4:$data['data'][$key]['type'] = "冻结扣除";break;
                case 5:$data['data'][$key]['type'] = "分成收入";break;
                case 6:$data['data'][$key]['type'] = "增加积分";break;
                case 7:$data['data'][$key]['type'] = "扣除积分";break;
            }
        }
        $total= D('ZyLearnc')->flowModel->where($map)->sum('num') ? : 0;

        $flow_data['total'] = $total;
        $flow_data['data'] = $data['data'] ? : [];
        unset($data);

        return $flow_data;
    }

    //用户账户收入余额
    public function spilt(){
        $spilt_info = D('ZySplit','classroom')->where('uid = '.$this->mid)->find();

        //选择模版
        $tab = intval($this->tab);
        $tpls = array('index','integral_list','take','take_list');
        if(!isset($tpls[$tab])) $tab = 0;
        $method = 'spilt_'.$tpls[$tab];
        if(method_exists($this, $method)){
            $data_info = $this->$method();
        }

        if($tab == 0) {
            $config['spilt_info'] = $spilt_info;
            $card_list = D('ZyBcard','classroom')->getUserBcard($this->mid,'id,accounttype,account,accountmaster');
            if($card_list){
                foreach($card_list as $key => &$val){
                    $val['card_info'] = $val['accounttype']." ".substr($val['account'],-4);//$val['accounttype']."(".hideStar($val['account']).")";//$val['accounttype'].substr($val['account'],-4)
                }
            }else{
                $card_list = [];
                $card_info = "未绑定";
            }

            $alipay_info = D('ZyBcard','classroom')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->field('id,account,accountmaster')->find();

            if($alipay_info){
                $alipay_info['accountmaster'] = hideStar($alipay_info['accountmaster']);
                $alipay_info['account'] = hideStar($alipay_info['account']);

            }else{
                $alipay_info['account'] = "";
            }
            $account_balance = M('zy_learncoin')->where('uid = '.$this->mid)->getField('balance');

            if($card_list) {
                foreach ($card_list as $key => &$val) {
                    $val['card_info'] = $val['accounttype'] . " " . substr($val['account'], -4);//$val['accounttype']."(".hideStar($val['account']).")";//$val['accounttype'].substr($val['account'],-4)
                }
            }

            $config['pay_type'] = [
                ['pay_num' =>'lcnpay','pay_type_note'=>"当前账户余额¥{$account_balance}",'learn_balance'=>$account_balance],
                ['pay_num' =>'unionpay','pay_type_note'=>"{$card_info}",'card_list'=>$card_list],
                ['pay_num' =>'alipay','pay_type_note'=>$alipay_info['account']],
            ];

            $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $config['pay_note'] = "注：提现到余额后不能再转至银行卡或支付宝,转账比例为1：1";
            $config['withdraw_basenum'] = $recharge_intoConfig['withdraw_basenum'];

            if (!$config) {
                $this->exitJson(array(), 0, "暂时没有相关信息");
            } else {
                $this->exitJson($config,1);
            }
        }

        if($tab == 1 || $tab == 3) {
            $data['balance'] = $spilt_info['balance'] ?: 0;
            $data = array_merge($data, $data_info);

            $this->exitJson($data, 1);
        }

        if($tab == 2) {
            $data['balance'] = $spilt_info['balance'] ?: 0;
            $data = array_merge($data, $data_info);

            $this->exitJson($data, 1);
        }
    }

    //收入余额流水记录
    protected function spilt_integral_list(){
        $map = array('uid'=>$this->mid);  //获取用户id

        $limit = intval($this->limit) ? : 5;
        $st    = t($this->st) ? : 0;
        $et    = t($this->et) ? : 0;

        if($this->st){
            $map['ctime'][] = array('gt', $st);
        }
        if($this->et){
            $map['ctime'][] = array('lt', $et);
        }
        $data = D('ZySplit','classroom')->flowModel->where($map)->order('ctime DESC,id DESC')->findPage($limit);
        foreach($data['data'] as $key=>$value){
            switch ($value['type']){
                case 0:$data['data'][$key]['type'] = "消费";break;
                case 1:$data['data'][$key]['type'] = "充值";break;
                case 2:$data['data'][$key]['type'] = "冻结";break;
                case 3:$data['data'][$key]['type'] = "解冻";break;
                case 4:$data['data'][$key]['type'] = "冻结扣除";break;
                case 5:$data['data'][$key]['type'] = "分成收入";break;
                case 6:$data['data'][$key]['type'] = "增加积分";break;
                case 7:$data['data'][$key]['type'] = "扣除积分";break;
            }
        }
        $total= D('ZySplit','classroom')->flowModel->where($map)->sum('num') ? : 0;

        $flow_data['total'] = $total ? : 0;
        $flow_data['data'] = $data['data'] ? : [];
        unset($data);

        return $flow_data;
    }

    //分成收入申请提现页面
    protected function spilt_take(){
        $card = D('ZyBcard','classroom')->getUserOnly($this->mid);
        if(!$card){
            $this->exitJson(array(), 0, '请先绑定银行卡！');
        }

        //申请提现
        $withdraw_num = intval($this->withdraw_num);
        if($withdraw_num){
            $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $withdraw_basenum = $recharge_intoConfig['withdraw_basenum'];

            if($withdraw_num<0||$withdraw_num<$withdraw_basenum||$withdraw_num%$withdraw_basenum!=0){
                $this->exitJson(array(), 0, "只允许提现为{$withdraw_basenum}的倍数");
            }
            $result = D('ZyService','classroom')->applySpiltWithdraw(
                $this->mid, $withdraw_num, $card['id']);
            if($result){
                $this->exitJson(array(), 1, '提交成功，请等待审核');
            }else{
                switch ($result) {
                    case 1:
                        $this->exitJson(array(), 1, "申请提现的收入余额不是系统指定的倍数，或小于0");
                        break;
                    case 2:
                        $this->exitJson(array(), 2, "没有找到用户对应的提现银行卡/账户");
                        break;
                    case 3:
                        $this->exitJson(array(), 3, "有未完成的提现记录，需要等待完成");
                        break;
                    case 4:
                        $this->exitJson(array(), 4, "余额转冻结失败：可能是余额不足");
                        break;
                    case 5:
                        $this->exitJson(array(), 5, "提现记录添加失败");
                        break;
                }
            }
        }

        $info_data = D('ZyWithdraw','classroom')->getUnfinished($this->mid,2);
        $data['data'] = $info_data[0];
        unset($info_data);

        $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
        $withdraw_basenum = $recharge_intoConfig['withdraw_basenum'];

        $data['withdraw_basenum'] = $withdraw_basenum;

        return $data;
    }

    //分成收入申请提现列表页面
    protected function spilt_take_list(){
        $map['uid']   = $this->mid;
        $map['wtype'] = 2;

        $limit = intval($this->limit) ? : 5;
        $st    = t($this->st) ? : 0;
        $et    = t($this->et) ? : 0;

        if($st){
            $map['ctime'][] = array('gt', $st);
        }
        if($et){
            $map['ctime'][] = array('lt', $et);
        }

        $data = D('ZyWithdraw','classroom')->order('ctime DESC, id DESC')->where($map)->findPage($limit);

        $total= D('ZyWithdraw','classroom')->where(array('uid'=>$this->mid,'status'=>2,'wtype'=>2))->sum('wnum');

        $flow_data['total'] = $total ? : 0;
        $flow_data['data'] = $data['data'] ? : [];
        unset($data);

        return $flow_data;
    }

    //取消提现
    public function cancleWithdraw(){
        $id = intval($this->id);
        if(!$id){
            $this->exitJson(array(), 0, '参数错误');
        }
        $result = D('ZyService','classroom')->setWithdrawStatus($id, $this->mid, 4);
        if ($result === true) {
            $this->exitJson(array(), 1, '取消成功');
        } else {
            switch ($result) {
                case 1:
                    $this->exitJson(array(), 0, "设置的状态不存在");
                    break;
                case 2:
                    $this->exitJson(array(), 0, "没有找到对应的提现记录");
                    break;
                case 3:
                    $this->exitJson(array(), 0, "收入余额冻结扣除失败");
                    break;
                case 4:
                    $this->exitJson(array(), 0, "学币解冻失败");
                    break;
                case 5:
                    $this->exitJson(array(), 0, "提现记录状态改变失败");
                    break;
                case 6:
                    $this->exitJson(array(), 0, "提现已完成或已经关闭");
                    break;
            }
        }
    }

    //申请提现
    public function applyWithdraw(){
        $card = D('ZyBcard','classroom')->getUserOnly($this->mid);
        if(!$card){
            $this->exitJson(array(), 0, '请先绑定银行卡！');
        }
    }

    //提现
    public function withdraw()
    {
        $card = D('ZyBcard', 'classroom')->getUserOnly($this->mid);
        if (!$card) {
            $this->exitJson(array(), 10037, '请先绑定银行卡');
        }
        $num    = intval($this->data['money']);
        $result = D('ZyService', 'classroom')->applyWithdraw($this->mid, $num, $card['id']);
        if (true === $result) {
            $this->exitJson(true, 10038, '申请提现成功');
        } else {
            $this->exitJson(array(), 10039, '申请提现失败');
        }
    }

    //用户账户积分
    public function credit(){
        $credit_info = M('credit_user')->where('uid = '.$this->mid)->find();

        //选择模版
        $tab = intval($_GET['tab']);
        $tpls = array('index','integral_list');
        if(!isset($tpls[$tab])) $tab = 0;
        $method = 'credit_'.$tpls[$tab];
        if(method_exists($this, $method)){
            $data_info = $this->$method();
        }

        if($tab == 0) {
            $config['credit_info'] = $credit_info;
            $account['learn'] = D('ZyLearnc','classroom')->where(['uid'=>$this->mid])->getField('balance') ? : 0;
            $account['split'] = D('ZySplit','classroom')->where(['uid'=>$this->mid])->getField('balance') ? : 0;
            $split_score = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $sple_score = array_filter(explode(':',$split_score['sple_score']))[1]/array_filter(explode(':',$split_score['sple_score']))[0];

            $config['pay_type'] = [
                ['pay_num' =>'lcnpay','pay_type_note'=>"当前账户可用¥{$account['learn']}",'balance'=>$account['learn']],
                ['pay_num' =>'spipay','pay_type_note'=>"当前账户可用¥{$account['split']}",'balance'=>$account['split']],
            ];
            $config['pay_note'] = "注：余额&收入与积分的兑换比例为 {$sple_score}";

            if (!$config) {
                $this->exitJson(array(), 0, "暂时没有相关信息");
            } else {
                $this->exitJson($config,1);
            }
        }

        if($tab == 1) {
            if (!$data_info) {
                $this->exitJson(array(), 0, "暂时没有相关流水");
            }

            $data['score'] = $credit_info['score'] ? : 0;
            $data = array_merge($data, $data_info);

            $this->exitJson($data,1);
        }
    }

    //充值积分
    public function rechargeScore(){
        $type = t($this->type);
        $exchange_score = intval(t($this->exchange_score));

        //系统设置的计算分成、余额与积分的比例值
        $split_score = model('Xdata')->get("admin_Config:rechargeIntoConfig");
        $sple_score = array_filter(explode(':',$split_score['sple_score']))[1]/array_filter(explode(':',$split_score['sple_score']))[0];

        //需要扣除的余额/收入
        $exchange_balance = $exchange_score / $sple_score;

        if($type == 'lcnpay'){
            $balance = D('ZyLearnc','classroom')->where(['uid'=>$this->mid])->getField('balance');

            if($exchange_score < 1){
                $this->exitJson(array(), 0, "充值的数量最少为1");
            }

            if(!floatval($balance)){
                $this->exitJson(array(), 0, "您暂无可充值的余额");
            }
            unset($balance);

            if(!D('ZyLearnc','classroom')->isSufficient($this->mid,$exchange_balance)){
                $this->exitJson(array(), 0, "您的余额不够此次充值的数量");
            }

            //扣除账号余额
            $consume = D('ZyLearnc','classroom')->consume($this->mid,$exchange_balance);
            if(!$consume){
                $this->exitJson(array(), 0, '出错了，请稍后再试');
            }

            $lnc_flow = D('ZyLearnc','classroom')->addFlow($this->mid,0,$exchange_balance,'充值为积分：'.$exchange_score,'','credit_user_flow');

            //添加积分并加相关流水
            $credit = model('Credit')->recharge($this->mid,$exchange_score);
            if($credit){
                $credit_flow = model('Credit')->addCreditFlow($this->mid,1,$exchange_score,$lnc_flow,'zy_split_balance','余额充值积分：'.$exchange_score);
                D('ZyLearnc','classroom')->flowModel->where(['id'=>$lnc_flow])->save(['rel_id'=>$credit_flow]);

                $this->exitJson(array(), 1, "充值成功");
            } else {
                $this->exitJson(array(), 0, "充值失败");
            }
        } else if($type == 'spipay'){
            $balance = D('ZySplit','classroom')->where(['uid' => $this->mid])->getField('balance');

            if ($exchange_score < 1) {
                $this->exitJson(array(), 0, "充值的数量最少为1");
            }

            if (!floatval($balance)) {
                $this->exitJson(array(), 0, "您暂无可充值的收入");
            }
            unset($balance);

            if (!D('ZySplit','classroom')->isSufficient($this->mid, $exchange_balance)) {
                $this->exitJson(array(), 0, "您的收入余额不够此次充值的数量");
            }

            //扣除分成收入
            $consume = D('ZySplit','classroom')->consume($this->mid, $exchange_balance);
            if (!$consume) {
                $this->exitJson(array(), 0, "出错了，请稍后再试");
            }

            //加相关分成扣除流水
            $split_flow = D('ZySplit','classroom')->addFlow($this->mid, 0, $exchange_balance, '充值为积分：' . $exchange_score, '', 'credit_user_flow');

            //添加积分并加相关流水
            $credit = model('Credit')->recharge($this->mid, $exchange_score);
            if ($credit) {
                $credit_flow = model('Credit')->addCreditFlow($this->mid, 1, $exchange_score, $split_flow, 'zy_split_balance', '收入充值积分：' . $exchange_score);
                D('ZySplit','classroom')->flowModel->where(['id' => $split_flow])->save(['rel_id' => $credit_flow]);

                $this->exitJson(array(), 1, "充值成功");
            } else {
                $this->exitJson(array(), 1, "充值失败");
            }
        }
    }

    //用户积分流水记录
    protected function credit_integral_list(){
        $map = array('uid'=>$this->mid);  //获取用户id

        $limit = intval($this->limit) ? : 5;
        $st    = t($this->st) ? : 0;
        $et    = t($this->et) ? : 0;

        if($this->st){
            $map['ctime'][] = array('gt', $st);
        }
        if($this->et){
            $map['ctime'][] = array('lt', $et);
        }
        $data = M('credit_user_flow')->where($map)->order('ctime DESC,id DESC')->findPage($limit);
        foreach($data['data'] as $key=>$value){
            switch ($value['type']){
                case 0:$data['data'][$key]['type'] = "消费";break;
                case 1:$data['data'][$key]['type'] = "充值";break;
                case 2:$data['data'][$key]['type'] = "冻结";break;
                case 3:$data['data'][$key]['type'] = "解冻";break;
                case 4:$data['data'][$key]['type'] = "冻结扣除";break;
                case 5:$data['data'][$key]['type'] = "分成收入";break;
                case 6:$data['data'][$key]['type'] = "增加积分";break;
                case 7:$data['data'][$key]['type'] = "扣除积分";break;
            }
        }
        $total= M('credit_user_flow')->where($map)->sum('num');

        $flow_data['total'] = $total ? : 0;
        $flow_data['data'] = $data['data'] ? : [];
        unset($data);

        return $flow_data;
    }

    //处理收入 转为余额/提现
    public function applySpiltWithdraw(){
        $tw_type = t($this->type);
        $exchange_balance = floatval(t($this->exchange_balance));

        if($tw_type == 'lcnpay'){//收入兑换余额
            $balance = D('ZySplit','classroom')->where(['uid'=>$this->mid])->getField('balance');

            if($exchange_balance < 1){
                $this->exitJson(array(), 0, '兑换的数量最少为1');
            }

            if(!floatval($balance)){
                $this->exitJson(array(), 0, '您暂无可兑换的收入');
            }
            unset($balance);

            if(!D('ZySplit','classroom')->isSufficient($this->mid,$exchange_balance)){
                $this->exitJson(array(), 0, '您的收入余额不够此次兑换的数量');
            }

            //扣除分成收入
            $consume = D('ZySplit','classroom')->consume($this->mid,$exchange_balance);
            if(!$consume){
                $this->exitJson(array(), 0, '出错了，请稍后再试');
            }
            $split_flow = D('ZySplit','classroom')->addFlow($this->mid,0,$exchange_balance,'转账为余额：'.$exchange_balance,'','zy_learncoin_flow');

            //添加余额并加相关流水
            $learnc = D('ZyLearnc','classroom')->recharge($this->mid,$exchange_balance);
            if($learnc){
                $learnc_flow = D('ZyLearnc','classroom')->addFlow($this->mid,1,$exchange_balance,'收入转账为余额：'.$exchange_balance,$split_flow,'zy_split_balance');
                D('ZySplit','classroom')->flowModel->where(['id'=>$split_flow])->save(['rel_id'=>$learnc_flow]);

                $this->exitJson(array(), 1, '转账成功');
            } else {
                $this->exitJson(array(), 0, '转账失败');
            }
        } else if($tw_type == 'unionpay') {//申请提现-银行卡
            $card = D('ZyBcard','classroom')->getUserOnly($this->mid);
            if (!$card[0]) {
                $this->exitJson(array(), 0, '请先绑定银行卡');
            }
            $card_id = intval($this->card_id);

            $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $withdraw_basenum = $recharge_intoConfig['withdraw_basenum'];

            if($exchange_balance<0||$exchange_balance<$withdraw_basenum||$exchange_balance%$withdraw_basenum!=0){
                $this->exitJson(array(), 0, "只允许提现为{$withdraw_basenum}的倍数");
            }

            $result = D('ZyService','classroom')->applySpiltWithdraw($this->mid, $exchange_balance, $card_id);

            if (true === $result) {
                $this->exitJson(array(), 1, '提现申请成功，请等待审核');
            } else {
                switch ($result) {
                    case 1:
                        $info = "申请提现的收入余额不是系统指定的倍数，或小于0";
                        break;
                    case 2:
                        $info = "没有找到用户对应的提现银行卡/账户";
                        break;
                    case 3:
                        $info = "有未完成的提现记录，需要等待完成";
                        break;
                    case 4:
                        $info = "余额转冻结失败：可能是余额不足";
                        break;
                    case 5:
                        $info = "提现记录添加失败";
                        break;
                }
                $this->exitJson(array(), 0, $info);
            }
        } else if($tw_type == 'alipay') {
            $card = D('ZyBcard','classroom')->getUserOnlyAliAccount($this->mid);
            if (!$card) {
                $this->exitJson(array(), 0, "请先绑定支付宝");
            }

            $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $withdraw_basenum = $recharge_intoConfig['withdraw_basenum'];

            if ($exchange_balance < 0 || $exchange_balance < $withdraw_basenum || $exchange_balance % $withdraw_basenum != 0) {
                $this->exitJson(array(), 0, "只允许提现为{$withdraw_basenum}的倍数");
            }

            $result = D('ZyService','classroom')->applySpiltWithdraw($this->mid, $exchange_balance, $card['id']);

            if (true === $result) {
                $this->exitJson(array(), 1, "提现申请成功，请等待审核");
            } else {
                switch ($result) {
                    case 1:
                        $info = "申请提现的收入余额不是系统指定的倍数，或小于0";
                        break;
                    case 2:
                        $info = "没有找到用户对应的提现银行卡/账户";
                        break;
                    case 3:
                        $info = "有未完成的提现记录，需要等待完成";
                        break;
                    case 4:
                        $info = "余额转冻结失败：可能是余额不足";
                        break;
                    case 5:
                        $info = "提现记录添加失败";
                        break;
                }
                $this->exitJson(array(), 0, $info);
            }
        }
    }

    //支付宝账户
    public function alipayInfo(){
        $alipay_info = D('ZyBcard','classroom')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->field('id,account,accountmaster')->find();

        if($alipay_info){
            $alipay_info['account'] = hideStar($alipay_info['account']);
            $alipay_info['accountmaster'] = hideStar($alipay_info['accountmaster']);
        }

        if (!$alipay_info) {
            $this->exitJson(array(), 0, "暂时没有相关信息");
        } else {
            $this->exitJson($alipay_info,1);
        }
    }

    //绑定、修改支付宝账号
    public function saveAlipay(){
        $real_name      = t($this->real_name);
        $alipay_account = t($this->alipay_account);
        if(!$real_name){
            $this->exitJson(array(), 0, "请输入真实姓名");
        }
        if(!$alipay_account){
            $this->exitJson(array(), 0, "请输入支付宝账号");
        }
        $phone = "/^1[3|4|5|7|8][0-9]\d{8}$/";
        $email = "/^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/";
        if (!preg_match( $phone, $alipay_account) &&  !preg_match( $email, $alipay_account)){
            $this->exitJson(array(), 0, "请输入正确的支付宝账号");
        }

        $data['uid'] = $this->mid;
        $data['account'] = $alipay_account;
        $data['accountmaster'] = $real_name;
        $data['accounttype'] = "alipay";

        $is_bond = D('ZyBcard','classroom')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->getField('id');

        if(!$is_bond){
            $res = D('ZyBcard','classroom')->add($data);
        } else {
            $res = D('ZyBcard','classroom')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->save($data);
        }
        if($res){
            $this->exitJson(array(), 1, "操作成功");
        }else{
            $this->exitJson(array(), 0, "操作成功");
        }
    }

    //删除绑定支付宝账号
    public function doBondAlipay(){
        $bond = D('ZyBcard','classroom')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->delete();

        if($bond){
            $this->exitJson(array(), 1, "解绑成功");
        }else{
            $this->exitJson(array(), 0, "解绑失败");
        }
    }

    //添加新的银行卡界面
    public function addBankCard()
    {
        $area_id = intval($this->area_id);

        $data = [];
        if($area_id == -1){
            $data["bank"] = D('ZyBcard','classroom')->getBanks();
        } else {
            $data["area"] = M("area")->where(['pid'=>$area_id])->findAll();
        }
        $this->exitJson($data,1);
    }

    //银行卡列表
    public function banks(){
        $card_list = D('ZyBcard','classroom')->getUserBcard($this->mid,'id,accounttype,account,accountmaster');
        if($card_list) {
            foreach ($card_list as $key => &$val) {
                $val['card_info_endnum'] = substr($val['account'], -4);
                $val['card_info_abb'] = hideStar($val['account']);
            }
        }

        $data['card_list'] = $card_list ? : [];
        $this->exitJson($data,1);
    }

    //添加银行卡方法
    public function saveBankCard()
    {
        $id            = intval($this->data['id']);
        $account       = $this->data['account']; //获取银行卡号
        $accountmaster = $this->data['accountmaster']; //获取姓名
        $accounttype   = $this->data['accounttype']; //获取账号类型
        $bankofdeposit = $this->data['bankofdeposit']; //开户行地址
        $location      = $this->data['location']; //省市区名称
        $province      = $this->data['province']; //省ID
        $city          = $this->data['city']; //市ID
        $area          = $this->data['area']; //区ID
        $tel_num       = $this->data['tel_num']; //获取银行预留手机号
        $userAuthInfo  = D('user_verified')->where('verified=1 AND uid=' . $this->mid)->find();
        //if(!$userAuthInfo){
        //  $this->exitJson( array() ,10028,'请先进行认证！');
        //}
        if ($account == "") {
            $this->exitJson(array(), 10028, '请输入账号！');
        }

        if ($accountmaster == "") {
            $this->exitJson(array(), 10028, '请输入开户姓名！');
        }

        if (strlen($account) < 16) {
            $this->exitJson(array(), 10028, '银行卡号不合法！');
        }

        if ($accounttype == "") {
            $this->exitJson(array(), 10028, '银行卡类型不合法！');
        }

        $card_count = D('ZyBcard','classroom')->where(array('uid'=>$this->mid,'is_school'=>0,'accounttype'=>['neq','alipay']))->field('id')->count();
        if($card_count >= 10){
            $this->exitJson(array(), 10028, '最多绑定10张银行卡');
        }

        if (empty($id)) {
            $res = M("zy_bcard")->where(['account'=>$account,'uid'=>$this->mid])->find();

            if ($res) {
                $this->exitJson(array(), 10028, '该账号已存在,请重新输入！');
            }
        }
        if (!preg_match("/^1[34578]\d{9}$/", $tel_num)) {
            $this->exitJson(array(), 10028, '手机号不合法！');
        }

        $res = M("zy_bcard")->where(['account'=>$account,'uid'=>$this->mid])->find();
        if ($res) {
            $this->exitJson(array(), 10028, '该账号已存在,请重新输入！');
        }

        $data = array(
            'account'       => $account,
            'accountmaster' => $accountmaster,
            'accounttype'   => $accounttype,
            'bankofdeposit' => $bankofdeposit,
            'location'      => $location,
            'province'      => $province,
            'city'          => $city,
            'area'          => $area,
            'tel_num'       => $tel_num,
            'uid'           => $this->mid,
        );

        $res = M('ZyBcard')->add($data);

        if ($res) {
            $this->exitJson([],1);
        }
        $this->exitJson(array(), 10028, '对不起，添加失败！');
    }

    //解绑银行卡
    public function doBondUncard(){
        $id = intval($this->id);

        $bond = D('ZyBcard')->where(['id'=>$id,'uid'=>$this->mid,'accounttype'=>['neq','alipay']])->delete();

        if($bond){
            $this->exitJson([],1);
        }else{
            $this->exitJson(array(), 10028, '对不起，解绑失败！');
        }
    }

    //充值
    public function pay()
    {
        $pay_list = array('alipay','unionpay','wxpay','cardpay');
        if(!in_array($this->pay_for,$pay_list)){
            $this->exitJson(array(), 0, '支付方式错误');
        }

        $money_str = t($this->data['money']);
        $money = array_filter(explode('=>',$money_str))[0] ? : 0;

        if($this->pay_for != 'cardpay' && $money <= 0){
            $this->exitJson(array(), 0, '请选择或填写充值金额');
        }

        $re           = D('ZyRecharge', 'classroom');
        $pay_pass_num = date('YmdHis', time()) . mt_rand(1000, 9999) . mt_rand(1000, 9999);

        if ($_POST['pay'] == 'cardpay'){
            $note = "{$this->site['site_keyword']}-余额充值：充值卡 ".t($_POST['card_number']);
        }else{
            $note = "{$this->site['site_keyword']}-余额充值：{$money_str}元";
        }

        //添加充值记录
        $pay_id = $re->addRechange(array(
            'uid'          => $this->mid,
            'type'         => 2,
            'money'        => $money,
            'note'         => $note,
            'pay_type'     => $this->pay_for == 'wxpay' ? 'app_wxpay' : $this->pay_for,
            'pay_pass_num' => $pay_pass_num,
        ));
        if (!$pay_id) {
            $this->exitJson(array(), 0, '操作异常');
        }

        $pay_for = in_array($this->pay_for, array('alipay', 'wxpay','cardpay')) ? [$this->pay_for] : ['alipay', 'wxpay','cardpay'];
        foreach ($pay_for as $p) {
            switch ($p) {
                case "alipay":
                    $pay_data['alipay'] = $this->alipay(array(
                        'out_trade_no' => $pay_pass_num,
                        'total_fee'    => $money,
                        'subject'      => "{$this->site['site_keyword']}-余额充值",
                        'money_str'    => $money_str,
                    ), 'account');
                    break;
                case "wxpay":
                    $pay_data['wxpay'] = $this->wxpay([
                        'out_trade_no' => $pay_pass_num,
                        'total_fee'    => $money * 100,
                        'subject'      => "{$this->site['site_keyword']}-余额充值",
                        'money_str'    => $money_str,
                    ], 'account');
                    break;
                case 'cardpay':
                    $res = $this->cardpay([
                        'out_trade_no' => $pay_pass_num,
                        'card_number'  => t($this->card_number),
                        'subject'      => "{$this->site['site_keyword']}-充值卡充值余额",
                    ], 'account');
                    if($res === true){
                        $this->exitJson([], 1,"充值成功");
                    }else{
                        $this->exitJson([], 0,$res);
                    }
                    break;
            }
        }
        $this->exitJson($pay_data, 1);
    }

    //修改用户资料
    public function saveUserInfo()
    {
        $uname = filter_keyword(t($this->data['uname']));
        $sex   = intval($this->data['sex']) ? intval($this->data['sex']) : 1;
        $intro = filter_keyword(t($this->data['intro']));
        if ($uname == "") {
            $this->exitJson(array(), 10029, '请输入用户名');
        }

        if (strlen($uname) < 4) {
            $this->exitJson(array(), 10029, '对不起，用户名不合法！');
        }

        $res = M('user')->where(array('uname' => $uname, array('uid' => array('neq', $this->mid))))->find();
        if ($res) {
            $this->exitJson(array(), 10029, '对不起，此用户名已被使用！');
        }

        $map['uname'] = $uname;
        $map['sex']   = $sex;
        $map['intro'] = $intro;
        $id           = M('user')->where(array('uid' => $this->mid))->save($map);
        if ($id !== false) {
            model('User')->cleanCache($this->mid);
            $this->exitJson(true);
        } else {
            $this->exitJson(array(), 10029, '对不起，用户资料修改失败！');
        }
    }

    //用户统计接口
    public function userContent()
    {
        //获取用户id
        $userid = intval($this->data['uid']) ? intval($this->data['uid']) : $this->mid;
        //获取我购买的课程总数
        $data['videocont']     = (int) D("ZyOrder", 'classroom')->where(array('uid' => $this->mid, 'is_del' => 0))->count(); //加载我购买的课程总数
        $data['wdcont']        = (int) M('ZyWenda')->where(array('uid' => $this->mid, 'is_del' => 0))->count(); //加载我的问答数量
        $data['note']          = (int) M('ZyNote')->where(array('uid' => $this->mid))->count(); //加载笔记总数
        $data['follow']        = (int) M('UserFollow')->where(array('uid' => $userid))->count(); //加载关注数量
        $data['fans']          = (int) M('UserFollow')->where(array('fid' => $userid))->count(); //加载关注数量
        $data['collect_video'] = (int) M('zy_collection')->where(array('uid' => $userid, 'source_table_name' => 'zy_video'))->count(); //我收藏的课程
        $data['collect_album'] = (int) M('zy_collection')->where(array('uid' => $userid, 'source_table_name' => 'album'))->count(); //我收藏的班级
        $data['card']          = (int) M('zy_bcard')->where('uid=' . $this->mid)->count(); //我的银行卡
        $intr                  = M('user')->where('uid=' . $this->mid)->getField('intro'); //我的简介
        $data['intr']          = ($intr && $intr != 'null') ? $intr : '你什么都还没说哦';
        $data['balance']       = intval(M('zy_learncoin')->where('uid=' . $this->mid)->getField('balance')); //我的余额
        //未读评论数量
        $data['no_read_comment'] = (int) M('ZyComment')->where(array('fid' => $this->mid, 'is_del' => 0, 'is_read' => 0))->count();
        //未读系统消息数量
        $data['no_read_notify'] = (int) M('notify_message')->where(array('uid' => $this->mid, 'is_read' => 0))->count();
        //未读私信数量
        $data['no_read_message'] = intval(M('notify_message')->where('uid=' . $this->mid . ' and is_read=0')->count());
//        $data['no_read_message'] = intval ( M('message_member')->where( array('member_uid'=>$this->mid,'is_del'=>0) )->sum('new') );
        //$data['no_read_message'] = (int)model('Message')->getUnreadMessageCount($this->mid);
        //获取用户剩余积分
        $credit        = model('Credit')->getUserCredit($userid);
        $data['score'] = $credit['credit']['score']['value'] ?: 0;
        if ($data) {
            $this->exitJson($data);
        } else {
            $this->exitJson(array(), 10029, '没有获取到相应的数据');
        }
    }
    //获取最近访客
    public function getUserVisitor()
    {
        $this->mid = intval($this->data['uid']) ? intval($this->data['uid']) : $this->mid;
        $data      = M('ZyUserVisitor')->where(array('fuid' => $this->mid))->limit(5)->select();
        foreach ($data as &$val) {
            $val['userinfo'] = model('User')->getUserInfo($val['uid']);
        }
        $this->exitJson($data);
    }

    //获取支付宝相关信息
    public function getAlipayInfo()
    {
        $data = model('Xdata')->get('admin_Config:alipay');
        $this->exitJson($data);
    }

    /**
     * @name 添加用户学习记录
     */
    public function addRecord()
    {
        $map = [
            'uid' => $this->mid,
            'vid' => (int) $this->vid,
            'sid' => (int) $this->sid,

        ];
        $data = [
            'time'  => intval($this->time) ?: 0,
            'ctime' => time(),
        ];
        if (!$map['vid'] || !$map['sid']) {
            $this->exitJson((object) [], 0, '请指定学习的课程与章节');
        }
        $id = M('learn_record')->where($map)->getField('id');
        if ($id) {
            $res = M('learn_record')->where($map)->save($data);
        } else {
            $id = $res = M('learn_record')->add(array_merge($map, $data));
        }
        if ($res) {
            $this->exitJson(['record_id' => $id], 1, '添加记录成功');
        }
        $this->exitJson((object) [], 0, '添加记录失败');
    }

    /**
     * @name 获取学习记录
     */
    public function getRecord()
    {
        $map['is_del'] = 0;
        $map['uid']    = $this->mid;
        $data          = M('learn_record')->where($map)->order('ctime desc')->findPage($this->count);
        if ($data['gtLastPage'] === true) {
            $this->exitJson((object) [], 1, '暂时更多记录');
        }
        if ($data['data']) {
            foreach ($data['data'] as &$v) {
                $v['sid']           = (int) $v['sid'];
                $v['vid']           = (int) $v['vid'];
                $v['record_id']     = (int) $v['id'];
                $v['video_info']    = $this->_videoInfo($v['vid']);
                $v['video_section'] = $this->_getSessionInfo($v['sid']);
                unset($v['id'], $v['uid'], $v['is_del']);
            }
            unset($v);
        }
        $data['data'] ? $this->exitJson($data['data'], 1) : $this->exitJson((object) [], 1, '暂时没有记录');
    }

    /*
     * @name 批量删除学习记录
     */
    public function delRecords()
    {
        $ids = trim(t($this->sid), ",");
        if ($ids == "") {
            $ids = t($this->sid);
        }
        $where = array(
            'id' => array('in', $ids),
        );
        $where['uid']   = (!$this->user_id) ? $this->mid : $this->user_id;
        $data['is_del'] = 1;
        $res            = M('learn_record')->where($where)->save($data);
        if ($res) {
            $this->exitJson(true, 1, '删除记录成功');
        }
        $this->exitJson((object) [], 0, '删除记录失败');
    }

    /**
     * @name 获取课程的信息
     */
    private function _videoInfo($video_id = 0)
    {
        $data = [];
        if ($video_id) {
            static $video_list;
            if (isset($video_list[$video_id])) {
                $data = $video_list[$video_id];
            } else {
                $map['id'] = $video_id;
                $data      = D('ZyVideo', 'classroom')->where($map)->find();
                // 处理数据
                $data['video_score']         = round($data['video_score'] / 20); // 四舍五入
                $data['vip_level']           = intval($data['vip_level']);
                $data['reviewCount']         = D('ZyReview', 'classroom')->getReviewCount(1, intval($data['id']));
                $data['video_title']         = $data['video_title'];
                $data['video_intro']         = $data['video_intro'];
                $data['video_category_name'] = getCategoryName($data['video_category'], true);
                $data['cover']               = getCover($data['cover'], 280, 160);
                $data['iscollect']           = D('ZyCollection', 'classroom')->isCollect($data['id'], 'zy_video', intval($this->mid));
                $data['follower_count']      = (int) D('ZyCollection', 'classroom')->where(['source_table_name' => 'zy_video', 'source_id' => $data['id']])->count() ?: 0;
                $data['mzprice']             = getPrice($data, $this->mid, true, true);
                $data['isSufficient']        = D('ZyLearnc', 'classroom')->isSufficient($this->mid, $data['mzprice']['price']);
                $data['isGetResource']       = isGetResource(1, $data['id'], array(
                    'video',
                    'upload',
                    'note',
                    'question',
                ));
                $data['isBuy']       = D('ZyOrder', 'classroom')->isBuyVideo($this->mid, $id);
                $data['is_play_all'] = ($data['isBuy'] || floatval($data['mzprice']['price']) <= 0) ? 1 : 0;
                //$data ['user'] = M ( 'User' )->getUserInfo ( $data ['uid'] );
                $data['school_info']   = model('School')->getSchoolInfoById($data['mhm_id']);
                $video_list[$video_id] = $data;
            }
        }
        return $data;
    }

    /**
     * @name 获取视频章节信息
     */
    private function _getSessionInfo($sid = 0)
    {
        $data = [];
        if ($sid) {
            static $session_list;
            if (isset($session_list[$sid])) {
                $data = $session_list[$sid];
            } else {
                $data                  = D('VideoSection', 'classroom')->setTable('zy_video_section')->getCategoryInfo($sid);
                $data['video_address'] = M('zy_video_data')->where('status =1 and is_del=0 and id=' . $data['cid'])->getField('video_address') ?: '';
            }
        }
        return $data;
    }

    /**
     * @name 获取我创建的小组信息
     */
    public function getGroupList()
    {
        $this->user_id = (!$this->user_id) ? $this->mid : $this->user_id;

        $map['uid']    = $this->user_id;
        $map['is_del'] = 0;
        $group         = M('group')->where($map)->order('ctime desc')->findpage(9);

        foreach ($group['data'] as $key => $val) {
            $group['data'][$key]['logo'] = $this->logo_path_to_url($val['logo']);
            $group['data'][$key]['cid0'] = M('group_category')->where('id =' . $val['cid0'])->getField('title');
        }
        return $group['data'];
    }

    /**
     * @name 获取我加入的小组信息
     */
    public function getJoinGroupList()
    {
        $this->user_id = (!$this->user_id) ? $this->mid : $this->user_id;

        $map['uid']    = $this->user_id;
        $map['status'] = 1;
        $map['level']  = ['gt', 1];

        $gids = M('group_member')->where($map)->field('gid')->findAll();
        $gid  = getSubByKey($gids, 'gid');

        $mapGroup['is_del'] = 0;
        $mapGroup['id']     = ['in', $gid];
        $group              = M('group')->where($mapGroup)->order('ctime desc')->findpage(9);
        foreach ($group['data'] as $key => $val) {
            $group['data'][$key]['logo'] = $this->logo_path_to_url($val['logo']);
            $group['data'][$key]['cid0'] = M('group_category')->where('id =' . $val['cid0'])->getField('title');
        }
        return $group['data'];
    }

    private function logo_path_to_url($save_path, $width = 186, $height = 186)
    {
        $path = getImageUrl($save_path, $width, $height, true);
        if ($save_path != 'default.png') {
            return $path;
        } else {
            return SITE_URL . '/apps/group/_static/images/default.png';
        }

    }

    /*
     *我购买的线下课
     */
    public function getLineClassList(){
        $this->user_id = (!$this->user_id) ? $this->mid : $this->user_id;
        $size = $this->count ? $this->count : 10;

        $lineclass = D('ZyLineClass', 'classroom')->getUserBuyLineclass($this->user_id,$size);
        $lineclass['data'] ? $this->exitJson($lineclass['data']) : $this->exitJson(array(), 0, '没有购买的线下课程');
    }

    /*
     *我收藏的线下课
     */
    public function getCollectLineClass(){
        $this->user_id = (!$this->user_id) ? $this->mid : $this->user_id;
        $size = $this->count ? $this->count : 10;

        $collect = D('ZyCollection', 'classroom')->where(['source_table_name' => 'zy_teacher_course', 'uid' => $this->user_id])->findPage($size);
        foreach($collect['data'] as $key=>$val){
            $val = D('ZyLineClass', 'classroom')->getLineclassById($val['source_id'],2) ? : [];
            unset($val['source_table_name']);
            $collect['data'][$key] = $val;
        }
        $collect['data'] ? $this->exitJson($collect['data']) : $this->exitJson(array(), 0, '没有收藏的线下课程');
    }

    //所有会员等级
    public function getUserVipList(){
        $map['is_del'] = 0;
        $vip_data = M('user_vip')->where($map)->order('sort asc')->select();
        foreach ($vip_data as &$value) {
            $value['cover'] = getCover($value['cover'] , 100 ,100);
        }
        $this->exitJson($vip_data,1);
    }

    //获取当前用户vip等级
    public function getUserVip(){
        $user_vip_data = M('zy_learncoin')->where(array('uid'=>$this->mid))->find();
        unset($user_vip_data['balance']);
        unset($user_vip_data['frozen']);

        $vip_info = M('user_vip')->where('is_del=0 and id=' . $user_vip_data['vip_type'])->find();
        $user_vip_data['vip_type_txt'] = $vip_info['title'] ? : "开通会员";
        $user_vip_data['cover'] = getCover($vip_info['cover'] , 100 ,100);

        $this->exitJson($user_vip_data,1);
    }

    //获取最新的会员用户
    public function getNewUserVipList(){
        $limit = $this->limit ? : 6;
        $vip_uid = M('zy_learncoin')->where(['vip_type'=>['neq',0],'vip_expire'=>['egt',time()]])->order('ctime desc')->field('uid')->findAll();
        $vip_uid = array_filter(getSubByKey($vip_uid,'uid'));
        $vip_user = model('User')->where(['uid'=>['in',$vip_uid]])->limit($limit)->field('uid,uname')->select();
        foreach($vip_user as $key => $value){
            $vip_user[$key]['user_head_portrait'] = getUserFace($value['uid'],'m');
        }
        $this->exitJson($vip_user,1);
    }

    //充值
    public function rechargeVip()
    {
        $pay_list = array('alipay','unionpay','wxpay');
        if(!in_array($this->pay_for,$pay_list)){
            $this->exitJson(array(), 0, '支付方式错误');
        }

        $type = intval($this->user_vip);

        $vip = M('user_vip')->where('id=' . $type)->find();

        $vip_type_time  = $this->vip_type_time;
        $vip_time       = $this->vip_time >= 1 ? $this->vip_time : 1 ;
        if($vip_type_time == 'year'){
            $vip_length = "+{$vip_time} year";
            $money      = $vip['vip_year']*$vip_time;
            $vip_length_info = "{$vip_time}年";
        } else {
            $vip_length = "+{$vip_time} month";
            $money      = $vip['vip_month']*$vip_time;
            $vip_length_info = "{$vip_time}月";
        }

        $re = D('ZyRecharge','classroom');
        $pay_pass_num = date('YmdHis',time()).mt_rand(1000,9999).mt_rand(1000,9999);

        $id = $re->addRechange(array(
            'uid'      => $this->mid,
            'type'     => "3,{$type}",
            'vip_length' => $vip_length,
            'money'    => $money,
            'note'     => "{$this->site['site_keyword']}-{$vip['title']}充值+$vip_length_info",
            'pay_type' => $this->pay_for == 'wxpay' ? 'app_wxpay' : $this->pay_for,
            'pay_pass_num'=>$pay_pass_num,
        ));
        if(!$id)$this->exitJson(array(), 0, '操作异常');

        if($this->pay_for == 'alipay'){
            $pay_data['alipay'] = $this->alipay(array(
                'out_trade_no' => $pay_pass_num,
                'subject'      => "{$this->site['site_keyword']}-{$vip['title']}充值",
                'total_fee'    => $money,
            ),'vip');
        }elseif($this->pay_for == 'unionpay'){
            $this->unionpay(array(
                'out_trade_no' => $pay_pass_num,
                'money' => $money,
                'subject' => "{$this->site['site_keyword']}-{$vip['title']}充值",
            ),'vip');
        }elseif($this->pay_for == 'wxpay'){
            $pay_data['wxpay'] = $this->wxpay(array(
                'out_trade_no'  => $pay_pass_num,
                'total_fee'     => $money * 100,//单位：分
                'subject' => "{$this->site['site_keyword']}-{$vip['title']}充值",
            ),'vip');
        }
        $this->exitJson($pay_data, 1);
    }

    public function userVipCourse(){
        $time = time();
        $order = '';
        $where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time";

        if(t($this->vip_id)){
            $where .= " AND vip_level = {$this->vip_id}";
        }
        $limit = $this->limit ? : 4;
        $data = M('zy_video')->where($where)->order($order)->limit($limit)->field("id,uid,video_title,cover,
        mhm_id,teacher_id,v_price,t_price,vip_level,type,endtime,starttime,limit_discount,uid,teacher_id")->select();

        foreach ($data as &$value) {
            $value['price']       = getPrice($value, $this->mid); // 计算价格
            $value['imageurl']    = getCover($value['cover'], 280, 160);
            $value['video_score'] = round($value['video_score'] / 20); // 四舍五入
            $value['teacher_name'] = D('ZyTeacher', 'classroom')->where(array('id' => $value['teacher_id']))->getField('name');
            $value['buy_count']     = (int) D('ZyOrderCourse', 'classroom')->where(array('video_id' => $value['id']))->count();
            $value['section_count'] = (int) M('zy_video_section')->where(['vid' => $value['id'], 'pid' => ['neq', 0]])->count();
        }
        $this->exitJson($data ? : array(),1);
    }


}
