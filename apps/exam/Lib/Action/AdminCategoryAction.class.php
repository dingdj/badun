<?php
/**
 * 考试系统后台配置
 * 分类管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminCategoryAction extends AdministratorAction
{
	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize()
	{
		
		parent::_initialize();
		$exam=M("ex_exam_category");
	}
	/**
     * 考试分类后台管理菜单
     * @return void
     */
    private function _initExamListAdminMenu(){
        $this->pageTab[] = array('title'=>'考试分类管理','tabHash'=>'index','url'=>U('exam/AdminCategory/index'));
        $this->pageTab[] = array('title'=>'试卷分类管理','tabHash'=>'paper','url'=>U('exam/AdminCategory/paper'));
        $this->pageTab[] = array('title'=>'试题分类管理','tabHash'=>'question','url'=>U('exam/AdminCategory/question'));
        $this->pageTab[] = array('title'=>'试题答案类型管理','tabHash'=>'option','url'=>U('exam/AdminCategory/option'));
        //$this->pageTab[] = array('title'=>'添加分类','tabHash'=>'addCategory','url'=>U('exam/AdminCategory/addCategory'));
    }
    /**
     * 后台考试分类的标题
     */
    private function _initExamListAdminTitle(){
        $this->pageTitle['index'] = '考试管理列表';
        $this->pageTitle['question'] = '题库分类管理';
        $this->pageTitle['paper'] = '试卷分类管理';
        $this->pageTitle['option'] = '试题答案类型管理';
    }
	//考试分类列表
	public function index(){
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $treeData = model ( 'CategoryTree' )->setTable ( 'ex_exam_category' )->getNetworkList ();
        $this->displayTree ( $treeData, 'ex_exam_category');
    }

    //考试分类列表
    public function paper(){
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $treeData = model ( 'CategoryTree' )->setTable ( 'ex_paper_category' )->getNetworkList ();
        $this->displayTree ( $treeData, 'ex_paper_category');
    }
    //试题分类列表
    public function question(){
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $treeData = model ( 'CategoryTree' )->setTable ( 'ex_question_category' )->getNetworkList ();
        $this->displayTree ( $treeData, 'ex_question_category');
    }
    
    //试题类型分类列表
    public function option(){
    	$this->_initExamListAdminMenu();
    	$this->_initExamListAdminTitle();
    	$this->pageKeyList = array('question_type_id','question_type_title','question_type_admin','question_type_insert_date','DOACTION');
    	$list = M("ex_question_type")->order('question_type_update_date desc')->field('question_type_id,question_type_title,question_type_admin,question_type_insert_date')->findPage($limit);
    	foreach ($list['data'] as $key => $value){
    		$list['data'][$key]['question_type_insert_date'] =date("Y-m-d H:i:s",$value['question_type_insert_date']);
    		$list['data'][$key]['question_type_admin'] =getUserSpace($value['question_type_admin'], null, '_blank');
    		$list['data'][$key]['DOACTION'] = '<a href="'.U('exam/AdminCategory/addCategory',array('id'=>$value['question_type_id'],'tabHash'=>'addCategory','category_type'=>2,'table'=>'option')).'">编辑</a> ';//| <a href="javascript:admin.mzOptionCategoryEdit('.$value['question_type_id'].',\'delOptionCatetory\',\'删除\',\'试题分类\');">删除</a> ';
            if($value['question_type_id'] > 5) {
                $list['data'][$key]['DOACTION'] .= '<a href="javascript:admin.mzOptionCategoryEdit('.$value['question_type_id'].',\'delOptionCatetory\',\'删除\',\'试题分类\');">删除</a> ';
            }
    	}
        $this->allSelected = false;
    	$this->displayList($list);
    }
    /**
     * 删除试题类型分类
     * @return void
     */
    public function delOptionCatetory(){
    	if($_POST['id']==1 || $_POST['id']==2 || $_POST['id']==3 || $_POST['id']==4 || $_POST['id']==5){
    		exit(json_encode(array('status'=>'0','info'=>'此类型不可删除！')));
    	}
    	$result = M('ex_question_type')->where('question_type_id ='.$_POST['id'])->delete();
    	if($result){
    		exit(json_encode(array('status'=>'1','info'=>'已删除')));
    	} else {
    		exit(json_encode(array('status'=>'0','info'=> '操作繁忙,请稍后再试')));
    	}
    }
    
    //添加分类
    public function addCategory(){
    	$this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        if( $_GET["id"] ){
            $this->pageTitle['addCategory'] = '编辑分类';
            if($_GET["table"]=="option"){
                $category=M("ex_question_type")->where(array('question_type_id'=>$_GET["id"]))->find();
                $category_name=$category["question_type_title"];
            }else{
                $category=M("ex_".$_GET["table"]."_category")->where(array($_GET['table'].'_category_id'=>$_GET["id"]))->find();
                $category_name=$category[$_GET['table']."_category_name"];  
            }
            $this->assign("category_name",$category_name);
            $this->assign("category_id",$_GET["id"]);
            $this->assign("table",$_GET["table"]);
            $this->assign("category_type",$_GET["category_type"]);
        }else{
            $this->pageTitle['addCategory'] = '添加分类';
        }
        $this->display();
    }
    
    //添加分类操作
    public function doAddCategory(){
        if($_POST['category_id']){
            $data[$_POST['name'].'_update_date'] = time();
            if($_POST['category_type']==2){
                $data["question_type_title"]=$_POST['category_name'];
                $result = M('ex_question_type')->where('question_type_id='.$_POST['category_id'])->data($data)->save();
            }else{
                $data[$_POST['name'].'_category_name']=$_POST['category_name'];
                $result = M('ex_'.$_POST["name"]."_category")->where($_POST["name"].'_category_id = '.$_POST['category_id'])->data($data)->save();
            }
        }else{
            if($_POST['category_type']==2){
                $type=1;
                $data['question_type_title']=$_POST['category_name'];
                $data['question_type_admin']=$this->mid;
                $data['question_type_update_date']= time();
                $data['question_type_insert_date']=time();
                $result = M('ex_question_type')->data($data)->add();
            }else{
                $data[$_POST['name'].'_category_name']=$_POST['category_name'];
                $data[$_POST['name'].'_category_admin']=$this->mid;
                $data[$_POST['name'].'_category_update_date'] = time();
                $data[$_POST['name'].'_category_insert_date'] = time();
                $result = M('ex_'.$_POST["name"]."_category")->data($data)->add();
            } 
        }
        if($result){
            if($_POST['category_id']){
            	if($_POST["name"]=="exam"){
            		exit(json_encode(array('status'=>'1','info'=>'编辑成功','name'=>'index')));
            	}
                exit(json_encode(array('status'=>'1','info'=>'编辑成功','name'=>$_POST['name'])));
            }else {
            	if($_POST["name"]=="exam"){
            		exit(json_encode(array('status'=>'1','info'=>'添加成功','name'=>'index')));
            	}
                exit(json_encode(array('status'=>'1','info'=>'添加成功','name'=>$_POST['name'])));
            }
        } else {
            exit(json_encode(array('status'=>'0','info'=>'系统繁忙，请稍后再试')));
        }
    }
	
}