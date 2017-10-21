<?php

tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
/**
 * 后台用户组管理
 *
 * @author jason
 */
class AdminUserGroupAction extends AdministratorAction
{
	public $pageTitle = array(
							'index'		  => '用户组管理',
							'addUsergroup'=> '编辑用户组',
							);
	public function _initialize(){
		$this->pageTitle['index'] = '用户组管理';
		$this->pageTitle['addUsergroup'] = L('编辑用户组');
		parent::_initialize();
	}
    /**
     * 递归形成树形结构
     * @param integer $pid 父分类ID
     * @param integer $level 等级
     * @return array 树形结构
     */
    private function _UserGroupMakeTree($pid = 4, $level = 0)
    {
        $result = M('user_group')->where(['pid'=>$pid,'uid'=>$_SESSION['mid']])->order('user_group_id ASC')->findAll();
        $list = [];
        if($result) {
            foreach($result as $key => $value) {
                $id = $value['user_group_id'];
                $list[$id]['id'] = $value['user_group_id'];
                $list[$id]['pid'] = $value['pid'];
                $list[$id]['title'] = $value['user_group_name'];
                $list[$id]['user_group_type'] = empty($value['user_group_type']) ? L('PUBLIC_ORDINARY'):L('PUBLIC_SPECIAL');
                $list[$id]['user_group_icon'] = $value['user_group_icon'] != '-1' ? '<img src="'.THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'].'">' :'暂无图标';
                $list[$id]['level'] = $level;
                $child = $this->_UserGroupMakeTree($value['user_group_id'], $level + 1)?:[];
                $child && $list[$id]['child'] = $child;
            }
        }

        return $list;
    }
	public function index(){
        $mugid = model( 'UserGroupLink' )->where('uid='.$this->mid.' and user_group_id=4')->getField('user_group_id');
        if($mugid == 4){
            $tree =  $this->_UserGroupMakeTree();
            $mugid = M('user_group')->where(['pid'=>4,'uid'=>$this->mid])->getField('user_group_id');
        }

        $this->assign('tree', $tree);
        $this->assign('mugid', $mugid);
        $this->assign('level', 0);
        $this->assign('extra', '');
        $this->assign('limit', 10);
        $this->display('admin_user_group');
//
//         // 页面具有的字段，可以移动到配置文件中！！！
//        $this->pageKeyList = array('user_group_id','app_name','user_group_name','user_group_type','user_group_icon','is_authenticate','DOACTION');
//
//        $this->pageButton[] = array('title'=>L('PUBLIC_ADD_USER_GROUP'),'onclick'=>"admin.addUserGroup()");
//        // $this->pageButton[] = array('title'=>L('PUBLIC_DELETE_USER_GROUP'),'onclick'=>"admin.delUserGroup(this)");
//
//        $list = model('UserGroup')->findPage(10);
//
//        foreach ($list['data'] as & $value) {
//			$value['user_group_type'] = empty($value['user_group_type']) ? L('PUBLIC_ORDINARY'):L('PUBLIC_SPECIAL');
//            $value['user_group_icon'] = $value['user_group_icon']!='-1' ? '<img src="'.THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'].'">' :'';
//            $value['is_authenticate'] = $value['is_authenticate']==1?'是':'否';
//            $value['DOACTION'] = "<a href='".U('admin/AdminUserGroup/addUsergroup',array('user_group_id'=>$value['user_group_id']))."'>".L('PUBLIC_EDIT')."</a>&nbsp;";
//            $value['DOACTION'] .= "<a href='".U('admin/Config/permissionset',array('gid'=>$value['user_group_id']))."'>".L('PUBLIC_PERMISSION_GROUP_CONFIGURATION')."</a>&nbsp;";
//            if($value['user_group_id'] > 5){
//                $value['DOACTION'] .= "<a href='javascript:void(0)' onclick=\"admin.delUserGroup(this,'{$value['user_group_id']}')\">".L('PUBLIC_STREAM_DELETE')."</a> ";
//            }
//        }
//
//        $this->_listpk = 'user_group_id';
//        $this->allSelected = false;
//        $this->displayList($list);
	}

