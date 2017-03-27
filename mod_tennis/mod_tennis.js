
const RES_TYPE_NONE = 0;
const RES_TYPE_MEMBER = 1;
const RES_TYPE_GUEST = 2;
const RES_TYPE_COURS = 3;
const RES_TYPE_MANIF = 4;

const ERR_INVAL = 1;
const ERR_GUEST = 2;
const ERR_INTERNAL = 3;
const ERR_BUSY = 4;
const ERR_MAXRESERV = 5;

const AJAX_FMT = "JSON";

function getStrings()
{
    var req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'cmd'    : 'getStrings',
	'format' : AJAX_FMT,
    };

    jQuery.ajax({
	type   : 'POST',
	data : req,
	
	success: function (response) {
	    ERR_NAMES = response.data[0];
	    RES_TYPE = response.data[1];
	    RES_TYPE_CLASS = response.data[2]
	},
	
	error: function(response) {
	    console.log("getStrings failed");
	    console.log(response);
	}
    })

}


/* return true if request is true */
function checkReq(cmd)
{
    var	req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'format' : AJAX_FMT,
	'cmd'    : cmd,
    }, ret = 0;

    jQuery.ajax({
	type   : 'POST',
	data   : req,
	async  : false, 

	success: function(response) {
	    switch (cmd) {
	    case "isUserBusy":
		ret = response.data;
		break;
	    }
	},
	error: function(response) {
	    console.log("ajax failed:");
	    console.log(response);
	    ret = ERR_INTERNAL;
	}
    })
    return ret;
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function message(msg)
{
    var popup = jQuery("#message");
    var w = msg.length;
    var h = (w / 20) >> 0;
    if (h)
	w = 20;
    
    popup.css({
	width: w * 8,
	height: 60 + h * 11,
	left: jQuery(window).width()/2 - w * 8 /2,
	top: jQuery(window).height()/2 - (60 + h * 11)/2
    });
    
    popup.html('<p align="center">' + msg + '</p>' +
	       '<p align="center"><input type="button" value="Ok"></input>');
    popup.modal("show");
	
    jQuery('input').click(function() {
    	popup.modal('hide');
    });
}

/* For the current user, if date & hour is free then add reservation
 * else remove it.
 */
function reserveReq(cell, date, hour, resType)
{
    var	req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'format' : AJAX_FMT,
	'cmd'    : 'reserve',
	'date'   : date,
	'hour'   : hour,
	'resType': resType
    };

    jQuery.ajax({
	type   : 'POST',
	data   : req,

    	success: function(response) {
	    data = response.data;
	    if (!isNaN(parseInt(data)))
		message(ERR_NAMES[data]);
	    else {
	    	cell.innerHTML = data;
		if (data == "")
		    resType = 0;
		cell.className = RES_TYPE_CLASS[resType];
	    }
	},
	
	error: function(response) {
	    console.log("ajax failed:");
	    console.log(response);
	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })   
}

function reserveDay(date, hour)
{
    var str = 'cell_' + date + '_' + hour,
	cell = document.getElementById(str),
	logged = (getCookie("joomla_user_state") == "logged_in"),
	elem = document.getElementById("resTypeList"),
	resType = elem ? elem.value : 0;
	
    if (logged == false) {
	message(ERR_NAMES[ERR_GUEST]);
    	return;
    }

    /* if res_type != 0 then priority over any normal reservation */ 
    if (resType == RES_TYPE_COURS || resType == RES_TYPE_MANIF) {
	reserveReq(cell, date, hour, resType);
	return;
    }
    
    if (cell.innerHTML == '') {

	if (checkReq('isUserBusy')) {
	    message(ERR_NAMES[ERR_MAXRESERV]);
	    return;
	}

	if (resType) {
	    reserveReq(cell, date, hour, resType);
	    return;
	}
	
	var popup = jQuery("#resType");

	popup.css({
	    width: 180,
	    height: 100,
	    left: jQuery(window).width()/2 - 180/2,
	    top: jQuery(window).height()/2 - 100/2
	});

	popup.modal('show');

	jQuery('.resType').click(function(event) {
	    popup.modal('hide');
	    reserveReq(cell, date, hour, event.target.id);
	    jQuery('.resType').unbind('click');
	});
	
    } else
	reserveReq(cell, date, hour, RES_TYPE_NONE);
}

function updateCalendar(cmd, width)
{
    var req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'cmd'    : cmd,
	'format' : AJAX_FMT,
	'width'  : width
    };

    jQuery.ajax({
	type   : 'POST',
	data : req,
	
	success: function (response) {
	    /* update whole calendar */
	    var elem = document.getElementById("calendar");
	    elem.innerHTML = response.data;
	    addEvent();
	},
	
	error: function(response) {
	    console.log("updateCalendar failed");
	    console.log(response);
    	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

var width;

function addEvent() {
    jQuery(".weekBtn").click(function(event) {
	updateCalendar(event.target.id, width);
    })
}

/* when document ready, add event */
jQuery(document).ready(function() {
    getStrings();
    width = jQuery("#calendar").css("width");
    updateCalendar('refreshCal', parseInt(width, 10));
    addEvent();
})

/* when window is resized */
jQuery(window).on('resize', function() {
    var w = jQuery("#calendar").css("width");
    if (w != width) {
	width = w;
	/* to refresh calendar */
	updateCalendar('refreshCal', parseInt(w, 10));
    }
});
