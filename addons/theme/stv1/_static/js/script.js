// JavaScript Document
/*index*/

$(function(){

	$(document).ready(function() {
		var js=document.scripts;
		var jsPath;
		for(var i=js.length;i>0;i--){
		 if(js[i-1].src.indexOf("jquery.lazyload.min.js")>-1){
		   jsPath=js[i-1].src.substring(0,js[i-1].src.lastIndexOf("/")+1);
		 }
		}
		$("img.lazyloading").lazyload({effect: "fadeIn",placeholder : jsPath+"/img/transparent.gif"});

		//$('.search_hd li').eq(0).addClass('selected');
		//$('.search_bd .search_combobox').hide().eq(0).show();
		//$('.search_hd li').click(function(){
		//	if(!$(this).hasClass('selected')){
		//		$('.search_hd li').removeClass('selected').eq($(this).index()).addClass('selected');
		//		//$('.search_bd .search_combobox').stop(true,true).hide().eq($(this).index()).show();
		//	}
		//});
		$(".direction a").on("click",function(){
			$("#search_cate").val($(this).attr("attr"));
			var search_cate = $("search_cate").val();
			var opposite = $(this).index();
			$(".direction a").css({"background-color":"#fff"});
			$(".direction a").css({"color":"#5F5A5A"});
			$(".direction a").eq(opposite).css({"background-color":"#02C1D0"});
			$(".direction a").eq(opposite).css({"color":"#fff"});
		});
		$(".direction a").on("click",function(){
			var inputKey = $(this).index();
			if(inputKey==0){
				$(".lookup input").attr('placeholder','请输入您要搜索的课程');
			}else if(inputKey==1){
				$(".lookup input").attr('placeholder','请输入您要搜索的机构');
			}else if(inputKey==2){
				$(".lookup input").attr('placeholder','请输入您要搜索的老师');
			}
		});


		$('.notice-hd li').eq(0).addClass('on');
		$('.notice-item').hide().eq(0).show();
		$('.notice-hd li').hover(function(){
			if(!$(this).hasClass('selected')){
				$(this).addClass("on").siblings().removeClass("on");
				$('.notice-item').stop(true,true).hide().eq($(this).index()).show();
			}
		});

		$('.full-course').hover(function(){
			$(this).addClass('hover');
		},function(){
			$(this).removeClass('hover');
		});
		$('.sider_box_ul > .item').hover(function(){
		   $(this).addClass('hover');
		},function(){
		   $(this).removeClass('hover');
		});
		/*$('.sider_box_ul > .item').hover(function(){
			var eq = $('.sider_box_ul > .item').index(this),				//获取当前滑过是第几个元素
				h = $('.sider_box_ul').offset().top,						//获取当前下拉菜单距离窗口多少像素
				s = $(window).scrollTop(),									//获取游览器滚动了多少高度
				i = $(this).offset().top,									//当前元素滑过距离窗口多少像素
				item = $(this).children('.sider_float').height(),				//下拉菜单子类内容容器的高度
				sort = $('.sider_box_ul').height();						//父类分类列表容器的高度

			/!*if ( item < sort ){												//如果子类的高度小于父类的高度
			 if ( eq == 0 ){
			 $(this).children('.sider_float').css('top', (i-h));
			 } else {
			 $(this).children('.sider_float').css('top', (i-h)+1);
			 }
			 } else {
			 if ( s > h ) {												//判断子类的显示位置，如果滚动的高度大于所有分类列表容器的高度
			 if ( i-s > 0 ){											//则 继续判断当前滑过容器的位置 是否有一半超出窗口一半在窗口内显示的Bug,
			 $(this).children('.sider_float').css('top', (s-h)+2 );
			 } else {
			 $(this).children('.sider_float').css('top', (s-h)-(-(i-s))+2 );
			 }
			 } else {
			 $(this).children('.sider_float').css('top', 3 );
			 }
			 }	*!/

			$(this).addClass('hover');
			$(this).children('.sider_float').css('display','block');
		},function(){
			$(this).removeClass('hover');
			$(this).children('.sider_float').css('display','none');
		});*/

		$('.sider_float > .close').click(function(){
			$(this).parent().parent().removeClass('hover');
			$(this).parent().hide();
		});

		jQuery(".focusBox").hover(function(){ jQuery(this).find(".prev,.next").stop(true,true).fadeTo("show",0.2) },function(){ jQuery(this).find(".prev,.next").fadeOut() });
		jQuery(".focusBox").slide({ mainCell:".pic",effect:"leftLoop", autoPlay:true, delayTime:300});

		jQuery(".focusBox2").slide({ mainCell:".bd ul", effect:"leftLoop", delayTime:800,vis:1,scroll:1,pnLoop:false,interTime:3500, autoPlay:true,trigger:"click",easing:"easeOutCubic" });


		jQuery(".banner").slide({ titCell:".hd ul", mainCell:".bd ul", effect:"fold",  autoPlay:true, autoPage:true,interTime:3500, trigger:"click" });


		$('').hide().eq(0).show();
		$('.ewm-hd li').click(function(){
			if(!$(this).hasClass('on')){
				$('.ewm-hd li').removeClass('on').eq($(this).index()).addClass('on');
				$('.ewm-bd .con').stop(true,true).hide().eq($(this).index()).show();
			}
		});





		$(".price_ara,.price_ara_box").hover(
			function () {
				$(".price_ara_box").stop(true,true).fadeIn(200);
			},
			function () {
				$(".price_ara_box").stop(true,true).fadeOut();
			}

		);

		$(".area_ara,.area_ara_box").hover(
			function () {
				$(".area_ara_box").stop(true,true).fadeIn(200);
			},
			function () {
				$(".area_ara_box").stop(true,true).fadeOut();
			}

		);


		$(document).ready(function(){
			$("[name='select-list'] li:first-child").addClass('selected');
			$("[name='select-box'] .classscon").hide();
			$("[name='select-box'] .classscon:first-child").show();
			$(document).on('mousemove','[name="select-list"] li',function(){
				$(this).addClass('selected').siblings().removeClass('selected');
				$(this).parent().parent().siblings().find('.classscon').stop(true,true).hide().eq($(this).index()).show();
			});
		});


		/* 详简切换通过添加on类名和css控制 */
		$(".ranking_bd li:first-child").addClass('on');
		$(".ranking_bd li").hover(function(){ $(this).addClass("on").siblings().removeClass("on")},function(){ });
		/* Tab切换 */
		$(".rankinglist").slide({ titCell:".ranking_hd li", mainCell:".ranking_bd",autoPlay:false,effect:"left",delayTime:300 });

		jQuery(".picScroll-top").slide({mainCell:".bd ul",autoPage:true,effect:"topLoop",autoPlay:true,vis:2});



		$(".indteacher li").hover(
			function () {
				$(this).children().stop(false,true);
				$(this).children("img").fadeOut("slow");
				$(this).children(".detail").fadeIn("slow");
				$(this).children(".line").css('background-color', '#1292f7');
				$(this).children(".dot").css('background-color', '#1292f7');
				$(this).children().children(".bot").animate({bottom:0},400);
			},
			function () {
				$(this).children().stop(false,true);
				$(this).children("img").fadeIn("slow")
				$(this).children(".detail").fadeOut("slow");
				$(this).children(".line").css('background-color', '#ddd');
				$(this).children(".dot").css('background-color', '#ddd');
				$(this).children().children(".bot").animate({bottom:-15},400);
			}
		);


		$(".select-list li").hover(
			function () {
				$(this).addClass('on');
			},
			function () {
				$(this).removeClass('on');
			}
		);



		jQuery(".institutionsbox").slide({ mainCell:".bd ul", autoPage:true,effect:"leftLoop",autoPlay:true,vis:6 });

		$(function(){
			$('.backtop').hide();
			$(function(){
				$(window).scroll(function(){
					if($(window).scrollTop()>300){
						$('.backtop').fadeIn(300);
					}
					else{$('.backtop').fadeOut(200);}
				});
				$('.backtop').click(function(){

					$('body,html').animate({scrollTop:0},300);
					return false;

				});
			});
		});



		$('.support-online').click(function(){
			if(!$(this).hasClass('on')){
				$(this).addClass('on');
			}
			else{
				$(this).removeClass('on')
			}
		});

		jQuery(".fullSlide").slide({ titCell:".hd ul", mainCell:".bd ul", effect:"fold",  autoPlay:true, autoPage:true, trigger:"click",interTime:3500 });
		jQuery(".TB-focus").slide({ mainCell:".bd ul",effect:"fold",autoPlay:true,delayTime:200 });


		$('.coursemainlist li label').on('click',function(){
			if(!$(this).hasClass('no')){
				$(this).addClass('no');
			}else{
				$(this).removeClass('no');

			}
		});

		$(".livetopbox .ewm").hover(
			function () {
				$('.ewm .ewmbox').fadeIn("slow");
			},
			function () {
				$('.ewm .ewmbox').fadeOut("slow");
			}
		);


		$( ".teachoutline .details tr:nth-child(n+6)" ).hide();
		$('.teachoutline .viewAll').click(function(){
			$(this).hide();
			$( ".teachoutline .details tr:nth-child(n+6)" ).show();
		});


		/* 使用js分组，每6个li放到一个ul里面 */
		jQuery(".multipleColumn .bd li").each(function(i){ jQuery(".multipleColumn .bd li").slice(i*8,i*8+8).wrapAll("<ul></ul>");});

		/* 调用SuperSlide，每次滚动一个ul，相当于每次滚动6个li */
		jQuery(".multipleColumn").slide({titCell:".hd ul",mainCell:".bd .picList",autoPage:true,effect:"leftLoop",autoPlay:true,interTime:4500});



		jQuery(".wrapper-video").slide({ titCell:".smallImg li", mainCell:".bigImg", effect:"fold", autoPlay:false,delayTime:0,});

		//小图左滚动切换
		jQuery(".wrapper-video .smallScroll").slide({ mainCell:"ul",delayTime:0,vis:5,scroll:1,effect:"left",autoPage:false,prevCell:".sPrev",nextCell:".sNext",pnLoop:false });

		$(document).on('click','body',function(){
			$('[name="slt-list"]').removeClass('focus');
			$('[name="slt-list"] .dropdown-menu').css('display','none');
		});

		$(document).on('click','[name="slt-list"] .btn-default',function(){
			$(this).parent().addClass('focus');
			$(this).parent().find('.dropdown-menu').css('display','block');
			return false;
		});

		$(document).on('click','[name="slt-list"] .dropdown-menu li',function(){
			var val = $(this).attr('data-value');
			$(this).addClass('active').siblings().removeClass('active');
			$(this).parent().siblings('.btn-default').find('.txt').text(val);
			$(this).parent().css('display','none');
			$(this).parent().parent().removeClass('focus');
			return false;
		});

		$(document).on('click','.question-conent-list li .answer',function(){
			$(this).addClass('selected').siblings().removeClass('selected');
		});


		//jQuery(".picScroll-left").slide({titCell:".hd ul",mainCell:".bd ul",autoPage:true,effect:"left",autoPlay:true,vis:3});

		jQuery(".left_banner").slide({ mainCell:".bd ul",effect:"fold",autoPlay:true,trigger:"click",delayTime:200 ,interTime:3500});

		jQuery(".raise-hot").slide({ mainCell:".bd ul", effect:"leftLoop", delayTime:800,vis:5,scroll:5,trigger:"click",easing:"easeOutCubic" });

		//jQuery(".scrollbox").slide({titCell:".hd ul",mainCell:".bd .ulWrap",effect:"leftLoop"});


		$(document).on('click','[name="group-list"] li',function(){
			$(this).addClass('selected').siblings().removeClass('selected');
		});

		jQuery(".focusBox3").hover(function(){ jQuery(this).find(".prev,.next").stop(true,true).fadeTo("show",1) },function(){ jQuery(this).find(".prev,.next").fadeOut() });
		jQuery(".focusBox3").slide({ mainCell:".pic",effect:"fold", autoPlay:true, delayTime:600, trigger:"click"});

		$(document).on('click','.class_order_card dt span',function(){
			if(!$('.class_order_card dt').hasClass('open')){
				$('.class_order_card dt').addClass('open').siblings().show(500);
			}else{
				$('.class_order_card dt').removeClass('open').siblings().hide(500);
			}
		});
		$(function() {
			var $div_ul = $(".class_order_hd li");
			$div_ul.click(function () {
				$(this).addClass("on")                  //当前<li>元素颜色
						.siblings().removeClass("on");    //去掉其它同辈<li>元素的颜色

			})
		});



			$('.class_order_bd .con').hide().eq(0).show();
			$('.class_order_hd li').click(function(){
				$('#pay_video_form').find('input:hidden[name="discount_type"]').val($(this).attr('val'));
				if(!$(this).hasClass('on')){
					$('.class_order_hd li').removeClass('on').eq($(this).index()).addClass('on');
					$('.class_order_bd .con').stop(true,true).hide().eq($(this).index()).show();
				}
			});


		$(document).on('click','.class_order_pay dd span',function(){
			$('#pay_video_form').find('input:hidden[name="pay"]').val($(this).attr('val'));
			$(this).addClass('selected').siblings().removeClass('selected');
		});

	});
});