 <?php
/**
 * ��̨�̳ǹ���
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminDomaiNameAction extends AdministratorAction
{
	/**
     * 机构管理
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index']       = '已审';
        $this->pageTab[] = array('title'=>'已审','tabHash'=>'index','url'=>U('school/AdminDomaiName/index'));
        if(is_admin($this->mid)){
            $this->pageTitle['domaiName']    = '待审';
            $this->pageTab[] = array('title'=>'待审','tabHash'=>'domaiName','url'=>U('school/AdminDomaiName/domaiName'));
        }
        parent::_initialize();

    }
    /**
     * 独立域名列表
     */
    public function index(){
        $this->pageKeyList = array( 'id','title','logo','uid','doadmin','DOACTION');
        if(is_admin($this->mid)){
            $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
            //搜索字段
            $this->searchKey = array('id','uid','title');
        }
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['title'] && $map['title'] = array('like', '%' . t($_POST['title']) . '%');
            $_POST ['uid'] && $map ['uid'] = array('in', (string)$_POST ['uid']);
            }
        $map['status'] = array('in','1,2');
        if(is_admin($this->mid)){
            $school = model('School')->where($map)->order("id DESC")->findPage(20);
        }else{
            $map['uid'] = $this->mid;
            $school = model('School')->where($map)->findPage(10);
        }
        foreach($school['data'] as $key => $val) {
            $school['data'][$key]['logo']  = "<img src=".getCover($val['logo'] , 60 ,60)." width='60px' height='60px'>";
            $school['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            if($val['doadmin']){
                $school['data'][$key]['doadmin'] = $val['doadmin'].MAIN_DOMAIN;
                $url = getDomain($val['doadmin']);
            }else{
                $url = U('school/School/index', array('id' => $val['id']));
            }
            $school['data'][$key]['title'] = getQuickLink($url,$val['title'],"未知机构");

            if(is_admin($this->mid)){
                $school['data'][$key]['DOACTION'] =  '<a href="'.U('school/AdminDomaiName/editDomaiName',array('id'=>$val['id'],'tabHash'=>'editDomaiName')).'">编辑</a> ';
            }else{
                $school['data'][$key]['DOACTION'] =  '<a href="'.U('school/User/domainName').'" target="_blank">申请</a> ';
            }
        }
        $this->assign('pageTitle','独立域名列表');
        $this->_listpk = 'id';
        $this->displayList($school);
    }

    /**
     * 编辑独立域名
     */
    public function editDomaiName(){
        if(isset($_POST)){

            $id = intval($_GET['id']);
            if(!$id){
               $this->error("参数错误");
            }
            $data['doadmin']  = t($_POST['doadmin']);
            $Regx = '/^[A-Za-z]+$/';
            if(empty($_POST['doadmin'])){$this->error("请填写独立域名");}
            if(preg_match($Regx, $data['doadmin'])==0 || strlen($data['doadmin'])>19){$this->error('只能输入英文字母');}
            $res = model('School')->where(array('id'=>$id))->save($data);
            if($res){
                $this->assign('jumpUrl',U('school/AdminDomaiName/index'));
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'editDomaiName';
            $this->pageKeyList   = array('id','doadmin');
            $this->notEmpty   = array('doadmin');
            $id = intval($_GET['id']);
            $school = model('School')->where('id ='.$id)-> find() ;
            $this->pageTitle['editDomaiName'] = '编辑机构-' . $school['title'];
            $this->savePostUrl = U('school/AdminDomaiName/editDomaiName','id='.$id);
            $this->displayConfig($school);
        }
    }

    /**
     * 待审核独立域名
     */
    public function domaiName(){
        $this->pageButton[] = array('title'=>'驳回','onclick'=>"admin.mzVerify('',-1,2)");

        $this->pageKeyList = array( 'id','title','logo','uid','doadmin','ctime','DOACTION');
        $where = array('status'=>0,'type'=>2);
        $listData = M('school_verified')->where($where)->findpage(20);
        $http_host = MAIN_DOMAIN;//stristr($_SERVER['HTTP_HOST'], '.', false);
        foreach($listData['data'] as $k=>$v){
            $listData['data'][$k]['logo'] = "<img src=".getCover($v['logo'] , 60 ,60)." width='60px' height='60px'>";
            $listData['data'][$k]['uid'] = getUserSpace($v['uid'], null, '_blank');
			$listData['data'][$k]['title'] = model('School')->where('uid='.$v['uid'])->getField('title');
			$listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v["ctime"]);
            if($v['doadmin']){
                $listData['data'][$k]['doadmin'] = $v['doadmin'].MAIN_DOMAIN;
            }
			$listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.mzVerify('.$v['id'].',1,2)">通过</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.mzVerify('.$v['id'].',-1,2)">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 执行审核
     * @return json 返回操作后的JSON信息数据
     */
    public function doVerify(){
        $status = intval($_POST['status']);
        $type   = intval($_POST['type']);
        $id = $_POST['id'];
        if(is_array($id)){
            $map['id'] = array('in',$id);
        }else{
            $map['id'] = $id;
        }
        $map['type'] = $type;
        $datas['status'] = $status;
        $res = M('school_verified')->where($map)->save($datas);
        if($res){
            $return['status'] = 1;
            if($status == 1){
                $school = M('school_verified')->where($map)->find();
                //通过独立域名申请
                $data["doadmin"]   = $school['doadmin'];
                $res = model('School')->where('uid='.$school['uid'])->save($data);
                $return['data'] = "审核通过";
            }
            if($status == -1){
                $return['data'] = "驳回成功";
            }
            
        }else{
            $return['status'] = 0;
            $return['data']   = "审核失败";
        }
        echo json_encode($return);exit();
    }
}