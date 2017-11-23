<?php
/**
 * 在线 调查后台管理
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class AdminAction extends AdministratorAction {	

	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize(){
		parent::_initialize();
		$this->pageTab[] = array( 'title' =>'在线调查管理', 'tabHash' => 'index', 'url' => U('survey/Admin/index') );
		$this->pageTab[] = array( 'title' =>'添加在线调查', 'tabHash' => 'add', 'url' => U('survey/Admin/add') );
		$this->pageTab[] = array( 'title' =>'在线调查分类', 'tabHash' => 'cate', 'url' => U('survey/Admin/cate') );
		$this->pageTab[] = array( 'title' =>'调查结果统计', 'tabHash' => 'stat', 'url' => U('survey/Admin/stat') );
	}
	
	/**
	 * 在线调查分类列表
	 */
	public function cate(){
		 $this->pageTitle ['cate'] = '在线调查分类';
		 $this->pageKeyList = array ('id','title','uid','stime','etime','ctime','action');
		 $this->pageButton [] = array ( 'title' =>'添加分类', 'onclick' => "admin.surveyCate()" );
		 $list = M('zy_survey_category')->where('is_del=0')->order('ctime desc')->findPage(10);
         //获取在线调查分类
		 foreach($list['data'] as $k => &$v){
		 	 $v['ctime']   = friendlyDate($v['ctime']);
		 	 $v['stime']   = date('Y-m-d H:i',$v['stime']);
		 	 $v['etime']   = date('Y-m-d H:i',$v['etime']);
			 $v['action'] = '<a href="'.U('survey/Admin/editCate',array('tabHash'=>'editCate','id'=>$v['id'])).'">编辑</a> - ';
			 $v['action'] .= '<a href="javascript:void(0)" onclick="admin.delSurveyCate(\''.$v['id'].'\')">删除</a>';
	    }
		$this->displayList($list);
	}
	
	/**
	 * 添加在线调查
	 */
	public function addCate(){
		$_REQUEST ['tabHash'] = 'addCate';
		$this->pageTitle ['addCate'] = '添加在线调查分类';
		$this->pageKeyList = array ('title','stime','etime');
		if($_SERVER['REQUEST_METHOD'] == 'POST'){//如果是post表单提交
            $data['title']     = t( $_POST['title'] );
            $data['stime']     = is_numeric($_POST['stime']) ? $_POST['stime'] : strtotime( $_POST['stime'] );
            $data['etime']     = is_numeric($_POST['etime']) ? $_POST['etime'] : strtotime( $_POST['etime'] );
            $data['ctime']     = time();
            $data['uid']       = $this->mid;
            if( $rst = M('zy_survey_category')->add($data)){
                $this->assign('jumpUrl' , U('survey/Admin/cate'));
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
            exit;
        }
        $this->savePostUrl = U('survey/Admin/addCate');
        $this->notEmpty = array('title');
        $this->displayConfig();
	}
	
	/**
	 * 修改在线调查
	 */
	public function editCate(){
	    $_REQUEST ['tabHash'] = 'editCate';
	    $this->pageTitle ['editCate'] = '编辑在线调查';
	    $this->pageKeyList = array ('title','stime','etime');
	    $id = intval( $_GET['id'] );
        if($id && $_SERVER['REQUEST_METHOD'] == 'POST'){//如果是post表单提交
            $data['title']     = t( $_POST['title'] );
            $data['stime']     = is_numeric($_POST['stime']) ? $_POST['stime'] : strtotime( $_POST['stime'] );
            $data['etime']     = is_numeric($_POST['etime']) ? $_POST['etime'] : strtotime( $_POST['etime'] );
            $data['uid']       = $this->mid;
            if (M('zy_survey_category')->where('id=' . $id)->save($data) !== false ){
                $this->assign('jumpUrl',U('survey/Admin/cate'));
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }
        $this->savePostUrl = U('survey/Admin/editCate' , array( 'id'=>$id ));
        $this->notEmpty = array('title');
        $res = M('zy_survey_category')->where('id=' . $id)->find();
        $res['stime'] = friendlyDate($res['stime'] , 'full');
        $res['etime'] = friendlyDate($res['etime'] , 'full');
        $this->displayConfig ( $res );
	}
	
	/**
	 * 删除在线调查
	 */
	public function delCate(){
		$id = $_POST['ids'];
		if( is_numeric($id) ) {
			$map['id'] = intval($id);
		} else {
			$map['id'] = array('in' , $id);
		}
		if(M('zy_survey_category')->where($map)->setField('is_del' , 1)){
			//LogRecord('admin_content', 'delPage', array('ids'=>$id) , true);
			$return['status'] = 1;
			$return['data'] = '删除成功';
		}else{
			$return['status'] = 0;
			$return['data'] = '删除失败';
		}
		echo json_encode($return);exit();
	}

	
	/**
	 * 在线调查列表
	 */
	public function index(){
		 $this->pageTitle ['index'] = '在线调查管理';
		 $this->pageKeyList = array ('id','title','fid','ctime','action');
		 $this->pageButton [] = array ( 'title' =>'删除', 'onclick' => "admin.delSurveys()" );
		 $list = M('zy_survey')->where('is_del=0')->order('ctime desc')->findPage(10);
         //获取在线调查分类
         $catelist = $this->getCate();
		 foreach($list['data'] as $k => &$v){
			 $v['fid']   = $catelist[$v['fid']];
			 $v['ctime']   = friendlyDate($v['ctime']);
			 $v['action'] = '<a href="'.U('survey/Admin/edit',array('tabHash'=>'edit','id'=>$v['id'])).'">编辑</a> - ';
			 $v['action'] .= '<a href="javascript:void(0)" onclick="admin.delSurvey(\''.$v['id'].'\')">删除</a>';
				
	    }
		$this->displayList($list);
	}
	
	/**
	 * 添加在线调查
	 */
	public function add(){
		$this->pageTitle ['add'] = '添加在线调查';
		$this->pageKeyList = array ('fid','title','type','option[]','option[]','option[]','option[]','option[]','option[]','option[]','option[]','option[]','option[]');
		if($_SERVER['REQUEST_METHOD'] == 'POST'){//如果是post表单提交
            $data['fid']     	 = intval( $_POST['fid'] );
            $data['title']       = t( $_POST['title'] );
            $data['type']        = intval( $_POST['type'] );
            $data['ctime']       = time();
            if( $id = M('zy_survey')->add($data) ){
            	$options = array_filter( $_POST['option'] );
            	$sql = "insert into `".C('DB_PREFIX')."zy_survey_option` (`fid`,`title`) values ";
            	foreach($options as $val) {
            		$sql .= "({$id},'{$val}'),";
            	}
            	M('')->query( trim($sql , ',') );
                $this->assign('jumpUrl' , U('survey/Admin/index'));
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
            exit;
        }
        $this->opt['fid']  = $this->getCate();
        $this->opt['type'] = array('1'=>'单选','2'=>'多选');
        $this->savePostUrl = U('survey/Admin/add');
        $this->notEmpty = array('title');
       // $this->onsubmit = 'admin.addPageSubmitCheck(this)';
        $this->displayConfig();
	}
	
	/**
	 * 修改在线调查
	 */
	public function edit(){
	    $_REQUEST ['tabHash'] = 'edit';
	    $this->pageTitle ['edit'] = '编辑在线调查';
	    $id = intval( $_GET['id'] );
        $fid = $this->getCate();
        $res = M('zy_survey')->where('id=' . $id)->find();
        $res['options'] = M('zy_survey_option')->where('fid='.$res['id'].' and is_del=0')->findAll();
        $this->assign('res',$res);
        $this->assign('fid',$this->getCate());
        $this->display ( 'addSurvey' );
	}
	
	public function doEdit(){
		$data['fid']     	 = intval( $_POST['fid'] );
		$data['type']        = intval( $_POST['type'] );
		$data['title']       = t( $_POST['title'] );
		
		$id = intval( $_POST['id'] );
		M('zy_survey')->where('id=' . $id)->save($data);
		if ( M('zy_survey')->where('id=' . $id)->save($data) !== false ){
			M('zy_survey_option')->where('fid='.$id)->delete();
			$options = array_filter( $_POST['option'] );
			$sql = "insert into `".C('DB_PREFIX')."zy_survey_option` (`fid`,`title`) values ";
			foreach($options as $val) {
				$sql .= "({$id},'{$val}'),";
			}
			M('')->query( trim($sql , ',') );
			$this->assign('jumpUrl',U('survey/Admin/index'));
			$this->success('修改成功');
		}else{
			$this->error('修改失败');
		}
	}
	
	
	/**
	 * 删除在线调查
	 */
	public function delSurvey(){
		$id = $_POST['ids'];
		if( is_numeric($id) ) {
			$map['id'] = intval($id);
		} else {
			$map['id'] = array('in' , $id);
		}
		if( M('zy_survey')->where($map)->setField('is_del' , 1) ){
			if( M('zy_survey_option')->where('fid='.$id)->getField('id') ){
				M('zy_survey_option')->where( array('fid'=>$id) )->setField('is_del' , 1);
			}
			//LogRecord('admin_content', 'delPage', array('ids'=>$id) , true);
			$return['status'] = 1;
			$return['data'] = '删除成功';
		}else{
			$return['status'] = 0;
			$return['data'] = '删除失败';
		}
		echo json_encode($return);exit();
	}
	
	/*
	 * 调查结果统计
	 */
	public function stat(){
		$this->pageTitle ['stat'] = '调查统计';
		$this->pageKeyList = array ('id','cate_title','survey_title','option_title','count');
		$this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
    	//搜索字段
    	$this->searchKey = array('fid', 'id');
    	$this->searchPostUrl = U('survey/Admin/stat');
    	$cate   = M('zy_survey_category')->where('is_del = 0')->getField('id,title');
    	$survey = M('zy_survey')->where('is_del = 0')->getField('id,title');
    	$this->opt['fid'] = array_merge( array('0'=>'不限') , $cate);
    	$this->opt['id']  = array_merge( array('0'=>'不限') , $survey);
		
    	//根据分类id查
    	if (!empty($_POST['fid'])) {
    		$map['cid'] = intval( $_POST['fid'] );
    	}
    	//根据调查id查
    	if (!empty($_POST['id'])) {
    		$map['pid'] = intval( $_POST['id'] );
    	}
    	
		$list = M('zy_survey_count')->where($map)->findPage(100);
		//获取在线调查分类
		foreach($list['data'] as $k => &$v){
			$v['option_title'] = M('zy_survey_option')->where('id='.$v['fid'].' and is_del=0')->getField('title');
			$v['survey_id']    = M('zy_survey_option')->where('id='.$v['fid'].' and is_del=0')->getField('fid');
			if( !$v['survey_id'] ) 
				unset($list['data'][$k]);
			$v['survey_title'] = M('zy_survey')->where('id='.$v['survey_id'].' and is_del=0')->getField('title');
			$v['cate_id']      = M('zy_survey')->where('id='.$v['survey_id'].' and is_del=0')->getField('fid');
			if( !$v['cate_id'] )
				unset($list['data'][$k]);
			$v['cate_title']   = M('zy_survey_category')->where('id='.$v['cate_id'].' and is_del=0')->getField('title');
// 			if( $fid && $fid != $v['cate_id'] ) {
// 				unset($list['data'][$k]);
// 			}
// 			if( $id && $id != $v['survey_id'] ) {
// 				unset($list['data'][$k]);
// 			}
		}
		$this->displayList($list);
	}
	
	/**
	 * 获取分类
	 * @return array
	 */
    private function getCate(){
		return M('zy_survey_category')->where('is_del=0')->order('ctime asc')->getField('id,title');
    }
	
}