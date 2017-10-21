// JavaScript Document

(function ($) {
	//加载
	$(window).load(function() { 
		$("#status").fadeOut(); 
		$("#preloader").delay(400).fadeOut("slow"); 
	});

	$(document).ready(function() {
		$(function(){
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
		
		
		$(document).on('click','[name="list_dl"] .hd',function(){
			if(!$(this).parent().hasClass('focus')){
				$(this).parent().addClass('focus').siblings('li').removeClass('focus');
			}else{
				$(this).parent().removeClass('focus');
			}
		});
		
		$(document).on('click','[name="group-list"] a,[name="test-radio"] dd',function(){
			$(this).addClass('checked').siblings().removeClass('checked');
		});
		
		$(document).on('click','[name="check-list"] a',function(){
			if(!$(this).hasClass('checked')){
				$(this).addClass('checked');
			}else{
				$(this).removeClass('checked');
			}
		});
		
		
		
		$(document).on('click','.librarylist .down',function(){
			$('.layer-shade,.downbox').show();
		});
		$(document).on('click','.librarylist .downed',function(){
			$('.layer-shade,.downedbox').show();
		});
		$(document).on('click','.layer-btn .cancel',function(){
			$('.layer-shade,.layer-dialog').hide();
		});
		
		
		
		$('.answertop').click(function(){
			$('.answerbot').slideToggle();
		});
		
		$('.test_foot_bot .testdate').click(function(){
			$('.cardbg').toggle();
		});
		
		$(document).on('click','.teacher_nav',function(){
			$('.layer-shade,.teacher_nav_box').show();
		});
		$(document).on('click','.teacher_nav_box a,.layer-shade',function(){
			$('.layer-shade,.teacher_nav_box').hide();
		});
		
		$(document).on('click','.icon-nav',function(){
			$('.nav-shade,.header_nav_box').show();
		});
		$(document).on('click','.header_nav_box a,.nav-shade',function(){
			$('.nav-shade,.header_nav_box').hide();
		});
		
	
	});



}(jQuery));