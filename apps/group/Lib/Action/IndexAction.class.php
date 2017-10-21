<?php
class IndexAction extends BaseAction
{
	protected $group;
	public function _initialize()
	{
		parent::base();
		$this->group = D('Group');
		/*
		 * 右侧信息
		 */
		 if (in_array((ACTION_NAME), array('find', 'search', 'add'))) {
	    	// 加入的群组的数量
	    	$join_group_count = D('Member')->where('(level>1 AND status=1)  AND uid=' . $this->mid)->count();
	    	$this->assign('join_group_count', $join_group_count);

	    	// 热门标签
	    	$hot_tags_list = D('GroupTag')->getHotTags();
	    	$this->assign('hot_tags_list', $hot_tags_list);

	    	// 群组热门排行
	    	$hot_group_list = $this->group->getHotList();
	    	$this->assign('hot_group_list', $hot_group_list);
		} else if (in_array((ACTION_NAME), array('index','message', 'post', 'replied', 'comment', 'atme', 'bbsNotify'))) {
		    //检查所有模板里面需要检查项
		    $check['inIndex'] = in_array(ACTION_NAME,array('index','message'));
		    $check['newly'] = $check['inIndex']?on:off;
		    $check['postly']      = ACTION_NAME  == 'post'?"on":"off";
		    $check['replied']     = ACTION_NAME  == "replied"?"on":"off";
            $check['commentes']   = ACTION_NAME  == 'comment'?"on":"off";
            $check['bbsNotifyes'] = ACTION_NAME == 'bbsNotify'?'on':"off";
            $check['atmes']        = ACTION_NAME  == 'atme'?'on':'off';

		    $this->assign($check);
			$this->assign('userCount', D('GroupUserCount', 'group')->getUnreadCount($this->mid));
		}
	}

    public function getGroupList(){
        $or = t($_GET['or']);
        if($or == 2){
            $order = 'ctime desc';
        }else if($or == 3){
            $order = 'membercount desc,threadcount desc';
        }
        $g_map['status'] = 1;
        $g_map['is_del'] = $h_map['is_del']= 0;
        if(intval($_GET['cate_id'])){
            $g_map['cid0'] = intval($_GET['cate_id']);
        }

        $ground_list = M('group')->where($g_map)->order($order)->findPage(16);
        foreach($ground_list['data'] as $key => $val){
            $member = M('group_member')-> where(['gid'=>$val['id'],'uid'=>$this->mid])->field('id,level')->find();
            if($member['id']){
                $ground_list['data'][$key]['groupstatus'] = $member['level']+1;
            }
        }
        $this->assign('ground_list',$ground_list);
        $html = $this->fetch('ajax_ground_w3g');
        $ground_list['data']=$html;
        exit( json_encode($ground_list) );
    }

