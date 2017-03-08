
/* return true if user is logged in */
function check_req(cmd)
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
function reserve_req(cell, date, hour, partner)
{
    var	req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'format' : 'debug',
	'cmd'    : 'reserve',
	'date'   : date,
	'hour'   : hour,
	'partner': partner
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
	    }
	},
	
	error: function(response) {
	    console.log("ajax failed:");
	    console.log(response);
	}
    })   
}

function reserve_day(date, jour, hour)
{
    var str = 'cell_' + jour + '_' + hour,
	cell = document.getElementById(str),
	logged = (getCookie("joomla_user_state") == "logged_in");

    if (logged == false) {
	message("Please log in first");
    	return;
    }

    if (cell.innerHTML == '') {

	if (check_req('isUserBusy')) {
	    message("You'reached max number of reservation");
	    return;
	}

	var popup = jQuery("#partner");
	popup.modal('show');

	jQuery('.partner').click(function(event) {
	    popup.modal('hide');
	    reserve_req(cell, date, hour, event.target.id);
	    jQuery('.partner').unbind('click');
	});
	
    } else
	reserve_req(cell, date, hour, null);
}

function change_week(cmd)
{
    var req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'cmd'    : cmd,
	'format' : 'debug'
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
	    console.log("change_week error");
	    console.log(response);
	}
    })
}

function addEvent() {
    jQuery(".day").hover(
	function() {
	    jQuery(this).css("background-color", "lightyellow");
	},
	function() {
	    jQuery(this).css("background-color", "white");
	})
    jQuery(".weekBtn").click(function(event) {
	change_week(event.target.id);
    })
}

/* when document ready, add event */
jQuery(document).ready(function() {
    addEvent();
})
