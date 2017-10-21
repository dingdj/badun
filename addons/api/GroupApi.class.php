<?php
/**
 * 小组Api接口
 */

class GroupApi extends Api{
    protected $group = '';//小组模型

    /**
     * 初始化模型
     */
    public function _initialize() {}
    /**
     * @name 获取小组分类
     */
    public function getGroupCate(){
        $list = M('group_category')->field('id,title')->findAll();
        $list ? $this->exitJson($list,1) : $this->exitJson([],0,'暂时没有小组分类');
    }
    /**
     * @name 小组列表
     * @access public
     */
    public function getList(){
        $map['status'] = 1;
		$map['is_del'] = 0;
        if($this->cate_id){
            $cate['id'] = array('in',array_unique(array_filter(explode(',',$this->cate_id))));
            $list = M('group_category')->where($cate)->field('id,title')->findAll();
        }else{
            $list = M('group_category')->field('id,title')->findAll();
        }
		foreach ( $list as &$val ){
			$map['cid0'] = $val['id'];
            if($this->page == 0){
                $val['group_list'] = D('Group')->where($map)->order('ctime desc')->limit($this->count)->findAll();
            }else{
                $group_list = D('Group')->where($map)->order('ctime desc')->findPage($this->count);
                $val['group_list'] = ($group_list['gtLastPage'] !== true) ? $group_list['data'] : [];
            }
			
			foreach( $val['group_list'] as &$v){
				$v['threadcount'] = M('group_topic')->where('gid='.$v['id'].' and is_del=0')->count();
                $v['membercount'] = M('group_member')-> where('gid='.$v['id'] .' and level != 0')->count();
                //是否已经加入
				$is_join = D('Member','group')->where("uid={$this->mid} AND gid={$v['id']} AND level > 0")->count() > 0;
				$v['is_join'] = $is_join ? 1 : 0;
                //是否为管理员
                $is_admin = D('Member','group')->where("uid={$this->mid} AND gid={$v['id']} AND level > 0 AND level <= 2")->count() > 0;
                $v['is_admin'] = $is_admin ? 1 : 0;
                $v['logourl'] = $v['logo'] ? getImageUrl($v['logo']) : THEME_PUBLIC_URL.'/images/default-cover.png';
			}
		}
        $list ? $this->exitJson($list,1) : $this->exitJson((object)[],0,'暂时没有小组');
    }
    /**
     * @name 获取小组话题列表
     */
    public function getGroupTopList(){
        if($this->group_id){
            $map['gid'] = $this->group_id;
            $map['is_del'] = 0;
            $this->keyword && $map['title'] = array('like','%'.h($this->keyword).'%');
            isset($this->data['dist']) && $map['dist'] = 1;
            $data = D('group_topic')->where($map)->order('top desc,dist desc,replytime desc')->findPage($this->count);
            if($data['gtLastPage'] !== true){
                //是否为管理员
                $is_admin = D('Member','group')->where("uid={$this->mid} AND gid={$this->group_id} AND level > 0 AND level <= 2")->count() > 0;
                
                $collect = D('Collect','group');;
                foreach($data['data'] as $k=>$val){
                    $data['data'][$k]['is_collect'] = $collect->isCollect($val['id'],$this->mid) ? 1 : 0;
                    $attach = unserialize($val['attach']);
                    //getAttachUrlByAttachId
                    //获取附件
                    //$attach = D('Dir','group')->where(array('id'=>array('in',$attach)))->field('attachId')->select();
                    //$attach = getSubByKey($attach,'attachId');
                    $data['data'][$k]['is_admin'] = $is_admin ? 1 : 0;
                    $data['data'][$k]['attach'] = $attach ? array_map(create_function('$attach_id','return getCover($attach_id,150,150) ?:"";'),$attach) : [];
                    $data['data'][$k]['content'] = D('Post','group')->where(array('tid'=>$val['id'],'gid'=>$val['gid']))->getField('content');
                }
                $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson((object)[],1,'暂时没有话题啦');
            }
        }
        $this->exitJson((object)[],0,'未能获取列表');
    }
    
