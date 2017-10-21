<?php
/**
 * 首页模块控制器
 * @author dengjb <dengjiebin@higher-edu.cn>
 * @version GJW2.0
 */
class LiveAction extends Action
{	
	protected $video = null; // 课程模型对象
	protected $category = null; // 分类数据模型
	protected $base_config = array();//直播配置
	protected $zshd_config = array();//展示互动
	//初始化
	public function _initialize() { 
		$this->video = D ( 'ZyVideo' ,'classroom');
		$this->category = model ( 'VideoCategory' );
		$this->zshd_config =  model('Xdata')->get('live_AdminConfig:zshdConfig');
		$this->base_config =  model('Xdata')->get('live_AdminConfig:baseConfig');
	}
	public function index() {
		$cateId = intval ( $_GET ['cateId'] );
		$selCat = $this->category->getTreeById ( $cateId, 3 );
		// 循环取出所有下级分类
		$datalist = array ();
		foreach ( $selCat ['list'] as &$val ) {
			$val ['childlist'] = $this->category->getChildCategory ( $val ['zy_video_category_id'], 3 );
			array_push ( $datalist, $val );
		}
		$this->assign ( 'mid', $this->mid );
		$this->assign ( 'cate', $datalist);
		$this->display ();
	}
	/**
     * 取得直播列表
     * @param boolean $return 是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getList($return = false) {
    	$cateId = intval ( $_GET ['cateId'] );
    	if ( $cateId > 0) {
    		$map['cate_id'] = array('like' , '%,'.$cateId.',%');
    	}
		if($this->base_config['live_opt'] == 1) {
			$map['is_active'] = 1;
			$map['is_del'] = 0;
			$data = M('live')->order('live_id desc')->where($map)->findPage(12);
			if ($data ['data']) {
				foreach($data ['data'] as &$val){
					if($val['startDate']  <= time() && $val['invalidDate']   >= time() ) {
						$val['note'] = '正在直播 '.date('m-d H:i' , $val['startDate'] );
					}

					if($val['startDate']  > time()){
						$val['note'] = '即将直播 '.date('m-d H:i'  , $val['startDate'] );
					}

					if($val['invalidDate']   < time()){
						$val['note'] = '直播结束';
					}
					$val['id'] = $val['number'];
					$val['title'] = $val['subject'];
				}
				$this->assign ( 'listData', $data ['data'] );
				$html = $this->fetch ( 'index_list' );
			} else {
				$html = '暂无直播课程';
			}
		}else if($this->base_config['live_opt'] == 2) {
			$html = '暂无直播课程';
		}else if($this->base_config['live_opt'] == 3) {
			$data = M('zy_live')->where ( $map )->order ( 'id desc' )->findPage ( 12 );
			if ($data ['data']) {
				foreach($data ['data'] as &$val){
					if($val['beginTime'] / 1000 <= time() && $val['endTime']  / 1000 >= time() ) {
						$val['note'] = '正在直播 '.date('m-d H:i' , $val['beginTime'] / 1000);
					}

					if($val['beginTime']  / 1000 > time()){
						$val['note'] = '即将直播 '.date('m-d H:i'  , $val['beginTime'] / 1000);
					}

					if($val['endTime']  / 1000 < time()){
						$val['note'] = '直播结束';
					}
					$val['price'] = intval( $val['price'] );
				}

				$this->assign ( 'listData', $data ['data'] );
				$html = $this->fetch ( 'index_list' );
			} else {
				$html = '暂无直播课程';
			}
		}
		$this->assign ( 'cateId', $cateId ); // 定义分类
		$this->assign ( 'live_opt', $this->base_config['live_opt'] );
		$data ['data'] = $html;
		if ($return) {
			return $data;
		} else {
			exit( json_encode ( $data ) );
		}
    }
	

	public function watch() {
    	$id = intval($_GET['id']);
    	$live_type = $this->getLiveType();

		if( $this->base_config['live_opt'] == 1) {//展示互动
			$res = M( 'live' )->where ( 'number='. $id)->find ();
			$tid = M('ZyTeacher')->where("uid=".$this->mid)->getField('id');
			$unmae = '学生_'.rand(11111,99999);
			if($tid != $res['speaker']){
				if($res['startDate'] >= time()){
					$this->error ( '还未到直播时间' );
				}
				if($res['invalidDate'] <= time()){
					$this->error ( '直播已经结束' );
				}
				// 是否已购买
				$is_buy = M('zy_order_live')->where('live_id='.$id .' and uid='.$this->mid)->count();
				if($res['price'] > 0 && $is_buy <= 0){
					$this->error('请先购买');
				}
				$field = 'uname';
				$userInfo = model('User')->findUserInfo($this->mid,$field);
				$unmae = $userInfo['uname'];
			}
			$url = $res['studentJoinUrl']."?nickname=".$unmae."&token=".$res['studentToken'];
		} else if($this->base_config['live_opt'] == 2) {//三芒
    		$url = $this->getClass();
    		$url = $url['url'].'?param='.$url['param'];
		} else if($this->base_config['live_opt'] == 3) {//光慧
    		$res = M('zy_live')->where('id=' . $id)->find();
    		// 是否已购买
    		$is_buy = M('zy_order_live')->where('live_id='.$id .' and uid='.$this->mid)->count();
    		if($res['price'] > 0 && $is_buy <= 0 && !is_admin($this->mid) ){
    			$this->error('请先购买');
    		}
    		
    		$gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
    		if ( $res['endTime'] / 1000 >= time() ) {
    			$url = $gh_config['video_url'] . '/student/index.html?liveClassroomId='.$res['room_id'].'&customerType=taobao&customer=seition&sp=0';
    		} else {//直播结束
    			$url = $gh_config['video_url'] . '/playback/index.html?liveClassroomId='.$res['room_id'].'&customerType=taobao&customer=seition&sp=0';
    		}
    	}
    	$this->assign('url' , $url);
    	$this->display();
    }

    //判断当前直播
    private function getLiveType(){
    	$res = model('Xdata')->get('live_AdminConfig:baseConfig');
    	return intval( $res['live_opt'] );
    }
		
	
}