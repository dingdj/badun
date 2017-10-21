<?php
/**
 * 登录日志管理
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class AdminLoginRecordAction extends AdministratorAction {

	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize(){
        $this->pageTitle['index']       = '登录日志列表';

        parent::_initialize();
    }

    /**
     * 登录日志列表
     * @return void
     */
    public function index(){

        //页面配置
        $this->pageKeyList = array('login_record_id','uid','browser_and_ver','os','ip','place','ctime','locktime');
        $this->_listpk = 'login_record_id';
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delLoginRecord()");

        $this->searchKey = array('login_record_id','uid');

        $_POST['login_record_id'] && $map['login_record_id'] = intval($_POST['login_record_id']);
        $_POST['uid'] && $map['uid'] = intval($_POST['uid']);

        //数据列表
        $listData = M('login_record')->where($map)->order('ctime DESC,login_record_id')->findPage(20);
        foreach($listData['data'] as $key=>$val){
            $val['uid']       = getUserSpace($val['uid'], null, '_blank');
            $browser_and_ver  = trim($val['browser']) ? : "未知浏览器";
            $browser_and_ver  .= "　";
            $browser_and_ver  .= trim($val['browser_ver']) ? : "未知浏览器版本号";
            $val['browser_and_ver']    = $browser_and_ver;
            $val['os']       = trim($val['os']) ? : "未知操作系统";
            $val['place']     = trim($val['place']) ? $val['place'] : "未知地区";
            $val['ctime']     = date('Y-m-d H:i:s',$val['ctime']);
            $val['locktime']  = $val['locktime'] ? date('Y-m-d H:i:s',$val['locktime']) : "未锁定";

            $listData['data'][$key] = $val;
        }

        $this->displayList($listData);
    }

    /**
     * 删除登录日志
     * @return void
     */
    public function delAdminLoginRecord(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
       /* if(is_array($_POST['id'])){
            $_POST['id'] = implode(',', $_POST['id']);
        }
        $id = "'".str_replace(array("'", ','), array('', "','"), $_POST['id'])."'";*/
        $res = M('login_record')->where("login_record_id IN($ids)")->delete();
        if($res){
            $this->ajaxReturn(null,"删除成功",1);
        }else{
            $this->ajaxReturn(null,"删除失败",0);
        }
    }
}