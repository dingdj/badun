<?php
class TopicAction extends Action
{
	
	public function index() {
        //加载首页头部轮播广告位
        $ad_map = array('is_active' => 1,'display_type' => 3,'place' => 4);
        $ad_list = M('ad')->where($ad_map)->order('display_order DESC')->find();

        //序列化广告内容
        $ad_list = unserialize($ad_list['content']);

        if($this->is_wap){
            $cate_name_one = model('Topics')->where(['zy_topic_category_id'=>$_GET['cate']])->getField('title');
        }
		$cate = model('Topics')->getCate(0);
		$_GET['cate'] = $_GET['cate'] ? $_GET['cate'] : 0;
		$data = model('Topics')->getTopic(1,$_GET['cate']);
        $this->assign('cate_name_one',$cate_name_one);
        $this->assign('ad_list',$ad_list);
        $this->assign('cate',$cate);
        $this->assign('topic_data',$data);
        $this->display();
	}
	
	public function getTopicList(){
        $_GET['cate'] = $_GET['cate'] ? $_GET['cate'] : 0;
        $data = model('Topics')->getTopic(1,$_GET['cate']);
        $this->assign('topic_data',$data);
	    $html = $this->fetch('ajax_topic');
	    $data['data']=$html;
	    exit( json_encode($data) );
	}



