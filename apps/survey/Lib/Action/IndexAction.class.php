<?php

/**
 * 出右考试系统首页控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
class IndexAction extends Action {
	
    /**
     * 获取在线调查
     * @return void
     */ 
    public function index() {
        $data = M('zy_survey_category')->where('is_del=0')->order('ctime desc')->findAll();
        $this->assign('data',$data);
        $this->display();
    }
    /**
     * 在线调查详情
     * @return void|array
     */
    public function goSurvey() {
        $fid = intval($_GET['id']);
        $res = M('zy_survey_category')->where('id='.$fid.' and is_del=0')->find();
        if( !$res ) {
        	$this->error('问卷调查不存在啦');
        }
        if( M('zy_survey_user')->where('uid='.$this->mid.' and sid = '.$fid)->getField('id') ) {
            $this->assign('jumpUrl' ,U('survey/Index/surveyShow' ,array('fid'=>$fid)) );
        	$this->error('你已经参加过问卷调查啦');

        }
        $survey_list = M('zy_survey')->where('fid='.$fid.' and is_del=0')->findAll();
        foreach($survey_list as &$val) {
        	$val['options'] = M('zy_survey_option')->where('fid='.$val['id'].' and is_del=0')->findAll();
        }
        $subscript = array("A","B","C","D","E","F","G","H","I","J","K");
        $this->assign('list',$survey_list);
        $this->assign('subscript',$subscript);
        $this->assign('res',$res);
        $this->display('go_survey');
    }
    
    /**
     * 统计在线调查结果
     */
    public function doSurvey(){
    	$count = intval( $_POST['survey_count'] );
    	for($i=1;$i<=$count;$i++){
    		$options[] = $_POST['option'.$i];
    	}
    	$options = array_filter($options);
    	foreach($options as $val) {
    		if( is_array($val) ) {//是多选项
    			foreach($val as $v) {
    				$option = explode('-', $v);
    				$res = M('zy_survey_count')->where('fid='.$option[2])->find();
    				if( $res ) {//已经有记录，则更新
    					$data['count'] = array('exp','`count`+1');
    					$rt = M('zy_survey_count')->where('fid='.$option[2])->save($data);
    				} else {
    					$data['cid']   = $option[0];
    					$data['pid']   = $option[1];
    					$data['fid']   = $option[2];
    					$data['count'] = 1;
    					$rt = M('zy_survey_count')->add($data);
    				}
    			}
    		} else {
    			$option = explode('-', $val);
    			$res = M('zy_survey_count')->where('fid='.$option[2])->find();
    			if( $res ) {//已经有记录，则更新
    				$data['count'] = array('exp','`count`+1');
    				$rt = M('zy_survey_count')->where('fid='.$option[2])->save($data);
    			} else {
    				$data['cid']   = $option[0];
    				$data['pid']   = $option[1];
    				$data['fid']   = $option[2];
    				$data['count'] = 1;
    				$rt = M('zy_survey_count')->add($data);
    			}
    		}
    	}
    	if( $rt !== false) {
    		$data['uid']  = $this->mid;
    		$data['sid']  = $_POST['sid'];
    		$data['time'] = time();
    		M('zy_survey_user')->add($data);
    	}
    	exit(json_encode(array('status'=>'1')));
    }
    
    /**
     * 在线调查结果展示
     */
    public function surveyShow(){
    	$fid = $_GET['fid'];
    	$title = M('zy_survey_category')->where('id='.$fid.' and is_del=0')->getField('title');
    	$list = M('zy_survey')->where('fid='.$fid.' and is_del=0')->findAll();
    	foreach($list as &$val) {
    		$val['options'] = M('zy_survey_option')->where('fid='.$val['id'].' and is_del=0')->findAll();
    		$val['num'] = 0;
    		foreach($val['options'] as &$v) {
    			$v['name'] = $v['title'];
    			$v['count'] = intval(M('zy_survey_count')->where('fid='.$v['id'])->getField('count'));
    			$val['num'] += $v['count'];
    			unset($v['id']);
    			unset($v['fid']);
    			unset($v['title']);
    			unset($v['is_del']);
    		}
    		foreach($val['options'] as &$v) {
    			//$v['y'] = round($v['count']/$val['num']*100, 2);//每个选项占总选项百分比
                $v['y'] = $v['count'];
    			unset($v['count']);
    		}
    	}
    	$this->assign('list_json' , json_encode($list));
    	$this->assign('list' , $list);
    	$this->assign('title' , $title);
    	$this->display('survey_show');
    }
  
}

