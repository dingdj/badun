<?php
/**
 * 独立账号后台管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminFinanceAction extends AdministratorAction
{
	/**
     * 独立账号管理
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index']       = '已审';
        $this->pageTab[] = array('title'=>'已审','tabHash'=>'index','url'=>U('school/AdminFinance/index'));
        if(is_admin($this->mid)){
            $this->pageTitle['finance']    = '待审';
            $this->pageTab[] = array('title'=>'待审','tabHash'=>'finance','url'=>U('school/AdminFinance/finance'));
        }
        parent::_initialize();

    }
    /**
     * 独立账号列表
     */
    public function index(){
        $this->pageKeyList = array( 'id','title','uid','account','accountmaster','accounttype','tel_num','DOACTION');

        if(is_admin($this->mid)){
            $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
            //搜索字段
            $this->searchKey = array('id','title','uid','account','accountmaster');
        }
        
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['uid'] && $map ['uid'] = array('in', (string)$_POST ['uid']);
            $_POST['title'] && $map['uid'] = model('School')->where(['title'=>array('like', '%' . t($_POST['title']) . '%')])->getField('uid');
            $_POST['account'] && $map ['account'] = array('like', (string)$_POST ['account']);
            $_POST['accountmaster'] && $map['accountmaster'] = array('like', '%' . t($_POST['accountmaster']) . '%');
            }
        $map['is_school'] = 1;
        if(is_admin($this->mid)){
            $finance = D('ZyBcard')->where($map)->order("id DESC")->findPage(20);
        }else{
            $map['uid'] = $this->mid;
            $finance = D('ZyBcard')->where($map)->findPage(20);
        }
        foreach($finance['data'] as $key => $val) {
            $school = model('School')->where('uid='.$val['uid'])->field('id,doadmin,title')->find();
            if(!$school['doadmin']){
                $url = U('school/School/index', array('id' => $school['id']));
            }else{
                $url = getDomain($school['doadmin']);
            }
            $finance['data'][$key]['title'] = getQuickLink($url,$school['title'],"未知机构");

            $finance['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            if(is_admin($this->mid)){
                $finance['data'][$key]['DOACTION'] =  '<a href="'.U('school/AdminFinance/editFinance',array('id'=>$val['id'],'tabHash'=>'editSchool')).'">编辑</a> ';
            }else{
                $finance['data'][$key]['DOACTION'] =  '<a href="'.U('school/User/finance').'" target="_blank">申请</a> ';
            }
        }
        $this->assign('pageTitle','独立账号列表');
        $this->_listpk = 'id';
        $this->displayList($finance);
    }

    /**
     * 编辑独立账号
     */
    public function editFinance(){
        $id = intval($_GET['id']);
        if(isset($_POST)){
            if(!$id){
               $this->error("参数错误");
            }
            $Regx1 = '/^\d{16,19}$/';
            $Regx2 = '/^[A-Za-z|\x{4e00}-\x{9fa5}]+$/u';
            $Regx3 = '/^[\d\-]{7,11}$/';

            $data['account']       = t($_POST['account']);
            $data['accountmaster'] = t($_POST['accountmaster']);
            $data['accounttype']   = t($_POST['accounttype']);
            $data['tel_num']       = t($_POST['tel_num']);
            $data["is_school"]     = 1;
            if(empty($_POST['account'])){$this->error("请输入对公账号");}
            if(preg_match($Regx1, $data['account'])==0 || strlen($data['account'])>19){$this->error('对公账号格式错误');}
            if(empty($_POST['accountmaster'])){$this->error("请输入账号开户人");}
            if(preg_match($Regx2, $data['accountmaster'])==0 || strlen($data['accountmaster'])>30){$this->error('账号开户人格式错误');}
            if(empty($_POST['accounttype'])){$this->error("请选择开户银行");}
            if(empty($_POST['tel_num'])){$this->error("请输入联系电话");}
            if(preg_match($Regx3, $data['tel_num'])==0 || strlen($data['tel_num']) !== 11 ){$this->error('联系电话格式错误');}
            $res = D('ZyBcard')->where(array('id'=>$id))->save($data);
            if($res){
                $this->assign('jumpUrl',U('school/AdminFinance/index'));
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'editFinance';
            $this->pageKeyList   = array('id','account','accountmaster','accounttype','tel_num');
            $this->notEmpty   = array('account','accountmaster','accounttype','tel_num');

            $card = D('ZyBcard','classroom')->where('id ='.$id)-> find() ;
            $a = D('ZyBcard','classroom')->getBanks();
            $this->opt['accounttype'] =  array_combine(array_values($a),$a);
            $title = model('School')->where('uid='.$card['uid'])->getField('title');

            $this->pageTitle['editFinance'] = '编辑独立账号-' . $title;
            $this->savePostUrl = U('school/AdminFinance/editFinance',array('id'=>$id));
            $this->displayConfig($card);
        }
    }

    /**
     * 待审核独立账号
     */
    public function finance(){
        $this->pageButton[] = array('title'=>'驳回','onclick'=>"admin.mzVerified('',-1)");
        $this->pageKeyList = array( 'id','title','uid','account','accountmaster','accounttype','tel_num','reason','attachment','ctime','DOACTION');
        $where = 'status=0 AND mhm_id <> 0 ';
        $listData = D('finance_verified')->where($where)->order("id DESC")->findpage(20);
        foreach($listData['data'] as $k=>$v){
            $listData['data'][$k]['uid'] = getUserSpace($v['uid'], null, '_blank');
            $school = model('School')->where('id='.$v['mhm_id'])->field('id,doadmin,title')->find();
            if(!$school['doadmin']){
                $url = U('school/School/index', array('id' => $school['id']));
            }else{
                $url = getDomain($school['doadmin']);
            }
            $listData['data'][$k]['title'] = getQuickLink($url,$school['title'],"未知机构");

            if($listData['data'][$k]['attach_id']){
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $list['data'][$k]['attachment'] = "";
                foreach($a as $key=>$val){
                    if($val !== ""){
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= msubstr($attachInfo['save_name'],0,25,"UTF-8",ture).'&nbsp;<a href="'.getImageUrl($attachInfo['save_path']).$attachInfo['save_name'].'" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v["ctime"]);
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.mzVerified('.$v['id'].',1)">通过</a> - ';
            $listData['data'][$k]['DOACTION'] .='<a href="javascript:void(0)" onclick="admin.mzVerified('.$v['id'].',-1)">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 执行审核
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
        $datas['status'] = $status;
        $res = D('finance_verified')->where($map)->save($datas);
        if($res){
            $return['status'] = 1;
            if($status == 1){
                $finance = D('finance_verified')->where('id='.$id)->find();
                $bcard = D('ZyBcard')->where('uid='.$finance['uid'])->find();
                //通过独立财务账号申请
                $data["uid"]           = $finance['uid'];
                $data["accounttype"]   = $finance['accounttype'];
                $data["account"]       = $finance['account'];
                $data["accountmaster"] = $finance['accountmaster'];
                $data["tel_num"]       = $finance['tel_num'];
                $data["is_school"]     = 1;
                if($bcard && $bcard['is_school'] == 1){
                    D('ZyBcard')->where('uid='.$finance['uid'])->save($data);
                }else{
                    D('ZyBcard')->add($data);
                }
                $return['data'] = "审核通过";
                model('Notify')->sendNotify($finance['uid'],'admin_school_finance_ok');
            }
            if($status == -1){
                $return['data']   = "驳回成功";
                model('Notify')->sendNotify($finance['uid'],'admin_school_finance_reject');
            }
        }else{
            $return['status'] = 0;
            $return['data']   = "审核失败";
        }
        echo json_encode($return);exit();
    }
}