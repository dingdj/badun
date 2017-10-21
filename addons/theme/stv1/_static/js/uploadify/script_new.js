// JavaScript Document
/*index*/

$(function(){
	
	$(document).ready(function() {
		
		
		$('.search_hd li').eq(0).addClass('selected');
		$('.search_bd .search_combobox').hide().eq(0).show(); 
		$('.search_hd li').click(function(){
			if(!$(this).hasClass('selected')){
				$('.search_hd li').removeClass('selected').eq($(this).index()).addClass('selected');
				$('.search_bd .search_combobox').stop(true,true).hide().eq($(this).index()).show();
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
		
		/*
		$('.header_sider_nav,.header_sider_menu,.header_sider_box').hover(function(){
			$('.header_sider_box').stop(true,true).fadeIn(200);
			},function(){
			$('.header_sider_box').stop(true,true).delay(500).fadeOut(100);
		});
		*/
		
		
		
		
		$('.header_sider_nav').hover(function(){
			$(this).addClass('hover');
			},function(){
			$(this).removeClass('hover');
		});
		$('.sider_box_ul > .item').hover(function(){
			var eq = $('.sider_box_ul > .item').index(this),				//获取当前滑过是第几个元素
				h = $('.sider_box_ul').offset().top,						//获取当前下拉菜单距离窗口多少像素
				s = $(window).scrollTop(),									//获取游览器滚动了多少高度
				i = $(this).offset().top,									//当前元素滑过距离窗口多少像素
				item = $(this).children('.sider_float').height(),				//下拉菜单子类内容容器的高度
				sort = $('.sider_box_ul').height();						//父类分类列表容器的高度
			
			if ( item < sort ){												//如果子类的高度小于父类的高度
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
			}	

			$(this).addClass('hover');
				$(this).children('.sider_float').css('display','block');
			},function(){
				$(this).removeClass('hover');
				$(this).children('.sider_float').css('display','none');
		});

		$('.sider_float > .close').click(function(){
			$(this).parent().parent().removeClass('hover');
			$(this).parent().hide();
		});
	
		
		/*
		$(".header_sider_box li,.sider_float").hover(
		  function () {
			 $(".sider_float").stop(true,true).fadeIn(200);
			 $('.header_sider_box li').hover(function(){
				if(!$(this).hasClass('on')){
					$('.header_sider_box li').removeClass('on').eq($(this).index()).addClass('on');
					$('.header_sider_box .sider_float_item').stop(true,true).hide().eq($(this).index()).show();
				}
			});
			}, 
		  function () {
			 $(".sider_float").stop(true,true).fadeOut();
			 $('.header_sider_box li').removeClass('on');
		  }
		
		);
		*/
		
		
		
		jQuery(".focusBox").slide({ mainCell:".pic",effect:"leftLoop", autoPlay:true, delayTime:300});
		
		jQuery(".focusBox2").slide({ mainCell:".bd ul", effect:"left", delayTime:800,vis:1,scroll:1,pnLoop:false, autoPlay:true,trigger:"click",easing:"easeOutCubic" });

		$(".price_ara,.price_ara_box").hover(
		  function () {
			 $(".price_ara_box").stop(true,true).fadeIn(200);
			}, 
		  function () {
			 $(".price_ara_box").stop(true,true).fadeOut();
			 //$('.header_sider_box li').removeClass('on');
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
		
		jQuery(".fullSlide").slide({ titCell:".hd ul", mainCell:".bd ul", effect:"fold",  autoPlay:true, autoPage:true, trigger:"click" });

		
		
		
		
		
		
		
		
		
		
	});

});	
	
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		


