<?php
/**
 * 后台公共方法
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class PublicAction extends AdministratorAction {
	
	public function _initialize(){
		if ( !in_array( ACTION_NAME , array('login','doLogin','logout','selectDepartment') ) ){
			parent::_initialize();
		}
		$this->assign('isAdmin',1);	//是否后台
	}
	/**
	 * 登录
	 * Enter description here ...
	 */
	public function login(){
		if ($_SESSION['adminLogin']) {
			redirect(U('admin/Index/index'));exit();
		}
		$this->setTitle( L('ADMIN_PUBLIC_LOGIN') );
		$this->display();
	}

	public function doLogin(){
        //检查验证码
        if (md5(strtoupper($_POST['verify'])) != $_SESSION['verify']) {
            $this->error('验证码错误');
        }
        if(is_school($_SESSION['mid']) ) {
            $school_info = M('school')->where(array('uid'=>$_SESSION['mid'],'is_del'=>0,'status'=>1))->getField('id');
            if(!$school_info){
                $this->error('您没有权限访问后台');
            }
        }
		$login = model('Passport')->adminLogin();
		if($login){
			if(CheckPermission('admin_login')){
				$this->success(L('PUBLIC_LOGIN_SUCCESS'));	
			}else{
				$this->assign('jumpUrl',SITE_URL);
				$this->error(L('PUBLIC_NO_FRONTPLATFORM_PERMISSION_ADMIN'));
			}
		}else{
			$this->error(model('Passport')->getError());
		}
	}
	
	/**
	 * 退出登录
	 * Enter description here ...
	 */
	public function logout(){
		model('Passport')->adminLogout();
		U('admin/Public/login','',true);
	}
	
	
	/**
	 * 通用部门选择数据接口
	 */
	public function selectDepartment(){
		$return = array('status'=>1,'data'=>'');
		
		if(empty($_POST['pid'])){
			$return['status'] = 0;
			$return['data']   = L('PUBLIC_SYSTEM_CATEGORY_ISNOT');
			echo json_encode( $return );exit();
		}

		$_POST['pid'] = intval($_POST['pid']);
        $_POST['sid'] = intval($_POST['sid']);
        $ctree = model('Department')->getDepartment($_POST['pid']);
        if(empty($ctree['_child'])){
        	$return['status'] = 0;
			$return['data']   = L('PUBLIC_SYSTEM_SONCATEGORY_ISNOT');	
        }else{
        	$return['data'] = "<select name='_parent_dept_id[]' onchange='admin.selectDepart(this.value,$(this))' id='_parent_dept_{$_POST['pid']}'>";
        	$return['data'] .= "<option value='-1'>".L('PUBLIC_SYSTEM_SELECT')."</option>";
        	$sid = !empty($_POST['sid']) ? $_POST['sid'] : '';
        	foreach ($ctree['_child'] as $key => $value) {
        		$return['data'] .="<option value='{$value['department_id']}' ".($value['department_id'] == $sid ? " selected='selected'":'').">{$value['title']}</option>";	
        	}	
			$return['data'] .="</select>";	
        }
        echo json_encode( $return );exit();
	}

    /*** 分类模板接口 ***/
    /**
     * 移动分类顺序API
     * @return json 返回相关的JSON信息
     */
    public function moveTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $type = t($_POST['type']);
        $stable = t($_POST['stable']);
        $result = model('CategoryTree')->setTable($stable)->moveTreeCategory($cid, $type);
        // 处理返回结果
        if($result) {
            $res['status'] = 1;
            $res['data'] = '分类排序成功';
        } else {
            $res['status'] = 0;
            $res['data'] = '分类排序失败';
        }

        exit(json_encode($res));
    }

 	/**
 	 * 添加分类窗口API
 	 * @return void
 	 */
    public function addTreeCategory()
    {
    	$cid = intval($_GET['cid']);
    	$this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
       	$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
       	$this->assign('type',$type);
    	$this->display('categoryBox');
    }

 	/**
 	 * 添加分类窗口API
 	 * @return void
 	 */
    public function addTreeChoiceCoverCategory()
    {
    	$cid = intval($_GET['cid']);
    	$this->assign('pid', $cid);
        if($cid == 0){
            $this->assign('add', 1);
        }
        $stable = t($_GET['stable']);
        if($stable == 'zy_currency_category' || $stable == 'goods_category'){
            $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
            if('0' == $category['pid']){
                if($stable == 'goods_category'){
                    $this->assign('goods_category_id',$cid);
                }else if($stable == 'zy_currency_category'){
                    $this->assign('currency_category_id',$cid);
                }
            }
        }
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
       	$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
       	$this->assign('type',$type);
    	$this->display('categoryChoiceCoverBox');
    }

 	/**
 	 * 添加分类窗口API
 	 * @return void
 	 */
    public function addAreeCategory()
    {
    	$cid = intval($_GET['cid']);
    	$this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
       	$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
       	$this->assign('type',$type);
    	$this->display('categoryChoiceBox');
    }
 	/**
 	 * 添加分类窗口API
 	 * @return void
 	 */
    public function addSreeCategory()
    {
    	$cid = intval($_GET['cid']);
    	$this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
       	$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
       	$this->assign('type',$type);
    	$this->display('categorySrttBox');
    }

    /**
     * 添加分类操作API
     * @return json 返回相关的JSON信息
     */
    public function doAddTreeCategory()
    {
    	$pid = intval($_POST['pid']);
    	$title = t($_POST['title']);
		$next_name = t($_POST['next_name']);
    	$stable = t($_POST['stable']);
        $data['attach_id'] = intval($_POST['attach_id']);
        if(intval($_POST['type']) > 0){
        	$data['type'] = intval($_POST['type']);
        }
    	if($_POST['type'] > 0){
        	$data['type'] = intval($_POST['type']);
        }
        if($_POST['is_del']){
            $data['is_del'] = intval($_POST['is_del']);
        }
        if(intval($_POST['middle_ids'])) {
            $data['middle_ids'] = intval($_POST['middle_ids']);
        }
        if(intval($_POST['is_choice_pc'])) {
            $data['is_choice_pc'] = intval($_POST['is_choice_pc']);
        }
        if(intval($_POST['is_choice_app'])) {
            $data['is_choice_app'] = intval($_POST['is_choice_app']);
        }
        if(intval($_POST['is_choice_ranking'])) {
            $data['is_choice_ranking'] = intval($_POST['is_choice_ranking']);
        }
        if(intval($_POST['is_h5_and_app'])) {
            $data['is_h5_and_app'] = intval($_POST['is_h5_and_app']);
        }
        if(intval($_POST['is_nav_left'])) {
            $data['is_nav_left'] = intval($_POST['is_nav_left']);
        }
		if(trim($next_name)){
			$data['next_name'] = $next_name;
		}
		if(t($_POST['category_url'])){
            $data['url'] = t($_POST['category_url']);
        }
        if($pid != 0){
            $zy_currency_category_grandson = M('zy_currency_category')->where(array('zy_currency_category_id'=>$pid))->field('zy_currency_category_id,pid,title,is_choice_pc,is_choice_app')->find();
            if($zy_currency_category_grandson['pid'] == 0 && $zy_currency_category_grandson['zy_currency_category_id']){
                $data['is_choice_pc']  = $zy_currency_category_grandson['is_choice_pc'];
                $data['is_choice_app'] = $zy_currency_category_grandson['is_choice_app'];
            }
            if($zy_currency_category_grandson['pid']){
                $zy_currency_category_son = M('zy_currency_category')->where(array('zy_currency_category_id'=>$zy_currency_category_grandson['pid']))->field('zy_currency_category_id,pid,title,is_choice_pc,is_choice_app')->find();
                if($zy_currency_category_son['pid'] == 0 && $zy_currency_category_son['zy_currency_category_id']){
                    $data['is_choice_pc']  = $zy_currency_category_son['is_choice_pc'];
                    $data['is_choice_app'] = $zy_currency_category_son['is_choice_app'];
                }
                if($zy_currency_category_son['pid']) {
                    $zy_currency_category_pid = M('zy_currency_category')->where(array('zy_currency_category_id' => $zy_currency_category_son['pid']))->field('zy_currency_category_id,pid,title,is_choice_pc,is_choice_app')->find();
                    if($zy_currency_category_pid['pid'] == 0 && $zy_currency_category_pid['zy_currency_category_id']){
                        $data['is_choice_pc']  = $zy_currency_category_pid['is_choice_pc'];
                        $data['is_choice_app'] = $zy_currency_category_pid['is_choice_app'];
                    }
                }
            }
        }

        $result = model('CategoryTree')->setTable($stable)->addTreeCategory($pid, $title, $data);
    	$res = array();
    	if($result) {
    		$res['status'] = 1;
    		$res['data'] = '添加分类成功';
    	} else {
    		$res['status'] = 0;
    		$res['data'] = '添加分类失败';
    	}
    	exit(json_encode($res));
    }

    /**
     * 编辑分类窗口API
     * @return void
     */
    public function upTreeCategory()
    {
        $cid = intval($_GET['cid']);
        $this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
        // 获取该分类的信息
        $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
        if(isset($category['attach_id']) && !empty($category['attach_id'])) {
            $attach = model('Attach')->getAttachById($category['attach_id']);
            $this->assign('attach', $attach);
        }
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $this->assign('type',$type);
        $this->assign('category', $category);

    	$this->display('categoryBox');
    }
    /**
     * 编辑分类窗口API
     * @return void
     */
    public function upTreeChoiceCoverCategory()
    {
        $cid = intval($_GET['cid']);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
        // 获取该分类的信息
        $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
        if($stable == 'zy_currency_category') {
            $this->assign('pid', $cid);
        }else if($stable == 'goods_category') {
            $this->assign('goods_category_id', 1);
            $category['pid'] = intval($category['pid']);
        }else{
            $category['pid'] = intval($category['pid']);
        }
        if($stable == 'zy_currency_category' || $stable == 'goods_category'){
            $category_pid = M($stable)->where(array($stable.'_id'=>$category['pid']))->getField('pid');
            if('0' == $category_pid){
                if($stable == 'goods_category'){
                    $this->assign('goods_category_id',$cid);
                }else if($stable == 'zy_currency_category'){
                    $this->assign('currency_category_id',$cid);
                }
            }
        }

        if(isset($category['attach_id']) && !empty($category['attach_id'])) {
            $attach = model('Attach')->getAttachById($category['attach_id']);
            $this->assign('attach', $attach);
        }
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $this->assign('type',$type);
        $this->assign('category', $category);

    	$this->display('categoryChoiceCoverBox');
    }
    /**
     * 编辑分类窗口API
     * @return void
     */
    public function upAreeCategory()
    {
        $cid = intval($_GET['cid']);
        $this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
        // 获取该分类的信息
        $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
        if(isset($category['attach_id']) && !empty($category['attach_id'])) {
            $attach = model('Attach')->getAttachById($category['attach_id']);
            $this->assign('attach', $attach);
        }
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $this->assign('type',$type);
        $this->assign('category', $category);

    	$this->display('categoryChoiceBox');
    }
    /**
     * 编辑分类窗口API
     * @return void
     */
    public function upSreeCategory()
    {
        $cid = intval($_GET['cid']);
        $this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
        // 获取该分类的信息
        $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
        if(isset($category['attach_id']) && !empty($category['attach_id'])) {
            $attach = model('Attach')->getAttachById($category['attach_id']);
            $this->assign('attach', $attach);
        }
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $this->assign('type',$type);
        $this->assign('category', $category);

    	$this->display('categorySrttBox');
    }

    /**
     * 编辑分类操作API
     * @return json 返回相关的JSON信息
     */
    public function doUpTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $title = t($_POST['title']);
		$next_name = t($_POST['next_name']);
        $data['url'] = t($_POST['category_url']);
        $stable = t($_POST['stable']);
        if($_POST['attach_id'] != 'NaN') {
            $data['attach_id'] = intval($_POST['attach_id']);
        }
        if($_POST['type'] > 0){
            $data['type'] = intval($_POST['type']);
        }
        $data['middle_ids'] = intval($_POST['middle_ids']);
        $data['is_choice_pc'] = $child_data['is_choice_pc'] = intval($_POST['is_choice_pc']);
        $data['is_choice_app'] = $child_data['is_choice_app'] = intval($_POST['is_choice_app']);
        $data['is_choice_ranking'] = intval($_POST['is_choice_ranking']);
        $data['is_h5_and_app'] = intval($_POST['is_h5_and_app']);
        $data['is_nav_left'] = intval($_POST['is_nav_left']);
        $data['is_del'] = intval($_POST['is_del']);
        if(trim($next_name)){
            $data['next_name'] = $next_name;
        }
        $zy_currency_category_pid = M('zy_currency_category')->where(array('zy_currency_category_id'=>$cid))->getField('pid');
        if($zy_currency_category_pid != 0 && $stable == 'zy_currency_category'){
            unset($data['is_choice_pc']);
            unset($data['is_choice_app']);
        }

        $result = model('CategoryTree')->setTable($stable)->upTreeCategory($cid, $title, $data);

        $res = array();
        if($result) {
            //针对zy_currency_category的推荐功能
            if($data['is_choice_pc'] || $data['is_choice_app']  && $zy_currency_category_pid != 0 && $stable == 'zy_currency_category'){
                $zy_currency_idss = '';
                $zy_currency_category_id_arr = M('zy_currency_category')->where(array('pid'=>$cid))->field('zy_currency_category_id')->select();
                $zy_currency_category_ids = trim(implode(',', getSubByKey($zy_currency_category_id_arr , 'zy_currency_category_id')),',');
                $zy_currency_idss .= implode(',',getSubByKey($zy_currency_category_id_arr , 'zy_currency_category_id')).',';
                $maps['zy_currency_category_id'] = array('in',$zy_currency_category_ids);
                $result = M('zy_currency_category')->where($maps)->field('zy_currency_category_id')->select();
                foreach ($result as $key => $val){
                    $zy_currency_category_child_id_arr = M('zy_currency_category')->where(array('pid'=>$val['zy_currency_category_id']))->field('zy_currency_category_id')->select();
                    $zy_currency_category_child_ids = trim(implode(',', getSubByKey($zy_currency_category_child_id_arr , 'zy_currency_category_id')),',');

                    $child_map['zy_currency_category_id'] = array('in',$zy_currency_category_child_ids);
                    $child_result[] = M('zy_currency_category')->where($child_map)->field('zy_currency_category_id')->select();
                }
                foreach ($child_result as $k => $v){
                    $zy_currency_idss .= implode(',', getSubByKey($v , 'zy_currency_category_id')).',';
                }
                $all_map['zy_currency_category_id'] = array('in',trim($zy_currency_idss,','));
                $child_result = M('zy_currency_category')->where($all_map)->save($child_data);
                if($child_result) {
                    $res['status'] = 1;
                    $res['data'] = '编辑分类成功!';
                } else {
                    $res['status'] = 0;
                    $res['data'] = '编辑分类失败!';
                }
            }
            $res['status'] = 1;
            $res['data'] = '编辑分类成功';
        } else {
            $res['status'] = 0;
            $res['data'] = '编辑分类失败';
        }

        exit(json_encode($res));
    }

    /**
     * 删除分类API
     * @return json 返回相关的JSON信息
     */
    public function rmTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $stable = t($_POST['stable']);
        $app = t($_POST['_app']);
        $module = t($_POST['_module']);
        $method = t($_POST['_method']);
        $result = model('CategoryTree')->setApp($app)->setTable($stable)->rmTreeCategory($cid, $module, $method);
        $msg = model('CategoryTree')->setApp($app)->setTable($stable)->getMessage();
        $res = array();
        if($result) {
            $res['status'] = 1;
            $res['data'] = $msg;
        } else {
            $res['status'] = 0;
            $res['data'] = $msg;
        }

        exit(json_encode($res));
    }

    /**
     * 设置分类配置页面
     * @return void
     */
    public function setCategoryConf()
    {
        $cid = intval($_GET['cid']);
        $stable = t($_GET['stable']);
        $ext = t($_GET['ext']);
        $ext = urldecode($ext);
        $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
        // 设置标题
        $pageTitle = '分类配置&nbsp;-&nbsp;'.$category['title'];
        $this->assign('pageTitle', $pageTitle);
        // 页面字段配置存在system_data表中的页面唯一key值
        $this->pageKey = 'category_conf_'.$stable;
        // 配置项字段设置
        $ext = array_map('t', $_GET); //需要过滤
        unset($ext['app']);
        unset($ext['mod']);
        unset($ext['act']);
        unset($ext['cid']);
        unset($ext['stable']);
        $pageKeyList = array();
        $data = array();
        foreach ($ext as $key => $val) {
            $fields = explode('_', $key);
            $fields[] = $val;
            $data[$fields[1]][$fields[0]] = (strpos($fields[2], '-') === false) ? $fields[2] : explode('-', $fields[2]);
        }
        foreach ($data as $value) {
            $pageKeyList[] = $value['ext'];
            isset($value['arg']) && $this->opt[$value['ext']] = $value['arg'];
            isset($value['def']) && $detailData[$value['ext']] = $value['def'];
            $this->assign('defaultS', $value['def']);
        }
        $this->pageKeyList = $pageKeyList;
        // 提交表单URL设置
        $this->savePostUrl = U('admin/Public/doSetCategoryConf', array('cid'=>$cid, 'stable'=>$stable));
        // 获取配置信息
        $extend = empty($category['ext']) ? $detailData : unserialize($category['ext']);

        $this->displayConfig($extend);
    }

    /**
     * 存储分类配置操作
     * @return void
     */
    public function doSetCategoryConf()
    {
        $cid = intval($_GET['cid']);
        $stable = t($_GET['stable']);
        // 去除多余的数据
        $data = $_POST;
        unset($data['systemdata_list']);
        unset($data['systemdata_key']);
        unset($data['pageTitle']);
        unset($data['avoidSubmitByReturn']);
        foreach($data as &$value) {
            $value = t($value);
        }
        $result = model('CategoryTree')->setTable($stable)->doSetCategoryConf($cid, $data);
        if($result) {
            $this->success('分类配置成功');
        } else {
            $this->error('分类配置失败');
        }
    }
    
    //发送测试邮件
    function test_email(){
        //$data['sendto_email'] = t($_POST['sendto_email']);
        $data = $_POST;
    	$result = model('Mail')->test_email($data);
        if ($result === false) {
            echo model('Mail')->message;
        }else{
            echo 1;
        }
    }
}	