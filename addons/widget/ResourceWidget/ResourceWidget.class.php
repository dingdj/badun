<?php
/**
 * 提问/点评/笔记/插件
 * @example W('Resource',array('kztype'=>1,'kzid'=>1,'type'=>1,'template'=>'album_question'))
 * @author ashangmanage
 * @version CY1.0
 */
class ResourceWidget extends Widget {
    private $tableList = array(
        1=>'zy_question',2=>'zy_review',3=>'zy_note',4=>'zy_comment'
    );
    
    
    
    /**
     * @param integer kztype //数据分类 1:课程;2:班级;3线下课
     * @param integer kzid //课程或者班级ID
     * @param integer type //分类类型 1:提问,2:点评,3:笔记
     * @param string template 模板名称
     */
    public function render($data) {
        $var = array();
        $var['kztype']      = 1;//数据分类 1:课程;2:班级;3线下课
        $var['kzid']        = 0;//课程或者班级ID
        $var['type']        = 1;//分类类型 1:提问,2:点评,3:笔记,4:评论
        $var['ispage']      = true;//是否分页
        $var['template']    = 'album_question';//模板名称
        $var['ispage']      = $data['ispage']?'true':'false';//是否分页
        //是否取信息
        $var['isdata']      = $data['isdata']?true:false;
        //评价级别
        $var['startype']    = 'all';
        is_array($data) && $var = array_merge($var,$data);
        //获得模板名称
        $template = $var['template'].'.html';
        
        $mulus = array();
        
        if($var['isdata']){
            if($var['kztype'] == 2){
                //序列化字段---让班级和课程的字段看起来一样
                $field = '`id`,`album_title` as `title`,`uid`,`album_score` as `score`,`cover`,`album_video`,`album_comment_count` as `comment_count`';
                //取得班级信息
                $baseInfo = M('Album')->where(array('id'=>array('eq',intval($var['kzid']))))->field($field)->find();
                
                $videoids = trim( D('Album','classroom')->getVideoId($baseInfo['id']) , ',');
                //取目录信息并根据后台添加的id顺序排序
//                $mulus    = M('ZyVideo')->where(array('id'=>array('in',(string)$videoids)))->field('*')->select();
                $sql = 'SELECT * FROM ' .C("DB_PREFIX").'zy_video WHERE `id` IN ('.(string)$videoids.') ORDER BY find_in_set(id,"'.(string)$videoids.'")';
                $result = M('')->query($sql);
                $mulus = $result;
            }else{
                //序列化字段---让班级和课程的字段看起来一样
                $field = '`id`,`video_title` as `title`,`uid`,`video_id`,`video_score` as `score`,`cover`,`video_comment_count` as `comment_count`';
                $field = '*';
                //取得课程信息
                $baseInfo   = M('ZyVideo')->where(array('id'=>array('eq',intval($var['kzid']))))->field($field)->find();
                //取目录信息
                $mulus[0] = array(
                    'id' => $baseInfo['id'],'video_id' => $baseInfo['video_id'],'video_title' => $baseInfo['title'],
                );
                $mulus[0] = $baseInfo;
            }
            //基础数据
            $baseInfo['username'] = getUserName($baseInfo['uid']);
            $baseInfo['score']    = floor(intval($baseInfo['score'])/20);
            $baseInfo['title']    = msubstr($baseInfo['title'],0,20);
            //目录
            foreach($mulus as &$value){
                $value['video_title'] = msubstr($value['video_title'],0,20);
                
                $value['mzprice']      = getPrice($value,$this->mid,true,true);
                
                $isok = M('ZyService')->checkVideoAccess(intval($this->mid),$value['id']);
                if($isok){
                    $value['isBuyVideo']   = 1;
                }else{
                    $value['isBuyVideo']   = isBuyVideo($this->mid,$value['id'])?1:0;    
                }
            }
            //转换数据
            $var['baseInfo'] = $baseInfo;
            $var['mulus']    = $mulus;
        
        }
        $var['username'] = getUserName($this->mid);
        $var['userface'] = $this->mid ? getUserFace($this->mid, 'm') : THEME_URL.'/_static/image/noavatar/middle.jpg';
        $var['user_src'] = U('classroom/UserShow/index','uid='.$this->mid);
        $var['mid'] = $this->mid;
        //print_r($var);
        //渲染模版
        $content = $this->renderFile(dirname(__FILE__)."/".$template,$var);
        unset($var,$data);
        //输出数据
        return $content;
    }
    
