<?php
/**
 * 机构列表详情
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminSchoolAction extends AdministratorAction
{
    /**
     * 机构管理
     * @return void
     */
    public function _initialize()
    {

        $this->pageTitle['index']       = '已审';
        $this->pageTab[] = array('title'=>'已审','tabHash'=>'index','url'=>U('school/AdminSchool/index'));


        if(is_admin($this->mid)){
            $this->pageTitle['verify']       = '资格审核';
            $this->pageTab[] = array('title'=>'资格审核','tabHash'=>'verify','url'=>U('school/AdminSchool/verify'));
            $this->pageTitle['editVerify']  = '信息更新审核';
            $this->pageTab[] = array('title'=>'信息更新审核','tabHash'=>'editVerify','url'=>U('school/AdminSchool/editVerify'));
            $this->pageTitle['divideVerify']  = '分成比例审核';
            $this->pageTab[] = array('title'=>'分成比例审核','tabHash'=>'divideVerify','url'=>U('school/AdminSchool/divideVerify'));
//            $this->pageTitle['buyideVerify']  = '机构与购买机构分成比例审核列表';
//            $this->pageTab[] = array('title'=>'机构与购买机构分成比例审核列表','tabHash'=>'buyideVerify','url'=>U('school/AdminSchool/buyideVerify'));
//            $this->pageTitle['pinclassVerify']  = '机构与销课者分成比例审核列表';
//            $this->pageTab[] = array('title'=>'机构与销课者分成比例审核列表','tabHash'=>'pinclassVerify','url'=>U('school/AdminSchool/pinclassVerify'));
            $this->pageTitle['addSchool']    = '添加';
            $this->pageTab[] = array('title'=>'添加','tabHash'=>'addSchool','url'=>U('school/AdminSchool/addSchool'));
        }
        parent::_initialize();

    }
    /**
     * 机构管理
     */
    public function index(){
        $_REQUEST['tabHash'] = 'index';
		if(is_admin($this->mid)){
        // 管理分页项目
            $this->pageKeyList = array( 'id','title','logo','uid','doadmin','videoSpace','info','school_vip','cuid','school_and_oschool','max_price_sys','status','is_re','ctime','DOACTION');//,'school_and_teacher'
            $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
            $this->pageButton[] = array('title' => '禁用', 'onclick' => "admin.mzSchool('','closeSchool','禁用','机构')");

            //搜索字段
            $this->searchKey = array('id','uid','title','doadmin','status','is_best','is_cete_floor',array('ctime','ctime1'),'quanzhong');
			//数据的格式化
			$order = 'id desc';
            $this->opt['status'] = array( '0' => '全部', '1' => '审核未通过','2' =>'已审核','3' => '禁用');
            $this->opt['is_best']   = array('0'=>'不限','1'=>'否','2'=>'是');
            $this->opt['is_cete_floor']   = array('0'=>'不限','1'=>'否','2'=>'是');
            $this->opt['quanzhong'] = array('best_sort asc'=>'精选推荐','cete_floor_sort asc'=>'首页分类楼层推荐');
			$list = $this-> _getCommnetList('index',null,$order,20);
			$this->assign('pageTitle', '机构管理');
			$this->_listpk = 'id';
			$this->allSelected = true;
			//$list = array_values($list);
			$this->displayList($list);
		}else{
			$data = model('School')->getSchoolInfoById(is_school($this->mid));
            $data['doadmin'] = $data['doadmin'] ? $data['doadmin'].".".$_SERVER["HTTP_HOST"] : "";
			$this->assign($data);
			$this->display('school');
		}

    }


    /***
     * @param $type
     * @param $limit
     * @param $order
     * @return mixed
     * 机构管理列表
     */
    private function _getCommnetList($type,$limit,$order){
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['doadmin'] && $map['doadmin'] = array('like', '%' . t($_POST['doadmin']) . '%');
            $_POST['info'] && $map['info'] = array('like', '%' . t($_POST['info']) . '%');
            $_POST['title'] && $map['title'] = array('like', '%' . t($_POST['title']) . '%');
            $_POST ['uid'] && $map ['uid'] = array('in', (string)$_POST ['uid']);
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) {   //时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }
            $status = t($_POST['status']);
            switch ($status) {
                case 1:
                    $map['status']  = 3;
                    break;
                case 2:
                    $map['status']  = 1;
                    break;
                case 3:
                    $map['status']  = 2;
                    break;
                default;
            }
            if($_POST ['quanzhong'] ){
                $order = '';
                foreach ($_POST ['quanzhong'] as $val)
                {
                    $order .= $val.",";
                }
                $order = substr($order,0,strlen($order)-1);
            }
            if($_POST ['is_best'] == 1){
                $map ['is_best'] = 0;
            }else if($_POST ['is_best'] == 2){
                $map ['is_best'] = 1;
            }
            if($_POST ['is_cete_floor'] == 1){
                $map ['is_cete_floor'] = 0;
            }else if($_POST ['is_cete_floor'] == 2){
                $map ['is_cete_floor'] = 1;
            }
        }else{
            $map['status'] = array('in','1,2,3');
        }
        if(is_admin($this->mid)){
            $school = model('School')->where($map)-> order($order)->findPage($limit);
        }else{
            $map['uid'] = $this->mid ;
            $school = model('School')->where($map)-> order($order)->findPage($limit);
        }
        foreach($school['data'] as $key => $val) {
            if(!$val['doadmin']){
                $url = U('school/School/index', array('id' => $val['id']));
            }else{
                $url = getDomain($val['doadmin']);
            }
            $school['data'][$key]['title'] = getQuickLink($url,$val['title'],"未知机构");

            $school['data'][$key]['info'] = mb_substr($val['info'],0,20,'utf-8')."...";
            $school['data'][$key]['logo']  = "<img src='".getCover($val['logo'] , 60 ,60)."' width='60px' height='60px'>";
            $school['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $school['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            $school['data'][$key]['cuid'] = getUserSpace($val['cuid'], null, '_blank');
            $school['data'][$key]['school_vip'] = M('school_vip')->where(['id'=>$val['school_vip']])->getField('title') ?: '普通机构';
            if($val['doadmin']){
                $school['data'][$key]['doadmin'] = $val['doadmin'].'.'.$_SERVER["HTTP_HOST"];
            }
            if ($val['status'] == 1) {
                $school['data'][$key]['status'] = "<p style='color: green;'>已审核</p>";
            }else if ($val['status'] == 2) {
                $school['data'][$key]['status'] = "<p style='color: gray;'>禁用</p>";
            }else if ($val['status'] == 3) {
                $school['data'][$key]['status'] = "<p style='color: red;'>审核未通过</p>";
            }
            if ($val['is_re'] == 0) {
                $school['data'][$key]['is_re'] = "<p style='color: red;'>未推荐</p>";
            }else if ($val['is_re'] == 1) {
                $school['data'][$key]['is_re'] = "<p style='color: green;'>已推荐</p>";
            }
            $school['data'][$key]['DOACTION'] =  '<a href="'.U('school/AdminSchool/editSchool',array('id'=>$val['id'],'tabHash'=>'editSchool')).'">编辑</a>';
            if(is_admin($this->mid)){
                if ($val['is_re'] == 0) {
                    $school['data'][$key]['DOACTION'] .=  ' | <a href="javascript:admin.mzSchool(' . $val['id'] . ',\'reSchool\',\'推荐该\',\'机构\');"> 设置推荐</a>';
                }else if ($val['is_re'] == 1) {
                    $school['data'][$key]['DOACTION'] .=  ' | <a href="javascript:admin.mzSchool(' . $val['id'] . ',\'cancelReSchool\',\'取消推荐该\',\'机构\');"> 取消推荐</a>';
                }
                if ($val['status'] == 1) {
                    $school['data'][$key]['DOACTION'] .= ' | <a href="javascript:void(0)" onclick="admin.mzGetVerifyBox('.$val['id'].',3)">驳回</a>';
                }

                if ($val['status'] != 2) {
                    $school['data'][$key]['DOACTION'] .=  ' | <a href="javascript:admin.mzSchool(' . $val['id'] . ',\'closeSchool\',\'禁用\',\'机构\',' . $val['uid'] . ');"> 禁用</a>';

                }else if ($val['status'] == 2) {
					$school['data'][$key]['DOACTION'] .=  ' | <a href="javascript:admin.mzSchool(' . $val['id'] . ',\'closeSchool\',\'启用\',\'机构\',' . $val['uid'] . ');"> 启用</a>';
                    $school['data'][$key]['DOACTION'] .=  ' | <a href="javascript:admin.mzSchool(' . $val['id'] . ',\'delSchool\',\'彻底删除\',\'机构\',' . $val['uid'] . ');"> 彻底删除</a>';
                }
            }
        }
        $this->assign('pageTitle','机构管理列表');
        $this->_listpk = 'id';
        $this->allSelected = true;
        return $school;
    }

    /**
     * 禁用操作
     * @return void
     */
    public function  closeSchool()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array('id' => array('in', $id));
		$status = model('School')->where($where)->getField('status');
		if ($status != 2) {
            $data['status'] = 2;
        } else {
            $data['status'] = 0;
        }
        $ret = model('School')->where($where)->save($data);
        if ($ret == true) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
        } else {
            $msg['data'] = '操作失败';
            $msg['status'] = 0;
        }
        echo json_encode($msg);
        exit();
    }

    /**
     * 推荐操作
     * @return void
     */
    public function  reSchool()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array('id' => array('in', $id));
        $data['is_re'] = 1;
        $ret = M('School')->where($where)->save($data);
        if ($ret == true) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
        } else {
            $msg['data'] = '操作失败';
            $msg['status'] = 0;
        }
        echo json_encode($msg);
        exit();
    }
    /**
     * 取消推荐操作
     * @return void
     */
    public function  cancelReSchool()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array('id' => array('in', $id));
        $data['is_re'] = 0;
        $ret = M('School')->where($where)->save($data);
        if ($ret == true) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
        } else {
            $msg['data'] = '操作失败';
            $msg['status'] = 0;
        }
        echo json_encode($msg);
        exit();
    }

    /**
     * 彻底删除操作
     * @return void
     */
    public function  delSchool()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array('id' => array('in', $id));
        $ret = M('School')->where($where)->delete();
        if ($ret == true) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
        } else {
            $msg['data'] = '操作失败';
            $msg['status'] = 0;
        }
        echo json_encode($msg);
        exit();
    }

    /**
     * 机构认证审核
     */
    public function verify(){
        $_REQUEST['tabHash'] = 'verify';

        $this->pageKeyList = array( 'id','title','uid','idcard','phone','reason','location', 'address','attachment','certification','ctime','DOACTION');
        if(is_admin($this->mid)){
            $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
            $this->pageButton[] = array('title'=>'驳回','onclick'=>"admin.mzVerifySchool('',3)");
            //搜索字段
            $this->searchKey = array('id','uid','title', 'idcard',array('ctime','ctime1'));
        }
        //数据的格式化
        $order = 'id desc';
        $map['status'] = 0;
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['idcard'] && $map['idcard'] = intval(t($_POST['idcard']));
            $_POST['title'] && $map['title'] = array('like', '%' . t($_POST['title']) . '%');
            $_POST ['uid'] && $map ['uid'] = array('in', (string)$_POST ['uid']);
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) {   //时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }
        }
        $list = M('School')->where($map)-> order($order)->findPage($limit);

        foreach($list['data'] as $key => $val) {
            $list['data'][$key]['title'] = $val['title'];
            if($list['data'][$key]['identity_id']){
                $a = explode(',', $list['data'][$key]['identity_id']);
                $list['data'][$key]['attachment'] = "";
                foreach($a as $k=>$v){
                    if($v !== ""){
                        $attachInfo = D('attach')->where("attach_id=$a[$k]")->find();
                        $list['data'][$key]['attachment'] .= msubstr($attachInfo['save_name'],0,25,"UTF-8",ture).'&nbsp;<a href="'.getImageUrl($attachInfo['save_path']).$attachInfo['save_name'].'" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            if($list['data'][$key]['attach_id']){
                $a = explode(',', $list['data'][$key]['attach_id']);
                $list['data'][$key]['certification'] = "";
                foreach($a as $k=>$v){
                    if($v !== ""){
                        $attachInfo = D('attach')->where("attach_id=$a[$k]")->find();
                        $list['data'][$key]['certification'] .= msubstr($attachInfo['save_name'],0,25,"UTF-8",ture).'&nbsp;<a href="'.getImageUrl($attachInfo['save_path']).$attachInfo['save_name'].'" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $list['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            $list['data'][$key]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.mzVerifySchool('.$val['id'].',1)">通过</a> - ';
            $list['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.mzGetVerifyBox('.$val['id'].')">驳回</a>';
        }
        $this->assign('pageTitle', '机构认证审核');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }

    /**
     * 驳回理由 窗口 （机构审核）
     * @return void
     */
    public function getVerifyBox () {
        $id = intval($_GET['id']);
        $this->assign('id', $id);
        $this->assign('action','doVerify');
        $this->display('verify');
    }

    /**
     * 执行认证审核
     * @return json 返回操作后的JSON信息数据
     */
    public function doVerify(){
        $status = intval($_POST['status']);
        $id = $_POST['id'];

        if(is_array($id)){
            $map['id'] = array('in',$id);
        }else{
            $map['id'] = $id;
        }
        $appuid = M('school') ->where('id = '.$id) ->getField('uid');
        $data['status'] = $status;
        $data['cuid'] = intval($this->mid);
        $res = model('School')->where($map)->save($data);

        if($res){
            $return['status'] = 1;
            if($status == 1) {
                $mhdata['mhm_id'] = $id;
                M('user') ->where('uid ='.$appuid) -> save($mhdata);
                if (is_array($id)) {
                    foreach ($id as $k => $v) {
                        $user_group = model('School')->where('id=' . $v)->find();
                        $maps['uid'] = $user_group['uid'];
                        $maps['user_group_id'] = '4';
                        $exist = D('user_group_link')->where($maps)->find();
                        if ($exist) {
                            continue;
                        }
                        D('user_group_link')->add($maps);

                        model('User')->cleanCache($user_group['uid']);
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_' . $user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_' . $user_group['uid']);
                        // 删除微博信息
                        $feed_ids = model('Feed')->where('uid=' . $user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);
                        model('Notify')->sendNotify($user_group['uid'], 'admin_school_doverify_ok');

                        unset($user_group);
                        unset($maps);
                    }
                } else {
                    $user_group = model('School')->where('id=' . $id)->find();
                    $maps['uid'] = $user_group['uid'];
                    $maps['user_group_id'] = '4';
                    $exist = D('user_group_link')->where($maps)->find();
                    if (!$exist) {
                        D('user_group_link')->add($maps);
                        model('User')->cleanCache($user_group['uid']);
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_' . $user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_' . $user_group['uid']);
                        // 删除微博信息
                        $feed_ids = model('Feed')->where('uid=' . $user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);
                        model('Notify')->sendNotify($user_group['uid'], 'admin_school_doverify_ok');

                        unset($user_group);
                        unset($maps);
                    }
                }
                $credit = M('credit_setting')->where(array('id'=>40,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $ztype = 6;
                    $note = '申请成为机构获得的积分';
                }
                model('Credit')->addUserCreditRule($appuid,$ztype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                $return['data'] = "通过审核";
            }
            if($status == 3){
                $return['data']	  = "驳回成功";
                $rejectInfo = array('reason'=>t($_POST['reason']));
                $data['rejectInfo'] = t($_POST['reason']);
                if(is_array($id)){
                    foreach($id as $k=>$v){
                        model('School')->where('id='.$id)->save($data);
                        $user_group = model('School')->where('id='.$v)->find();
                        $maps['uid'] = $user_group['uid'];
                        $maps['user_group_id'] = '4';
                        D('user_group_link')->where($maps)->delete();

                        model('User')->cleanCache($user_group['uid']);
                        // 清除用户组缓存
                        model ( 'Cache' )->rm ('user_group_'.$user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_'.$user_group['uid']);
                        // 删除微博信息
                        $feed_ids = model('Feed')->where('uid='.$user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);

                        model('Notify')->sendNotify($user_group['uid'],'admin_school_doverify_reject', $rejectInfo);
                        unset($user_group);
                        unset($maps);
                    }
                }else{
                    model('School')->where('id='.$id)->save($data);
                    $user_group = model('School')->where('id='.$id)->find();
                    $maps['uid'] = $user_group['uid'];
                    $maps['user_group_id'] = '4';
                    D('user_group_link')->where($maps)->delete();

                    model('User')->cleanCache($user_group['uid']);
                    // 清除用户组缓存
                    model ( 'Cache' )->rm ('user_group_'.$user_group['uid']);
                    // 清除权限缓存
                    model('Cache')->rm('perm_user_'.$user_group['uid']);
                    // 删除微博信息
                    $feed_ids = model('Feed')->where('uid='.$user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                    model('Feed')->cleanCache($feed_ids);

                    model('Notify')->sendNotify($user_group['uid'],'admin_school_doverify_reject', $rejectInfo);
                }
            }
        }else{
            $return['status'] = 0;
            $return['data']   = "操作失败";
        }
        echo json_encode($return);exit();
    }
    /*
     *机构信息审核
     */
    public function editVerify(){
        $_REQUEST['tabHash'] = 'editVerify';
        $this->pageKeyList = array( 'id','mhm_id','title','category','info','ctime','DOACTION');
        if(is_admin($this->mid)){
            $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
            //搜索字段
            $this->searchKey = array('id','mhm_id','title',array('ctime','ctime1'));
        }
        //数据的格式化
        $order = 'id desc';
        $map['verified'] = 0;
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['mhm_id'] && $map['mhm_id'] = intval(t($_POST['mhm_id']));
            $_POST['title'] && $map['title'] = array('like', '%' . t($_POST['title']) . '%');
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) {   //时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }
        }
        $list = M('school_edit_verified')->where($map)-> order($order)->findPage($limit);

        foreach($list['data'] as $key => $val) {
            $domain = model('School')->where('id='.$val['mhm_id'])->getField('doadmin');
            if(!$domain){
                $url = U('school/School/index', array('id' => $val['mhm_id']));
            }else{
                $url = getDomain($domain);
            }
            $list['data'][$key]['title'] = getQuickLink($url,$val['title'],"未知机构");

            $list['data'][$key]['info'] = msubstr($val['info'],0,20,"UTF-8",true);

            $ceta_name_data = array_filter(explode(',', $val['fullcategorypath']));
            foreach ($ceta_name_data as $k => $v){
                $ceta_name .= M('school_category')->where(array('school_category_id'=>$v))->getField('title')." ";
            }
            $list['data'][$key]['category'] = trim($ceta_name,' ');

            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $list['data'][$key]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.mzEditVerifySchool('.$val['id'].',1)">通过</a> - ';
            $list['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.mzEditGetVerifyBox('.$val['id'].')">驳回</a>';
        }
        $this->assign('pageTitle', '机构信息审核');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }
    /**
     * 驳回理由 窗口（机构信息）
     * @return void
     */
    public function getEditVerifyBox () {
        $id = intval($_GET['id']);
        $this->assign('id', $id);
        $this->assign('action','doEditVerify');
        $this->display('verify');
    }

    /**
     * 执行信息审核
     * @return json 返回操作后的JSON信息数据
     */
    public function doEditVerify(){
        $id = $_POST['id'];
        $status = intval($_POST['status']);
        if($status == 1) {
            $school_edit = M('school_edit_verified')->where('id='.$id)->find();
            //修改机构信息
            if($school_edit["verified"]==0){
                $data["title"]   = $school_edit['title'];
                $data["school_category"]  = $school_edit['school_category'];
                $data["fullcategorypath"]  = $school_edit['fullcategorypath'];
                $data["info"]  = $school_edit['info'];

                model("School")->where('id='.$school_edit['mhm_id'])->save($data);
            }
            $data['verified'] = "1";
            M('school_edit_verified')->where('id='.$id)->save($data);
            $return['data'] = "通过审核";
        }
        if($status == 3){
            $return['data']	  = "驳回成功";
            $mhm_id = M('school_edit_verified')->where('id='.$id)->getField('mhm_id');
            $s['uid'] = model('School')->where('id='.$mhm_id)->getField('uid');
            $rejectInfo = t($_POST['reason']);

            $s['title'] = "抱歉，您的机构信息修改申请被驳回。驳回理由：$rejectInfo";
            $s['body'] = "抱歉，您的机构信息修改申请被驳回。驳回理由：$rejectInfo";
            $s['ctime'] = time();
            model('Notify')->sendMessage($s);
            M('school_edit_verified')->where('id='.$id)->delete();
        }
        $return['status'] = 1 ;
        echo json_encode($return);exit();
    }
    /**
     *机构与挂载机构分成比例审核
     *@return json 返回操作后的JSON信息数据
     */
    public function divideVerify() {
        $_REQUEST['tabHash'] = 'divideVerify';
        $this->pageKeyList = array( 'id','title','school_and_oschool','school_and_oschool_action','DOACTION');
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array('id');
        //数据的格式化
        $order = 'id desc';
        $map = "status=1 AND (school_and_oschool_action!=school_and_oschool) AND school_and_oschool_action>0";
        if(isset($_POST)) {
            $id = intval(t($_POST['id']));
            $map.= " AND id=$id ";
        }
        $list = model('School')->where($map)-> order($order)->findPage($limit);
        foreach($list['data'] as $key => $val) {
            $list['data'][$key]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.mzSaveDivide('.$val['id'].',1,1)">通过</a> - ';
            $list['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.mzSaveDivide('.$val['id'].',-1,1)">驳回</a>';
        }
        $this->assign('pageTitle', '机构分成比例审核');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }
    /**
     *机构与购买机构分成比例审核
     *@return json 返回操作后的JSON信息数据
     */
    public function buyideVerify() {
        $_REQUEST['tabHash'] = 'buyideVerify';
        $this->pageKeyList = array( 'id','title','school_and_buyschool','school_and_buyschool_action','DOACTION');
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array('id');
        //数据的格式化
        $order = 'id desc';
        $map = "status=1 AND (school_and_buyschool_action!=school_and_buyschool) AND school_and_buyschool_action>0";
        if(isset($_POST)) {
            $id = intval(t($_POST['id']));
            $map.= " AND id=$id ";
        }
        $list = model('School')->where($map)-> order($order)->findPage($limit);
        foreach($list['data'] as $key => $val) {
            $list['data'][$key]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.mzSaveDivide('.$val['id'].',1,3)">通过</a> - ';
            $list['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.mzSaveDivide('.$val['id'].',-1,3)">驳回</a>';
        }
        $this->assign('pageTitle', '机构分成比例审核');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }
    /**
     *机构与销课者分成比例审核
     *@return json 返回操作后的JSON信息数据
     */
    public function pinclassVerify() {
        $_REQUEST['tabHash'] = 'pinclassVerify';
        $this->pageKeyList = array( 'id','title','school_pinclass','school_pinclass_action','DOACTION');
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array('id');
        //数据的格式化
        $order = 'id desc';
        $map = "status=1 AND (school_pinclass_action!=school_pinclass) AND school_pinclass_action>0";
        if(isset($_POST)) {
            $id = intval(t($_POST['id']));
            $map.= " AND id=$id ";
        }
        $list = model('School')->where($map)-> order($order)->findPage($limit);
        foreach($list['data'] as $key => $val) {
            $list['data'][$key]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.mzSaveDivide('.$val['id'].',1,2)">通过</a> - ';
            $list['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.mzSaveDivide('.$val['id'].',-1,2)">驳回</a>';
        }
        $this->assign('pageTitle', '机构分成比例审核');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }
    /**
     * 添加机构
     */
    public function addSchool(){
        if(isset($_POST)){
//            $sat = explode(':',t($_POST['school_and_teacher']));
            $sot = explode(':',t($_POST['school_and_oschool']));
            $spc = explode(':',t($_POST['school_pinclass']));
            if(empty($_POST['title'])){$this->error("请上传机构名称");}
            if(empty($_POST['school_levelhidden'])){$this->error("请选择分类");}
            if(empty($_POST['logo'])){$this->error("请上传机构logo");}
            if(empty($_POST['uid'])){$this->error("绑定用户名称不能为空");}
            $Regx = '/^[A-Za-z]+$/';
            //if(empty($_POST['doadmin'])){$this->error("请填写独立域名");}
            //if(preg_match($Regx,strlen($data['doadmin'])>19){$this->error('独立域名只能输入英文字母');}

//            if(empty($_POST['videoSpace'])){$this->error("机构视频空间不能为空");}
//            if(empty($_POST['school_and_teacher'])){$this->error("机构与教师分成比例不能为空");}
//            if(floatval($sat[0]) + floatval($sat[1]) != 1){$this->error("机构与教师分成比例之和须为1");};
            if(empty($_POST['info'])){$this->error("机构简介不能为空");}
            if(empty($_POST['school_and_oschool'])){$this->error("机构与挂载机构分成比例不能为空");}
            if(floatval($sot[0]) + floatval($sot[1]) != 1){$this->error("机构与挂载机构分成比例之和须为1");};
            if(empty($_POST['school_pinclass'])){$this->error("机构与销课者分成比例不能为空");}
            if(floatval($spc[0]) + floatval($spc[1]) != 1){$this->error("机构与挂载机构分成比例之和须为1");};

            $myAdminLevelhidden 		= getCsvInt(t($_POST['school_levelhidden']),0,true,true,',');  //处理分类全路径
            $fullcategorypath 			= explode(',',$_POST['school_levelhidden']);
            $category 					= array_pop($fullcategorypath);
            $category					= $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['school_category']    = '0' ? array_pop($fullcategorypath) : $category;
            $data['fullcategorypath']   = $myAdminLevelhidden;//分类全路径

            $data['title']          = t($_POST['title']);
            $data['logo']           = intval($_POST['logo']);
            $data['doadmin']        = t($_POST['doadmin']);
            $data['videoSpace']     = t($_POST['videoSpace']);
            $data['info']           = t($_POST['info']);
            $data['str_tag']        = $_POST['str_tag'];
            $data['is_re']          = $_POST['is_re'];
            $data['is_best']        = $_POST['is_best'];
            $data['best_sort']      = $_POST['best_sort'];
            $data['is_cete_floor']  = $_POST['is_cete_floor'];
            $data['cete_floor_sort']= $_POST['cete_floor_sort'];
//            $data['school_and_teacher'] = t($_POST['school_and_teacher']);
            $data['school_and_oschool'] = t($_POST['school_and_oschool']);
            $data['school_pinclass']    = t($_POST['school_pinclass']);
            $data['school_vip']         = intval($_POST['school_vip']);
            $data['status']             = 1;
            $data['ctime']              = time();
            $data['uid']                = intval($_POST['uid']);
            $data['cuid']               = intval($this->mid);
            $data['about_us']           = $_POST['about_us'];
            $data['school_qq']          = intval($_POST['school_qq']);
            if($_POST['max_price_sys']){
                $data['max_price_sys'] = intval($_POST['max_price_sys']);
            }else{
                $max_price_sys= model('Xdata')->get("classroom_AdminVideoCoupon:couponMaxPriceSys");
                $data['max_price_sys'] = $max_price_sys['max_price_sys'];
            }
            $uid = model('School')->where(array('uid'=>$data['uid']))->getField('uid');
            if($uid){
                $this->error("此用户已认证机构");
            }
            model('School')->add($data);
            $id = model('School')->getLastInsID();
            if($id) {
                $uid = model('School')->where('id='.$id)->getField('uid');
                $datas['mhm_id'] = $id;
                $res  = M('user') ->where('uid ='.$uid) -> save($datas);
                if($res){
                    $user_group = model('School')->where('id=' . $id)->find();
                    $maps['uid'] = $user_group['uid'];
                    $maps['user_group_id'] = '4';
                    $exist = D('user_group_link')->where($maps)->find();
                    if (!$exist) {
                        D('user_group_link')->add($maps);
                        model('User')->cleanCache($user_group['uid']);
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_' . $user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_' . $user_group['uid']);
                        // 删除微博信息
                        $feed_ids = model('Feed')->where('uid=' . $user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);
                        model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_ok');

                        $status = '1';
                        unset($user_group);
                        unset($maps);
                    }
                }else{
                    model('School')->where('id='.$id)->delete();
                }
            }
            if($status == 1){
                $this->assign('jumpUrl',U('school/AdminSchool/index'));
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'addSchool';

            $this->onsubmit = 'admin.checkSchool(this)';
            $this->pageKeyList   = array('title', 'category', 'logo', 'uid', 'doadmin','videoSpace','school_vip','max_price_sys','school_and_oschool',
                'school_pinclass','info','str_tag','is_re','is_best','best_sort','is_cete_floor','cete_floor_sort','about_us','school_qq');// 'school_and_teacher',
            $this->notEmpty   = array('title','category', 'logo',  'uid','doadmin','videoSpace','school_and_oschool','school_pinclass' , 'info');

            $this->opt['is_re'] = array( '0' => '否', '1' => '是');
            $this->opt['is_best'] = array( '0' => '否', '1' => '是');
            $this->opt['is_cete_floor'] = array( '0' => '否', '1' => '是');
            //机构等级
            $treeData = M('school_vip')->where('is_del=0')->order('sort desc')->findALL();
            $school_vip = array_merge(array('0'=>'无'),array_column($treeData,'title','id'));
            $this->opt['school_vip'] = $school_vip;

            ob_start();
            echo W('CategoryLevel', array('table' => 'school_category', 'id' => 'school_level'));
            $output = ob_get_contents();
            ob_end_clean();

            $this->savePostUrl = U('school/AdminSchool/addSchool');
            $this->displayConfig(array('category'=>$output));
        }
    }

    /**
     * 修改机构信息
     */
    public function editSchool(){
        if(isset($_POST)){
            $id = intval($_POST['id']);
            if(!$id){
               $this->error("参数错误");
            }
//            $sat = explode(':',t($_POST['school_and_teacher']));
            $sot = explode(':',t($_POST['school_and_oschool_action']));
            $sao = explode(':',t($_POST['school_and_oschool']));
            $boa = explode(':',t($_POST['school_and_buyschool_action']));
            $bsl = explode(':',t($_POST['school_and_buyschool']));
            $pct = explode(':',t($_POST['school_pinclass_action']));
            $pco = explode(':',t($_POST['school_pinclass']));

            if(empty($_POST['title'])){$this->error("请上传机构名称");}
            if(empty($_POST['school_categoryhidden'])){$this->error("请选择分类");}
            if(is_admin($this->mid)){
                if(empty($_POST['logo'])){$this->error("请上传机构logo");}
            }else{
                if(empty($_POST['logo_ids'])){$this->error("请上传机构logo");}
            }
//            if(empty($_POST['school_and_teacher'])){$this->error("机构与教师分成比例不能为空");}
//            if(floatval($sat[0]) + floatval($sat[1]) != 1){$this->error("机构与教师分成比例之和须为1");};
            $school_and_oschool = model('School')->where('id='.$id)->getField('school_and_oschool');
            if(!$school_and_oschool && empty($_POST['school_and_oschool'])){$this->error("机构与挂载机构分成比例不能为空");}
            if($_POST['school_and_oschool']){
                if(floatval($sao[0]) + floatval($sao[1]) != 1){$this->error("机构与挂载机构分成比例之和须为1");};
            }else if($_POST['school_and_oschool_action']){
                if(floatval($sot[0]) + floatval($sot[1]) != 1){$this->error("机构与挂载机构分成比例之和须为1");};
            }

//            $school_and_buyschool = model('School')->where('id='.$id)->getField('school_and_buyschool');
//            if(!$school_and_buyschool && empty($_POST['school_and_buyschool'])){$this->error("机构与购买机构分成比例不能为空");}
//            if($_POST['school_and_buyschool']){
//                if(floatval($bsl[0]) + floatval($bsl[1]) != 1){$this->error("机构与购买机构分成比例之和须为1");};
//            }else if($_POST['school_and_buyschool_action']){
//                if(floatval($boa[0]) + floatval($boa[1]) != 1){$this->error("机构与购买机构分成比例之和须为1");};
//            }

//            $school_pinclass = model('School')->where('id='.$id)->getField('school_pinclass');
//            if(!$school_pinclass && empty($_POST['school_pinclass'])){$this->error("机构与销课者分成比例不能为空");}
//            if($_POST['school_pinclass']){
//                if(floatval($pco[0]) + floatval($pco[1]) != 1){$this->error("机构与销课者分成比例之和须为1");};
//            }else if($_POST['school_pinclass_action']){
//                if(floatval($pct[0]) + floatval($pct[1]) != 1){$this->error("机构与销课者机构分成比例之和须为1");};
//            }

            if(empty($_POST['info'])){$this->error("机构简介不能为空");}
            if($_POST['school_qq'] && (strlen($_POST['school_qq']) > 11 || strlen($_POST['school_qq']) < 6)){$this->error("请输入正确格式的QQ号");}

            $myAdminLevelhidden 		= getCsvInt(t($_POST['school_categoryhidden']),0,true,true,',');  //处理分类全路径
            $fullcategorypath 			= explode(',',$_POST['school_categoryhidden']);
            $category 					= array_pop($fullcategorypath);
            $category					= $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['school_category']    = '0' ? array_pop($fullcategorypath) : $category;
            $data['fullcategorypath']   = $myAdminLevelhidden;//分类全路径

            $data['title']  = t($_POST['title']);
            $data['logo']   = intval($_POST['logo'])? : intval(trim(($_POST['logo_ids']),"|"));
            $data['info']   = $_POST['info'];
            $data['str_tag']   = $_POST['str_tag'];
            $data['is_re']   = $_POST['is_re'];
            $data['is_best']   = $_POST['is_best'];
            $data['best_sort']   = $_POST['best_sort'];
            $data['is_cete_floor']   = $_POST['is_cete_floor'];
            $data['cete_floor_sort']   = $_POST['cete_floor_sort'];
            $data['school_vip']  = intval($_POST['school_vip']);
            $data['about_us']    = $_POST['about_us'];
            $data['school_qq']   = intval($_POST['school_qq']);
            if($_POST['max_price_sys']){
                $data['max_price_sys'] = intval($_POST['max_price_sys']);
            }else{
                $max_price_sys= model('Xdata')->get("classroom_AdminVideoCoupon:couponMaxPriceSys");
                $data['max_price_sys'] = $max_price_sys['max_price_sys'];
            }
//            $data['school_and_teacher'] = t($_POST['school_and_teacher']);
            if(is_admin($this->mid)){
                $data['school_and_oschool'] = t($_POST['school_and_oschool']);
                $data['school_and_buyschool'] = t($_POST['school_and_buyschool']);
                $data['school_pinclass'] = t($_POST['school_pinclass']);
            }else{
                if(!$school_and_oschool){
                    $data['school_and_oschool'] = t($_POST['school_and_oschool']);
                }else if(!$school_pinclass){
                    $data['school_pinclass'] = t($_POST['school_pinclass']);
                }else if(!$school_and_buyschool){
                    $data['school_and_buyschool'] = t($_POST['school_and_buyschool']);
                }
            }
            $data['school_and_oschool_action']  = t($_POST['school_and_oschool_action']);
            $data['school_and_buyschool_action']  = t($_POST['school_and_buyschool_action']);
            $data['school_pinclass_action']     = t($_POST['school_pinclass_action']);
            $data['ctime']  = time();

            $res = model('School')->where(array('id'=>$id))->save($data);

            if($res){
				if(isAjax()){
					echo json_encode(array('status'=>1));exit;
				}else{
					$this->assign('jumpUrl',U('school/AdminSchool/index'));
					$this->success("编辑成功");
				}
            }else{
				if(isAjax()){
					echo json_encode(array('status'=>0));exit;
				}else{
					$this->error("编辑失败");
				}
            }
        }else{
            $_REQUEST['tabHash'] = 'editSchool';

            $this->onsubmit = 'admin.checkSchool(this)';
            $this->pageKeyList   = array('id', 'title', 'category', 'logo','uid','doadmin','videoSpace','school_vip'
                , 'max_price_sys','school_and_oschool','school_pinclass', 'info','str_tag','is_re','is_best','best_sort','is_cete_floor',
                'cete_floor_sort','about_us','school_qq');//,'school_and_teacher'
            $this->notEmpty   = array('title','category', 'logo','doadmin','uid','videoSpace', 'info','school_and_oschool','school_pinclass');

            $id = intval($_GET['id']);
            if(!$id){
                $this->error("参数错误");
            }
            $school = model('School')->where('id ='.$id)-> find() ;
            if($school['doadmin']){
                $school['doadmin'] = $school['doadmin'] . '.'.$_SERVER["HTTP_HOST"];
            }
            $this->pageTitle['editSchool'] = '编辑机构-' . $school['title'];

            $this->opt['is_re'] = array( '0' => '否', '1' => '是');
            $this->opt['is_best'] = array( '0' => '否', '1' => '是');
            $this->opt['is_cete_floor'] = array( '0' => '否', '1' => '是');
            //机构等级
            $treeData = M('school_vip')->where('is_del=0')->order('sort desc')->findALL();
            $school_vip = array_merge(array('0'=>'无'),array_column($treeData,'title','id'));
            $this->opt['school_vip'] = $school_vip;

            ob_start();
            echo W('CategoryLevel', array('table' => 'school_category', 'id' => 'school_category', 'default' => trim($school['fullcategorypath'], ',')));
            $output = ob_get_contents();
            ob_end_clean();
            $school['category'] = $output;
            $school['uid'] = getUserSpace($school['uid'], null, '_blank');

            $this->savePostUrl = U('school/AdminSchool/editSchool');
            $this->displayConfig($school);
        }
    }

    /**
     * 修改认证状态 信息
     * @return json 返回操作后的JSON信息数据
     */
    public function saveVerify(){
        $map['id'] = $_POST['id'];
        $data['verified'] = intval($_POST['status']);
        M('user_verified')->where($map)->save($data);

        echo json_encode(array('status'=>1,'data'=>"认证成功"));exit();
    }

    /**
     *机构分成比例审核
     *@return json 返回操作后的JSON信息数据
     */
    public function doSaveDivide() {
        $id = intval($_POST['id']);
        $type = intval($_POST['type']);
        $status = intval($_POST['status']);
        if($status == 1){
            if($type == 1){
                $data['school_and_oschool'] = model('School')->where('id='.$id)->getField('school_and_oschool_action');
                $res = model('School')->where('id='.$id)->save($data);
                if($res){
                    $return['status'] = 1;
                    $return['info']   = "通过审核";
                }else{
                    $return['status'] = 0;
                    $return['info']   = "审核失败";
                }
            }else if($type == -1){
                $data['school_and_oschool_action'] = '0';
                $res = model('School')->where('id='.$id)->save($data);
                if($res){
                    $return['status'] = 1;
                    $return['info']   = "驳回成功";
                }else{
                    $return['status'] = 0;
                    $return['info']   = "驳回失败";
                }
            }
        }else if($status == 2){
            if($type == 1){
                $data['school_pinclass'] = model('School')->where('id='.$id)->getField('school_pinclass_action');
                $res = model('School')->where('id='.$id)->save($data);
                if($res){
                    $return['status'] = 1;
                    $return['info']   = "通过审核";
                }else{
                    $return['status'] = 0;
                    $return['info']   = "审核失败";
                }
            }else if($type == -1){
                $data['school_pinclass_action'] = '0';
                $res = model('School')->where('id='.$id)->save($data);
                if($res){
                    $return['status'] = 1;
                    $return['data']   = "驳回成功";
                }else{
                    $return['status'] = 0;
                    $return['data']   = "驳回失败";
                }
            }
        }else if($status == 3){
            if($type == 1){
                $data['school_and_buyschool'] = model('School')->where('id='.$id)->getField('school_and_buyschool_action');
                $res = model('School')->where('id='.$id)->save($data);
                if($res){
                    $return['status'] = 1;
                    $return['info']   = "通过审核";
                }else{
                    $return['status'] = 0;
                    $return['info']   = "审核失败";
                }
            }else if($type == -1){
                $data['school_and_buyschool_action'] = '0';
                $res = model('School')->where('id='.$id)->save($data);
                if($res){
                    $return['status'] = 1;
                    $return['data']   = "驳回成功";
                }else{
                    $return['status'] = 0;
                    $return['data']   = "驳回失败";
                }
            }
        }

        echo json_encode($return);exit();
    }
}