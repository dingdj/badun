<?php
return array(
	/**
	 * 路由的key必须写全称. 比如: 使用'wap/Index/index', 而非'wap'.
	 */
	'router' => array(
		//一级导航
		//'classroom/Index/index'		=> 	SITE_URL.'/',//首页
		//'classroom/Album/index'     => SITE_URL.'/album.html',//套餐
		//'classroom/Video/index'     =>  SITE_URL.'/course.html',//课程
		//'live/Index/index'          =>  SITE_URL.'/live.html',//直播
		//'school/Index/index'        =>  SITE_URL.'/school.html',//机构
		//'classroom/Teacher/index'   =>  SITE_URL.'/teacher.html',//讲师
		//'classroom/Crow/index'   	=>  SITE_URL.'/crow.html',//众筹
		//'mall/Index/index'   		=>  SITE_URL.'/mall.html',//积分商城
		//'wenda/Index/index'         =>  SITE_URL.'/question.html',//问答
		//'exam/Index/index'          =>  SITE_URL.'/exam.html',//考试
		//'group/Index/index'         =>  SITE_URL.'/group.html',//小组
		//'event/Index/index'         =>  SITE_URL.'/event.html',//活动
		//'classroom/Topic/index'     =>  SITE_URL.'/news.html',//资讯
		//'classroom/Mount/index'     =>  SITE_URL.'/mount.html',//分销挂载
		//'classroom/Mount/albumIndex'=>  SITE_URL.'/mount_album.html',//分销挂载
		//'classroom/Library/index'   =>  SITE_URL.'/library.html',//文库
		//'public/ChangeCity/index'   =>  SITE_URL.'/changecity/init.html',//切换城市
		//'classroom/LineClass/index' =>  SITE_URL.'/lineclass.html',//线下课

		//登录/注册
		//'public/Passport/login_g'   =>  SITE_URL.'/login.html',
		//'public/Passport/reg'   	=>  SITE_URL.'/register.html',
		//'home/Repwd/index'   	=>  SITE_URL.'/repwd.html',

		//课程详情
		//'classroom/Video/view'  	=>  SITE_URL.'/course/[id].html',
		//'classroom/Video/view_mount'=>  SITE_URL.'/course/[id]h[mid].html',
//		'classroom/Video/watch'  	=>  SITE_URL.'/course/watch/[id]w[s_id].html',
		//'classroom/Video/watch'  	=>  SITE_URL.'/course/watch/[id].html',
		//线下课
		//'classroom/LineClass/view' 	=>  SITE_URL.'/lineclass/[id].html',

		//直播详情
		//'live/Index/view'  			=>  SITE_URL.'/live/[id].html',
		//'live/Index/view_mount'     =>  SITE_URL.'/live/[id]l[mid].html',
		//'live/Index/watch'  		=>  SITE_URL.'/live/watch/[id].html',
		//'live/Index/live_teacher'  	=>  SITE_URL.'/live/live_teacher/[id].html',

		//机构详情
		//'school/School/index'  		=>  SITE_URL.'/school/[id]_[doadmin].html',

		//套餐详情
		//'classroom/Album/view'  	=>  SITE_URL.'/album/[id].html',
		//'classroom/Album/view_mount'=>  SITE_URL.'/album/[id]l[mid].html',

        //机构详情
       // 'school/User/setInfo'  		=>  SITE_URL.'/school/set_info.html',
       // 'school/School/index'  		=>  SITE_URL.'/school/[id].html',
       // 'school/School/about_us'  	=>  SITE_URL.'/school/about_us/[id].html',
        //'school/School/course'  	=>  SITE_URL.'/school/video_list/[id].html',
		//'school/School/live'  		=>  SITE_URL.'/school/live_list/[id].html',
		//'school/School/album' 		=>  SITE_URL.'/school/album_list/[id].html',
       // 'school/School/teacher_index'=>  SITE_URL.'/school/teacher_list/[id].html',

		//讲师详情
		//'classroom/Teacher/view'  	=>  SITE_URL.'/teacher/[id].html',
		//'classroom/Teacher/video'  	=>  SITE_URL.'/teacher/course/[id].html',
		//'classroom/Teacher/style'  	=>  SITE_URL.'/teacher/photo_album/[id].html',
		//'classroom/Teacher/getPhotoList'=>  SITE_URL.'/teacher/photo_info/[id]_[photo_id].html',
		//'classroom/Teacher/article' =>  SITE_URL.'/teacher/article/[id].html',
		//'classroom/Teacher/checkDeatil'=>  SITE_URL.'/teacher/article_info/[id]_[aid].html',
		//'classroom/Teacher/details' =>  SITE_URL.'/teacher/details/[id].html',
		//'classroom/Teacher/evaluate'=>  SITE_URL.'/teacher/evaluate/[id].html',

		//问答详情
		//'wenda/Index/detail'  		=>  SITE_URL.'/question/[id].html',
		//'wenda/Index/index$'  		=>  SITE_URL.'/question/type/[wdtype].html',
		//'wenda/Index/classifywd'    =>  SITE_URL.'/question/[type]/[tpid]/[wdtype].html',
		//'wenda/Index/addWenda'  	=>  SITE_URL.'/question/add.html',

		//考试详情
		//'exam/Index/exam'  	        =>  SITE_URL.'/exam/[id].html',
		//'exam/UserExam/exam_info'  	=>  SITE_URL.'/exam/report/[exam_id]/[paper_id].html',

		//小组详情
        //'group/Index/addtopic'  	=>  SITE_URL.'/group/addtopic.html',
        //'group/Index/addgroup'  	=>  SITE_URL.'/group/addgroup.html',
       // 'group/Topic/index'  	    =>  SITE_URL.'/group/[gid].html',
        //'group/Index/add'  	        =>  SITE_URL.'/group/add/[fid].html',
        //'group/Index/view'  	    =>  SITE_URL.'/group/view/[gid].html',
      //  'group/Index/detail'  	    =>  SITE_URL.'/group/detail/[id].html',
      //  'group/Manage/index'  	    =>  SITE_URL.'/group/edit/[gid].html',
		//'group/Manage/membermanage' =>  SITE_URL.'/group/member/[gid].html',
		//'group/Topic/topic'  	    =>  SITE_URL.'/group/bbs/[gid]/[tid].html',
	//	'group/Topic/add'  	        =>  SITE_URL.'/group/bbs/add/[gid].html',
		//'group/Topic/edit'  	    =>  SITE_URL.'/group/bbs/edit/[gid]/[tid].html',

		//积分商城
	//	'mall/Goods/index'  	    =>  SITE_URL.'/mall/list.html',
     //   'mall/Goods/view'  	        =>  SITE_URL.'/mall/[id].html',

		//活动详情
	//	'event/Index/index'  	    =>  SITE_URL.'/event/cid/[cid].html',
	//	'event/Index/eventDetail'  	=>  SITE_URL.'/event/[id].html',
	//	'event/Index/addEvent'  	=>  SITE_URL.'/event/add.html',

		//资讯详情
	//	'classroom/Topic/view'  	=>  SITE_URL.'/news/[id].html',

		//管理中心
	//	'classroom/User/index'      => SITE_URL.'/my/index.html',
	//	'classroom/User/setInfo'    => SITE_URL.'/my/set_info.html',
	//	'classroom/Home/video'      => SITE_URL.'/my/course.html',
	//	'classroom/Home/crow'       => SITE_URL.'/my/crow.html',
     //   'classroom/Home/live'       => SITE_URL.'/my/live.html',
    //    'classroom/Home/album'      => SITE_URL.'/my/album.html',
    //    'classroom/Home/share'      => SITE_URL.'/my/share.html',
     //   'classroom/Home/course'     => SITE_URL.'/my/reserve.html',
     //   'classroom/Home/wenda'      => SITE_URL.'/my/question.html',
     //   'classroom/Home/wenti'      => SITE_URL.'/my/put_question.html',
     //   'classroom/Home/review'     => SITE_URL.'/my/comment.html',
     //   'classroom/Home/note'       => SITE_URL.'/my/note.html',
    //    'classroom/Home/follow'     => SITE_URL.'/my/follow.html',
     //   'classroom/Home/collect'    => SITE_URL.'/my/collect.html',
    //    'classroom/Home/learn'      => SITE_URL.'/my/learn.html',
     //   'classroom/Home/teacher_course'=> SITE_URL.'/my/arrange_course.html',
    //    'classroom/User/teacherVideo'  => SITE_URL.'/my/upload.html',
     //   'classroom/User/recharge'   => SITE_URL.'/my/recharge.html',
    //    'classroom/User/credit'     => SITE_URL.'/my/account.html',
    //    'classroom/User/card'       => SITE_URL.'/my/bank_card.html',
    //    'classroom/User/videoCoupon'=> SITE_URL.'/my/card_coupons.html',
    //    'classroom/User/discount'   => SITE_URL.'/my/discount.html',
    //    'classroom/User/vipCard'    => SITE_URL.'/my/vip_card.html',
     //   'classroom/User/rechargeCard'=> SITE_URL.'/my/recharge_card.html',
      //  'classroom/User/exchangeCard'=> SITE_URL.'/my/recardco.html',
     //   'classroom/Home/order'      => SITE_URL.'/my/order.html',
     //   'classroom/User/goodsOrder' => SITE_URL.'/my/conver_flow.html',
     //   'classroom/User/address'    => SITE_URL.'/my/address.html',
	//	'classroom/User/teacherDeatil' => SITE_URL.'/my/teacher_info.html',

        //消息
	//	'public/Message/index'        => SITE_URL.'/message/index.html',
	//	'public/Message/comment'      => SITE_URL.'/message/comment.html',
	//	'public/Message/notify'       => SITE_URL.'/message/notify.html',
	//	'public/Message/detail'       => SITE_URL.'/message/reply/[id]_[type].html',

		//个人首页
	//	'classroom/UserShow/index'  => SITE_URL.'/user/[uid].html',
	//	'classroom/UserShow/wenda'  => SITE_URL.'/user/wenda/[uid].html',
	//	'classroom/UserShow/note'   => SITE_URL.'/user/note/[uid].html',
	//	'classroom/UserShow/fans'   => SITE_URL.'/user/fans/[uid].html',

        //单页
     //   'public/Single/info'        => SITE_URL.'/single/[id].html',
     //   'public/Single/indie'       => SITE_URL.'/indie/[ie].html',

		//app下载
	//	'home/Index/appdownload'    => SITE_URL.'/appdownload.html',

        //微信
	//	'public/Passport/landed'    => SITE_URL.'/landed.html',
	//	'public/Passport/smessage'  => SITE_URL.'/smessage.html',
	//	'classroom/PayVideo/index'		=> SITE_URL.'/pay/[vst].html',

		//点播、直播、套餐购买页
//        'classroom/PayVideo/index'  => SITE_URL.'/pay/[vst].html',

		//支付回调地址



	)
);