
const RES_TYPE_NONE = 0;
const RES_TYPE_MEMBER = 1;
const RES_TYPE_GUEST = 2;
const RES_TYPE_COURS = 3;
const RES_TYPE_MANIF = 4;

/* return true if request is true */
function checkReq(cmd)
{
    var	req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'format' : 'debug',
	'cmd'    : cmd,
    }, ret = 0;

    jQuery.ajax({
	type   : 'POST',
	data   : req,
	async  : false, 

	success: function(response) {
	    switch (cmd) {
	    case "isUserBusy":
		if (response == "errMaxReserv")
		    ret = 1;
		break;
	    }
	},
	error: function(response) {
	    console.log("ajax failed:");
	    console.log(response);
	    ret = -1;
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
	       '<p align="center"><input type="button" value="close"></input>');
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
	'format' : 'debug',
	'cmd'    : 'reserve',
	'date'   : date,
	'hour'   : hour,
	'resType': resType
    };

    jQuery.ajax({
	type   : 'POST',
	data   : req,

    	success: function(response) {
	    switch (response) {
	    case "errInval":
		message("Invalid request");
		break;
	    case "errGuest":
		message("Please login first");
		break;
	    case "errInternal":
		message("Server internal error");
		break;
	    case "errBusy":
		message("Already reserved");
		break;
	    case "errMaxReserv":
		message("Max reservation number reached");
		break;
	    default:
	    	cell.innerHTML = response;
		cell.className = (response == '') ? "day" : "day-busy";
	    }
	},
	
	error: function(response) {
	    console.log("ajax failed:");
	    console.log(response);
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
	message("Please log in first");
    	return;
    }

    /* if res_type != 0 then priority over any normal reservation */ 
    if (resType == RES_TYPE_COURS || resType == RES_TYPE_MANIF) {
	reserveReq(cell, date, hour, resType);
	return;
    }
    
    if (cell.innerHTML == '') {

	if (checkReq('isUserBusy')) {
	    message("You'reached max number of reservation");
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
	'format' : 'debug',
	'width'  : width
    };

    jQuery.ajax({
	type   : 'POST',
	data : req,
	
	success: function (response) {
	    /* update whole calendar */
	    var elem = document.getElementById("calendar");
	    elem.innerHTML = response;
	    addEvent();
	},
	
	error: function(response) {
	    console.log("updateCalendar failed");
	    console.log(response);
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
