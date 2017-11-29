<?php
/**
 * 后台，系统配置控制器
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class TopicAction extends AdministratorAction {

	//资讯分类
	public function cate() {
		$this->_top ();
		$this->pageTitle ['cate'] = '分类';
		$treeData = model ( 'CategoryTree' )->setTable ( 'zy_topic_category' )->getNetworkList ();
		$this->displayAree ( $treeData, 'zy_topic_category', 1 );
	}

	//资讯列表
	public function index() {
		$this->_top ();
		$this->pageTitle ['index'] = '列表';
		$this->pageKeyList = array ('id','title','cate','desc','dateline','from','action','re' );
		$this->searchKey = array('id','title','cate','re','from',array('ctime','ctime1'));
		$this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");

        if(isset($_POST)){
            if(!empty($_POST['id'])){$map['id'] = intval($_POST['id']);}
            if(!empty($_POST['title'])){$map['title'] = array('like', '%'.t($_POST['title']).'%');}
            if(!empty($_POST['from'])){$map['from'] = array('like', '%'.t($_POST['from']).'%');}
            if(!empty($_POST['cate'])){$map['cate'] = intval($_POST['cate']);}
            if($_POST['re'][0] === '1'){
                $map['re'] = array('eq',1);
            }elseif($_POST['re'][0] === '0'){
                $map['re'] = array('eq',0);
            }
            if (! empty ( $_POST ['ctime'] )) {
                if (! empty ( $_POST ['ctime'] [0] ) && ! empty ( $_POST ['ctime'] [1] )) { // 时间区间条件
                    $map ['dateline'] = array ('BETWEEN',array (strtotime ( $_POST ['ctime'] [0] ),
                                    strtotime ( $_POST ['ctime'] [1] )));
                } else if (! empty ( $_POST ['ctime'] [0] )) {// 时间大于条件
                    $map ['dateline'] = array ('GT',strtotime ( $_POST ['ctime'] [0] ));
                } elseif (! empty ( $_POST ['ctime'] [1] )) {// 时间小于条件
                    $map ['dateline'] = array ('LT',strtotime ( $_POST ['ctime'] [1] ));
                }
            }
        }
		$list = model ( 'Topics' )->getTopic ( 1, 0 ,$map );
		$this->pageButton [] = array ('title' => L ( 'PUBLIC_ADD' ),'onclick' => "admin.newZixun()" );
		$this->pageButton [] = array ('title' => '删除','onclick' => "admin.delZixun()" );
		$cates = model ( 'Topics' )->getAdmincate (0);
		$this->opt['cate'] = $cates;
		$this->opt['re'] = array(1=>'是',0=>'否',2=>'不选');
		foreach ( $list ['data'] as &$v ) {
            $v['title'] = '<a href="'.U('classroom/Topic/view',array('id'=>$v['id'])).'" target="_blank">'.msubstr($v['title'],0,20).'</a>';
			$v['cate'] = $cates [$v ['cate']];
			$v['desc'] = getShort($v['desc'] ,40);
			$v['dateline'] = date ( 'Y-m-d H:i', $v ['dateline'] );
			$v['action'] = '<a href="javascript:;" onClick="admin.editZixun(' . $v ['id'] . ')">编辑</a>';
            if($v['re'] == 1) {
                $v['re'] = '<a href="javascript:admin.Zixuntj(' . $v['id'] . ',\'recommend\',\'取消\',\'推荐\');">取消推荐</a>';
            }
            else {
                $v['re'] = '<a href="javascript:admin.Zixuntj(' . $v['id'] . ',\'recommend\',\'设为\',\'推荐\');">设为推荐</a>';
            }
		}
		$this->displayList ( $list );
	}
	//设置推荐
	public function recommend() {
        $msg =array();
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $id)
        );
        $is_del = M('zy_topic')->where($where)->getField('re');
        if ($is_del == 1) {
            $data['re'] = 0;
        } else {
            $data['re'] = 1;
        }
        $res = M('zy_topic')->where($where)->save($data);

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

	public function editor() {
		$this->pageTitle ['editor'] = '编辑';
		$id = $_GET ['id'];
		if (! $id) {
			$this->assign ( 'jumpUrl', U ( 'admin/Topic/index' ) );
			$this->error ( '参数错误' );
		} else {
			$_REQUEST ['tabHash'] = 'editor';
			$this->_top ();
			$this->pageTitle ['newZixun'] = '编辑';
			$this->pageTab [] = array (
					'title' => '编辑',
					'tabHash' => 'editor',
					'url' => U ( 'admin/Topic/editor' ) 
			);
			
			$this->pageKeyList = array (
					'title',
					'desc',
					'text',
					'image',
					'cate',
					're',
                   'from',
		 			'readcount'
			);
			$this->opt ['cate'] = model ( 'Topics' )->getAdmincate (0);
			$data = model ( 'Topics' )->getOnedata ( $id );
			$this->opt ['re'] [1] = '是';
			$this->opt ['re'] [0] = '否';
			$this->savePostUrl = U ( 'admin/Topic/doeditor', array (
					'id' => $id 
			) );
			$this->displayConfig ( $data );
		}
	}
	public function doeditor() {
		$id = $_GET ['id'];
		if (! $id) {
			$this->assign ( 'jumpUrl', U ( 'admin/Topic/index' ) );
			$this->error ( '参数错误' );
		} else {
			$id = intval ( $_GET ['id'] );
			if (! $_POST ['title']) {
				$this->error ( '请输入标题' );
			} elseif (! $_POST ['desc']) {
				$this->error ( '请输入摘要' );
			} elseif (! $_POST ['text']) {
				$this->error ( '请输入内容' );
			} elseif (! $_POST ['cate']) {
				$this->error ( '请选择分类' );
			} else {
				$ary ['title'] = t ( $_POST ['title'] );
				$ary ['desc'] = t ( $_POST ['desc'] );
				$ary ['text'] = $_POST ['text'];
				$ary ['image'] = intval ( $_POST ['image'] );
				$ary ['cate'] = intval ( $_POST ['cate'] );
				$ary ['re'] = intval ( $_POST ['re'] );
                $ary ['from'] = t ( $_POST ['from'] );
				$ary ['dateline'] = time ();
				$ary ['recount'] = intval ( $_POST ['recount'] );
				if (model ( 'Topics' )->savedata ( $ary, $id )) {
					$this->assign ( 'jumpUrl', U ( 'admin/Topic/index' ) );
					$this->success ( '编辑成功' );
				} else {
					$this->error ( '未知错误' );
				}
			}
		}
	}
	public function newZixun() {
		$_REQUEST ['tabHash'] = 'newZixun';
		$this->_top ();
		$this->pageTitle ['newZixun'] = '添加资讯';
		$this->pageTab [] = array ('title' => '添加资讯','tabHash' => 'newZixun','url' => U ( 'admin/Topic/newZixun' ) );
		
		$this->pageKeyList = array ('title','desc','text','image','cate','re','from','recount');
		$this->opt ['cate'] = model ( 'Topics' )->getAdmincate (0);
		$this->opt ['re'] [1] = '是';
		$this->opt ['re'] [0] = '否';
		$this->savePostUrl = U ( 'admin/Topic/donewZixun' );
		$this->displayConfig ();
	}
	public function donewZixun() {
		if (! $_POST ['title']) {
			$this->error ( '请输入标题' );
		} elseif (! $_POST ['desc']) {
			$this->error ( '请输入摘要' );
		} elseif (! $_POST ['text']) {
			$this->error ( '请输入内容' );
		} elseif (! $_POST ['cate']) {
			$this->error ( '请选择分类' );
		} else {
			$ary ['title'] = t ( $_POST ['title'] );
			$ary ['desc'] = t ( $_POST ['desc'] );
			$ary ['text'] = $_POST ['text'] ;
			$ary ['image'] = intval ( $_POST ['image'] );
			$ary ['cate'] = intval ( $_POST ['cate'] );
			$ary ['re'] = intval ( $_POST ['re'] );
            $ary ['from'] = t ( $_POST ['from'] );
			$ary ['dateline'] = time ();
			$ary ['readcount'] = intval ( $_POST ['readcount'] );
            $res = M('zy_topic')->add($ary);
			if ($res) {
				$this->assign ( 'jumpUrl', U ( 'admin/Topic/index' ) );
				$this->success ( '添加成功' );
			} else {
				$this->error ( '未知错误' );
			}
		}
	}
	private function _top() {
		$this->pageTab [] = array (
				'title' => '列表',
				'tabHash' => 'index',
				'url' => U ( 'admin/Topic/index' ) 
		);
		$this->pageTab [] = array (
				'title' => '分类',
				'tabHash' => 'cate',
				'url' => U ( 'admin/Topic/cate' ) 
		);
	}
	// 删除资讯
	public function delTopics() {
		$data ['is_del'] = 1;
		$where = array (
				'id' => array (
						'in',
						$_POST ['id'] 
				) 
		);
		$res = M ( 'ZyTopic' )->where ( $where )->save ( $data );
		
		if ($res !== false) {
			$msg ['data'] = L ( 'PUBLIC_DELETE_SUCCESS' );
			$msg ['status'] = 1;
			echo json_encode ( $msg );
		} else {
			$msg ['data'] = "删除失败!";
			echo json_encode ( $msg );
		}
	}
}