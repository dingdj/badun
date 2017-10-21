<?php

/**
 * Eduline机构课程挂载首页控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class MountAction extends CommonAction
{

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * Eduline机构课程挂载首页方法
     * @return void
     */
    public function index() {
        $mount_currency_category = $this->_MakeTree();

        $map ['is_activity'] = 1;
        $map ['is_del']      = 0;
        $map ['is_mount']    = 1;
        if(is_school($this->mid)){
            $map ['mhm_id']      = ['neq',is_school($this->mid)];
        }
        $map ['_string']     = ' (is_charge != 1)  AND ( t_price != 0) ';
        $map ['uctime']      = ['gt',time()];
        $map ['listingtime'] = ['lt',time()];

        $mount_data = M('zy_video')->where($map)->field('id,video_title,cover,video_binfo,teacher_id,v_price,t_price,mhm_id,type,live_type')->findPage(30);

        foreach ($mount_data['data'] as $key => $val){
            $smap['vid'] = $mmap['vid'] = $val['id'];
            $smap['pid'] = array('neq',0);
            $count = M('zy_video_section')->where($smap)->count();
            $mount_data['data'][$key]['sectionNum'] = $count;
            $school_info = model('School')->where('id='.$val['mhm_id'])->field('title,school_and_oschool')->find();
            $mount_data['data'][$key]['school_title'] = $school_info['title'];
            $mount_data['data'][$key]['video_binfo'] =msubstr ($mount_data['data'][$key]['video_binfo'], 0, 85);
            $mount_data['data'][$key]['mount_price'] = round(floatval(explode(':',$school_info['school_and_oschool'])[1]) * $val['t_price'],2);
            if($val['type'] == 2){
                $mount_data['data'][$key]['teacher_name'] = $this->teacher($val['live_type'],$val['id']);
            }else{
                $mount_data['data'][$key]['teacher_name'] = M('zy_teacher')->where('id='.$val['teacher_id'])->getField('name');
            }
            $mmap['uid'] = $this->mid;
            $mount_status = M( 'zy_video_mount')->where ( $mmap )->getField('is_activity');
            if($mount_status === '0'){
                $mount_data['data'][$key]['mount_status'] = 1;
            }
            if($mount_status === '1'){
                $mount_data['data'][$key]['mount_status'] = 2;
            }
//            $data ['mzprice'] = getPrice ( $val, $this->mid, true, true );
        }
        $this->assign('mount_currency_category',$mount_currency_category);
        $this->assign('mount_data',$mount_data);
        $this->display();
    }

    /**
     * Eduline机构课程挂载首页方法
     * @return void
     */
    public function albumIndex() {
        $mount_currency_category = $this->_AlbumMakeTree();

        $map ['status']      = 1;
        $map ['is_mount']    = 1;
        $map ['price']      = ['neq',0];
        if(is_school($this->mid)){
            $map ['mhm_id']      = ['neq',is_school($this->mid)];
        }

        $mount_data = M('album')->where($map)->field('id,mhm_id,album_title,cover,price,album_intro')->findPage(30);
        foreach ($mount_data['data'] as &$value){
            $value['album_title']   = msubstr($value['album_title'],0,20);
            $school_info = model('School')->where('id='.$value['mhm_id'])->field('title,school_and_oschool')->find();
            $value['school_title']  = $school_info['title'];
            $album_video_list = M('album_video_link')->where(['album_id'=>$value['id'],'_string'=>' type = 1 OR type = 2'])->order('id desc')->field('video_id')->select();
            $teacher_inid = M('zy_video')->where(['is_del'=>0,'id'=>['in',implode(',',getSubByKey($album_video_list , 'video_id'))]])->field('teacher_id')->select();
            foreach($teacher_inid as $key => $val){
                $tch_ids[$key] = $val['teacher_id'];
            }
            $it_id = trim(implode(',',array_unique(array_filter($tch_ids))),",");
            $tdata=D('ZyTeacher')->where(array('id'=>array('IN',$it_id)))->field('id,name')->select();
            $value['sectionNum']    = count($album_video_list);
            $value['teacher_name']  = $tdata[0]['name'];
            $value['mount_price'] = round(floatval(explode(':',$school_info['school_and_oschool'])[1]) * $value['price'],2);
            $mmap['aid'] = $value['id'];
            $mmap['uid'] = $this->mid;
            $mount_status = M( 'zy_video_mount')->where ( $mmap )->getField('is_activity');
            if($mount_status === '0'){
                $value['mount_status'] = 1;
            }
            if($mount_status === '1'){
                $value['mount_status'] = 2;
            }
        }
        $this->assign('mount_currency_category',$mount_currency_category);
        $this->assign('mount_data',$mount_data);
        $this->display('album_index');
    }

    /**
     * 递归形成树形结构
     * @param integer $pid 父级ID
     * @param integer $level 等级
     * @return array 树形结构
     */
    private function _MakeTree($pid = '0', $level = '0'){
        $result = model('VideoCategory')->where('pid='.$pid)->order('sort ASC')->findAll();

        $map ['is_activity'] = 1;
        $map ['is_del']      = 0;
        $map ['is_mount']    = 1;
//        $map ['type']        = 1;
        $map ['_string']     = ' (is_charge != 1)  AND ( t_price != 0) ';
        $map ['uctime']      = ['gt',time()];
        $map ['listingtime'] = ['lt',time()];
        if(is_school($this->mid)){
            $map ['mhm_id']      = ['neq',is_school($this->mid)];
        }

        if($result) {
            foreach($result as $key => $value) {
                $map['fullcategorypath'] = ['like','%,'.$value['zy_currency_category_id'].',%'];
                $id = $value['zy_currency_category_id'];
                $list[$id]['id'] = $value['zy_currency_category_id'];
                $list[$id]['pid'] = $value['pid'];
                $list[$id]['title'] = $value['title'];
                $list[$id]['level'] = $level;
                $list[$id]['video_count'] = count(M('zy_video')->where($map)->field('id')->select());
                $list[$id]['child'] = $this->_MakeTree($value['zy_currency_category_id'], $level + 1);
            }
        }

        return $list;
    }

    /**
     * 递归形成树形结构
     * @param integer $pid 父级ID
     * @param integer $level 等级
     * @return array 树形结构
     */
    private function _AlbumMakeTree($pid = '0', $level = '0'){
        $result = M('zy_package_category')->where('pid='.$pid)->order('sort ASC')->findAll();

        $map ['status']      = 1;
        $map ['is_mount']    = 1;
        $map ['price']      = ['neq',0];
        if(is_school($this->mid)){
            $map ['mhm_id']      = ['neq',is_school($this->mid)];
        }

        if($result) {
            foreach($result as $key => $value) {
                $map['album_category'] = ['like','%,'.$value['zy_package_category_id'].',%'];
                $id = $value['zy_package_category_id'];
                $list[$id]['id'] = $value['zy_package_category_id'];
                $list[$id]['pid'] = $value['pid'];
                $list[$id]['title'] = $value['title'];
                $list[$id]['level'] = $level;
                $list[$id]['video_count'] = count(M('album')->where($map)->field('id')->select());
//                $list[$id]['child'] = $this->_MakeTree($value['zy_currency_category_id'], $level + 1);
            }
        }

        return $list;
    }

    public function doMount(){
        if(!$this->mid){
            $this->mzError("需要登录才可以进行操作");
        }
        $vid     = intval($_POST['mount_id']);
        $map['id'] = $smap['vid'] = $vid;
        $smap['uid'] = $this->mid;

        if(intval($_POST['type'])){
            $map ['status']      = 1;
            $map ['is_mount']    = 1;
            $map ['price']      = ['neq',0];
            if(is_school($this->mid)){
                $map ['mhm_id']      = ['neq',is_school($this->mid)];
            }
            $smap['aid']        = $vid;
            $data['aid']        = $vid;

            $video_id = M('album')->where($map)->field('id,mhm_id,album_title,cover,price,album_intro')->getField('id');
        }else{

            $map['is_activity'] = 1;
            $map['is_del']      = 0;
            $map['uctime']      = ['gt',time()];
            $map['listingtime'] = ['lt',time()];
            $smap['vid']        = $vid;
            $data['vid']        = $vid;

            $video_id = D ( 'ZyVideo' )->where ( $map )->getField('id');
            $mount_id = M( 'zy_video_mount')->where ( $smap )->getField('id');
        }

        if(!$video_id){
            $this->mzError("课程不存在");
        }
        if(!is_school($this->mid)){
            $this->mzError("申请挂载须为机构管理员");
        }
        if($mount_id){
            $this->mzError("已申请挂载");
        }

        $data['uid']        = $this->mid;
        $data['mhm_id']     = is_school($this->mid);
        $data['ctime']      = time();
        $data['is_activity'] = 1;
        $data['is_del']     = 0;
        $res = M('zy_video_mount')->add($data);

        if($res){
            $this->mzSuccess('申请成功。。');
        }else{
            $this->mzError("申请失败");
        }
    }

    public function change_mount(){
        $mount_map = t($_POST['mount_map']);
        $mount_order = t($_POST['mount_order']);

        if($mount_map == 'default'){
            
        }
        if($mount_map == 'quality'){
            $map ['is_best'] = 1;
        }
        if($mount_map == 'time'){
            $order = 'listingtime desc,ctime desc';
        }
        if($mount_order == 'price_asc'){
            $order = 'v_price asc,t_price asc';
        }
        if($mount_order == 'price_desc'){
            $order = 'v_price desc,t_price desc';
        }

        if($mount_map == 'time' && $mount_order){
            if($mount_order == 'price_asc'){
                $order = 'v_price asc,t_price asc';
            }
            if($mount_order == 'price_desc'){
                $order = 'v_price desc,t_price desc';
            }
            $order .= ',listingtime desc,ctime desc';
        }

        if(intval($_POST['mount_cid'])){
            $mount_cid = '%,'.intval($_POST['mount_cid']).',%';
            $map ['fullcategorypath'] = ['like',$mount_cid];
        }

        if($_POST['search_key']){
            $search_key = "'%{$_POST['search_key']}%'";
            $map['_string'] = "( `video_title` like {$search_key}) OR ( `video_binfo` like {$search_key}) AND  (is_charge != 1)  AND ( t_price != 0) ";
        }else{
            $map ['_string']     = ' (is_charge != 1)  AND ( t_price != 0) ';
        }

        $map ['is_activity'] = 1;
        $map ['is_del']      = 0;
        $map ['is_mount']    = 1;
//        $map ['type']        = 1;

        if(is_school($this->mid)){
            $map ['mhm_id']      = ['neq',is_school($this->mid)];
        }
        $map ['uctime']      = ['gt',time()];
        $map ['listingtime'] = ['lt',time()];

        $mount_data = M('zy_video')->where($map)->order($order)->field('id,video_title,cover,video_binfo,teacher_id,
        v_price,t_price,mhm_id,type')->findPage(30);


        foreach ($mount_data['data'] as $key => $val){
            $smap['vid'] = $mmap['vid'] = $val['id'];
            $smap['pid'] = array('neq',0);
            $count = M('zy_video_section')->where($smap)->count();
            $mount_data['data'][$key]['sectionNum'] = $count;
            $school_info = model('School')->where('id='.$val['mhm_id'])->field('title,school_and_oschool')->find();
            $mount_data['data'][$key]['school_title'] = $school_info['title'];
            $mount_data['data'][$key]['video_binfo'] =msubstr ($mount_data['data'][$key]['video_binfo'], 0, 85);
            $mount_data['data'][$key]['mount_price'] = round(floatval(explode(':',$school_info['school_and_oschool'])[1]) * $val['t_price'],2);
            $mount_data['data'][$key]['teacher_name'] = M('zy_teacher')->where('id='.$val['teacher_id'])->getField('name');
            $mmap['uid'] = $this->mid;
            $mount_status = M( 'zy_video_mount')->where ( $mmap )->getField('is_activity');
            if($mount_status === '0'){
                $mount_data['data'][$key]['mount_status'] = 1;
            }
            if($mount_status === '1'){
                $mount_data['data'][$key]['mount_status'] = 2;
            }
//            $data ['mzprice'] = getPrice ( $val, $this->mid, true, true );
        }

        if ($mount_data['data']) {
            $this->assign('mount_data',$mount_data);
            $html = $this->fetch('mount_data_list');
        } else {
            $html =
            '<table id="J_course_cl" class="dataTable" aria-describedby="J_course_cl_info" style="width: 960px;">
                                <thead>
                                <tr role="row">
                                    <th class="sorting_disabled text-center" tabindex="0" rowspan="1" colspan="1" style="width: 48px;"><span></span></th>
                                    <th class="sorting_disabled text-left text-width" tabindex="0" rowspan="1" colspan="1" style="width: 670px;"><span></span></th>
                                    <th class="sorting_disabled text-center text-padding" tabindex="0" rowspan="1" colspan="1" style="width: 242px;"><span></span></th>
                                </tr>
                                </thead>
                                <tbody role="alert" aria-live="polite" aria-relevant="all" class="change_mount_tbody"><tr class="odd">
                <td valign="top" colspan="3" class="dataTables_empty">
                    <div id="J_emptyInit">暂无相关课程</div>
                </td>
            </tr></tbody>
                            </table>';
        }

        $guess_you_like ['data'] = $html;

        echo json_encode($guess_you_like);
        exit ();
    }

    public function change_album_mount(){
        $mount_map = t($_POST['mount_map']);
        $mount_order = t($_POST['mount_order']);

        if($mount_map == 'default'){

        }
//        if($mount_map == 'quality'){
//            $map ['is_best'] = 1;
//        }
        if($mount_map == 'time'){
            $order = 'ctime desc';
        }
        if($mount_order == 'price_asc'){
            $order = 'price asc';
        }
        if($mount_order == 'price_desc'){
            $order = 'price desc';
        }

        if($mount_map == 'time' && $mount_order){
            if($mount_order == 'price_asc'){
                $order = 'price asc';
            }
            if($mount_order == 'price_desc'){
                $order = 'price desc';
            }
            $order .= ',ctime desc';
        }

        if(intval($_POST['mount_cid'])){
            $mount_cid = '%,'.intval($_POST['mount_cid']).',%';
            $map ['album_category'] = ['like',$mount_cid];
        }

        if($_POST['search_key']){
            $search_key = "'%{$_POST['search_key']}%'";
            $map['_string'] = "( `album_title` like {$search_key}) OR ( `album_intro` like {$search_key}) AND ( `price` != 0) ";
        }else{
            $map ['_string']     = ' ( `price` != 0) ';
        }

        $map ['status']      = 1;
        $map ['is_mount']    = 1;

        if(is_school($this->mid)){
            $map ['mhm_id']      = ['neq',is_school($this->mid)];
        }

        $mount_data = M('album')->where($map)->order($order)->field('id,mhm_id,album_title,cover,price,album_intro')->findPage(30);
        foreach ($mount_data['data'] as &$value){
            $value['album_title']   = msubstr($value['album_title'],0,20);
            $school_info = model('School')->where('id='.$value['mhm_id'])->field('title,school_and_oschool')->find();
            $value['school_title']  = $school_info['title'];
            $album_video_list = M('album_video_link')->where(['album_id'=>$value['id'],'_string'=>' type = 1 OR type = 2'])->order('id desc')->field('video_id')->select();
            $teacher_inid = M('zy_video')->where(['is_del'=>0,'id'=>['in',implode(',',getSubByKey($album_video_list , 'video_id'))]])->field('teacher_id')->select();
            foreach($teacher_inid as $key => $val){
                $tch_ids[$key] = $val['teacher_id'];
            }
            $it_id = trim(implode(',',array_unique(array_filter($tch_ids))),",");
            $tdata=D('ZyTeacher')->where(array('id'=>array('IN',$it_id)))->field('id,name')->select();
            $value['sectionNum']    = count($album_video_list);
            $value['teacher_name']  = $tdata[0]['name'];
            $value['mount_price'] = round(floatval(explode(':',$school_info['school_and_oschool'])[1]) * $value['price'],2);
            $mmap['aid'] = $value['id'];
            $mmap['uid'] = $this->mid;
            $mount_status = M( 'zy_video_mount')->where ( $mmap )->getField('is_activity');
            if($mount_status === '0'){
                $value['mount_status'] = 1;
            }
            if($mount_status === '1'){
                $value['mount_status'] = 2;
            }
        }

        if ($mount_data['data']) {
            $this->assign('mount_data',$mount_data);
            $html = $this->fetch('album_mount_data_list');
        } else {
            $html =
                '<table id="J_course_cl" class="dataTable" aria-describedby="J_course_cl_info" style="width: 960px;">
                                <thead>
                                <tr role="row">
                                    <th class="sorting_disabled text-center" tabindex="0" rowspan="1" colspan="1" style="width: 48px;"><span></span></th>
                                    <th class="sorting_disabled text-left text-width" tabindex="0" rowspan="1" colspan="1" style="width: 670px;"><span></span></th>
                                    <th class="sorting_disabled text-center text-padding" tabindex="0" rowspan="1" colspan="1" style="width: 242px;"><span></span></th>
                                </tr>
                                </thead>
                                <tbody role="alert" aria-live="polite" aria-relevant="all" class="change_mount_tbody"><tr class="odd">
                <td valign="top" colspan="3" class="dataTables_empty">
                    <div id="J_emptyInit">暂无相关课程</div>
                </td>
            </tr></tbody>
                            </table>';
        }

        $guess_you_like ['data'] = $html;

        echo json_encode($guess_you_like);
        exit ();
    }

    //直播主讲教师id
    protected function teacher($live_type,$id)
    {
        if($live_type == 1){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_zshd')->where($map)->order('startDate asc')->field('speaker_id')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['invalidDate']=array('gt',time());
                $live_data = M('zy_live_zshd')->where($maps)->order('invalidDate asc')->field('speaker_id')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        }elseif ($live_type == 3){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_gh')->where($map)->order('startDate asc')->field('speaker_id')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['endTime']=array('gt',time());
                $live_data = M('zy_live_gh')->where($maps)->order('invalidDate asc')->field('speaker_id')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        } elseif ($live_type == 4){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_cc')->where($map)->order('startDate asc')->field('speaker_id')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['endTime']=array('gt',time());
                $live_data = M('zy_live_cc')->where($maps)->order('invalidDate asc')->field('speaker_id')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        }
        $teacher_name = M('zy_teacher')->where('id='.$speaker_id)->getField('name');
        return $teacher_name;
    }
}