    /**
     * 获取提问列表
     * @param integer kztype //数据分类 1:课程;2:班级;
     * @param integer kzid //课程或者班级ID
     * @param integer type //分类类型 1:提问,2:点评,3:笔记
     * @param string template 模板名称
     */
    public function getList(){
        $kztype = intval($_POST['kztype']);
        $kzid   = intval($_POST['kzid']);
        $type   = intval($_POST['type']);
        $startype   = t($_POST['startype']);
        $limit  = intval($_POST['limit']);
        
        $stable = parse_name($this->tableList[$type],1);
        //如果是课程的话就是=，班级就是in
        $map['oid']        = $kzid;
        $map['parent_id']  = 0;
        
        //如果是班级的话、、需要把下面的所有的
        if($kztype == 2){
            $vids = M('Album')->where(array('id'=>array('eq',$kzid)))->getField('album_video');
            $vids = getCsvInt($vids);
            $vids = $vids?$vids:'0';
            if($type!=2){
                $map['oid']        = array('in',(string)$kzid.','.$vids);
            }
        }else{
            $map['type']       = $kztype;
        }
        if($type == 3){
            //复合查询--如果是他本人就连带私密的也查出来
            $where['uid']      = array('eq', $this->mid);
            $where['is_open']  = array('eq',1);
            $where['_logic']   = 'or';
            
            $map['_complex'] = $where;
        }
        if($startype == 'good')
        {
            $map['star'] = array('in','80,100');
        }
        if($startype == 'middle')
        {
            $map['star'] = array('in','40,60');
        }
        if($startype == 'bad') {
            $map['star'] = array('in', '20');
        }
        if($startype == 'all' || $startype == "" )
        {
            unset($map['star']) ;
        }

        $data = M($stable)->where($map)->order('`ctime` DESC')->findPage($limit);
        $data['userface'] = $this->mid ? getUserFace($this->mid, 's') : THEME_URL.'/_static/image/noavatar/small.jpg';
        $data['user_src'] = U('classroom/UserShow/index','uid='.$this->mid);
        $data['username'] = getUserName($this->mid);
        $zyVoteMod = D('ZyVote','classroom');
        foreach($data['data'] as $key =>$v)
        {
             if($v['is_del'] == '1')
             {
                 unset($data['data'][$key]);
             }
        }
        array_values($data['data']);
        $total = 0;
        foreach($data['data'] as $key => &$value){
            $value['strtime']  = friendlyDate($value['ctime']);
            $value['uid']      = $value['uid'];
            $value['username'] = getUserName($value['uid']);
            $value['userface'] = getUserFace($value['uid'],'m');
            $value['user_src'] = U('classroom/UserShow/index','uid='.$value['uid']);
            $value['count']    = $this->getListCount($type,$value['id']);
            //综合评星
            $total += intval($value['star']);
            if($type == 1){
                $value['qst_src']  = U('classroom/Index/resource','rid='.$value['id'].'&type=3');
            }elseif($type == 2){
                $value['star']     = $value['star']/20;
                $value['sname']    = $this->reSName($value['uid'],$value['oid'],$kztype);
                //判断时候已经投票了
                $value['isvote']   = $zyVoteMod->isVote($value['id'],'zy_review',$this->mid)?1:0;
                $value['username'] = intval($value['is_secret'])?'*****':$value['username'];
            }elseif($type == 3){
                $value['note_src']  = U('classroom/Index/resource','rid='.$value['id'].'&type=4');
                $value['note_description']  = msubstr($value['note_description'],0,78);
            }
            $value['username'] = msubstr($value['username'],0,8);
        }
        $totals = $total/20/count($data['data']);
        $data['total_star'] = ceil($totals);
        echo json_encode($data?$data:array());exit;
    }
    

    private function getListCount($type,$id){
        $stable = parse_name($this->tableList[$type],1);
        $map['parent_id'] = array('eq',$id);
        $count = M($stable)->where($map)->order('`ctime` DESC')->count();
        return $count;
    }
    