	public function index()
    {
        $map['status'] = 1;
        $map['is_del'] = 0;
        $cate_list = M('group_category')->field('id,title')->findAll();

        $or = t($_GET['or']);
        if($or == 2){
            $order = 'ctime desc';
        }else if($or == 3){
            $order = 'membercount desc,threadcount desc';
        }
        $g_map['is_del'] = $h_map['is_del']= 0;
        $g_map['status'] = $h_map['status']= 1;
        if(intval($_GET['cate_id'])){
            $g_map['cid0'] = intval($_GET['cate_id']);
            $cate_catt_list = M('group_category')->where('id='.intval($_GET['cate_id']))->field('id,title')->find();
            $this->assign('cate_catt_list',$cate_catt_list);
        }

        $ground_list = M('group')->where($g_map)->order($order)->findPage(16);
        foreach($ground_list['data'] as $key => $val){
            $member = M('group_member')-> where(['gid'=>$val['id'],'uid'=>$this->mid])->field('id,level')->find();
            if($member['id']){
                $ground_list['data'][$key]['groupstatus'] = $member['level']+1;
            }
            $ground_list['data'][$key]['threadcount'] = M('group_topic')->where('gid='.$val['id'].' and is_del=0')->count();
            $ground_list['data'][$key]['membercount'] = M('group_member')-> where('gid='.$val['id'] .' and level != 0')->count();
        }

        $ground_hot_list = D('Group')->where($h_map)->order('membercount desc,threadcount desc')->findPage(12);
        foreach($ground_hot_list['data'] as $key => $val){
            $member = M('group_member')-> where(['gid'=>$val['id'],'uid'=>$this->mid])->field('id,level')->find();
            if($member['id']){
                $ground_hot_list['data'][$key]['groupstatus'] = $member['level']+1;
            }
            $ground_hot_list['data'][$key]['membercount'] = M('group_member')-> where('gid='.$val['id'] .' and level != 0')->count();
        }
        $this->assign('cate_list',$cate_list);
        $this->assign('ground_list',$ground_list);
        $this->assign('ground_hot_list',$ground_hot_list);

		$this->display();
	}

// 获取评价
    public function grouptopics(){
        $list = M('group_category')->field('id,title')->findAll();
        foreach ( $list as $val ){
                $data[]['topic'] = M('group_topic')->where('cid0 ='.$val['id'] . ' and is_del=0')->order('viewcount desc')->limit(3)->select();
            }
       return $data;
    }

//获取标签
public function taghot()
{
 $data = M('group_tag')->limit(15)->select();
foreach ($data as $k=>$v)
{
    $list[$k]['tag_name']  =  M('tag') -> where(array('tag_id' => $v['tag_id'] ))->getField('name');
    if($list[$k]['tag_name'] == null)
    {
        unset($list[$k] );
    }
}
return $list;

}

//获取今日话题
    public function todaytalk()
    {
        $data = M('group_topic')->order('viewcount desc')->limit(10)->field('id,
        title')->select();

        return $data;

    }
/*
//随机查询
 public  function index()
 {
     $id = implode(",", $_POST['id']);
     $id =5 ;
     $list = M('group_category')->where('id='.$id)->field('id,title')->findAll();
     foreach ( $list as &$val ){
         $map['cid0'] = $val['id'];
         $val['group_list'] = D('Group')->where($map)-> limit(5)->order('rand()')->select();
         }
dump($list);
     $this->assign('list',$list);
     $this->display();

     }*/





	/**
	 * 首页右侧数据查询
	 */
	private function _indexRight(){
		$list = D('Category')->order('pid')->findAll();
		$catelist = array();
		foreach ( $list as $v ){
			if ( $v['pid'] && $catelist[$v['pid']] ){
				$catelist[$v['pid']]['child'][] = $v;
			} else {
				$catelist[$v['id']] = $v;
			}
		}
		$hotlist = D('Group', 'group')->getHotList();
		foreach ( $hotlist as &$hv ) {
			$hv['short_name'] = getShort( $hv['name'] , 10);
			$hv['logo'] = logo_path_to_url( $hv['logo'] );
		}
		$this->assign( 'hotlist' , $hotlist );
		$this->assign( 'catelist' , $catelist );
	}
	
	
    public function message()
    {



        $order = in_array($_GET['order'], array('group', 'ctime')) ? $_GET['order'] : 'group';
        $my_group = D('Member')->field('gid')
        					   ->where("uid={$this->mid} AND status=1 AND level>0")
        					   ->order('level ASC,ctime DESC')
        					   ->findAll();
		// 无任何群组，则跳转一次到发现页
        if (!$my_group && !cookie('new_group_user')) {
        	cookie('new_group_user', $this->mid);
        	$this->redirect('group/Index/find');
        	exit;
        }

        switch ($order) {
        	case 'ctime':	// 按照时间查看
        		foreach ($my_group as $v) {
        			$_gids[] = $v['gid'];
        		}
        		$index_list = D('WeiboOperate', 'group')->doSearchTopic('gid IN (' . implode(',', $_gids) . ')', 'ctime DESC', $this->mid);
        		// 群组名称
        		$index_list_gids = getSubByKey($index_list['data'], 'gid');
        		$map	   = array();
        		$map['id'] = array('IN', $index_list_gids);
        		$group_name = $this->group->field('id,name')->where($map)->findAll();
        		foreach ($group_name as $v) {
        			$_group_name[$v['id']] = $v['name'];
        		}
        		foreach ($index_list['data'] as &$v) {
        			$v['group_name'] = $_group_name[$v['gid']];
        		}
        		break;
        	default:	// 按照群组查看
        		foreach ($my_group as $v) {
        			$_gids[] = $v['gid'];
        		}
        		// 群组基本信息
        		$map	  	   = array();
        		$map['id'] 	   = array('IN', $_gids);
        		$map['status'] = 1;
        		$map['is_del'] = 0;
        		$my_group_info = $this->group->field('id,name,logo,membercount')->where($map)->findAll();
        		foreach ($my_group_info as $v) {
        			$index_list[$v['id']]['group_info'] = $v;
        			// 最新微博
        			$index_list[$v['id']]['weibo_list'] = D('GroupWeibo')->field('weibo_id,gid,uid,content,ctime')
			        												     ->where("gid={$v['id']} AND isdel=0")
			        												     ->order('weibo_id DESC')->limit(3)->findAll();
        		}
        		// 今日微博统计
        		$today = mktime(0,0,0,date("m"),date("d"),date("Y"));
        		$new_weibo_count = D('GroupWeibo')->field('gid,count(gid) AS count')->where('gid IN (' . implode(',', $_gids) . ') AND isdel=0 AND ctime>' . $today)->group('gid')->findAll();
        		foreach ($new_weibo_count as $v) {
        			$index_list[$v['gid']]['new_weibo_count'] = $v['count'];
        		}
        		// 今日新帖
        		$new_topic_count = D('Topic')->field('gid,count(gid) AS count')->where('gid IN (' . implode(',', $_gids) . ') AND is_del=0 AND addtime>' . $today)->group('gid')->findAll();
        		foreach ($new_topic_count as $v) {
        			$index_list[$v['gid']]['new_topic_count'] = $v['count'];
        		}
        		// 今日文件
        		$new_file_count = D('Dir')->field('gid,count(gid) AS count')->where('gid IN (' . implode(',', $_gids) . ') AND is_del=0 AND ctime>' . $today)->group('gid')->findAll();
        		foreach ($new_file_count as $v) {
        			$index_list[$v['gid']]['new_file_count'] = $v['count'];
        		}
        		// 今日新成员统计
        		$new_member_count = D('Member')->field('gid,count(gid) AS count')->where('gid IN (' . implode(',', $_gids) . ') AND level>1 AND ctime>' . $today)->group('gid')->findAll();
        		foreach ($new_member_count as $v) {
        			$index_list[$v['gid']]['new_member_count'] = $v['count'];
        		}
        		break;
        }

        $check['byGroup'] = $order == 'group';
        $check['byCtime'] = $order == 'ctime';
        $this->assign($check);
        $this->assign('order',  $order);
        $this->assign('index_list', $index_list);
        $this->setTitle("最近更新");
        $this->display("index");
    }

    function post()
    {
    	$index_list = D('WeiboOperate', 'group')->doSearchTopic('uid=' . $this->mid, 'ctime DESC', $this->mid);
        // 群组名称
        $index_list_gids = getSubByKey($index_list['data'], 'gid');
        $map	   = array();
        $map['id'] = array('IN', $index_list_gids);
        $group_name = $this->group->field('id,name')->where($map)->findAll();
        foreach ($group_name as $v) {
        	$_group_name[$v['id']] = $v['name'];
        }
        foreach ($index_list['data'] as &$v) {
        	$v['group_name'] = $_group_name[$v['gid']];
        }

        $this->assign('index_list', $index_list);
        $this->setTitle("我发布的");
        $this->display('index');
    }

    function replied()
    {
    	$weibo_count = D('WeiboComment', 'group')->field('COUNT(DISTINCT(weibo_id)) AS count')->where("uid={$this->mid} AND isdel=0")->find();
    	$index_list  = D('WeiboComment', 'group')->field('DISTINCT(weibo_id)')->where("uid={$this->mid} AND isdel=0")->findPage(20, $weibo_count['count']);
    	foreach ($index_list['data'] as $v) {
    		$_weibo_ids[] = $v['weibo_id'];
    	}
    	$weibo_list = D('WeiboOperate', 'group')->doSearchTopic('weibo_id IN (' . implode(',', $_weibo_ids) . ')', 'ctime DESC', $this->mid);
    	$index_list['data'] = $weibo_list['data'];
        // 群组名称
        $index_list_gids = getSubByKey($index_list['data'], 'gid');
        $map	   = array();
        $map['id'] = array('IN', $index_list_gids);
        $group_name = $this->group->field('id,name')->where($map)->findAll();
        foreach ($group_name as $v) {
        	$_group_name[$v['id']] = $v['name'];
        }
        foreach ($index_list['data'] as &$v) {
        	$v['group_name'] = $_group_name[$v['gid']];
        }

        $this->assign('index_list', $index_list);
        $this->setTitle("我评论的");
        $this->display('index');
    }

    // 群内评论
    public function comment()
    {
    	D('GroupUserCount')->setZero($this->mid, 'comment');
    	$type = $_GET['type'] == 'send' ? 'send' : 'receive';
    	//$from_app = $_GET['from_app'] == 'other' ? 'other' : 'weibo';
    	$from_app = 'weibo';
    	$comment_list  = D('WeiboComment','group')->getCommentList($type, 'all', $this->mid);

    	$this->assign('comment_list', $comment_list);
    	$this->assign('from_app', $from_app);
    	$this->assign('type', $type);
        $this->setTitle('群内评论');
    	$this->display();
    }

    // @到我的微博
    public function atme()
    {
    	D('GroupUserCount')->setZero($this->mid, 'atme');
        $index_list = D('WeiboOperate','group')->getAtme($this->mid);

        $group_map['id'] = array('IN', array_unique(getSubByKey($index_list['data'], 'gid')));
        $group_info = D('Group', 'group')->field('id,name')->where($group_map)->findAll();
        $group_names = array();
        foreach ($group_info as $value) {
        	$group_names[$value['id']] = $value['name'];
        }

        $this->assign('index_list', $index_list);
        $this->assign('group_names', $group_names);
        $this->setTitle('群内@到我的');
    	$this->display('index');
    }

    // 群内其他消息
    public function bbsNotify()
    {
    	D('GroupUserCount')->setZero($this->mid, 'bbs');
		$list = X('Notify')->get('receive=' . $this->mid . ' AND type LIKE "group\_topic\_%"', 10);
		// 解析表情
		foreach($list['data'] as $k => $v) {
			$list['data'][$k]['title'] = preg_replace_callback("/\[(.+?)\]/is",replaceEmot,$v['title']);
			$list['data'][$k]['body']  = preg_replace_callback("/\[(.+?)\]/is",replaceEmot,$v['body']);
			$list['data'][$k]['other'] = preg_replace_callback("/\[(.+?)\]/is",replaceEmot,$v['other']);
		}
		//$this->assign('userCount', X('Notify')->getCount($this->mid));
		$this->assign($list);
		$this->setTitle('群内帖子消息');
    	$this->display('notify');
    }

    // 发现群组
    public function newIndex()
    {
		U('group/Index/index','', true);
    }

    // 可能感兴趣的群组
    public function interesting()
    {
		$group_list =  $this->group->interestingGroup($this->mid);
		if($group_list['count']==0){
			exit;
		}

		$this->assign('next_page', ($group_list['nowPage'] < $group_list['totalPages'])?($group_list['nowPage'] + 1):'1');
		$this->assign('now_page', $group_list['nowPage']);
		$this->assign('group_list', $group_list['data']);
    	$this->display();
    }

	//群的创建
	function addgroup()
	{
		if (0 == $this->config['createGroup']) {
            $this->assign('isAdmin',1);
			// 系统后台配置关闭创建
			$this->error('群组创建已关闭');
		} else if ($this->config['createMaxGroup'] <= $this->group->where('is_del=0 AND uid=' . $this->mid)->count()) {
            $this->assign('isAdmin',1);
			// 系统后台配置要求，如果超过，则不可以创建
			$this->error('你不可以再创建了，超过系统规定数目');
		}
        if(!$this->mid){
            $this->assign('isAdmin',1);
		   $this->error('请先登录');
	    }
		$this->_getSearchKey();
		$categrory = D('Category')->_maskTreeNew(0);
		$this->assign('categrory', $categrory);
		$this->assign('reTags', D('GroupTag')->getHotTags('recommend'));
        $this->setTitle("创建群组");
		$this->display();
	}

    //群的编辑
    function editgroup()
    {
        if(!$this->mid){
            $this->assign('isAdmin',1);
            $this->error('请先登录');
        }
        $id = $_GET['id'];
        $groupinfo = M('group')->where('id='.$id)->find();
        $categrory = M('group_category')->field('id,title')->findAll();

        $this->assign('groupinfo', $groupinfo);
        $this->assign('categrory', $categrory);
        $this->setTitle("编辑群组");
        $this->display();
    }


//  创建帖子
    function addtopic()
    {
        $gid = $_GET['gid'];
        $level = M('group_member')->where(array('gid'=>$gid,'uid'=>$this->mid)) -> getField('level');
        if($level == 0){
            $this->error('抱歉，您还不是该小组成员');
        }
        $groupmap['uid'] = $this ->mid;
        $group = M('group_member')->where($groupmap) -> field('gid') ->select();

        foreach($group  as $key =>$val)
        {
            $group[$key]['groupname'] = M('group')-> where('id= '.$val['gid']) ->getField('name');
        }

        $this->assign('group',$group);
        $this->assign('gid',$gid);
        $this->display();

    }




    function   doaddtopic()
    {

//        $Verify = new Verify();
//
//        if (!$Verify->check($_POST['verify'])) {
//            echo "验证码错误！";
//            exit;
//        }


        $attach = explode('|',$_POST['attach_ids']);

        $i = 0;
        foreach($attach as $val)
        {
            if($val != "")
            {
                $attachs[$i] = intval($val);
                $i++;
            }
        }

        $attachs = serialize($attachs);



        $data['attach']  = $attachs; //封面
        $data['title'] =  filter_keyword(t($_POST['topcititle']));
        $data['intro'] =  filter_keyword(t($_POST['intro']));
        $data['gid'] = t($_POST['group_id']);
        $data['addtime'] = time();
        $data['is_del'] =0;
        $data['uid'] = $this->mid;
        $data['name'] = getUserName($this->mid);

        $res = M('group_topic') -> add($data);

        if($res)
        {
            //积分操作
            $credit = M('credit_setting')->where(array('id'=>24,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $ttype = 6;
                $note = '小组发帖获得的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$ttype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
            echo $res;
            exit;
        }
        else{
            echo "发帖失败！";
            exit;
        }

    }




	public function code(){
        $Verify = new Verify();

        if(!$Verify->check($_POST['verify']))
        {
            echo "验证码错误";
            return;
        }
        else{
            echo "right";
        }
	}

    public function verify()
    {
        $Verify = new Verify();
        $Verify->entry();
    }




    //做创建操作
	public function doAdd()
    {
        $id = intval($_POST['id']);

//        $Verify = new Verify();
//
//        if (!$Verify->check($_POST['verify'])) {
//            echo "验证码错误！";
//            exit;
//        }

        if (0 == $this->config['createGroup']) {
            $this->assign('isAdmin', 1);
            // 系统后台配置关闭创建
            echo '群组创已经关闭';
        } else if ($this->config['createMaxGroup'] <= $this->group->where('is_del=0 AND uid=' . $this->mid)->count()) {
            //系统后台配置要求，如果超过，则不可以创建
            $this->assign('isAdmin', 1);
            echo '你不可以再创建了，超过系统规定数目';
        }


        $group['uid'] = $this->mid;
        $group['name'] = filter_keyword(h(t($_POST['gruopname'])));
        $group['intro'] = filter_keyword(h(t($_POST['intro'])));
        $group['announce'] = filter_keyword(h(t($_POST['announce'])));
        $group['cid0'] = intval($_POST['cid0']);
        $cid1 = D('Category', 'group')->_digCateNew($_POST);
        intval($cid1) > 0 && $group['cid1'] = intval($cid1);

        if (!$group['name']) {
            $this->assign('isAdmin', 1);
            echo '群组名称不能为空';exit;
        } else if (get_str_length($_POST['name']) > 30) {
            $this->assign('isAdmin', 1);
            echo '群组名称不能超过30个字';exit;
        }

        if (D('Group', 'group')->where(array('name' => $group['name']))->find()) {
            $this->assign('isAdmin', 1);
            echo '这个群组名称已被占用';exit;
        }

        if (get_str_length($_POST['intro']) > 200) {
            $this->assign('isAdmin', 1);
            echo '群组简介请不要超过200个字';exit;
        }
        if (get_str_length($_POST['announce']) > 200) {
            $this->assign('isAdmin', 1);
            echo '群组公示请不要超过200个字';exit;
        }

        $group['type'] = $_POST['type'] == 'open' ? 'open' : 'close';

        $group['need_invite'] = intval($this->config[$group['type'] . '_invite']);  //是否需要邀请
        $group['brower_level'] = $_POST['type'] == 'open' ? '-1' : '1'; //浏览权限

        $group['openWeibo'] = intval($this->config['openWeibo']);
        $group['openUploadFile'] = intval($this->config['openUploadFile']);
        $group['openBlog'] = intval($this->config['openBlog']);
        $group['whoUploadFile'] = intval($this->config['whoUploadFile']);
        $group['whoDownloadFile'] = intval($this->config['whoDownloadFile']);
        $group['openAlbum'] = intval($this->config['openAlbum']);
        $group['whoCreateAlbum'] = intval($this->config['whoCreateAlbum']);
        $group['whoUploadPic'] = intval($this->config['whoUploadPic']);
        $group['anno'] = intval($_POST['anno']);
        $group['ctime'] = time();
        if (0 == $this->config['createAudit']) {
            $group['status'] = 1;
        }else{
            $group['status'] = 0;
        }

        // 群组LOGO
        $options['allow_exts'] = 'jpg,gif,png,jpeg,bmp';
        $options['max_size'] = 2 * 1024 * 1024;
        $options['attach_type'] = 'group_logo';
        $data['upload_type'] = 'image';
        $info = model('Attach')->upload($data, $options);
        if ($info['status']) {
            $group['logo'] = $info['info'][0]['save_path'] . $info['info'][0]['save_name'];
        } else {
//            $group['logo'] = 'default.png';
            $attachInfo = model('Attach')->getAttachById(intval($_POST['cover_ids']));
            $group['logo'] = $attachInfo['save_path'] . $attachInfo['save_name'];
        }
        if (is_admin($this->mid))
        {
            $group['status'] = 1;
        }
        if($_POST['state'] == 'edit'){
            if($_POST['logo']){
                $group['logo'] = $_POST['logo'];
            }
            $result = $this->group->where('id='.$id)->save($group);
        }else{
            $gid = $this->group->add($group);
        }
        if(!$result){
            if($gid) {
                // 积分操作
//                X('Credit')->setUserCredit($this->mid,'add_group');
                if (0 == $this->config['createAudit']) {
                    $credit = M('credit_setting')->where(array('id'=>23,'is_open'=>1))->field('id,name,score,count')->find();
                    if($credit['score'] > 0){
                        $gtype = 6;
                        $note = '创建小组获得的积分';
                    }
                    model('Credit')->addUserCreditRule($this->mid,$gtype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                }

                // 把自己添加到成员里面
                $this->group->joingroup($this->mid, $gid, 1, $incMemberCount=true);

                // 添加群组标签
                D('GroupTag')->setGroupTag($_POST['tags'], $gid);



                S('Cache_MyGroup_'.$this->mid,null);

                if (1 == $this->config['createAudit']) {
                    $this->assign('isAdmin',1);
                    echo 200;
                    exit;
                } else {
                    echo 500;
                    exit;
                }
            } else {
                echo "创建失败！";
                exit;
            }
        }

	}



	//最新话题
	function newtopic(){
		$this->assign('mymanagegroup',$this->group->mymanagegroup($this->mid));  //我管理的群组
		$this->assign('myjoingroup',$this->group->myjoingroup($this->mid));    //我加入的群组

		$this->assign('newTopic',$this->group->getnewtopic($this->mid));         //最新话题 自己加入的群组和自己创建的
		$this->setTitle($this->siteTitle['my_group_new_topic']);
		$this->display();
	}

	function allTopic(){
		$type = isset($_GET['type']) && in_array($_GET['type'],array('post','reply','collect')) ? $_GET['type'] : '';


		if($type == 'post'){ //发表的话题
			$value = D('Post')->field('tid')->order('ctime DESC')->where('istopic=1 AND is_del=0 AND uid='.$this->mid)->findPage();
			$this->setTitle($this->siteTitle['newTopic_my_post']);
		}elseif($type == 'reply'){ //回复话题
			$value = D('Post')->field('distinct(tid) as tid')->order('ctime DESC')->where('istopic=0 AND is_del=0 AND uid='.$this->mid)->findPage();
			$this->setTitle($this->siteTitle['newTopic_my_reply']);
		}elseif($type == 'collect'){
			$value = D('Collect')->order('addtime DESC')->field('tid')->where('is_del=0')->findPage();
			$this->setTitle($this->siteTitle['newTopic_my_collect']);
		}
		else{  //所有话题
			$value = D('Topic')->order('isrecom DESC,replytime DESC')->field('id as tid')->where('is_del=0')->findPage();
			$this->setTitle($this->siteTitle['newTopic_all']);
		}


		$this->assign('value',$value);
		$this->assign('type',$type);
		$this->display();
	}


	//首页发布话题
	function issue(){
		//获取我所有的群组

		if($myAllGroup){
			$this->assign('myAllGroup',$myAllGroup);
			$this->setTitle($this->siteTitle['issue_topic']);
			$this->display();
		}else{  //如果没有群组，则跳转到创建页面
			$url = __APP__."/Index/add";
			$this->assign('jumpUrl',$url);
            $this->assign('isAdmin',1);
			$this->error('你还没有创建群组，请你先创建群组！');
		}
	}



	//最新动态
	function myjoinfeed() {
		$myJoinFeed = $this->group->getMyJoinGroup($this->mid);

		$this->assign('myJoinFeed',$myJoinFeed);
		$this->display();
	}


	// 好友群组
	function flist() {
		$group = $page = '';
		$groupdata = $this->group->friendjoingroup($this->mid);
		if($groupdata) {
			list($group,$page) = $groupdata;
		}

		$this->assign('group',$group);
		$this->assign('page',$page);
		$this->setTitle($this->siteTitle['my_friend_group']);

		$this->display();
	}

	// 搜索群组
	function search() {
		$search_key = $this->_getSearchKey();

		$db_prefix  = C('DB_PREFIX');
		if ($search_key) {
			$tag_id = M('tag')->getField('tag_id', "tag_name='{$search_key}'");
			$map = "g.is_del=0 AND (g.name LIKE '%{$search_key}%' OR g.intro LIKE '%{$search_key}%'";
			if ($tag_id) {
				$map .= ' OR t.tag_id=' . $tag_id;
				$tag_id_score = "+IF(t.tag_id={$tag_id},2,0)";
			}
			$map .= ')';
			$group_count = $this->group->field('COUNT(DISTINCT(g.id)) AS count')
	    							   ->table("{$db_prefix}group AS g LEFT JOIN {$db_prefix}group_tag AS t ON g.id=t.gid")
	    							   ->where($map)
	    							   ->find();
			$group_list = $this->group->field('DISTINCT(g.id),g.name,g.intro,g.logo,g.cid0,g.cid1,g.membercount,g.ctime')
	    							  ->table("{$db_prefix}group AS g LEFT JOIN {$db_prefix}group_tag AS t ON g.id=t.gid")
	    							  ->where($map)
	    							  ->order("IF(LOCATE('{$search_key}',g.name),4,0)+IF(LOCATE('{$search_key}',g.intro),1,0){$tag_id_score} DESC")
	    							  ->findPage(20, $group_count['count']);
		} else if(intval($_GET['cid']) > 0) {
			// 当前分类
			$current_category = D('Category')->field('id,title,pid')->where('id=' . intval($_GET['cid']))->find();
			$this->assign('current_cid', $current_category['id']);
			$map = 'is_del=0';
			// 判断是否未最小分类
			$isMinCate = D('Category')->where('pid='.intval($_GET['cid']))->count();
			if($isMinCate == 0 && $current_category['pid'] > 0) {
				$map .= ' AND cid1=' . $current_category['id'];
				$top_cid = $current_category['pid'];
				// 当前顶级分类
				$topCateInfo = D('Category')->field('id,title')->where("id={$current_category['pid']}")->find();
				$this->assign('top_category', $topCateInfo);
				// 面包屑
				$this->assign('top_path', D('Category')->getPathWithCateId($topCateInfo['id']));
			} else {
				// 获取所有分类下的所有cid
				$allPid = D('Category')->getAllCateIdWithPid($current_category['id']);
				array_push($allPid, $current_category['id']);
				if(!empty($allPid)) {
					$map .= ' AND cid1 IN ('.implode(',', $allPid).')';
				}
				$map .= ' AND cid0=' . $current_category['id'];
				$top_cid = $current_category['id'];
				// 当前顶级分类
				$this->assign('top_category', $current_category);
				// 面包屑
				$this->assign('top_path', D('Category')->getPathWithCateId($current_category['id']));
			}
			// 当前顶级分类的子分类列表
			$son_categorys = D('Category')->field('id,title')->where("pid='{$top_cid}'")->findAll();
			$this->assign('son_categorys', $son_categorys);

			$group_list = $this->group->field('id,name,intro,logo,cid0,cid1,membercount,ctime')
	    							  ->where($map)
	    							  ->findPage();
		}

		foreach ($group_list['data'] as $v) {
			$_cids[] = $v['cid0'];
			$_cids[] = $v['cid1'];
			$_gids[] = $v['id'];
		}
		D('GroupTag')->setGroupTagObjectCache($_gids);

		foreach ($group_list['data'] as &$group) {
            // 群分类
            $group['cname0'] = D('Category')->getField('title', array('id'=>$group['cid0']));
            $group['cname1'] = D('Category')->getField('title', array('id'=>$group['cid1']));
            // 群标签
            $_tags = array();
            $tags  = D('GroupTag')->getGroupTagList($group['id']);
            foreach ($tags as $tag) {
            	$href = U('group/Index/search', array('k'=>urlencode($tag['tag_name'])));
            	$_tags[] = ($tag['tag_name'] == $search_key)?"<a href=\"{$href}\" class=\"cRed\">{$tag['tag_name']}</a>":"<a href=\"{$href}\">{$tag['tag_name']}</a>";
            }
            $group['tags']   = implode('<span class="cGray2"> | </span> ', $_tags);

			if ($search_key) {
				$group['name']	 = preg_replace("/{$search_key}/i", "<span class=\"cRed\">\\0</span>", $group['name']);
				$group['intro']	 = preg_replace("/{$search_key}/i", "<span class=\"cRed\">\\0</span>", $group['intro']);
			}
		}

		$this->assign('group_list', $group_list);
        $this->setTitle("群组搜索");
    	$this->display();
    }


    public function view(){

        $map['id'] = t($_GET['gid']);
        $map['is_del'] = 0;
        $map['status'] = 1;

        $group = M('group')->where($map)->field('id,name,intro,logo,cid0')->find();
        $level =  M('group_member') ->where(array('gid'=>$map['id'],'uid'=>$this->mid))->getField('level');

        $membercount = M('group_member') ->where('gid='.$group['id'] .' and level != 0')->count();
        if(!$membercount)
        {
            $membercount = 0;
        }
        $grouporder ='top desc ,dist desc';
        if($_GET['type'] !=1) {
            $groupmap['gid'] =  $group['id'];
            $groupmap['is_del'] = 0;

            $topic = M('group_topic')->where($groupmap)->field('id,uid,title,dist,top,`lock`,intro,replycount,addtime')->order($grouporder)->findpage(10);
        }


        if($_GET['type'] == 1)
        {
            $groupmap['gid'] =  $group['id'];
            $groupmap['is_del'] = 0;
            $groupmap['dist'] = 1;
            $topic = M('group_topic')->where($groupmap)-> field('id,uid,title,dist,top,`lock`,intro,replycount,addtime')->order($grouporder) -> findpage(10);
        }

        foreach ($topic['data'] as $key =>$val)
        {
            $topicmap['row_id'] = $val['id'];
            $topicmap['table']  ='group_topic';
            $topic['data'][$key]['replycount'] = M('comment') ->where($topicmap) -> count();
        }

        $maps['gid'] = $group['id'];
        $maps['is_del'] = 0;
        $topiccount = M('group_topic')->where($maps) ->count();

        foreach($topic['data'] as $key=>$val)
        {
            $topic['data'][$key]['addtime'] = date('m-d H:i',$val['addtime']);
            $topic['data'][$key]['uname'] = M('user') ->where('uid ='.$val['uid'])->getField('uname');
            $commentmap['table'] = 'group_topic';
            $commentmap['row_id'] = $val['id'];
            $count = M('comment') -> where($commentmap) ->count();
            $topic['data'][$key]['commentcount'] = $count;
        }

        //精华帖子
        $dist_topic = M('group_topic')->where(array('gid'=>$group['id'],'is_del'=>0,'dist'=>1))->field('id,uid,title,dist,top,intro,replycount,addtime')->order($grouporder)->findpage(10);
        foreach($dist_topic['data'] as $key=>$val)
        {
            $dist_topic['data'][$key]['addtime'] = date('m-d H:i',$val['addtime']);
            $dist_topic['data'][$key]['uname'] = M('user') ->where('uid ='.$val['uid'])->getField('uname');
            $commentmap['table'] = 'group_topic';
            $commentmap['row_id'] = $val['id'];
            $count = M('comment') -> where($commentmap) ->count();
            $dist_topic['data'][$key]['replycount'] = $count;
        }

        //热门帖子
        $hot['is_del'] = 0;
        $hottopic = M('group_topic')-> where($hot) ->order('replycount desc,viewcount desc,replytime desc') -> field('id,gid,title') ->limit(10)->select();
        foreach ($hottopic['data'] as $key =>$val)
        {
            $hotmap['row_id'] = $val['id'];
            $hotmap['table']  ='group_topic';
            $hottopic['data'][$key]['replycount'] = M('comment') ->where($hotmap) -> count();
        }

        $membermap['gid'] =  $map['id'];
        $membermap['uid'] =  $this ->mid;
        $memberid =  M('group_member')->where($membermap)->getField('id');
        if($memberid)
        {
            $this ->assign('memberid',$memberid);
        }

        $this ->assign('gid',$group['id']);
        $this ->assign('group',$group);
        $this ->assign('level',$level);
        $this ->assign('membercount',$membercount);
        $this ->assign('hottopic',$hottopic);
        $this ->assign('topiccount',$topiccount);

        $this ->assign('topic',$topic);
        $this ->assign('dist_topic',$dist_topic);
        $this->display();
    }


    public function detail(){

        $map['id'] = t($_GET['id']);
        $topic = M('group_topic')->where('id =' . $map['id'])->field('id,uid,gid,title,intro,replycount,addtime')->find();
        $topic['uname'] = M('user') ->where('uid ='.$topic['uid'])->getField('uname');
        $topic['addtime'] = date('Y-m-d H:i',$topic['addtime']);

        $gid = M('group_topic')->where('id =' . $map['id']) ->getField('gid');

         $downmap['id'] = array ('LT',$map['id']);
         $downmap['is_del'] = 0;
         $downmap['gid'] = $gid;
         $upmap['id'] = array ('GT',$map['id']);
         $upmap['gid'] = $gid;
         $upmap['is_del'] = 0;

         $authmap['uid'] = $topic['uid'];
         $authmap['is_del'] = 0;
        $authtopic = M('group_topic')->where($authmap)->field('id,uid,title')->order('addtime desc')->limit(10)->select();


        $down = M('group_topic') ->where($downmap)-> find();
        $up = M('group_topic') ->where($upmap)-> find();

        $newmap['is_del'] =0;
        $newtopic = M('group_topic')->where($newmap)->field('id,title')->order('addtime desc')->limit(10)->select();


        $this->assign('down',$down);
        $this->assign('up',$up);
        $this->assign('newtopic',$newtopic);
        $this->assign('authtopic',$authtopic);

        $where=array(
            'app'=>'public',
            'table'=>'group_topic',
            'is_del'=>0,
            'row_id'=>$topic['id'],
            'to_uid'=>0
        );
        $comment_data = M('comment')->where($where)->order("ctime asc")->findPage(20);
        foreach($comment_data['data'] as $key=>$val){
            unset($where['to_uid']);
            $where['to_comment_id'] = ['gt',0];
            $comment_data['data'][$key][child_comment] = M('comment')->where($where)->order("ctime asc")->count() ? : 0;
        }
        $this->assign("cmlist",$comment_data);//渲染评论列表

        $this ->assign('topic',$topic);
        $this->display();
    }


    // 加入该群
    public function  joinGroup()
    {
        if($_GET['gid']){
            $this->gid = intval($_GET['gid']);
        }else{
            $this->gid = intval($_POST['gid']);
        }
        $groupinfo =  D('Group') ->where('id = '.$this->gid)->find();
        $this->is_invited = M('group_invite_verify')->where("gid={$this->gid} AND uid={$this->mid} AND is_used=0")->getField('invite_id');

        if (isset($_POST['addsubmit'])) {
            $level = 0;
            $incMemberCount = false;
            if ($this->is_invited) {
                M('group_invite_verify')->where("gid={$this->gid} AND uid={$this->mid} AND is_used=0")->save(array('is_used'=>1));
                if (0 === intval($_POST['accept'])) {
                    // 拒绝邀请
                    exit;
                } else {
                    // 接受邀请加入
                    $level = 3;
                    $incMemberCount = ture;
                }
            } else if ($groupinfo['need_invite'] == 0) {
                // 直接加入
                $level = 3;
                $incMemberCount = ture;
            } else if ($groupinfo['need_invite'] == 1) {
                // 需要审批，发送私信到管理员
                $level = 0;
                $incMemberCount = false;
                // 添加通知
                $toUserIds = D('Member')->field('uid')->where('gid='.$this->gid.' AND (level=1 or level=2)')->findAll();
                foreach ($toUserIds as $k=>$v) {
                    $toUserIds[$k] = $v['uid'];
                }

                $message_data['title']   = "申请加入群组 {$groupinfo['name']}";
                $message_data['content'] = "你好，请求你批准加入“{$groupinfo['name']}” 群组，点此"
                    ."<a href='".U('group/Manage/membermanage', array('gid'=>$this->gid,'type'=>'apply'))."' target='_blank'>"
                    . U('group/Manage/membermanage', array('gid'=>$this->gid,'type'=>'apply')) . '</a>进行操作。';
                $message_data['to']      = $toUserIds;
                $res = model('Message')->postMessage($message_data,  $this->mid);

            }

            $result = D('Group')->joinGroup($this->mid, $_POST['gid'], $level, $incMemberCount, $_POST['reason']);   //加入
            S('Cache_MyGroup_'.$this->mid,null);
            exit;
        }

        parent::base();

        $this ->assign('groupinfo',$groupinfo);
        $this ->assign('gid',$this->gid);
        $this ->assign('mid',$this -> mid);
        $this->assign('joinCount', D('Member')->where("uid={$this->mid} AND level>1")->count());
        $member_info = D('Member')->field('level')->where("gid={$this->gid} AND uid={$this->mid}")->find();
        $this->assign('isjoin', $member_info['level']);  // 是否加入过或加入情况
        $this->display();
    }


    // 加入该群
    public function  joinGroups()
    {
        $this->gid = intval($_POST['gid']);
        $this->groupinfo =  D('Group') ->where('id = '.$this->gid)->find();
        $this->is_invited = M('group_invite_verify')->where("gid={$this->gid} AND uid={$this->mid} AND is_used=0")->getField('invite_id');

        if (isset($_POST['addsubmit'])) {

            $level = 0;
            $incMemberCount = false;
            if ($this->is_invited) {
                M('group_invite_verify')->where("gid={$this->gid} AND uid={$this->mid} AND is_used=0")->save(array('is_used'=>1));
                if (0 === intval($_POST['accept'])) {
                    // 拒绝邀请
                    exit;
                } else {
                    // 接受邀请加入
                    $level = 3;
                    $incMemberCount = ture;
                }
            } else if ($this->groupinfo['need_invite'] == 0) {
                // 直接加入
                $level = 3;
                $incMemberCount = ture;
            } else if ($this->groupinfo['need_invite'] == 1) {
                // 需要审批，发送私信到管理员
                $level = 0;
                $incMemberCount = false;
                // 添加通知
                $toUserIds = D('Member')->field('uid')->where('gid='.$this->gid.' AND (level=1 or level=2)')->findAll();
                foreach ($toUserIds as $k=>$v) {
                    $toUserIds[$k] = $v['uid'];
                }

                $message_data['title']   = "申请加入群组 {$this->groupinfo['name']}";
                $message_data['content'] = "你好，请求你批准加入“{$this->groupinfo['name']}” 群组，点此"
                    ."<a href='".U('group/Manage/membermanage', array('gid'=>$this->gid,'type'=>'apply'))."' target='_blank'>"
                    . U('group/Manage/membermanage', array('gid'=>$this->gid,'type'=>'apply')) . '</a>进行操作。';
                $message_data['to']      = $toUserIds;
                $res = model('Message')->postMessage($message_data,  $this->mid);

            }

            $result = D('Group')->joinGroup($this->mid, $_POST['gid'], $level, $incMemberCount, $_POST['reason']);   //加入

            if($result){
                echo 1;  exit;
            }else{
                echo 0;  exit;
            }

            S('Cache_MyGroup_'.$this->mid,null);
            //   $this ->assign('groupname',$groupname);
            exit;
        }

        parent::base();

        $groupname =  M('Group') ->where('id = '.$_GET['gid'])->getField('name');
        $this ->assign('groupname',$groupname);
        $this ->assign('gid',$_GET['gid']);
        $this->assign('joinCount', D('Member')->where("uid={$this->mid} AND level>1")->count());
        $member_info = D('Member')->field('level')->where("gid={$this->gid} AND uid={$this->mid}")->find();
        $this->assign('isjoin', $member_info['level']);  // 是否加入过或加入情况
    }

    /*小组评论*/
    public function dogtopicComment(){
        $topicid=intval($_POST['topicid']);//获取问答id
        $cont=$_POST['content'];//获取评论内容
        if(empty($topicid)||empty($cont)){
            echo "评论失败，请重试！";
            exit;
        }
        $data=array(
            'uid'=>$this->mid,
            'row_id'=>$topicid,
            'content'=>filter_keyword($cont),
            'app'=>'public',
            'table'=>'group_topic',
            'ctime'=>time()
        );
        $res=M('comment')->add($data);
        if($res){
            //评论成功
            //积分操作
            $credit = M('credit_setting')->where(array('id'=>27,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $ttype = 6;
                $note = '小组回复帖子获得的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$ttype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
            //查询应用的作者
            $info= M('group_topic')->where(array('id'=>$topicid))->find();
            //添加消息记录
            model('Message')->doCommentmsg($this->mid,$info['uid'],$topicid,$info['uid'],'group',$res,0,limitNumber($info['intro'],500),$cont);
            echo 200;
            exit;
        }else{
            echo "评论失败，请重试！";
            exit;
        }
    }



    /**
     * 添加子回复
	 *
     */
    public function doSonComment(){
        $id=intval($_POST['id']);//获取父级评论id
        $count=t($_POST['txt']);//获取回复内容
        $cid=intval($_POST['cid']);//获取问答id
        if(strlen($count)<3){
            echo "对不起，回复内容最少为3个字符";
            exit;
        }
        $uid = M('comment')->where('comment_id='.$id)->getField('uid');
        $map=array(
            'row_id'=>$cid,
            'to_comment_id'=>$id,
            'to_uid'=>$uid,
            'content'=>filter_keyword($count),
            'ctime'=>time(),
            'uid'=>$this->mid,
            'app'=>'public',
            'table'=>'group_topic',
        );
        $res=M('comment')->add($map);
        if($res){
            //查询应用的作者
            $info= M('group_topic')->where(array('id'=>$id))->find();
            //添加消息记录
            model('Message')->doCommentmsg($this->mid,$info['uid'],$topicid,$info['uid'],'group',$res,0,limitNumber($info['intro'],500),$cont);

            echo 200;
            exit;
        }else{
            echo "对不起，回复失败，请重试！";
            exit;
        }

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
        $data= M('comment')->where($map)->order("ctime DESC")->findPage($limit);
        //循环取时间差
        foreach($data['data'] as &$val){
            $val['ctime']=getDateDiffer($val['ctime']);
        }
        $this->assign("data",$data['data']);
        $this->assign("pid",$id);
        $data['data']=$this->fetch("comm_list");
        echo json_encode($data);exit;
    }

    /**
     * 第三级回复
     */
    public function doSonComms(){
        $id=intval($_POST['id']);//获取父级评论id
        $count=t($_POST['txt']);//获取回复内容
        $wid=intval($_POST['wid']);//获取问答id
        $fid=intval($_POST['uid']);//获取被回复人uid
        $sid=intval($_POST['sid']);
        if(strlen($count)<3){
            echo "对不起，回复内容最少为3个字符";
            exit;
        }
        if(strlen($count)>140){
            echo "对不起，内容最多70个字符！";
            exit;
        }
        $map=array(
            'parent_id'=>$id,
            'wid'=>$wid,
            'description'=>$count,
            'ctime'=>time(),
            'uid'=>$this->mid,
            'fid'=>$fid
        );
        $res=$this->wenda_comment->add($map);
        if($res){
            //设置问答评论数量+1
            $this->wenda->addCommentCount($wid);
            //设置子评论数量+1
            $this->wenda_comment->addCommentCount($id);
            //查询应用的作者
            $wuid=$this->wenda->where(array('id'=>$wid))->getField('uid');
            //查询评论内容
            $cominfo=$this->wenda_comment->where(array('id'=>$sid))->find();
            //添加消息记录

            model('Message')->doCommentmsg($this->mid,$fid,$wid,$wuid,'wenda',$res,$id,limitNumber($cominfo['description'],500),$count);

            echo 200;
            exit;
        }else{
            echo "对不起，回复失败，请重试！";
            exit;
        }

    }

    /**
     * 删除三级评论
     */
    function delCommComment(){
        $id=$_POST["id"];
        $data["is_del"]=1;
        $data["ctime"]=time();
        $res= M('comment')->where("comment_id=".$id)->save($data);
        if($res){
            echo 200;
            exit;
        }else{
            echo "对不起，删除失败，请重试！";
            exit;
        }
    }
}