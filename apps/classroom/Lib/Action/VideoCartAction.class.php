<?php
/**
 * 云课堂点播(班级)控制器
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class VideoCartAction extends CommonAction {

    public function index(){
        if (! $this->mid) {
            $this->assign ( 'isAdmin', 1 );
            $this->error ( "请登录先，客官!" );
        }
        import ( session_id (), $this->mid );
        $merge_video_list ['data'] = D ( "ZyVideoMerge" )->getList ( $this->mid, session_id () );

        dump($merge_video_list);
        $merge_video_list ['total_price'] = 0;
        foreach ( $merge_video_list ['data'] as $key => $value ) {
            $aid[$key]=$value['video_id'];
        }
        $condition['id']=array(
            'in',
            $aid
        );
        $getAlbumInfo = D('ZyAlbum')->where($condition)->select();
        foreach ( $merge_video_list ['data'] as $key => $value ) {
            $merge_video_list['data'][$key]['album_title']=$getAlbumInfo[$key]['album_title'];
            $merge_video_list['data'][$key]['price']=$getAlbumInfo[$key]['price'];
        }

        $user_info = D ( "ZyLearnc", "classroom" )->getUser ( $this->mid );
        $this->assign ( 'user_info', $user_info );
        $this->assign ( 'merge_video_list', $merge_video_list );
        $this->display ();
    }

    //将课程添加到购物车
    public  function  addVideoMerge(){
        if(!$this->mid){
            $this->mzError("需要登录才可以进行操作");
        }
        $id     = intval($_POST['id']);
        $type   = intval($_POST['type']);

//        if(D('ZyOrderAlbum')->where(" album_id=$id AND is_del`=0 AND uid=$this->mid")->select()){
//            $this->mzError(" 您已经购买了此专辑，不能再次购买");
//        }
        if (D ( 'ZyVideoMerge' )->addVideo ( $id, $this->mid, session_id (), $type)) {
            $this->ajaxReturn ( true, '', true );
        }

        $this->ajaxReturn ( false, '', false );
    }
}
