<?php
/**
 * 点评控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
class ReviewAction extends CommonAction
{
	/**
    * 初始化
    * @return void
    */
    public function _initialize() {
        parent::_initialize();
    }
	
	//http://127.0.0.1/gaojiao/index.php?app=classroom&mod=Review&act=index
	/**
	 * 点评控制器
	 * @return void
	 */
	public function index()
	{
		$data['video_score'] = 80.0000;
		$data['video_comment_count'] = array('exp','`video_comment_count` + 1');
		//课程
		M('ZyVideo')->where(array('id'=>array('eq',12)))->save($data);
		$this->display();
	}
	/**
	 * 添加点评
	 * @return void
	 */
	public function add(){
		//查看此人是否已经购买此课程//班级
		if(intval($_POST['kztype']) == 1 || intval($_POST['kztype']) == 4){
            $kztype = intval($_POST['kztype']);
            if($kztype == 4){
                $_POST['kztype'] = 1;
            }
			//课程
			$isbuy = D('ZyService')->checkVideoAccess($this->mid,intval($_POST['id']));
			if(!$isbuy){
				$this->mzError('需要购买之后才能点评!');
			}
		}else if(intval($_POST['kztype']) == 2){
			//班级
			$isbuy = isBuyAlbum($this->mid,intval($_POST['id']));
			if(!$isbuy){
				$this->mzError('需要购买之后才能点评!');
			}
		}else if(intval($_POST['kztype']) == 3){
			//线下课
			$isbuy = M('zy_order_teacher')->where(array('uid'=>$this->mid, 'video_id'=>intval($_POST['id'])))->getField('pay_status');
			if($isbuy != 3){
				$this->mzError('需要购买之后才能点评!');
			}
		}
		//每个人只能点评一次
		$count = M('ZyReview')->where(array('oid'=>intval($_POST['id']),'parent_id'=>0,'uid'=>$this->mid,'type'=>array('eq',intval($_POST['kztype']))))->count();
		if($count){
			$this->mzError('已经点评了');
		}

		$data['parent_id']           = 0;
		$data['star']		         = intval($_POST['score'])*20;//分数
		$data['type']		         = intval($_POST['kztype']);//
		$data['uid'] 			     = intval($this->mid);
		$data['is_secret'] 			 = intval($_POST['is_secret']);
		$data['oid'] 			     = intval($_POST['id']);//对应的ID【班级ID/课程ID】
		$data['review_source'] 	     = 'web网页';
		$data['review_description']  = filter_keyword(t($_POST['content']));
		$data['ctime']			     = time();
		if(!$data['uid']){
			$this->mzError('评价需要先登录');
		}
		if(!$data['star']){
			$this->mzError('请给课程打分');
		}
		if(!$data['review_description']){
			$this->mzError('请输入评价内容');
		}
		if($data['type']==1)
        {
            $data['tid'] = M('zy_video')->where('id='.$data['oid'])->getField('teacher_id');
        }
        if($data['type']==2)
        {
            $uid = M('album')->where('id='.$data['oid'])->getField('uid');
            $data['tid'] = M('zy_teacher')->where('uid ='.$uid)->getField('id');
        }
		if($data['type']==3)
		{
			$data['tid'] = M('zy_teacher_course')->where('course_id='.$data['oid'])->getField('teacher_id');
		}


		$i = M('ZyReview')->add($data);
		if($i){
			//点评之后 要计算此班级的总评分
			$star = M('ZyReview')->where(array('oid'=>intval($_POST['id']),'parent_id'=>0,'type'=>array('eq',intval($_POST['kztype']))))->Avg('star');
			
			if(intval($_POST['kztype']) == 1){
				$_data['video_score'] = intval($star);
				$_data['video_comment_count'] = array('exp','`video_comment_count` + 1');
				//课程
				M('ZyVideo')->where(array('id'=>array('eq',$data['oid'])))->save($_data);
			}else{
				$_data['album_score'] = intval($star);
				$_data['album_comment_count'] = array('exp','`album_comment_count` + 1');
				//班级	
				M('Album')->where(array('id'=>array('eq',$data['oid'])))->save($_data);
			}
			//session('mzaddreview',time()+180);
            if($kztype == 1){
                $credit = M('credit_setting')->where(array('id'=>7,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $type = 1;
                    $note = '课程点评获得的积分';
                }
            }else if($kztype == 4){
                $credit = M('credit_setting')->where(array('id'=>13,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $type = 6;
                    $note = '直播点评获得的积分';
                }
            }
			model('Credit')->addUserCreditRule($this->mid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
			$this->mzSuccess('评价成功');
		}else{
			$this->mzSuccess('评价失败');
		}
	}
	
	/**
	 * 添加点评回复
	 * @return void
	 */
	public function addHuiFu(){
		$data['parent_id']           = intval($_POST['parent_id']);;
		$data['star']		         = 0;//分数
		$data['type']		         = intval($_POST['kztype']);//
		$data['uid'] 			     = intval($this->mid);
		$data['oid'] 			     = intval($_POST['kzid']);//对应的ID【班级ID/课程ID】
		$data['review_source'] 	     = 'web网页';
		$data['review_description']  = t($_POST['content']);
		$data['ctime']			     = time();
		
		if(session('mzaddreviewHuiFu'.$data['parent_id'].$data['oid'].$data['type']) >= time()){		
			//请不要重复刷新
			$this->mzError('请不要重复添加,3分钟之后再试!');
		}
		
		if(!$data['uid']){
			$this->mzError('回复需要先登录');
		}
		if(!$data['review_description']){
			$this->mzError('请输入回复内容');
		}
		
		if($data['id'] = M('ZyReview')->add($data)){
			session('mzaddreviewHuiFu'.$data['parent_id'].$data['oid'].$data['type'],time()+180);
			
			$data['strtime']  = friendlyDate($data['ctime']);
			$data['username'] = getUserName($data['uid']);
			
			//把父级的回复数量 +1
			M('ZyReview')->where(array('id'=>array('eq',$data['parent_id'])))->setInc('review_comment_count');
			
			$this->mzSuccess('回复成功','',$data);
		}else{
			$this->mzSuccess('回复失败');
		}
	}
	
	
}