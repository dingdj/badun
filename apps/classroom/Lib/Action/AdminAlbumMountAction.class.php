<?php
/**
 * 后台商城管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminAlbumMountAction  extends AdministratorAction
{

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index']       = '套餐挂载管理';
        $this->pageTitle['action']       = '待审核套餐挂载管理';

        $this->pageTab[] = array('title'=>'挂载套餐列表','tabHash'=>'index','url'=>U('classroom/AdminAlbumMount/index'));
        $this->pageTab[] = array('title'=>'待审核挂载套餐列表','tabHash'=>'action','url'=>U('classroom/AdminAlbumMount/action'));

        parent::_initialize();
    }
    /**
     * 套餐挂载列表管理
     */
    public function index(){
        $this->pageKeyList      = array('id','album_title','price','cover','user_title','ctime','mount_status','DOACTION');
        $this->pageButton[]  =  array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->searchKey      = array('id','album_title');
        $this->searchPostUrl = U('classroom/AdminAlbumMount/index',array('tabHash'=>'index'));

        $map['is_mount']    = ['neq',0];
        $list = $this->_getData(10 , 0,$map);

        $this->displayList($list);
    }

    /**
     * 待审核套餐挂载列表管理
     */
    public function action(){
        $_REQUEST['tabHash'] = 'action';
        $this->pageKeyList      = array('id','album_title','price','cover','user_title','ctime','mount_status','DOACTION');
        $this->pageButton[]  =  array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->searchKey      = array('id','album_title');
        $this->searchPostUrl = U('classroom/AdminAlbumMount/action',array('tabHash'=>'index'));

        $map['is_mount']    = 2;

        $list = $this->_getData(10 , 0,$map);

        $this->displayList($list);
    }

    //获取套餐数据
    private function _getData($limit = 20, $is_del,$map){
        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST['album_title'] && $map['album_title'] = array('like', '%'.t($_POST['album_title']).'%');
        }
        $map['status']  = $is_del ? array('eq',0) : array('neq',0); //搜索非隐藏内容
        $map['price']   = ['neq',0];
        $map['is_del']  = ['eq',0];
        $list = M('album')->where($map)->order('ctime desc,id desc')->findPage($limit);
        foreach ($list['data'] as &$value){
            $value['album_title'] = msubstr($value['album_title'],0,20);
            $url = U('classroom/Album/view', array('id' => $value['id']));
            $value['album_title'] = getQuickLink($url,$value['album_title'],"未知套餐");
            $value['user_title'] = getUserSpace($value['uid'], null, '_blank');
            $value['price'] = $value['price'].'元';
            $value['ctime'] = date("Y-m-d H:i:s", $value['ctime']);
            $value['cover'] = "<img src=".getCover($value['cover'] , 60 , 60)." width='60px' height='60px'>";
            if($value['is_mount'] == 1) {
                $value['mount_status'] = '<span onclick="admin.closeAlbumMount('.$value['id'].','.$value['is_mount'].');" style="color:green">已挂载</span>';
                $value['DOACTION'] .=   '<a onclick="admin.closeAlbumMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">取消挂载</a>';
            } if($value['is_mount'] == 0) {
                $value['mount_status'] = '<span onclick="admin.openAlbumMount('.$value['id'].','.$value['is_mount'].');" style="color:color: rgba(169, 169, 169, 0.6);">未挂载</span>';
                $value['DOACTION'] .=   '<a onclick="admin.openAlbumMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">允许挂载</a>';
            }else if($value['is_mount'] == 2) {
                $value['mount_status'] = '<span onclick="admin.openAlbumMount('.$value['id'].','.$value['is_mount'].');" style="red">已提交挂载待审核</span>';
                $value['DOACTION'] .=   '<a onclick="admin.openAlbumMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">挂载审核</a> ';
            }
        }
        return $list;
    }

    /**
     *  审核
     */
    public function Mountacivity()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $where = array(
            'id' => array('in', $id)
        );
        $data['is_activity'] = 1;
        $data['atime'] = time();
        $res = M('zy_video_mount')->where($where)->save($data);
        if ($res !== false) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }

}