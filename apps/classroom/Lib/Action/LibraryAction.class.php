<?php
/**
 * Created by Ashang.
 * 文库控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class LibraryAction extends CommonAction{
    /**
     * 文库首页显示方法
     */
    public function index(){
        $map="status=1 AND is_reviewed=1 AND is_del=0";
        $order = "id DESC";
        $limit = 10;

        //获取分类列表
        $cate_id = t($_GET['cateId']);
        if($cate_id > 0){
            $cateId = explode(",", $cate_id );
        }
        $category = model('DocCategory')->getDocList();
        $this->assign ( 'category', $category);

        if($cateId){
            $cate_attd_gory = model('DocCategory')->where('doc_category_id='.$cateId[0])->field("doc_category_id,title")->find();

            $title=model('DocCategory')->where('doc_category_id='.end($cateId))->getField("title");
            $this->assign('title',$title);
            $selCate = model("DocCategory")->where(array('pid'=>$cateId[0]))->field("doc_category_id,title")->findALL();
            $this->assign('cate_attd_gory',$cate_attd_gory);
            $this->assign('category_two',$selCate);
        }
        if($cateId[1]){
            $selChildCate = model("DocCategory")->where(array('pid'=>$cateId[1]))->field("doc_category_id,title")->findALL();
            $this->assign('category_three',$selChildCate);
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);

        if($cateId>0){
            $doc_category = implode(',',$cateId);
            $map .=" AND fullcategorypath like '%,$doc_category,%'";
        }
        //条件筛选
        $sort_type= t($_GET['sort_type']);
        if($sort_type){
            switch ($sort_type) {
                case 1:
                    $map .=" AND is_re = $sort_type";
                    break;
                case 'hot':
                    $order = "down_nums DESC";
                    break;
                default;
            }
        }
        $data = model('Doc')->getList($map,$order,$limit);
        foreach ($data['data'] as $key => &$value) {
            $isExist = D('ZyCollection')->where(array('source_id'=>$value['doc_id'],'uid'=>$this->mid,'source_table_name'=>'doc'))->count();
            if($isExist){
                $value['collection'] = 1;
            }else{
                $value['collection'] = 0;
            }
            $mhm_id = model('User')->where('uid='.$value['uid'])->getField('mhm_id');
            $value['school_title'] = model('School')->where('id='.$mhm_id)->getField('title') ? : "Eduline平台";
        }
        //猜你喜欢
        $guess_you_like = D('ZyGuessYouLike')->getGYLData(0,$this->mid,7);
        foreach ($guess_you_like as $key=> $val){
            $mhmName = model('School')->getSchoolInfoById($val['mhm_id']);
            $datas[$key]['mhmName'] = $mhmName['title'];
            //教师头像和简介
            $teacher = M('zy_teacher')->where(array('id'=>$val['teacher_id']))->find();
            $guess_you_like[$key]['teacherInfo']['name'] = $teacher['name'];
            $guess_you_like[$key]['teacherInfo']['inro'] = $teacher['inro'];
            $guess_you_like[$key]['teacherInfo']['head_id'] = $teacher['head_id'];
            //直播课时
            if($val['type'] == 2){
                $live_data = $this->live_data($val['live_type'],$val['id']);
                $guess_you_like[$key]['live']['count'] = $live_data['count'];
                $guess_you_like[$key]['live']['now'] = $live_data['now'];
            }
        }
        //热门文档
        $hotDoc = M('Doc')->where($map)->order('down_nums desc')->findPage(5);

        $this->assign("hotDoc",$hotDoc['data']);
        $this->assign("data",$data);
        $this->assign("listData",$data['data']);
        $this->assign("sort_type",$sort_type);
        $this->assign("guess_you_like",$guess_you_like);
        $this->display();
    }

    //获取文档列表
    public function getLibraryList() {
        $map="status=1 AND is_reviewed=1 AND is_del=0";
        $orders = array (
            'default' => 'id DESC',
            'hot' => 'down_nums DESC'
        );
        $limit = 10;
        $order = $orders['default'];

        //获取分类列表
        $cate_id = t($_GET['cateId']);
        if($cate_id > 0){
            $cateId = explode(",", $cate_id );
        }
        $category = model('DocCategory')->getDocList();
        $this->assign ( 'category', $category);

        if($cateId){
            $cate_attd_gory = model('DocCategory')->where('doc_category_id='.$cateId[0])->field("doc_category_id,title")->find();

            $title=model('DocCategory')->where('doc_category_id='.end($cateId))->getField("title");
            $this->assign('title',$title);
            $selCate = model("DocCategory")->where(array('pid'=>$cateId[0]))->field("doc_category_id,title")->findALL();
            $this->assign('cate_attd_gory',$cate_attd_gory);
            $this->assign('category_two',$selCate);
        }
        if($cateId[1]){
            $selChildCate = model("DocCategory")->where(array('pid'=>$cateId[1]))->field("doc_category_id,title")->findALL();
            $this->assign('category_three',$selChildCate);
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);

        if($cateId>0){
            $doc_category = implode(',',$cateId);
            $map .=" AND fullcategorypath like '%,$doc_category,%'";
        }
        //条件筛选
        $sort_type= t($_GET['sort_type']);
        if($sort_type){
            switch ($sort_type) {
                case 1:
                    $map .=" AND is_re = $sort_type";
                    break;
                case 'hot':
                    $order = $orders['hot'];
                    break;
                default;
            }
        }
        $data = model('Doc')->getList($map,$order,$limit);
        foreach ($data['data'] as $key => &$value) {
            $isExist = D('ZyCollection')->where(array('source_id'=>$value['doc_id'],'uid'=>$this->mid,'source_table_name'=>'doc'))->count();
            if($isExist){
                $value['collection'] = 1;
            }else{
                $value['collection'] = 0;
            }
            $mhm_id = model('User')->where('uid='.$value['uid'])->getField('mhm_id');
            $value['school_title'] = model('School')->where('id='.$mhm_id)->getField('title') ? : "跟我学平台";
        }
        $this->assign('data', $data);

        $html = $this->fetch('ajax_library');
        $data['data']=$html;
        exit( json_encode($data) );
    }

    //收藏文库
    public function collect(){
        $zyCollectionMod = D('ZyCollection');
        $type   = intval($_POST['type']);//0:取消收藏;1:收藏;
        $source_id = intval($_POST['source_id']);//文库ID

        $data['uid'] = intval($this->mid);
        $data['source_id'] = intval($source_id);
        $data['source_table_name'] = 'doc';
        $data['ctime'] = time();
        if(!$type){
            $i = $zyCollectionMod->delcollection($data['source_id'],$data['source_table_name'],$data['uid']);
            if($i === false){
                $this->mzError($zyCollectionMod->getError());
            }else{
                M('doc')->where(array('id'=>$source_id))->setDec('collect_num');
                $credit = M('credit_setting')->where(array('id'=>53,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] < 0){
                    $ctype = 7;
                    $note = '取消收藏文库扣除的积分';
                }
                model('Credit')->addUserCreditRule($this->mid,$ctype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

                $this->mzSuccess('取消收藏成功!');
            }
        }else{
            $i = $zyCollectionMod->addcollection($data);
            if($i === false){
                $this->mzError($zyCollectionMod->getError());
            }else{
                M('doc')->where(array('id'=>$source_id))->setInc('collect_num');
                $credit = M('credit_setting')->where(array('id'=>37,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $ctype = 6;
                    $note = '收藏文库获得的积分';
                }
                model('Credit')->addUserCreditRule($this->mid,$ctype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

                $this->mzSuccess('收藏成功!');
            }
        }
    }
    //获取文档信息
    public function getLibraryInfo(){
        $id = intval($_GET['source_id']);
        $data = model('Doc')->getDocById($id);
        if($data['price'] == '0'){
            $data['price'] = '免费';
        }
        exit( json_encode($data) );
    }
    //文库下载方法
    public function down(){
        if(!$this->mid){
            $this->assign('jumpUrl',U('public/Passport/login_g'));
            $this->error('请先登录');
        }
        $uid = intval($this->mid);
        $source_id = intval($_GET['source_id']);//文库ID
        $doc = model('Doc')->getDocById($source_id);

        $userCredit = model('Credit')->getUserCredit($uid);
        foreach ($userCredit['credit'] as $key => $val) {
            $credit = $val['value'];
        }

        if(($credit >= $doc['price'] && $doc['price'] > 0) || is_admin($uid) || ($uid == $doc['uid']) || ($doc['price'] == 0)){
            $file_url = getAttachPathByAttachId($doc['attach_info']['attach_id']);
            if($file_url){
                $file_url = '/data/upload/'.$file_url;
                if(file_exists(SITE_PATH.$file_url)){
                    $re = model('Credit')->rmfreeze($uid,$doc['price']);
                    if($re){
                        model('Credit')->addCreditFlow($uid, 0, $doc['price'], $source_id, '', '下载文库消费的积分');
                    }
                    M('doc')->where(array('id'=>$source_id))->setInc('down_nums');
                    $data = array(
                        'uid'   => $this->mid,
                        'cid'   => $source_id,
                        'price' => $doc['price'],
                        'ctime' => time()
                    );
                    $res = M('doc_user')->add($data);
                    if($res){
                        downloadFile(SITE_URL.$file_url,SITE_PATH.$file_url);
                        exit;
                    }
                }
            }
            $this->error('下载的资源不存在');
        }
        $this->error('对不起，您的积分不足');
    }
    //下载链接
    public function downLink(){
        $doc = model('Doc')->getDocById(intval($_GET['source_id']));
        $file_url = getAttachPathByAttachId($doc['attach_info']['attach_id']);
        $file_url = '/data/upload/'.$file_url;
        downloadFile(SITE_URL.$file_url,SITE_PATH.$file_url);
    }
    //文库下载方法
    public function downW3g(){
        if(!$this->mid){
            $this->mzError('请先登录');
        }
        $uid = intval($this->mid);
        $source_id = intval($_POST['source_id']);//文库ID
        $doc = model('Doc')->getDocById($source_id);

        $userCredit = model('Credit')->getUserCredit($uid);
        foreach ($userCredit['credit'] as $key => $val) {
            $credit = $val['value'];
        }
        if(($credit >= $doc['price'] && $doc['price'] > 0) || is_admin($uid) || ($uid == $doc['uid']) || ($doc['price'] == 0)){
            $file_url = getAttachPathByAttachId($doc['attach_info']['attach_id']);
            if($file_url){
                model('Credit')->rmfreeze($uid,$doc['price']);
                M('doc')->where(array('id'=>$source_id))->setInc('down_nums');
                $data = array(
                    'uid'   => $this->mid,
                    'cid'   => $source_id,
                    'price' => $doc['price'],
                    'ctime' => time()
                );
                $res = M('doc_user')->add($data);
                if($res){
                    $this->mzSuccess('成功');exit;
                }
            }
            $this->mzError('下载的资源不存在');
        }
        $this->mzError('对不起，您的积分不足');
    }

    //直播数据处理
    protected function live_data($live_type,$id)
    {
        $count = 0;
        //第三方直播类型
        if($live_type == 1){
            $live_data = M('zy_live_zshd')->where(array('live_id'=>$id,'is_del'=>0))->order('invalidDate asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['invalidDate'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else {
                $live_data = array(1);
                $count = 1;
            }
        }elseif ($live_type == 3){
            $live_data = M('zy_live_gh')->where(array('live_id'=>$id,'is_del'=>0))->order('endTime asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['endTime'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else{
                $live_data = array(1);
                $count = 1;
            }
        }
        $live_data['count'] = count($live_data);
        $live_data['now'] = $count;

        return $live_data;
    }
    /**
      * @name 获取子分类
      */
    public function getSubCategory(){
        $pid = $_POST['pid'];
        $list = model('DocCategory')->getChildCategory($pid);
        if($list){
            $res = [
                'status'=>1,
                'data' => $list
            ];
        }else{
            $res = [
                'status'=>0,
                'message' => '暂无子分类'
            ];
        }
        echo json_encode($res);exit;
    }

    /**
     * 获取文库列表
     */
    public function getList(){
        $map['status'] = 1;
        $map['is_reviewed'] = 1;
        $category= intval($_GET['category']);
        $sort_type= intval($_GET['sort_type']);
        if($sort_type>0){
            switch ($sort_type) {
                case 1:
                    $map['is_re'] = "1";
                    break;
                case 2:
                    $order="down_nums desc";
                    break;
                default;
            }
        }
        if($category>0){
            $map['category'] = array('like', '%' . $category . '%');
        }
        $data = model('Doc')->getList($map,$order);

        if ($data['data']) {
            $this->assign('listData',$data['data']);
            $this->assign('category', $category);
            $this->assign('sort_type', $sort_type);
            $html = $this->fetch('index_list');
        }else{
            $html="<div style=\"margin-top:20px;\">对不起，没有找到符合条件的文档T_T</div>";
        }
        $data['data'] = $html;
        echo json_encode($data);
        exit;
    }
	
}


?>