    /**
     * @name 申请小组
     */
    public function createGroup(){
        //获取小组配置
        $this->config = model('Xdata')->lget('group');
        $this->group = D('Group','group');
        if (1 != $this->config['createGroup']) {
            $this->assign('isAdmin',1);
			// 系统后台配置关闭创建
			$this->exitJson((object)[],0,'小组创建已关闭');
		} else if ($this->config['createMaxGroup'] <= $this->group->where('is_del=0 AND uid='.$this->mid)->count()) {
			//系统后台配置要求，如果超过，则不可以创建
            $this->exitJson((object)[],0,'你不可以再创建了，超过系统规定数目');
		}
        //验证通过，开始创建
        //小组名称
        $group['name']  = filter_keyword(h(t($this->name)));
        if (!$group['name']) {
            $this->exitJson((object)[],0,'小组名称不能为空');
		}elseif (get_str_length($group['name']) > 30) {
            $this->exitJson((object)[],0,'小组名称不能超过30个字');
		}elseif ($this->group->where(array('name'=>$group['name']))->count() > 0) {
             $this->exitJson((object)[],0,'小组名称已被占用');
		}
        //小组简介
        $group['intro'] = filter_keyword(h(t($this->intro)));
        if (get_str_length($group['intro']) > 200) {
            $this->exitJson((object)[],0,'小组简介请不要超过200个字');
		}
        
        //添加创建人UID
		$group['uid']   = $this->mid;
        //小组分类
		$group['cid0']  = intval($this->cate_id);
        if (!$group['cid0']) {
            $this->exitJson((object)[],0,'请选择小组分类');
		}
		//$cid1 = D('Category','group')->_digCateNew($this->data);
		//intval($cid1) > 0 && $group['cid1'] = intval($cid1);
        $group['cid1'] = intval($this->cate_id);
        $group['type']  = $this->type == 'open'?'open':'close';
        $group['need_invite']  = intval($this->config[$group['type'] . '_invite']);  //是否需要邀请
		$group['brower_level'] = $this->type == 'open'?'-1':'1'; //浏览权限
        $group['openWeibo'] = intval($this->config['openWeibo']);
		$group['openUploadFile'] = intval($this->config['openUploadFile']);
		$group['openBlog'] = intval($this->config['openBlog']);
		$group['whoUploadFile'] = intval($this->config['whoUploadFile']);
		$group['whoDownloadFile'] = intval($this->config['whoDownloadFile']);
		$group['openAlbum'] = intval($this->config['openAlbum']);
		$group['whoCreateAlbum'] = intval($this->config['whoCreateAlbum']);
		$group['whoUploadPic'] = intval($this->config['whoUploadPic']);
		$group['anno'] = intval($this->anno);
		$group['ctime'] = time();
		$group['announce'] = h(t($this->announce));
		if (1 == $this->config['createAudit']) {
			$group['status'] = 0;
		}
        // 群组LOGO
        if($this->group_logo){
            $attach = model('Attach')->getAttachById($this->group_logo);
            $group['logo'] = $attach['save_path'].$attach['save_name'];
        }else{
            $group['logo'] = 'default.png';
        }
	    //添加小组
        $gid = $this->group->add($group);

		if($gid) {
			// 积分操作
			//X('Credit')->setUserCredit($this->mid,'add_group');

			// 把自己添加到成员里面
			$this->group->joingroup($this->mid, $gid, 1, true);

			// 添加群组标签
			//D('GroupTag','group')->setGroupTag($_POST['tags'], $gid);
			S('Cache_MyGroup_'.$this->mid,null);

			if (1 == $this->config['createAudit']) {
			     $this->exitJson(['group_id'=>(int)$gid],1,'创建成功，请等待审核');
			} else {
                // 积分操作
                model('Credit')->getCreditInfo($this->mid,23);
				$this->exitJson(['group_id'=>(int)$gid],1,'创建成功');
			}
		} else {
			$this->exitJson((object)[],0,'创建失败');
		}
    }
    /**
     * @name 获取小组信息
     */
    public function getGroupInfo(){
        if(!isset($this->data['group_id']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:group_id');
        }
        $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除');
        }
        $groupinfo['openWeibo']      = model('Xdata')->get('group:weibo') ? $groupinfo['openWeibo'] : 0;
        $groupinfo['openBlog']       = model('Xdata')->get('group:discussion') ? $groupinfo['openBlog'] : 0;
        //$groupinfo['openUploadFile'] = model('Xdata')->get('group:uploadFile') ? $groupinfo['openUploadFile'] : 0;
        if (0 == $groupinfo['status'] && !in_array(ACTION_NAME,array('delGroupDialog', 'delGroup'))) {
	 		$this->exitJson((object)[],0,"该小组正在审核中");
	 	}
        $groupinfo['cname0'] 	= D('Category','group')->getField('title', array('id'=>$groupinfo['cid0']));
        $groupinfo['cname1'] 	= D('Category','group')->getField('title', array('id'=>$groupinfo['cid1']));
        $groupinfo['type_name'] = $groupinfo['brower_level'] == '-1' ? '公开':'私密';
        $groupinfo['openUploadFile'] = (model('Xdata')->get('group:uploadFile')) ? $groupinfo['openUploadFile'] : 0 ;
        $groupinfo['path'] = D('Category','group')->getPathWithCateId($groupinfo['cid1']);
        if ( !$groupinfo['path'] ){
        	 $groupinfo['path'] = D('Category','group')->getPathWithCateId($groupinfo['cid0']);
        }
        $groupinfo['path'] = implode(' - ', $groupinfo['path']);
        $groupinfo['logourl'] = logo_path_to_url( $groupinfo['logo'] );
        //新增  获取群组发言总数（群聊）
        $groupinfo['group_weibo_count'] = (int)D('Group','group')->getGroupWeibo($this->group_id);
		$is_join = D('Member','group')->where("uid={$this->mid} AND gid={$this->group_id}")->count() > 0;
		$groupinfo['is_join'] = $is_join ? 1 : 0;
        //是否为管理员
        $is_admin = D('Member','group')->where("uid={$this->mid} AND gid={$this->group_id} AND level > 0 AND level <= 2")->count() > 0;
        $groupinfo['is_admin'] = $is_admin ? 1 : 0;
        $groupinfo['threadcount'] = M('group_topic')->where('gid='.$groupinfo['id'].' and is_del=0')->count();
        $groupinfo['membercount'] = M('group_member')-> where('gid='.$groupinfo['id'] .' and level != 0')->count();
        $this->exitJson($groupinfo,1);
    }
    /**
     * @name 编辑小组
     */
    public function editGroup(){
        if(!isset($this->data['group_id']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:group_id');
        }
        $this->group = D('Group','group');
        $groupinfo = $this->group->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除');
        }
        //权限检测
        $this->checkGroupAuth($groupinfo);
        if(!iscreater($this->mid,$this->group_id) || !$this->isadmin) {
            $this->exitJson((object)[],0,'你没有权限操作');;
        }
        //修改的数据信息
        $group = array();
        //小组名称
        if(isset($this->data['name'])){
            $group['name']  = h(t($this->name));
            if (get_str_length($group['name']) > 30) {
                $this->exitJson((object)[],0,'小组名称不能超过30个字');
    		}elseif ($this->group->where(array('name'=>$group['name'],'id'=>['neq',$this->group_id]))->count() > 0) {
                 $this->exitJson((object)[],0,'小组名称已被占用');
    		}
        }
        //小组简介
        if(isset($this->data['intro']) && $this->intro){
            $group['intro'] = h(t($this->intro));
            if (get_str_length($group['intro']) > 200) {
                $this->exitJson((object)[],0,'小组简介请不要超过200个字');
    		}
        }

        //小组分类
        if(is_numeric($this->cate_id) && $this->cate_id){
            $group['cid0']  = intval($this->cate_id);
            $group['cid1']  = intval($this->cate_id);
        }
        //小组公告
        if(isset($this->data['announce']) && $this->announce){
            $group['announce']  = h(t($this->announce));
        }
        // 群组LOGO
        if(isset($this->data['group_logo']) && $this->group_logo){
            $attach = model('Attach')->getAttachById($this->group_logo);
            $group['logo'] = $attach['save_path'].$attach['save_name'];
        }
        //如果没有接收到修改的数据信息,则不做任何修改
        if(empty($group)){
            $this->exitJson((object)[],0,'未做任何修改');
        }
        $res = $this->group->where('id=' . $this->group_id)->save($group);

		if ($res !== false) {
			D('Log','group')->writeLog($this->group_id, $this->mid, '修改小组基本信息', 'setting');
            $this->exitJson(['group_id'=>$this->group_id],1,'修改成功');
		}
        $this->exitJson(['group_id'=>$this->group_id],1,'修改失败，请重试');
    }
    /**
     * @name 删除/解散小组
     */
    public function deleteGroup(){
        if(!isset($this->data['group_id']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:group_id');
        }
        $this->group = D('Group','group');
        $groupinfo = $this->group->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除');
        }
        //权限检测
        $this->checkGroupAuth($groupinfo);
        if(!iscreater($this->mid,$this->group_id) || !$this->isadmin){
            $this->exitJson((object)[],0,'你没有权限操作');
        }
        if(D('Group','group')->remove($this->group_id)){
            S('Cache_MyGroup_'.$this->mid, NULL);
            $this->exitJson(['group_id'=>(int)$this->group_id],1,'解散成功');
        }
        $this->exitJson((object)[],0,'解散失败,请稍后再试');
    }
    /**
     * @name 申请加入小组
     */
    public function joinGroup(){
        if(!isset($this->data['group_id']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:group_id');
        }
        $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除');
        }
        if(D('Member','group')->where("uid={$this->mid} AND gid={$this->group_id}")->count() > 0) {
            $this->exitJson((object)[],0,'你已经加入该小组');
        }
        //权限检测
        $this->checkGroupAuth($groupinfo);
        $level = 0;
        $incMemberCount = false;
        if ($this->is_invited) {
            M('group_invite_verify')->where("gid={$this->group_id} AND uid={$this->mid} AND is_used=0")->save(array('is_used'=>1));
            if (0 === intval($this->accept)) {
                // 拒绝邀请
                $this->exitJson((object)[],0,'已拒绝邀请');
            } else {
                // 接受邀请加入
                $level = 3;
                $incMemberCount = true;
            }
        } else if ($groupinfo['need_invite'] == 0) {
            // 直接加入
            $level = 3;
            $incMemberCount = true;
        } else if ($groupinfo['need_invite'] == 1) {
            // 需要审批，发送私信到管理员
            $level = 0;
            $incMemberCount = false;
            // 添加通知
            $toUserIds = D('Member','group')->field('uid')->where('gid='.$this->group_id.' AND (level=1 or level=2)')->findAll();
            foreach ($toUserIds as $k=>$v) {
                $toUserIds[$k] = $v['uid'];
            }

            $message_data['title']   = "申请加入群组 {$groupinfo['name']}";
            $message_data['content'] = "你好，请求你批准加入“{$groupinfo['name']}” 群组，点此"
                                     ."<a href='".U('group/Manage/membermanage', array('gid'=>$this->group_id,'type'=>'apply'))."' target='_blank'>"
                                     . U('group/Manage/membermanage', array('gid'=>$this->group_id,'type'=>'apply')) . '</a>进行操作。';
            $message_data['to']      = $toUserIds;
            $res = model('Message')->postMessage($message_data,  $this->mid);

        }
        //拼接数据
		$member['uid'] = $this->mid;
		$member['gid'] = $this->group_id;
		$member['name'] = getUserName($this->mid);
		$member['level'] = $level;
		$member['ctime'] = time();
		$member['mtime'] = time();
		$member['reason'] = $this->reason ?:'';//申请加入的理由
		$ret = D('Member','group')->add($member);
		// 不需要审批直接添加，审批就不用添加了。
        if ($incMemberCount) {
        	// 成员统计
        	D('Group','group')->setInc('membercount', 'id=' . $this->group_id);
			// 积分操作
			X('Credit')->setUserCredit($mid, 'join_group');
        }
        S('Cache_MyGroup_'.$this->mid,null);
        if($ret){
            if($groupinfo['need_invite'] == 1){
                $this->exitJson(['group_id'=>(int)$this->group_id],1,'已申请加入,请等待审核');
            }
            $this->exitJson(['group_id'=>(int)$this->group_id],1,'加入成功');
        }
        $this->exitJson((object)[],0,'申请加入失败,请重新尝试');
    }
    /**
     * @name 退出小组
     */
    public function quitGroup(){
        if(!isset($this->data['group_id']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:group_id');
        }
        $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除');
        }
        //权限检测
        $this->checkGroupAuth($groupinfo);
        if(iscreater($this->mid,$this->group_id) || !$this->ismember) {
            $this->exitJson((object)[],0,'暂时不能退出该组');;
        }
        $res = D('Member','group')->where("uid={$this->mid} AND gid={$this->group_id}")->delete();  //用户退出
        if($res){
        	$map['uid'] = $this->mid;
        	$map['gid'] = $this->group_id;
        	D('GroupUserCount','group')->where($map)->delete();
            D('Group','group')->setDec('membercount', 'id=' . $this->group_id);     //用户数量减少1
            // 积分操作
            X('Credit')->setUserCredit($this->mid, 'quit_group');
            S('Cache_MyGroup_'.$this->mid,null);
            $this->exitJson(['group_id'=>(int)$this->group_id],1,'退出成功');
        }
        $this->exitJson((object)[],0,'处理失败,请重新尝试');;
    }
    /**
     * @name 小组权限访问权限检测
     */
    protected function checkGroupAuth($groupinfo = []){
        // 判读当前用户的成员状态
      	$member_info = D('Member','group')->where("uid={$this->mid} AND gid={$this->group_id}")->find();
      	if ($member_info) {
      		if ($member_info['level'] > 0) {
        		$this->ismember = 1;
     			if ($member_info['level'] == 1 || $member_info['level'] == 2) {
     				$this->isadmin = 1;
     			}
	            // 记录访问时间
	            D('Member','group')->where('gid=' . $this->group_id." AND uid={$this->mid}")->setField('mtime',time());
      		}
      	}
        // 浏览权限
      	if (!$this->ismember) {
        	// 邀请加入
        	if (M('group_invite_verify')->where("gid={$this->group_id} AND uid={$this->mid} AND is_used=0")->count() > 0) {
        		$this->is_invited = 1;
        	}
            if ($groupinfo['brower_level'] == 1) {
        		if ( strtolower(ACTION_NAME) == 'addtopic' ){
        			$this->exitJson((object)[],0,'抱歉，您不是该小组成员');
        		}
            }
      	}
    }
    /**
     * @name 获取小组成员
     */
    public function getGroupMember(){
        if($this->group_id){
            //获取待审核成员列表,已审核成员列表
            $map['level'] = $this->type == 'apply' ? 0 : array('gt',0);
            $map['gid'] = $this->group_id;
            $memberlist = D('Member','group')->where($map)->order('level ASC')->findPage($this->count);
            if($memberlist['gtLastPage'] !== true && $memberlist['data']){
                foreach($memberlist['data'] as $k=>$value){
                    $value['is_admin'] = 0;
                    if($value['level'] ==1 || $value['level'] == 2) {
                        $value['is_admin'] = 1;
                    }
                    $memberlist['data'][$k] = model('User')->formatForApi($value, $value['uid'], $this->mid);
                }
                $this->exitJson($memberlist['data'],1);
            }
            $this->exitJson((object)[],1,'暂时没有成员信息了');
        }
        $this->exitJson((object)[],0,'未能获取列表');
        
    }
    /**
     * @name 小组成员管理
     */
    //操作：设置成管理员，降级成为普通会员，剔除会员，允许成为会员
	public function member() {
		$batch = false;
		$uidArr = strpos($this->uid,',') !== false ? explode(',', $this->uid) : (int)$this->uid;
		if(is_array($uidArr)) {
			$batch = true;
		}

		if(!isset($this->data['action']) || !in_array($this->action,array('admin','normal','out','allow'))){
            $this->exitJson((object)[],0,'暂时不支持该操作');
		}
        $this->member = D('Member','group');
		switch ($this->action)
		{
			case 'admin':  // 设置成管理员
				if (!iscreater($this->mid,$this->group_id)) {
                     // 创建者才可以进行此操作
                     $this->exitJson((object)[],0,'创建者才有的权限');
				}
				if($batch) {
					$uidStrLog = array();
					foreach($uidArr as $val) {
						$uidInfo = getUserSpace($val, 'fn', '_blank', '@' . getUserName($val));
						array_push($uidStrLog, $uidInfo);
					}
					$uidStr = implode(',', $uidStrLog);
					$content = '将用户 '.$uidStr.'提升为管理员 ';
					$res = $this->member->where('gid=' . $this->group_id . ' AND uid IN ('.$this->uid.') AND level<>1')->setField('level', 2);   //3 普通用户
				} else {
					$content = '将用户 ' . getUserSpace($this->uid, 'fn', '_blank', '@' . getUserName($this->uid)) . '提升为管理员 ';
					$res = $this->member->where('gid=' . $this->group_id . ' AND uid=' . $this->uid . ' AND level<>1')->setField('level', 2);   //3 普通用户
				}
				break;
			case 'normal':   // 降级成为普通会员
				if (!iscreater($this->mid,$this->group_id)) {
					$this->exitJson((object)[],0,'创建者才有的权限');
				}
				$content = '将用户 ' . getUserSpace($this->uid, 'fn', '_blank', '@' . getUserName($this->uid)) . '降为普通会员 ';
				$res = $this->member->where('gid=' . $this->group_id . ' AND uid=' . $this->uid . ' AND level=2')->setField('level', 3);   //3 普通用户
				break;
			case 'out':     // 剔除会员
				if (iscreater($this->mid, $this->group_id)) {
					$level = ' AND level<>1';
				} else {
					$level = ' AND level<>1 AND level<>2';
				}
				if($batch) {
					$current_level = $this->member->field('uid, level')->where('gid = '.$this->group_id.' AND uid IN ('.$_POST['uid'].')'.$level)->findAll();
					$res = $this->member->where('gid='.$this->group_id.' AND uid IN ('.$_POST['uid'].')'.$level)->delete();
					if($res) {
						$count = count($current_level);
						$uidStrLog = array();
						foreach($current_level as $value) {
							$uidInfo = getUserSpace($value['uid'], 'fn', '_blank', '@' . getUserName($value['uid']));
							array_push($uidStrLog, $uidInfo);
							if($value['level'] > 0) {
								D('Group','group')->setDec('membercount', 'id=' . $this->group_id);
								X('Credit')->setUserCredit($value['uid'], 'quit_group');
							}
						}
						$uidStr = implode(',', $uidStrLog);
						$content = '将用户 '.$uidStr. '踢出群组 ';
					}
				} else {
					$current_level = $this->member->getField('level', 'gid=' . $this->group_id . ' AND uid=' . $this->uid . $level);
					$res = $this->member->where('gid=' . $this->group_id . ' AND uid=' . $this->uid . $level)->delete();   //剔除用户
					if ($res) {
						$content = '将用户 ' . getUserSpace($this->uid, 'fn', '_blank', '@' . getUserName($this->uid)) . '踢出群组 ';
						// 被拒绝加入不扣积分
						if (intval($current_level) > 0) {
							D('Group','group')->setDec('membercount', 'id=' . $this->group_id);     //用户数量减少1
							X('Credit')->setUserCredit($this->uid, 'quit_group');
						}
					}
				}
				break;
			case 'allow':   // 批准成为会员
				$content = '将用户 ' . getUserSpace($this->uid, 'fn', '_blank', '@' . getUserName($this->uid)) . '批准成为会员 ';
				$res = $this->member->where('gid=' . $this->group_id . ' AND uid=' . $this->uid . ' AND level=0')->setField('level', 3);   //level级别由0 变成 3
				if ($res) {
					D('Group','group')->setInc('membercount', 'id=' . $this->group_id); //增加一个成员
					X('Credit')->setUserCredit($this->uid, 'join_group');
				}
				break;
		}

		if ($res) {
			D('Log','group')->writeLog($this->group_id, $this->mid, $content, 'member');
            $this->exitJson(['group_id'=>(int)$this->group_id,'uid'=>(string)$this->uid],1,'操作成功');
		}
        $this->exitJson((object)[],0,'操作失败');
		header('Location:'.$_SERVER['HTTP_REFERER']);
	}
    /**
     * @name 发布话题
     */
    public function addTopic(){
        if(!isset($this->data['group_id']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:group_id');
        }
        $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除');
        }
        //权限检测
        $this->checkGroupAuth($groupinfo);
        if(!$this->title){
            $this->exitJson((object)[],0,'标题不能为空');
        }
        $title = getShort($this->title, 30);
        $this->__checkContent($this->content, 10, 10000);
        $topic['attach'] = $this->_setTopicAttach();	// 附件信息
    	$topic['gid'] = $this->group_id;
    	$topic['uid'] = $this->mid;
    	$topic['name'] = getUserName($this->mid);
    	$topic['title'] = h(t($title));
    	$topic['cid']   = 0;
		$topic['addtime'] = time();
		$topic['replytime'] = time();
    	if($tid = M('group_topic')->add($topic)) {
    		$post['gid'] = $this->group_id;
    		$post['uid'] = $this->mid;
    		$post['tid'] = $tid;
    		$post['content'] = h($this->content);
    		$post['istopic'] = 1;
    		$post['ctime'] = time();
    		$post['ip'] = get_client_ip();
            $post['attach'] = $topic['attach'];
    		$post_id = D('Post','group')->add($post);
            M('group')->where(array('id'=>$this->group_id))->setInc("threadcount");
            //操作积分
            model('Credit')->getCreditInfo($this->mid,24);
    		// 微博
    		D('GroupFeed','group')->syncToFeed('我发布了一个小组话题“'.t($this->title).'”,详情请点击'.U('group/Topic/topic',array('tid'=>$tid,'gid'=>$this->group_id)),$this->mid,0,0,$this->group_id);
    		$this->exitJson(['group_id'=>(int)$this->group_id,'tid'=>(int)$tid],1,'发布话题成功');
        }
        $this->exitJson((object)[],0,'发布话题失败，请稍后再试');
    }
    /**
     * @name 检测内容
     */
    private function __checkContent($content, $mix = 5, $max = 5000){
		$content_length = get_str_length($content, true);
		if (0 == $content_length) {
            $this->exitJson((object)[],0,'内容不能为空');
		} else if ($content_length < $mix) {
            $this->exitJson((object)[],0,'内容不能少于' . $mix . '个字');
		} else if ($content_length > $max) {
            $this->exitJson((object)[],0,'内容不能超过' . $max . '个字');
		}
	}
    /**
     * @name 设置附件
     */
    protected function _setTopicAttach($old_attach = '',$local = false){
		// 添加附件
        $finalid = [];
		if ($this->attach) {
            $this->attach = array_unique(array_filter(explode(',',$this->attach)));
            if (count($this->attach) > 3){
                $this->exitJson((object)[],0,'附件数量不能超过3个');
            }
			
			$finalid = $this->attach;
			if($local){
				$data = model('Attach')->getAttachByIds( $this->attach );
				$dirids = array();
				foreach ( $data as $d ){
					$attchement['gid'] = $this->group_id;
					$attchement['uid'] = $this->mid;
					$attchement['attachId'] = $d['attach_id'];
					$attchement['name'] = $d['name'];
					$attchement['note'] = $d['name'];
					$attchement['filesize'] = formatsize($d['size']);
					$attchement['filetype'] = $d['extension'];
					$attchement['fileurl'] = $d['save_path'] . $d['save_name'];
					$attchement['ctime'] = time();
					$attchement['is_del'] = 0;
					$dirids[] = D('Dir','group')->add( $attchement );
				}
				
				//$dmap['id'] = array( 'in' , $dirids );
				//D('Dir','group')->setField('is_del', 0, $dmap);
				$finalid = $dirids;
			}
            
        }
        
        return $finalid ? serialize($finalid) : '';
	}
    /**
     * @name 编辑话题
     */
    public function editTopic(){
        if(!isset($this->data['tid']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:tid');
        }
        $this->topic = D('Topic','group');
        $thread = $this->topic->getThread($this->tid);
		if (!$thread){
            $this->exitJson((object)[],0,'所编辑的话题不存在');
        }
        $this->group_id = $thread['gid'];
        $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除,无法编辑话题');
        }
        //权限检测
        $this->checkGroupAuth($groupinfo);

		// 管理员或者帖子主人
		if(!$this->isadmin && $thread['uid'] != $this->mid){
            $this->exitJson((object)[],0,'你没有权限操作');
        }
        //修改的数据信息
        $info = array();
        //小组名称
        if($this->title){
            $info['title'] = getShort($this->title, 30);
        }
        //小组简介
        if($this->content){
            $this->__checkContent($this->content, 10, 10000);
            $content = h(t($this->content));
            D('Post','group')->setField('content', $content, 'tid='.$this->tid." AND istopic=1");
        }
        if($this->attach){
            $info['attach'] = $this->_setTopicAttach();	// 附件信息
        }
        if(!empty($info)){
            $info['mtime'] = time();
            if($this->topic->where('id=' . $this->tid)->save($info)){
                $this->exitJson(['group_id'=>$this->group_id,'tid'=>$this->tid],1,'编辑话题成功');
            }
        }
         $this->exitJson((object)[],0,'编辑话题失败');
        
    }
    /**
     * @name 删除话题
     */
    public function deleteTopic(){
        if(!isset($this->data['tid']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:tid');
        }
        $this->topic = D('Topic','group');
        $map['id'] = ['in',array_unique(array_filter(explode(',',$this->tid)))];
        $map['is_del'] = 0;
        $threadList = $this->topic->field('id,uid,title')->where($map)->findAll();
        if(!$threadList){
            $this->exitJson((object)[],0,'所删除的话题不存在或已被删除');
        }
        if(!isset($this->data['group_id']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:group_id');
        }
        $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除,无法操作话题');
        }
        $map['gid'] = $this->group_id;
        //权限检测
        $this->checkGroupAuth($groupinfo);
		if (strpos($this->tid, ',') && $this->isadmin) {
			$topicInfo = $this->topic->field('id,uid,title')->where($map)->findAll();
		} elseif(is_numeric($this->tid)){
			$topicInfo = $this->topic->field('id,uid,title')->where($map)->find();
			if (!$this->isadmin && $topicInfo['uid'] != $this->mid) {
                $this->exitJson((object)[],0,'你没有权限管理');
			}
        }else{
            $this->exitJson((object)[],0,'你没有权限管理');
        }
		//设置日志
		$this->_setOperationLog('删除', $topicInfo);

		$res = $this->topic->remove($this->tid);
		if ($res !== false) {
            //操作积分
            if(count($topicInfo) == count($topicInfo,1)){
                $topic[] = $topicInfo;
            }else{
                $topic = $topicInfo;
            }
            foreach($topic as &$v) {
                model('Credit')->getCreditInfo($v['uid'],45);
            }
			M('group')->where(array('id'=>$this->group_id))->setDec("threadcount");
			$this->exitJson(['group_id'=>(int)$this->group_id,'tid'=>(int)$this->tid],1,'删除成功');
		} else {
			$this->exitJson((object)[],0,'删除失败');
		}
    }
    /**
     * @name 获取我搜藏的话题
     */
    public function getCollectList(){
        $map['d.is_del'] = 0;
        $this->group_id && $map['d.gid'] = $this->group_id;
        $this->keyword && $map['d.title'] = array('like','%'.h($this->keyword).'%');
        isset($this->data['dist']) && $map['d.dist'] = 1;
        $data = M('group_topic')->where($map)->join('d INNER JOIN (SELECT * FROM `'.C('DB_PREFIX').'group_topic_collect` WHERE `mid` = '.$this->mid.' AND `is_del` = 0) AS t ON d.id = t.tid')->order('t.addtime desc')->findPage($this->count);
        if($data['gtLastPage'] !== true){
            //是否为管理员
            $is_admin = D('Member','group')->where("uid={$this->mid} AND gid={$this->group_id} AND level > 0 AND level <= 2")->count() > 0;
            $collect = D('Collect','group');;
            foreach($data['data'] as $k=>$val){
                $data['data'][$k]['is_collect'] = $collect->isCollect($val['tid'],$this->mid) ? 1 : 0;
                $attach = unserialize($val['attach']);
                $attach = D('Dir','group')->where(array('id'=>array('in',$attach)))->field('attachId')->select();
                $attach = getSubByKey($attach,'attachId');
                $data['data'][$k]['is_admin'] = $is_admin ? 1 : 0;
                $data['data'][$k]['attach'] = $attach ? array_map(create_function('$attach_id','return getCover($attach_id,150,150) ?:"";'),$attach) : [];
                $data['data'][$k]['content'] = D('Post','group')->where(array('tid'=>$val['id'],'gid'=>$val['gid']))->getField('content');
            }
        }
        $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson((object)[],1,'暂时没有搜藏的话题');
    }
    /**
     * @name 话题置顶/精华/锁定/收藏
     */
    public function operatTopic(){
        if(!isset($this->data['tid']) && isset($this->data['api_debug'])){
            $this->exitJson((object)[],0,'参数不完整:tid');
        }
        //获取话题
        $this->topic = D('Topic','group');
        $thread = $this->topic->getThread($this->tid);
        if(!$thread){
            $this->exitJson((object)[],0,'所操作的话题不存在或已被删除');
        }
        if(!isset($this->data['action']) || !in_array((int)$this->action,[1,2])){
            $this->exitJson((object)[],0,'暂不能对该话题进行此操作');
        }
        //获取话题的分组
        $this->group_id = $thread['gid'];
        $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
        if(!$groupinfo){
            $this->exitJson((object)[],0,'该小组不存在，或者被删除,无法操作话题');
        }
        
        //对该话题分组的操作权限进行检测
        $this->checkGroupAuth($groupinfo);
        //操作的类别 ['收藏','设置为精华','置顶','锁定']
        $action_type = in_array($this->type,['collect','dist','top','lock']) ? $this->type : '';
        if($action_type !== 'collect' && !$this->isadmin){
            $this->exitJson((object)[],0,'你没有权限管理');
        }
        //调用对应的方法进行处理
        $method = $action_type.'Topic';
        $this->$method();
        
    }
    /**
     * @name 收藏
     */
    protected function collectTopic(){
        //操作
        if($this->action == '1'){
            //收藏
            $tid = intval($this->tid);
			$Collect = D('Collect','group');
			if($tid >0){
				$map['tid'] = $tid;
				$map['mid'] = $this->mid;
				$map['addtime'] = time();
				if($Collect->isCollect($tid,$this->mid)) {
				    $this->exitJson((object)[],0,'你已经收藏了该话题');
				}
				if($Collect->add($map)){
					$this->exitJson(['tid'=>(int)$this->tid],1,'收藏成功');
				}
			}
			$this->exitJson((object)[],0,'收藏失败,请重新尝试');
        }else{
            //取消收藏
            $tid = intval($this->tid);
			if($tid >0){
				if(D('Collect','group')->where('tid='.$tid." AND mid=".$this->mid)->delete()){
					$this->exitJson(['tid'=>(int)$this->tid],1,'取消收藏成功');
				}
			}
			$this->exitJson((object)[],0,'取消收藏失败,请重新尝试');
        }
    }
    /**
     * @name 精华
     */
    protected function distTopic(){
        $this->topic = D('Topic','group');
        if (strpos($this->tid, ',')) {
			$map['id'] = array('IN',$this->tid);
			$topicInfo = $this->topic->field('id,uid,title,dist')->where($map)->findAll();
		} else if(is_numeric($this->tid)) {
			$map = "id={$this->tid}";
			$topicInfo = $this->topic->field('id,uid,title,dist')->where($map)->find();
		}
        //操作
        if($this->action == '1'){
            //设置精华
			$result = $this->topic->setField('dist', 1, $map);

			if($result !== false) {
                //操作积分
                if(count($topicInfo) == count($topicInfo,1)){
                    $topic[] = $topicInfo;
                }else{
                    $topic = $topicInfo;
                }
                foreach($topic as &$v) {
                    model('Credit')->getCreditInfo($v['uid'], 25);
                }
				//设置日志
				$this->_setOperationLog('设置为精华', $topicInfo);
				$this->exitJson(['tid'=>(int)$this->tid],1,'设为精华成功');
			}
			$this->exitJson((object)[],0,'设置精华失败,请重新尝试');
        }else{
            //取消精华
			$result = $this->topic->setField('dist', 0, $map);
			if($result !== false) {
				//设置日志
				$this->_setOperationLog('取消了精华', $topicInfo);
				$this->exitJson(['tid'=>(int)$this->tid],1,'取消精华成功');
			}
			$this->exitJson((object)[],0,'取消精华失败,请重新尝试');
        }
    }
    /**
     * @name 置顶
     */
    protected function topTopic(){
        $this->topic = D('Topic','group');
        if (strpos($this->tid, ',')) {
			$map['id'] = array('IN',$this->tid);
			$topicInfo = $this->topic->field('id,uid,title,dist')->where($map)->findAll();
		} else if(is_numeric($this->tid)) {
			$map = "id={$this->tid}";
			$topicInfo = $this->topic->field('id,uid,title,dist')->where($map)->find();
		}
        //操作
        if($this->action == '1'){
            //设置置顶
			$result = $this->topic->setField('top', 1, $map);

			if($result !== false) {
                //操作积分
                if(count($topicInfo) == count($topicInfo,1)){
                    $topic[] = $topicInfo;
                }else{
                    $topic = $topicInfo;
                }
                foreach($topic as &$v) {
                    model('Credit')->getCreditInfo($v['uid'], 26);
                }
				//设置日志
				$this->_setOperationLog('置顶', $topicInfo);
				$this->exitJson(['tid'=>(int)$this->tid],1,'置顶成功');
			}
			$this->exitJson((object)[],0,'置顶失败,请重新尝试');
        }else{
            //取消置顶
			$result = $this->topic->setField('top', 0, $map);
			if($result !== false) {
				//设置日志
				$this->_setOperationLog('取消了置顶', $topicInfo);
				$this->exitJson(['tid'=>(int)$this->tid],1,'取消置顶成功');
			}
			$this->exitJson((object)[],0,'取消置顶失败,请重新尝试');
        }
    }
    /**
     * @name 锁定
     */
    protected function lockTopic(){
        $this->topic = D('Topic','group');
        if (strpos($this->tid, ',')) {
			$map['id'] = array('IN',$this->tid);
			$topicInfo = $this->topic->field('id,uid,title,dist')->where($map)->findAll();
		} else if(is_numeric($this->tid)) {
			$map = "id={$this->tid}";
			$topicInfo = $this->topic->field('id,uid,title,dist')->where($map)->find();
		}
        //操作
        if($this->action == '1'){
            //设置置顶
			$result = $this->topic->setField('lock', 1, $map);

			if($result !== false) {
				//设置日志
				$this->_setOperationLog('锁定', $topicInfo);
				$this->exitJson(['tid'=>(int)$this->tid],1,'锁定成功');
			}
			$this->exitJson((object)[],0,'锁定失败,请重新尝试');
        }else{
            //取消置顶
			$result = $this->topic->setField('lock', 0, $map);
			if($result !== false) {
				//设置日志
				$this->_setOperationLog('解锁', $topicInfo);
				$this->exitJson(['tid'=>(int)$this->tid],1,'取消锁定成功');
			}
			$this->exitJson((object)[],0,'取消锁定失败,请重新尝试');
        }
    }
    /**
     * @name 话题回复
     */
	public function commentTopic(){
		//权限判读
		$tid = (int)$this->tid;
		if($tid > 0) {
            $this->topic = D('Topic','group');
			$topic = $this->topic->field('`id`,`uid`,`title`,`lock`,`gid`')->where("id={$tid} AND is_del=0")->find();  //获取话题内容
			if (!$topic) {
                $this->exitJson((object)[],0,'话题不存在或已被删除');
			} else if($topic['lock'] == 1) {
                $this->exitJson((object)[],0,'话题已被锁定');
			}
            $this->group_id = $topic['gid'];
            $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
            if(!$groupinfo){
                $this->exitJson((object)[],0,'话题不存在或已被删除');
            }
            //权限检测
            $this->checkGroupAuth($groupinfo);
            if(!$this->ismember && !$this->isadmin){
                $this->exitJson((object)[],0,'请先加入该小组');
            }
			$this->__checkContent($this->content, 5, 10000);

			//$post['gid'] = $this->group_id;
			//$post['uid'] = $this->mid;
			//$post['tid'] = $tid;
			//$post['content'] = h($this->content);
			//$post['istopic'] = 0;
			//$post['ctime'] = time();
			//$post['ip'] = get_client_ip();
			$post = [
				'app' => 'public',
				'table' => 'group_topic',
				'row_id' => $tid,
				'uid'	=> $this->mid,
				'content' => filter_keyword(h($this->content)),
				'data'	 => serialize(array()),
				'ctime'		=> time()
			];
			$result = M('comment')->add($post);  //添加回复
			if($result) {
				$this->topic->setField('replytime', time(), 'id='.$tid);
				$this->topic->setInc('replycount', 'id='.$tid);
				// 积分
				//X('Credit')->setUserCredit($this->mid, 'group_reply_topic');
                model('Credit')->getCreditInfo($this->mid,27);
                $this->exitJson(['tid'=>$tid,'pid'=>$result],1,'回复成功');
			}
            $this->exitJson((object)[],0,'回复失败,请稍后重试');
		} else {
            isset($this->data['api_debug']) ? $this->exitJson((object)[],0,'参数不正确:tid') : $this->exitJson((object)[],0,'话题不存在或已被删除');
		}
	}
    /**
     * @name 编辑话题回复
     */
    public function editCommentTopic(){
        //权限判读 (管理员和创建者)
		$pid = (int)$this->pid;
        if($pid > 0){
            $this->post = M('comment');
    		$post = $this->post->where('id='.$pid.' AND is_del=0')->find();
            if(!$post){
                $this->exitJson((object)[],0,'编辑的回复不存在');
            }
			// 查询小组ID
			$gid = M('group_topic')->where('id='.$post['row_id'])->getField('gid');
            $this->group_id = $gid;
            $groupinfo = D('Group','group')->where('id='.$this->group_id." AND is_del=0")->find();
            if(!$groupinfo){
                $this->exitJson((object)[],0,'该回复不存在或已被删除');
            }
    		//管理员或者帖子主人
            $this->checkGroupAuth($groupinfo);
            if(!$this->isadmin && $post['uid'] != $this->mid){
                $this->exitJson((object)[],0,'你没有权限编辑');
            }
    		$this->__checkContent($this->content, 5, 10000);
    		$content = h($this->content);
    		$map['content'] = $content;
    		$res = $this->post->where('id='.$pid)->save($map);
    		if ($res !== false) {
    		 	$this->exitJson(['pid'=>$pid],1,'修改成功');
    		} 
        }
		isset($this->data['api_debug']) ? $this->exitJson((object)[],0,'参数不正确:pid') : $this->exitJson((object)[],0,'修改失败');
    }
    /**
     * @name 记录日志
     */
    protected function _setOperationLog($operation, &$post_info){
        $content =  '把 ' . getUserSpace($post_info['uid'], 'fn', '_blank', '@' . getUserName($post_info['uid']))
					 . ' 的话题“<a href="' . U('group/Topic/topic', array('gid'=>$this->group_id, 'tid'=>$post_info['id'])) . '" target="_blank">'
					 . $post_info['title'] . '</a>” ' . $operation;
		D('Log','group')->writeLog($this->group_id, $this->mid, $content, 'topic');
	}
    /**
     * 获取我的小组列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-08-10
     * @return [type] [description]
     */
    public function getMyGroupList()
    {
        $map['status'] = 1;
        $map['is_del'] = 0;
        
        if($this->type == 'create'){
            // 我创建的小组
            $map['uid'] = $this->mid;
        }else if($this->type == 'join'){
            // 我加入的小组
            $map['uid'] = ['neq',$this->mid];
            // 获取我加入的小组ID
            $gids = D('Member','group')->where("uid={$this->mid} AND level > 0")->order('ctime desc')->field('gid')->findPage($this->count);
            $gids = getSubByKey($gids['data'],'gid');
            $map['id'] = array('in',$gids);
        }

        $group_list = D('group')->where($map)->order('ctime desc')->findPage($this->count);
        if(!$group_list['data'] || $group_list['gtLastPage'] === true){
            $this->exitJson([],1);
        }
        $group_list = $group_list['data'];
        foreach($group_list as &$v){
            $v['threadcount'] = M('group_topic')->where('gid='.$v['id'].' and is_del=0')->count();
            $v['membercount'] = M('group_member')-> where('gid='.$v['id'] .' and level != 0')->count();
            //是否已经加入
            $is_join = D('Member','group')->where("uid={$this->mid} AND gid={$v['id']} AND level > 0")->count() > 0;
            $v['is_join'] = $is_join ? 1 : 0;
            //是否为管理员
            $is_admin = D('Member','group')->where("uid={$this->mid} AND gid={$v['id']} AND level > 0 AND level <= 2")->count() > 0;
            $v['is_admin'] = $is_admin ? 1 : 0;
            $v['logourl'] = $v['logo'] ? getImageUrl($v['logo']) : THEME_PUBLIC_URL.'/images/default-cover.png';
        }

        $this->exitJson($group_list,1);
    }

}