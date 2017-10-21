// JavaScript Document

(function ($) {
	//加载
	$(window).load(function() { 
		$("#status").fadeOut(); 
		$("#preloader").delay(400).fadeOut("slow"); 
	});

	$(document).ready(function() {
		
		$('.nav_btn').click(function(){
			if(!$('.nav_btn').hasClass('open')){
				$('.nav_btn').addClass('open');
				$('.overbox').stop(true,true).fadeIn();
				$('.topmenu').stop(true,true).slideDown(200);
			}else{
				$('.nav_btn').removeClass('open');
				$('.topmenu').stop(true,true).delay(100).slideUp(200);
				$('.overbox').stop(true,true).fadeOut();
			}
		});
				
		$('.overbox').click(function(){
			$('.nav_btn').removeClass('open');
			$('.picture_pop').removeClass('up');
			$('.topmenu').stop(true,true).delay(100).slideUp(200);
			$('.overbox').stop(true,true).fadeOut();
		});
		
		
		$('.picture_btn').click(function(){
			$('.picture_pop').addClass('up');
			$('.overbox').stop(true,true).fadeIn();
		});
		
		$('.picture_cancel').click(function(){
			$('.picture_pop').removeClass('up');
			$('.overbox').stop(true,true).fadeOut();
		});
		
		
		
		var D_width=$(".ind-list li").width();
		$(".ind-list li,.ind-list li.mx img").height(D_width);
		
		
		$('.nav_tit').click(function(){
			if(!$('.nav-s').hasClass('open')){
				$('.nav-s').addClass('open');
				$('.nav_ul').stop(true,true).slideDown(200);
			}else{
				$('.nav-s').removeClass('open');
				$('.nav_ul').stop(true,true).slideUp(200);
			}
		})
		
		
		$('.type_hd li').click(function(){
			if(!$(this).hasClass('on')){
				$('.type_hd li').removeClass('on').eq($(this).index()).addClass('on');
				$('.type_bd').stop(true,true).hide().eq($(this).index()).show();
			}
		});
		
		$('.teacher_hd li').click(function(){
			if(!$(this).hasClass('on')){
				$('.teacher_hd li').removeClass('on').eq($(this).index()).addClass('on');
				$('.teacher_bd').stop(true,true).hide().eq($(this).index()).show();
			}
		});
		
		$('.login_hd li').click(function(){
			if(!$(this).hasClass('on')){
				$('.login_hd li').removeClass('on').eq($(this).index()).addClass('on');
				$('.login_bd').stop(true,true).hide().eq($(this).index()).show();
			}
		});
		
		
		$(document).on('click','[name="group-list"] label',function(){
			$(this).addClass('selected').siblings().removeClass('selected');
		});
		
		
		$(function(){
			$('[name="multi-select"] label').click(
			function(){
				if($(this).hasClass('selected')){
				$(this).removeClass('selected');
				}else{
					$(this).addClass('selected');
				}
			});
		});
		
		var D_width=$(".management_time label").width();
		$(".management_time label").css('height',D_width).css('line-height',D_width + 'px');

	
	});



}(jQuery));