    /**
     * 添加权限组窗口API
     * @return void
     */
    public function addUsergroupTreeCategory()
    {
        $pid = intval($_GET['id']);
        $limit = intval($_GET['limit']);
        require_once ADDON_PATH.'/library/io/Dir.class.php';
        $dirs   = new Dir(THEME_PUBLIC_PATH.'/image/usergroup');
        $dirs   = $dirs->toArray();
//      $icons = array('-1'=>'无');
        $icons = array('-1'=>"无图标");
        foreach($dirs as $k=>$v){
            $icons[$v['filename']] = "<img src='".THEME_PUBLIC_URL.'/image/usergroup/'.$v['filename']."'>";
        }
        $this->assign('pid', $pid);
        $this->assign('limit', $limit);
        $this->assign('icons', $icons);
        $this->display('categoryBox');
    }
    /**
     * 编辑权限用户组窗口API
     * @return void
     */
    public function upUserGroupTreeCategory()
    {
        $id = intval($_GET['id']);
        $limit = intval($_GET['limit']);
        require_once ADDON_PATH.'/library/io/Dir.class.php';
        $dirs   = new Dir(THEME_PUBLIC_PATH.'/image/usergroup');
        $dirs   = $dirs->toArray();
//      $icons = array('-1'=>'无');
        $icons = array('-1'=>"无图标");
        foreach($dirs as $k=>$v){
            $icons[$v['filename']] = "<img src='".THEME_PUBLIC_URL.'/image/usergroup/'.$v['filename']."'>";
        }
        $category = M('user_group')->where(['user_group_id'=>$id])->find();

        $this->assign('pid', $id);
        $this->assign('limit', $limit);
        $this->assign('icons', $icons);
        $this->assign('category', $category);
        $this->display('categoryBox');
    }

    public function upUserGroupListTreeCategory(){
        $id = intval($_GET['id']);
        $rids = M('user_group')->where(['uid'=>$this->mid,'pid'=>4])->field('rule_list')->findAll()[0]['rule_list'];
        $list_tree = $this->_UserGroupListMakeTree(0,0,$id,$rids);

        $this->assign('id', $id);
        $this->assign('list_tree', json_encode($list_tree));
        $this->display('ruleListBox');
    }

    private function checkAssParent($pid,$rids){
        $result = M('permission_node')->where(['pid'=>$pid,'id'=>['in',trim($rids,',')]])->order('id ASC')->findAll();
        return $result;
    }
    /**
     * 递归形成树形结构
     * @param integer $pid 父分类ID
     * @param integer $level 等级
     * @return array 树形结构
     */
    private function _UserGroupListMakeTree($pid = 0, $level = 0,$ug_id,$rids)
    {
        $result = $this->checkAssParent($pid,$rids);
        $list = [];
        if($result) {
            foreach($result as $key => $value) {
                if(!strpos($rids,",{$value['id']},")){
                    unset($key);
                }
                $list[$key]['checkboxValue'] = $value['id'];
                if($value['pid'] == 9){
                    $list[$key]['data']['nodeName'] = $value['rulename'];
                    $list[$key]['data']['alias'] = model('PinYin')->Pinyin($value['rulename']);
                }
                $result = M('user_group')->where(['user_group_id'=>$ug_id,'rule_list'=>['like','%,'.$value['id'].',%']])->find();

                if($result){
                    $list[$key]['checked']   = true;
                }else{
                    $list[$key]['checked']   = false;
                }
                $list[$key]['name']          = $value['rulename'];
//                $list[$id]['level'] = $level;
                if($level == 0){
                    $list[$key]['spread']        = true;
                }else{
                    $list[$key]['spread']        = false;
                }


                $res = M('permission_node')->where('pid='.$value['id'])->getField('id');

                if($res){
                    $child = $this->_UserGroupListMakeTree($value['id'], $level + 1,$ug_id,$rids)?:[];
                    $child && $list[$key]['children'] = $child;
                }
            }
        }

        return $list;
    }

    public function saveUserPermNode(){
        if(empty($_POST['rule_list'])){
            $this->mzError('尚未选择权限!');
        }

        if($_POST['user_group_id']) {
            $map['user_group_id'] = intval($_POST['user_group_id']);
            $user_group_name = M('user_group')->where($map)->getField('user_group_name');
            $data['rule_list']    = ','.implode(',',$_POST['rule_list']).',';
            $res = M('user_group')->where($map)->save($data);
        }
        if($res){
            $d['log'] = var_export([$data],true);
            $d['k']   = "{$user_group_name}分配权限";
            LogRecord('admin_extends','saveUserPermNode',$d,true);
            $this->mzSuccess("操作成功");
        }else{
            $this->mzError("操作失败");
        }
    }