	public function view(){
	    $id = $_GET['id'] ? intval($_GET['id']) : $this->error('参数错误');
		$data = model ('Topics')->getOnedata($id);
        $data['uname'] = M('user') ->where('uid ='.$data['uid'])->getField('uname');
        //推荐阅读
        $map['id'] = array('neq',$id);
        $recData = M('zy_topic')->where($map)->order('readcount desc')->field('id,title,image')->limit(5)->select();

		if(!$data['id']){
			$this->error('不存在的数据');
		}
        //阅读量+1
        model ('Topics')->addread($id);


        $down = model ('Topics')->downPage ($id);

        //获取上一篇
        $up = model ('Topics')->upPage($id);

        if($data['from'] == '0' || $data['from'] == "")
        {
            $data['from'] = "未知";
        }
        $collect['source_table_name'] = "zy_topic";
        $collect['source_id'] = $id;
        $mid = $this -> mid;
        $collect['uid'] = $mid;

        $collect = M('zy_collection') -> where($collect)->getField('collection_id');
        $collectcount['source_table_name'] = "zy_topic";
        $collectcount['source_id'] = $id;
        $video_collect_count = M('zy_collection') -> where($collectcount)->count();


        $maps ['app'] = 'public';
        $maps ['table'] = 'zy_topic';
        $maps ['to_comment_id'] = '0';
        $maps ['is_del'] = '0';
        $maps ['row_id'] = $id; // 必须存在
        if (! empty ($maps['row_id'] )) {
            // 分页形式数据
            $limit = 10;
            $order = 'ctime DESC';
            $list = model ( 'Comment' )->getCommentList ( $maps, $order, $limit);
            foreach($list['data'] as $k=>&$v){
                $topicmap['to_comment_id'] = $v['comment_id'];
                $topicmap['is_del'] = 0 ;
                $list['data'][$k]['commentcounts'] = M('comment')->where($topicmap)->count();
            }
            $this->assign('cmlist',$list);
        }



		$commentSwitch = model('Xdata')->get('admin_Config:commentSwitch');
		$switch = $commentSwitch['news_switch'];


		$this->assign('lid',$id);
        $this->assign('mid',$mid);
        $this->assign('collect',$collect);
        $this->assign('video_collect_count',$video_collect_count);
        $this->assign('down',$down);
        $this->assign('up',$up);
        $this->assign('data',$data);
        $this->assign('down',$down);
        $this->assign('up',$up);
        $this->assign('recData',$recData);
		$this->assign('switch',$switch);
        $this->display();
	}
    //提交评论
    public function addcomment() {
        // 返回结果集默认值
        $return = array (
            'status' => 0,
            'data' => L ( 'PUBLIC_CONCENT_IS_ERROR' )
        );
        // 获取接收数据
        $data = $_POST;
        // 安全过滤
        foreach ( $data as $key => $val ) {
            $data [$key] = t ( $data [$key] );
        }
        // 评论所属与评论内容
        $data ['app'] = 'public';
        $data ['table'] = 'zy_topic';
        $data ['content'] = filter_keyword(h ( $data ['content'] ));
        // 判断资源是否被删除
        $dao = M ( $data ['table'] );
        $idField = $dao->getPk ();
        $map [$idField] = $data ['row_id'];
        $sourceInfo = $dao->where ( $map )->find ();
        /*echo  $dao->getLastSql();
        die();*/
        if (! $sourceInfo) {
            $return ['status'] = 0;
            $return ['data'] = '内容已被删除，评论失败';
            exit ( json_encode ( $return ) );
        }
        //兼容旧方法
        if(empty($data['app_detail_summary'])){
            $source = model ( 'Source' )->getSourceInfo ( $data ['table'], $data ['row_id'], false, $data ['app'] );
            $data['app_detail_summary'] = $source['source_body'];
            $data['app_detail_url']     = $source['source_url'];
            $data['app_uid']            = $source['source_user_info']['uid'];
        }else{
            $data['app_detail_summary'] = $data ['app_detail_summary'] . '<a class="ico-details" href="' . $data['app_detail_url'] . '"></a>';
        }
        // 添加评论操作
        $data ['comment_id'] = model ( 'Comment' )->addComment ( $data );
        if ($data ['comment_id']) {
            $return ['status'] = 1;
            $return ['data'] = $this->parseComment ( $data );

            // 去掉回复用户@
            $lessUids = array ();
            if (! empty ( $data ['to_uid'] )) {
                $lessUids [] = $data ['to_uid'];
            }

            //添加消息记录

            model('Message')->doCommentmsg($this->mid,$data['to_uid'],$data['row_id'],$data['app_uid'],$data['table_name'],$data['to_comment_id'],0,"",$data['content']);
            //如果是帖子，则同步更新回复时间
            M( $data['table_name'] )->where('id='.$data['row_id'])->save( array('replytime'=>time()) );

            //积分操作
            $credit = M('credit_setting')->where(array('id'=>31,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $ztype = 6;
                $note = '资讯回复获得的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$ztype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

        }
        !$data['comment_id'] && $return['data'] = model('Comment')->getError();
        exit ( json_encode ( $return ) );
    }

    public function parseComment($data) {
        $data ['userInfo'] = model ( 'User' )->getUserInfo ( $GLOBALS ['ts'] ['uid'] );
        // 获取用户组信息
        $data ['userInfo'] ['groupData'] = model ( 'UserGroupLink' )->getUserGroupData ( $GLOBALS ['ts'] ['uid'] );
        $data ['content'] = preg_html ( $data ['content'] );
        $data ['content'] = parse_html ( $data ['content'] );
        $data ['iscommentdel'] = CheckPermission ( 'core_normal', 'comment_del' );

        return $data;
    }


    /**
     * 加载评论下的子评论
     */
    public function getSonComment(){
        $limit=6;
        $id=intval($_REQUEST['id']);
        $map=array(
            'to_comment_id'=>$id,
            'is_del'=>0
        );
        $data=model('Comment')->where($map)->order("ctime DESC")->findPage($limit);
        //循环取时间差
        foreach($data['data'] as &$val){
            $val['ctime']=getDateDiffer($val['ctime']);
        }
        $this->assign("data",$data['data']);
        $this->assign("to_comment_id",$id);
        $data['data']=$this->fetch("comm_list");
        echo json_encode($data);exit;
    }
    /**
     * 添加子回复
     */
    public function doSonComment(){
        $id=intval($_POST['id']);//获取父级评论id
        $content=filter_keyword(t($_POST['txt']));//获取回复内容
        $wid=intval($_POST['wid']);//获取回复id
        if(strlen($content)<3){
            echo "对不起，回复内容最少为3个字符";
            exit;
        }
        $to_uid = model('Comment')->where('comment_id='.$id)->getField('uid');
        $map=array(
            'app'=>'public',
            'table'=>'zy_topic',
            'row_id'=>$wid,
            'to_comment_id'=>$id,
            'content'=>$content,
            'ctime'=>time(),
            'uid'=>$this->mid,
            'to_uid'=>$to_uid
        );
        $res = model('Comment')->addComment($map);
        if($res){
            //查询评论内容
            $data=model('Comment')->where(array('id'=>$id))->find();

            //添加消息记录
            model('Message')->doCommentmsg($this->mid,$data['to_uid'],$data['row_id'],$data['app_uid'],$data['table_name'],$data['to_comment_id'],0,"",$data['content']);
            echo 200;
            exit;
        }else{
            echo "对不起，回复失败，请重试！";
            exit;
        }

    }
    //删除子回复
    function delCommComment(){
        $map['comment_id']=$_POST["id"];
        $map['to_comment_id']=$_POST["pid"];
        $data["is_del"]=1;
        $data["ctime"]=time();
        $res=model('Comment')->where($map)->save($data);
        if($res){
            echo 200;
            exit;
        }else{
            echo "对不起，删除失败，请重试！";
            exit;
        }
    }

    /**
     * 设置赞+1
     */
    public function dotopicCommentZan(){
        $id=intval($_POST['id']);
        $map=array(
            'uid'=>$this->mid,
            'comment_id'=>$id
        );
        $res=M('comment_praise')->where($map)->find();
        if($res){
            echo "500";
            exit;
        }else{
            //积分操作
            $fid = M('comment')->where('comment_id ='.$id)->getField('uid');
            $credit = M('credit_setting')->where(array('id'=>33,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $ztype = 6;
                $note = '资讯回复被点赞获得的积分';
            }
            model('Credit')->addUserCreditRule($fid,$ztype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            M('comment_praise')->add($map);

            M('comment')->where('comment_id ='.$id)->setInc('help_num');
            echo 200;
            exit;
        }
    }


}