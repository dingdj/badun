
$(function(){
    $(document).bind("keydown",function(e){   
        e=window.event||e;
        if(e.keyCode==116 || e.keyCode == 123){
            e.keyCode = 0;
            return false; //屏蔽F5刷新键,F12审查元素
        }
    });

    // 禁用右键菜单
	document.oncontextmenu = function(){
	    return false;
	}
	// 禁用网页上选取的内容
	document.onselectstart = function(){
	    return false;
	}
	// 禁止复制
	document.oncopy = function(){
	    return false;
	}
});