	public function addUsergroup(){

		if(!empty($_POST)){ //添加&编辑积分类型
            if(intval($_POST['user_group_id'])){
                $info = "编辑";
            }else{
                $info = "添加";
            }
            if(is_school($this->mid)){
                $_POST['uid'] = $this->mid;
            }
//            dump($_POST);exit;
            $res = model('UserGroup')->addUsergroup($_POST);

            if($res ){
                //TODO 记录日志
                $this->assign('jumpUrl',U('admin/AdminUserGroup/index'));
                $this->mzSuccess("{$info}成功");
            }else{
                $this->mzError("{$info}失败");
            }
        }


        $this->pageKeyList = array('user_group_id','user_group_name','user_group_icon','user_group_type','is_authenticate');

        $this->opt['user_group_type'] = array(0=>L('PUBLIC_ORDINARY'),1=>L('PUBLIC_SPECIAL'));
        $this->opt['is_authenticate'] = array(1=>'是',0=>'否');

        require_once ADDON_PATH.'/library/io/Dir.class.php';        
        $dirs   = new Dir(THEME_PUBLIC_PATH.'/image/usergroup');
        $dirs   = $dirs->toArray();
//      $icons = array('-1'=>'无');
        $icons = array('-1'=>L('PUBLIC_NO_MORE_INFO'));
        foreach($dirs as $k=>$v){
            $icons[$v['filename']] = "<img src='".THEME_PUBLIC_URL.'/image/usergroup/'.$v['filename']."'>";
        }

        $this->opt['user_group_icon'] = $icons;
            
        $this->savePostUrl = U('admin/AdminUserGroup/addUsergroup');

		$detailData =  array();

        if(!empty($_REQUEST['user_group_id'])){
        	$map['user_group_id'] = $_REQUEST['user_group_id'];
        	$detailData = model('UserGroup')->where($map)->find();
        }else{
             $this->pageTitle[ACTION_NAME] = L('PUBLIC_ADD_USER_GROUP');
        }

        $this->onsubmit = 'admin.checkUserGroup(this)';
        $this->displayConfig($detailData);

	}
	//删除用户
	public function delUserGroup(){

        $group_id = intval($_POST['id']);
		if(!$group_id){
			$this->mzError("请选择删除的用户组");
		}
        if($this->groupHasUser($group_id)){
            $this->mzError('当前用户组或子用户组下存在用户,不允许删除');
        }
        $user_ground_id = $this->getAllUserGroupId($group_id);
        $data = model('UserGroup')->where(['user_group_id'=>['in',$user_ground_id]])->select();
        $res = model('UserGroup')->delUsergroup($user_ground_id);
		if($res){
            //TODO 记录操作日志
            $d['log'] = var_export([$data],true);
            $d['k']   = "删除用户组组";
            LogRecord('admin_extends','delUserGroup',$d,true);
            $this->mzSuccess("删除成功");
		}else{
            $this->mzError("删除失败");
		}
	}

    /**
     * 当前用户组下是否有用户
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-05-19
     * @param  integer $group_id 用户组ID
     * @return boolean
     */
    public function groupHasUser($group_id = 0)
    {
        $is_user_link = M('user_group_link')->where(['user_group_id'=>$group_id])->count() > 0;
        if($is_user_link){
            return true;
        }

        $groups = M('user_group')->where(['pid'=>$group_id])->field('user_group_id')->select();
        if($groups){
            foreach ($groups as $group_id) {
                return $this->groupHasUser($group_id['user_group_id']);
            }
        }
        return false;
    }

    /**
     * 根据group_id 获取所有用户组的id
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-05-22
     * @param  integer $group_id 用户组ID
     * @return array
     */
    public function getAllUserGroupId($group_id = 0)
    {
        $list = [$group_id];
        $group_ids = M('user_group')->where(['pid'=>$group_id])->field('user_group_id')->select();
        if($group_ids){
            foreach ($group_ids as $group_id) {
                $list[] = $group_id['user_group_id'];
                $list = array_merge($list,$this->getAllUserGroupId($group_id['user_group_id']));
            }
        }
        return array_filter(array_unique($list));
    }

    public function getUserGroupLink(){
        $group_ids = M('user_group')->where(['pid'=>$group_id])->field('user_group_id')->select();

    }
}