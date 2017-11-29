<?php
/**
 * 首页模块控制器
 * @author dengjb <dengjiebin@higher-edu.cn>
 * @version GJW2.0
 */

tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class IndexAction extends CommonAction
{
	//初始化
	public function _initialize() { 

	}
	public function index() {
		//加载首页头部轮播广告位
        $ad_map = array(
            'is_active' => 1,
            'display_type' => 3,
            'place' => 15
        );
        $ad_list = M('ad')->where($ad_map)->order('display_order DESC')->find();
        //序列化广告内容
        $ad_list = unserialize($ad_list['content']);
        //获取精选班级
        $best_recommend_list = D('Album',"classroom")->getBestRecommend();
        //格式化精选班级 3条带封面 其余标题连接
        $br_list = array(
            'big_list' => array() ,
            'title_list' => array()
        );
        $br_le = 0;
        foreach ($best_recommend_list as $bl) {
            $br_le+= 1;
            if ($br_le <= 4) {
                array_push($br_list['big_list'], $bl);
            } else {
                array_push($br_list['title_list'], $bl);
            }
        }
        //获取畅销榜单
        $get_sell_well_list = D('ZyVideo',"classroom")->getSellWell(4);
        foreach ($get_sell_well_list as & $value) {
            $value['imageurl'] = getAttachUrlByAttachId($value['cover']);
        }
        //购物车
        $vms = D('ZyVideoMerge',"classroom")->getList($this->mid, session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        //加载限时免费
        $map['is_del'] = '0';
        $map['is_activity'] = '1';
        $map['uctime'] = array(
            'GT',
            time()
        );
        $map['listingtime'] = array(
            'LT',
            time()
        );
        $map['limit_discount'] = 0.00;
        $map['is_tlimit'] = '1';
        $map['starttime'] = array(
            'LT',
            time()
        );
        $map['endtime'] = array(
            'GT',
            time()
        );
        $free_limit_list = M('zy_video')->where($map)->order('ctime DESC')->limit(4)->select();
        foreach ($free_limit_list as $key => &$val) {
            if ($val['uid'] == $GLOBALS['ts']['mid']) {
                $val['t_price'] = 0;
            }
            $val['mzprice'] = getPrice ( $val, $this->mid, true, true );
        }
        $time = time();
        //根据销售最佳读取最佳讲师等信息
        $beVideos = M()->query("SELECT zv.`id`, zv.`teacher_id`,zv.`video_title`,zt.`name`,zt.`inro`,zt.`head_id` FROM `".C('DB_PREFIX')."zy_video` zv,`".C('DB_PREFIX')."zy_teacher` zt WHERE zv.teacher_id=zt.id AND zt.id AND zt.is_del=0 and zv.`is_del`=0 AND `is_activity`=1 AND `uctime`>'$time' AND `listingtime`<'$time' and teacher_id >0 GROUP BY `teacher_id` ORDER BY `video_order_count` DESC ,`id` DESC  LIMIT 4");
        if(!$beVideos){
            $beVideos = M()->query("SELECT `id` as teacher_id,`name`,`inro`,`head_id` FROM `".C('DB_PREFIX')."zy_teacher` WHERE is_del=0 and course_count>0 order by `id` DESC  LIMIT 4");
        }
        $this->assign("beTeacher", $beVideos);
        $this->assign("ad_list", $ad_list);
        $this->assign("br_list", $br_list);
        $this->assign("sw_list", $get_sell_well_list);
        $this->assign("free_limit_list", $free_limit_list);
        $this->display();
	}
	

	
		
	
}