    /**
     * 根据ID获取评论信息【提问/点评/笔记】
     * @param integer id   //提问/点评/笔记   表里面的ID
     * @param integer type //分类类型 1:提问,2:点评,3:笔记
     */
    public function getListForId(){
        $type = intval($_POST['type']);
        $id   = intval($_POST['id']);
        $limit  = intval($_POST['limit']);
        
        
        $stable = parse_name($this->tableList[$type],1);
        $map['parent_id'] = array('eq',$id);
        if($type == 2){
            $data = M($stable)->where($map)->order('`ctime` ASC')->findPage($limit);
        }else{
            $data = M($stable)->where($map)->order('`ctime` DESC')->findPage($limit);
        }
        foreach($data['data'] as $key =>&$value){
            $value['strtime']  = friendlyDate($value['ctime']);
            $value['username'] = getUserName($value['uid']);
            $value['userface'] = getUserFace($value['uid'], 's');
            $value['user_src'] = U('classroom/UserShow/index','uid='.$value['uid']);
            $value['reply_user'] = $value['reply_uid'] ? getUserName($value['reply_uid']) : '';
            // $value['content'] = $type == 1 ? $value['qst_description'] : $type == 2 ? $value['review_description'] : $value['note_description'];
            if($type == 1){
                $value['content'] = $value['qst_description'];
            }else if($type == 2){
                $value['content'] = $value['review_description'];
            }else{
                $value['content'] = $value['note_description'];
            }
        }
        echo json_encode($data?$data:array());exit;
    }


    
    
    
    
    
    
    
    private function reSName($uid,$oid,$kztype){
        $status = D('ZyOrder','classroom')->getLearnStatus($uid,$oid,$kztype);
        if($status == 0){
            return '待学习';
        }else if($status == 1){
            return '在上课';
        }else if($status == 2){
            return '已学完';
        }
    }
    
    
    public function ajaxComment(){
        $map = array();
        $map['to_comment_id'] = 0;
        $map['is_del'] = 0;
        $map['app_id'] = intval($_POST['app_id']);
        $map['app_table'] = t($_POST['app_table']);
        
        $type    = intval($_POST['type']);
        $fid    = intval($_POST['fid']);
        $stable = parse_name($this->tableList[$type],1);
        
        $data  = array();
        $limit = 100;
        $order = '`to_comment_id` ASC ,`ctime` DESC';
        $list  = M($stable)->where($map)->order($order)->findPage($limit);
        /*查询子评论*/
        $comment_ids = getSubByKey($list['data'],'id');
        if($comment_ids){
            $arr = array();
            $map['to_comment_id'] = array('in',$comment_ids);
            $child = M($stable)->where($map)->order($order)->select();
            foreach ($child as $key=>$val){
                $val['uidInfo'] = model('User')->getUserInfo($val['uid']);
                $val['ctime'] = date('Y-m-d H:i',$val['ctime']);
                $arr[$val['to_comment_id']][] = $val;
            }
        }
        foreach ($list['data'] as $keys=>&$vals){
            $vals['uidInfo'] = model('User')->getUserInfo($vals['uid']);
            $vals['ctime'] = date('Y-m-d H:i',$vals['ctime']);
            $vals['zan_num'] = M('zy_vote')->where(array('source_id'=>$vals['id'],'source_table_name'=>'album'))->count();
            $is_zan = M('zy_vote')->where(array('source_id'=>$vals['id'],'source_table_name'=>'album','uid'=>$this->mid))->find();
            $vals['is_zan'] = $is_zan ? 1 : 0;
            $vals['zan_title'] = $is_zan ? '取消点赞' : '点赞';
            if(isset($arr[$vals['id']])){
                $vals['child'] = $arr[$vals['id']];
            }else{
                $vals['child'] = array();
            }
        }
        
        
        //$list['data'] = array2tree($list['data'],'id','to_comment_id','child');
//         dump($arr);
//         dump($list['data']);exit;
        $list['username'] = getUserName($this->mid);
        $list['userface'] = $this->mid ? getUserFace($this->mid, 'm') : THEME_URL.'/_static/image/noavatar/middle.jpg';
        $list['user_src'] = U('classroom/UserShow/index','uid='.$this->mid);
        $list['mid'] = $this->mid;
        $list['fid'] = $fid;
        
        $content = $this->renderFile(dirname(__FILE__)."/ajaxComment.html",$list);
        
        
        $rtn['html'] = $data['html'];
        $rtn['data'] = $content;
        exit(json_encode($rtn));
    }
    
    
    
    
    
}