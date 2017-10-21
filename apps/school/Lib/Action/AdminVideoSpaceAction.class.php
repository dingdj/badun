<?php
/**
 * 视频空间管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminVideoSpaceAction extends AdministratorAction
{
	/**
     * 视频空间管理
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index']       = '视频空间列表';
        $this->pageTab[] = array('title'=>'视频空间列表','tabHash'=>'index','url'=>U('school/AdminVideoSpace/index'));
        if(is_admin($this->mid)){
            $this->pageTitle['videoSpace']    = '待审核视频空间';
            $this->pageTab[] = array('title'=>'待审核视频空间','tabHash'=>'videoSpace','url'=>U('school/AdminVideoSpace/videoSpace'));
            $this->pageTitle['videoSpaceprice']    = '视频空间价格';
            $this->pageTab[] = array('title'=>'视频空间价格','tabHash'=>'videoSpaceprice','url'=>U('school/AdminVideoSpace/videoSpaceprice'));
        }
        parent::_initialize();

    }
        
    /**
     * 视频空间列表
     */
    public function index(){
        $this->pageKeyList = array( 'id','title','logo','uid','videoSpace','usedSpace','DOACTION');

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
            $school['data'][$key]['usedSpace']  = M('zy_video_space')->where('mhm_id='.$val['id'])->getField('used_video_space') ? : 0;
            $school['data'][$key]['logo']  = "<img src=".getCover($val['logo'] , 60 ,60)." width='60px' height='60px'>";
            $school['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            if(!$val['doadmin']){
                $url = U('school/School/index', array('id' => $val['id']));
            }else{
                $url = getDomain($val['doadmin']);
            }
            $school['data'][$key]['title'] = getQuickLink($url,$val['title'],"未知机构");
            if(is_admin($this->mid)){
                $school['data'][$key]['DOACTION'] =  '<a href="'.U('school/AdminVideoSpace/editVideo',array('id'=>$val['id'],'tabHash'=>'editSchool')).'">编辑</a> ';
            }else{
                $school['data'][$key]['DOACTION'] =  '<a href="'.U('school/User/videoSpace').'" target="_blank">申请</a> ';
            }

        }
        $this->assign('pageTitle','视频空间列表');
        $this->_listpk = 'id';
        $this->displayList($school); 
        
    }
    
    /**
     * 编辑视频空间
     */
    public function editVideo(){
        if(isset($_POST)){
            $id = intval($_GET['id']);
            if(!$id){
               $this->error("参数错误");
            }
            $data['videoSpace']  = t(intval($_POST['videoSpace']));
            $Regx = '/^\d+$/';
            if(empty($_POST['videoSpace'])){$this->error("请填写视频空间");}
            if(preg_match($Regx, $data['videoSpace'])==0 || strlen($data['videoSpace'])>4){$this->error('只能输入数字且长度不能超过4位');}
            $res = model('School')->where(array('id'=>$id))->save($data);
            if($res){
                $this->assign('jumpUrl',U('school/AdminVideoSpace/index'));
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'editVideo';
            $this->pageKeyList   = array('id','title','videoSpace');
            $this->notEmpty   = array('videoSpace');
            $id = intval($_GET['id']);
            $school = model('School')->where('id ='.$id)-> find() ;
            $this->pageTitle['editVideo'] = '编辑机构-' . $school['title'];
            $this->savePostUrl = U('school/AdminVideoSpace/editVideo','id='.$id);
            $this->displayConfig($school);
        }
    }

    /**
     * 待审核视频空间
     */
    public function videoSpace(){
        $this->pageButton[] = array('title'=>'驳回','onclick'=>"admin.mzVerify('',-1,1)");

        $this->pageKeyList = array( 'id','title','logo','uid','videoSpace','ctime','DOACTION');
        $where = array('status'=>0,'type'=>1);
        $listData = M('school_verified')->where($where)->findpage(20);
        foreach($listData['data'] as $k=>$v){
            $listData['data'][$k]['logo'] = "<img src=".getCover($v['logo'] , 60 ,60)." width='60px' height='60px'>";
            $listData['data'][$k]['uid'] = getUserSpace($v['uid'], null, '_blank');
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v["ctime"]);
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.mzVerify('.$v['id'].',1,1)">通过</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.mzVerify('.$v['id'].',-1,1)">驳回</a>';
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
                //通过视频空间申请
                $videoSpace = model('School')->where('uid='.$school['uid'])->getField('videoSpace');
                $data["videoSpace"]   = $videoSpace + $school['videoSpace'];
                $res = model('School')->where('uid='.$school['uid'])->save($data);
                if($res){
                    $school_id = $school['mhm_id'];
                    $video_space = M('zy_video_space')->where('mhm_id='.$school_id)->getField('video_space');
                    if($video_space){
                        $data['video_space'] = model('School')->where('uid='.$school['uid'])->getField('videoSpace');
                        M('zy_video_space')->where('mhm_id='.$school_id)->save($data);
                    }else{
                        $data['mhm_id'] = $school_id;
                        $data['video_space'] = $school['videoSpace'];
                        M('zy_video_space')->add($data);
                    }
                    $return['data'] = "申请成功";
                }
            } elseif ($status == -1){
                $return['data']   = "驳回成功";
            }
        }else{
            $return['status'] = 0;
            $return['data']   = "申请失败";
        }
        echo json_encode($return);exit();
    }

    /***
     *配置视频空间价格
     */
    public function videoSpaceprice()
    {
        if (isset($_POST)) {
            $result['oneprice'] = intval(t($_POST['oneprice']));

            if (!is_numeric($_POST['oneprice'])) {
                $this->error("价格必须为数字");
            }
            $res = M('videospaceprice')->where('id =1')->save($result);
            if ($res) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }

        }
        else {

            $this->pageKeyList = array('oneprice');
            $this->notEmpty   = array('oneprice');
            $num = M('videospaceprice')->where('id =1')-> find() ;
            $this->savePostUrl = U('school/AdminVideoSpace/videoSpaceprice');
            $this->displayConfig($num);
        }
    }
}