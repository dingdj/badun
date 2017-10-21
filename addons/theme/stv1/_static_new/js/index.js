$(function(){
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

		$("[name='select-list'] li:first-child").addClass("classify-cla");
		$("[name='select-list'] li:first-child").css("color","#00BED4");
		$("[name='select-box'] .compostion-books-box").hide();
		$("[name='select-box'] .compostion-books-box:first-child").show();
		$(document).on("mouseover",'[name="select-list"] li',function(){
			$(this).addClass('classify-cla').siblings().removeClass('classify-cla');
			$(this).css("color","#00BED4").siblings().css("color","#626262");
			$(this).parent().parent().parent().parent().siblings().find('.compostion-books-box').stop(true,true).hide().eq($(this).index()).show();
		})

 		var mySwiper = new Swiper ('.swiper-container', {
		   //竖着滚动 direction: 'vertical',
		    loop: true,
		    autoplay: 5000,
		    autoplayDisableOnInteraction : true,
		    
		    //分页器
		    paginationClickable :true,
		    pagination: '.swiper-pagination',
		    
		    //前进后退按钮
		    nextButton: '.swiper-button-next',
		    prevButton: '.swiper-button-prev',  
		}) 
		$(".swiper-slide").addClass("swiper-no-swiping"); 

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

		/* 原理：移动的是装有所有图片的ul*/
    /*ul向左移动，出现下一张图片，然后移除第一张图片并克隆第一张图片加切加在ul后面，重置ul的marginLeft*/
		   function showNextT(time){
		       $(".mechanism-mec-box").animate({marginLeft:"-170px"},time,"linear",function(){
		           $(".mechanism-mec-box a:first").remove().clone().appendTo(".mechanism-mec-box");
		           $(this).css("marginLeft","11px");/*清除了第一张，则第二张会自动向前移动变为第一张,第三张占领第二张的位置，如果不恢复ul的marginleft为0，则会显示第三张图片*/
		       });
		   }
		  function showNext(){
		    showNextT(200);
		  }

		/*   var timer;
	    timer=setInterval(showNext,4000);
	    //鼠标悬停，停止轮播
	    $(".mechanism-box").hover(function(){
	        clearInterval(timer);
	    },function(){
	        timer=setInterval(showNext,4000);
	    });*/

	    /*图片右移效果函数*/
	   $(".picScroll-left").slide({
	   		titCell:".hd ul",
	   		mainCell:".bd ul",
	   		autoPage:true,
	   		effect:"left",
	   		autoPlay:true,
	   		vis:6,
	   		trigger:"click"
	   	});

	    $(".news-other span").on('mouseover',function(){
			var newsPos = $(this).index();
			$(".news-other-content").hide();
			$(".news-other-content").eq(newsPos).show();
			$('.news-other span').css("border-bottom","1px solid #A2A79C");
			$('.news-other span').eq(newsPos).css("border-bottom","1px solid #6DD9DE");
		});

		var imgList = [],  // 页面所有img元素集合  
		    delay,   // setTimeout 对象  
		    offset,  //偏移量，用于指定图片距离可视区域多少距离，进行加载  
		    time,  // 延迟载入时间  
		    _selector; // 选择器 默认为 .m-lazyload  
		function _isShow(el) {  
		    var coords = el.getBoundingClientRect();  
		    return ( (coords.top >= 0 && coords.left >= 0 && coords.top) <= (window.innerHeight || document.documentElement.clientHeight) + parseInt(offset));  
		}  
		function _loadImage() {  
		    for (var i = imgList.length; i--;) {  
		        var el = imgList[i];  
		        if (_isShow(el)) {  
		        el.src = el.getAttribute('data-src');  
		        el.className = el.className.replace(new RegExp("(\\s|^)" + _selector.substring(1, _selector.length) + "(\\s|$)"), " ");  
		        imgList.splice(i, 1);  
		    }  
		}  
	}  
		function _delay() {  
		    clearTimeout(delay);  
		    delay = setTimeout(function () {  
		      _loadImage();  
		    }, time);  
		}  
		function ImageLazyload(selector, options) {  
		    var defaults = options || {};  
		    offset = defaults.offset || 0;  
		    time = defaults.time || 250;  
		    _selector = selector || '.m-lazyload';  
		    this.getNode();  
		    _delay();//避免首次加载未触发touch事件,主动触发一次加载函数  
		    if (defaults.iScroll) {  
		      defaults.iScroll.on('scroll', _delay);  
		      defaults.iScroll.on('scrollEnd', _delay);  
		    } else {  
		      window.addEventListener('scroll', _delay, false);  
		    }  
		}  
		ImageLazyload.prototype.getNode = function () {  
		    imgList = [];  
		    var nodes = document.querySelectorAll(_selector);  
		    for (var i = 0, l = nodes.length; i < l; i++) {  
		      imgList.push(nodes[i]);  
		    }  
		};  
	});
    
	$(".modular").on("click",function(){
			$(".win").animate({marginLeft:'0'});
		});
	$(".window-shadow").on("click",function(){
		$(".win").animate({marginLeft:'-100%'});
	});


