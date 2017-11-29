<?php

/**
 * Eduline课堂会员中心控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class VipAction extends CommonAction
{
    /**
     * Eduline课堂会员中心方法
     * @return void
     */
    public function index() {

        //加载首页头部轮播广告位
        $ad_map = array('is_active' => 1,'display_type' => 3,'place' => 24);
        $ad_list = M('ad')->where($ad_map)->order('display_order DESC')->find();
        //序列化广告内容
        $ad_list = unserialize($ad_list['content']);
        $ad_list[0]['banner'] = getImageUrlByAttachId($ad_list[0]['banner']);
        $this->assign('ad_list',$ad_list);

        //获取当前用户的会员信息
        $userInfo = D('ZyLearnc')->getUserVip($this->mid);
        $userInfo['exp_date'] = ceil(($userInfo['vip_expire'] - time())/(3600*24));

        //获取所有会员类型
        $vipInfo = $vipInfo = M('user_vip')->where('is_del=0')->order('sort ASC')->findAll();
        //获取所有会员课程
        foreach($vipInfo as $key=>$value){
            $vipInfo[$key]['vip_course'] = $this->vipCourse($value['id']);
        }

        //最新会员
        $new_vip = M('zy_learncoin')->where(['vip_type'=>['neq',0],'vip_expire'=>['egt',time()]])->order('ctime desc')->field('uid')->findAll();

        $this->assign('user',$userInfo);
        $this->assign('vipInfo',$vipInfo);
        $this->assign('new_vip',$new_vip);
        $this->display();
    }

    //获取会员课程
    public function vipCourse($vip_level){
        $time = time();
        $order = '';
        $where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time AND vip_level = $vip_level";
        $limit = 8;
        $data = M('zy_video')->where($where)->order($order)->limit($limit)->field("id,uid,video_title,cover,
        mhm_id,teacher_id,v_price,t_price,vip_level,type,endtime,starttime,limit_discount,uid,teacher_id,view_nums")->select();
        foreach ($data as &$value) {
            $value['price']       = getPrice($value, $this->mid); // 计算价格
            $value['imageurl']    = getCover($value['cover'], 280, 160);
            $value['video_score'] = round($value['video_score'] / 20); // 四舍五入
            $value['buy_count']     = (int) D('ZyOrderCourse', 'classroom')->where(array('video_id' => $value['id']))->count();
            $value['section_count'] = (int) M('zy_video_section')->where(['vid' => $value['id'], 'pid' => ['neq', 0]])->count();
            //教师头像和简介
            $teacher               = M('zy_teacher')->where(array('id' => $value['teacher_id']))->field('name,head_id')->find();
            $value['teacher_name'] = $teacher['name'];
            $value['head_id']      = $teacher['head_id'];
            //机构域名
            $mhm_info = model('School')->where('id = ' . $value['mhm_id'])->field('id,title,doadmin')->find();
            $value['mhm_title']  = $mhm_info['title'];
            if ($mhm_info['doadmin']) {
                $value['domain'] = getDomain($mhm_info['doadmin']);
            } else {
                $value['domain'] = U('school/School/index', array('id' => $mhm_info['id']));
            }
        }
        return $data;
    }

    //获取会员购买信息
    public function rechargeInfo(){
        $vip = intval($_POST['vip']);
        $type = intval($_POST['type']);

        $map['is_del'] = 0;
        $field = 'id,title,vip_month,vip_year';
        if($type == 1){
            $map['id'] = $vip;
            $vipInfo = $vipInfo = M('user_vip')->where($map)->field($field)->order('sort ASC')->find();
        }else{
            $sort = $vipInfo = M('user_vip')->where('id='.$vip)->getField('sort');
            if($vip){
                $map['sort'] = ['gt',$sort];
            }
            $vipInfo = $vipInfo = M('user_vip')->where($map)->field($field)->order('sort ASC')->findAll();
        }

        echo json_encode($vipInfo);
        exit();
    }

    //获取会员信息
    public function getVipInfo(){
        $vip = intval($_POST['vip']);
        $map['is_del'] = 0;
        $map['id'] = $vip;
        $field = 'id,title,vip_month,vip_year';
        $vipInfo = $vipInfo = M('user_vip')->where($map)->field($field)->find();

        echo json_encode($vipInfo);
        exit();
    }

}
