$(".sub-menu").hide();
var isMSIE = (/msie/i.test(navigator.userAgent));
/**
* ie equals one of false|6|7|8|9 values, ie5 is fucked down.
* Based on the method: https://gist.github.com/527683
*/
function getIEVersion() {
    var v = 3, //原作者的此处代码是3，考虑了IE5的情况，我改为4。
          div = document.createElement('div'),
          i = div.getElementsByTagName('i');
    do {
        div.innerHTML = '<!--[if gt IE ' + (++v) + ']><i></i><![endif]-->';
    } while (i[0]);
    return v > 5 ? v : false; //如果不是IE，之前返回undefined，现改为返回false。
}
var ieVersion = getIEVersion();

function getDocHeight(thisDoc, defaultHeight) {
    var docHeight =
          thisDoc.window.document.body.offsetHeight ||
          thisDoc.window.document.body.clientHeight ||
          thisDoc.window.document.body.scrollHeight;
    return (docHeight == 0) ? defaultHeight : docHeight;
}


function SetCwinHeight(iframeObj) {
    if (document.getElementById) {
        if (iframeObj && !window.opera) {
            if (iframeObj.contentDocument && iframeObj.contentDocument.body.offsetHeight) {
                iframeObj.height = iframeObj.contentDocument.body.offsetHeight;
            } else if (document.frames[iframeObj.name].document && document.frames[iframeObj.name].document.body.scrollHeight) {
                iframeObj.height = document.frames[iframeObj.name].document.body.scrollHeight;
            }
        }
    }
}


function hidex(objId) {
    var obj = document.getElementById(objId);
    obj.style.display = "none";
}

function externallinks(evn) {
    if (!document.getElementsByTagName) return;
    var anchors = document.getElementsByTagName("a");
    for (var i = 0; i < anchors.length; i++) {
        var anchor = anchors[i];
        if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "external")
            anchor.target = "_blank";
    }

    evn = evn || window.event;
    var o = evn.target || evn.srcElement;
    var str = "";
    if (o.title != null && o.title != "") {
        str = o.title;
        str = str.replace(/[ ][|][ ]/g, "\n");
        o.title = str;
    }
}
document.onmouseover = externallinks;

function converToTable(panel) {
    $(panel + " label").wrap("<th></th>");
    $(panel + " :text").wrap("<td></td>");
    $(panel + " select").wrap("<td></td>");
    $(panel + " :checkbox").wrap("<td></td>");
    $(panel + " :submit").wrap("<td colspan=\"2\" align=\"center\"></td>");
    $(panel + " th").addClass("txtr");
    $(panel + " div").replaceWith(function () {
        var txt = $(panel + " div").html();
        return "<tr>" + txt + "</tr>";
    });
    $(panel).wrapInner("<table></table>");
    //alert($(panel).html());
}


$(document).ready(function () {
    if ($('.sidebar-menu').length > 0) InitMenuEffects();
});


/* *********************************************************************
* Main Menu
* *********************************************************************/
function InitMenuEffects() {
    /* Sliding submenus */
    $('.sidebar-menu ul ul').hide();
    $('.sidebar-menu ul li.active ul').show();
    //
    var dispIndex = $.localStorage.getItem('MenuBoxIndex');

    if (dispIndex != null) {
        var current = $('.sidebar-menu li.ui-mainmenu-extend:eq(' + dispIndex + ')');
        current.find("a").addClass("menu-expanded");
        current.find("ul").show();
        var dispAIndex = parseInt($.localStorage.getItem('MenuBoxAIndex')) + 1;
        $('.sidebar-menu li.ui-mainmenu-extend a.menu-expanded:eq(' + dispAIndex + ')').addClass("selected");
    };

    $('.sidebar-menu ul li a').click(function () {
        $(".sidebar-menu .ui-mainmenu-extend a").removeClass("menu-expanded");

        var self = $(this);
        $.localStorage.setItem("MenuBoxAIndex", $(this).parent().index());

        var nextElement = $(this).next();
        if ((nextElement.is('ul')) && (nextElement.is(':visible'))) {
            $('.ui-mainmenu-extend ul:visible').slideUp(150);
            return false;
        }
        if ((nextElement.is('ul')) && (!nextElement.is(':visible'))) {
            $('.ui-mainmenu-extend ul:visible').slideUp(150);
            nextElement.slideDown(200);
            //
            $.localStorage.setItem("MenuBoxIndex", $(this).parent().index(), 24 * 3600000);
            self.addClass("menu-expanded");
            return false;
        }
    });

    /* Hover effect on links */
    //  $('.sidebar-menu li a').hover(
    //		function () { $(this).stop().animate({ 'paddingLeft': '18px' }, 200); },
    //		function () { $(this).stop().animate({ 'paddingLeft': '12px' }, 200); }
    //	)
}

