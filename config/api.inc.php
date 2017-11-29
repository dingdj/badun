<?php
/*
 * 游客访问的黑/白名单，不需要开放的，可以注释掉
 * 此处只配置不能后台修改的项目
 */
return array(
	"access" => array(
		'Oauth/*'        	     => true,
		'Attach/*' 			     => true,
		'Login/*' 			     => true,
		'Teacher/*' 		     => true,
		'Public/*' 		     => true,

		'Album/getAlbumList' 	 => true,
		'Album/albumView' 		 => true,
		'Album/albumprices' 	 => true,
		'Album/getCatalog' 		 => true,
		'Album/getAlbumTag' 	 => true,

		'Video/videoList' 		 => true,
		'Video/videoInfo' 		 => true,
		'Video/getAttrImage' 	 => true,
		'Video/getListCount' 	 => true,
		'Video/getVideoGroup' 	 => true,
		'Video/questionDetail' 	 => true,
		'Video/strSearch' 		 => true,
		'Video/tagSearch' 		 => true,
		'Video/render' 			 => true,
		'Video/getFreeTime' 	 => true,
		'Video/getCatalog' 	 	 => true,
        'Video/screen' 	         => true,
		'Video/lineClassList' 	 => true,
		'Video/lineClassInfo' 	 => true,

		'Wenda/getCate' 	     => true,
		'Wenda/getWendaList' 	 => true,
		'Wenda/getWendaByCourse' => true,
		'Wenda/sevendayHot' 	 => true,
		'Wenda/tagSearch' 		 => true,
		'Wenda/strSearch' 		 => true,
		'Wenda/detail' 			 => true,
		'Wenda/wendaComment' 	 => true,
		'Wenda/wendaCommentDesc' => true,
		'Wenda/getSonComment'    => true,
        
        //小组
        'Group/getList'			 => true,//小组列表
		'Group/getGroupTopList'	 => true,//小组话题列表
		'Group/getGroupMember'	 => true,//小组成员列表
        'Group/getGroupCate'     => true,//小组分类
        'Group/getGroupInfo'     => true,//小组详情
        //积分商品
        'Goods/getGoodsList'	 => true,//积分商城列表
		'Goods/getGoodsCate'	 => true,//积分商品分类
        'Goods/index'	         => true,//积分商城首页
        'Goods/getDetail'        => true,//积分商品详情
        //机构
        'School/getSchoolList'	 => true,//机构列表
		'School/getSchoolInfo'	 => true,//机构详情
        'School/getArrange'      => true,//排课
        'School/addViewCount'    => true,//添加机构浏览量
        'School/getMonthsCourseCount' => true,// 获取机构指定月份的每天排课量
        'School/getCouponList'   => true,//获取机构优惠券列表
        'School/getOrderInfo'    => true,//机构的订单
        //文库
        'Doc/getDocList'		 => true,//文库列表
        'Doc/getDocCategory'     => true,//文库分类
        //直播
        'Live/getLiveList'       => true,//直播列表
        'Live/getDetail'         => true,//直播详情
        'Live/getLiveByTimespan' => true,//获取时间段内的直播
        'Live/getLiveTeachers'	 => true,//直播列表赛选中间讲师数据
        //通用
        'Home/feedback'			 => true,//意见反馈
		'Home/getCateList'		 => true,//分类列表
        'Home/getAdvert'         => true,//获取广告位
        //用户
		'User/addCard'           => true,
		'News/*'                 => true,
		'Exam/*'                 => true,
        //首页
		'Home/index'             => true,//获取首页
        'Home/getRecCateList'    => true,//获取推荐的分类
        'Home/search'            => true,//全站搜索
        'Home/getArea'           => true,//获取地区
		'Home/getHotKeyword'     => true,//获取搜索关键字
		'Home/indexAllReCate'    => true,//获取所有分类
		'Home/indexReTeacher'    => true,//获取推荐讲师
		'Home/indexReSchool'     => true,//获取推荐机构
		'Home/indexNewLive'      => true,//获取最新直播
		'Home/indexNewCourse'    => true,//获取最新课程
		'Home/indexHotCourse'    => true,//获取推荐课程

		'Exams/getPaperList' => true,
		'Exams/getExamsMoudles' => true,
		'Exams/getSubjectCategory' => true,

	) 
);