/** 
jquery localStorage插件
来源：http://www.stoimen.com/blog/2010/02/26/jquery-localstorage-plugin-alpha/
**/
(function (jQuery) {

    var supported = true;
    if (typeof localStorage == 'undefined' || typeof JSON == 'undefined')
        supported = false;
    else
        var ls = localStorage;

    this.setItem = function (key, value, lifetime) {
        if (!supported)
            return false;

        ls.setItem(key, JSON.stringify(value));
        var time = new Date();
        ls.setItem('meta_ct_' + key, time.getTime());
        ls.setItem('meta_lt_' + key, lifetime);
    };

    this.getItem = function (key) {
        var time = new Date();
        if (!supported || time.getTime() - ls.getItem('meta_ct_' + key) > ls.getItem('meta_lt_' + key))
            return null;
        return JSON.parse(ls.getItem(key));
    };

    this.removeItem = function (key) {
        return supported && ls.removeItem(key);
    };

    jQuery.localStorage = this;

})(jQuery);



function rGet(cqstr, name) {
    if (cqstr == '') cqstr = location.search;
    name += '=';
    var value = cqstr.replace('?' + name, '&' + name);
    name = '&' + name;
    if (value.indexOf(name) > -1) {
        value = value.substring(value.indexOf(name) + name.length);
        if (value.indexOf('&') > -1) value = value.substring(0, value.indexOf('&'));
    } else value = '';
    return value;
}

String.prototype.replaceAll = function (s1, s2) {
    return this.replace(new RegExp(s1, "gm"), s2);
}

var curl = top.location.toString();
if (curl.indexOf("&rn=") > 0) curl = curl.substring(0, curl.indexOf("&rn="));

jQuery.mySlider = {
    build: function (options) {
        if (this.length > 0)
            return jQuery(this).each(function (i) {
                var
                        $this = $(this),
                        bgSlider = jQuery('<div>', { id: 'bgSlider' }),
                        Slider = jQuery('<div>', { id: 'Slider' }),
                        Icons = jQuery('<div>', { id: 'Icons' });
                Txts = jQuery('<div>', { id: 'Txts' });

                bgSlider.appendTo($this);
                Txts.insertAfter(bgSlider);
                Icons.insertAfter(bgSlider);
                Slider.appendTo(bgSlider);
                $this.show();
                Txts.text(options.txt);

                Slider.draggable({
                    revert: function () {
                        if (parseInt(Slider.css("left")) > 150) return false;
                        else return true;
                    },
                    containment: bgSlider,
                    axis: 'x',
                    stop: function (event, ui) {
                        if (ui.position.left > 150) {
                            Icons.css('background-position', '-16px 0');
                            Txts.css("color", "#007B09");
                            setTimeout(options.success, 1000);
                        }
                    }
                });
            });
    }
}; jQuery.fn.mySlider = jQuery.mySlider.build;


function getUnixTime(time) {
    var yt = new Date(time * 1000);
    var nt = yt.getFullYear() + '-' + (yt.getMonth() + 1) + '-' + yt.getDate() + ' ' + yt.getHours() + ':' + yt.getMinutes() + ':' + yt.getSeconds();
    return nt;
}

if (isMSIE) {
    if (ieVersion == 6) {
        document.write("<link href=\"/Css/ie6fix.css\" type=\"text/css\" rel=\"stylesheet\" />");
    }
}

$(document).on("click", ".ui-boxchecker", function () {
    var self = $(this);
    var ctrl = self.children("input[type='checkbox']");
    ctrl.click();

    var ctrl = self.children("input[type='radio']");
    ctrl.prop("checked", "checked");

    var name = ctrl.attr("name");

    var allCtrls = $("input[name='" + name + "']");
    allCtrls.parent().removeClass("ui-boxchecker-checked");

    if (ctrl.prop("checked")) {
        self.addClass("ui-boxchecker-checked");
    }
});

/*
$(document).on("change", ".ui-boxchecker input[type='checkbox']", function () {
var self = $(this);
var parent = self.parent();

parent.removeClass("ui-boxchecker-checked");

if (self.prop("checked")) {
parent.addClass("ui-boxchecker-checked");
}
});
*/

$(document).ready(function () {
    $(".ui-boxchecker :checked").click();
});



