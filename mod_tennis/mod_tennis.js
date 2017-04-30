
const RES_TYPE_NONE = 0;
const RES_TYPE_NORMAL = 1;
const RES_TYPE_COURS = 2;
const RES_TYPE_MANIF = 3;

const ERR_INVAL = 1;
const ERR_GUEST = 2;

const AJAX_FMT = "JSON";

const debug = true;

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
	    RES_TYPE_CLASS = response.data[2];
	},

	error: function(response) {
	    debug && console.log("getStrings failed");
	    debug && console.log(response);
	}
    })
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
	height: 100 + h * 11,
	left: jQuery(window).width()/2 - w * 8 /2,
	top: jQuery(window).height()/2 - (100 + h * 11)/2
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
function reserveReq(resType, player1, player2, cell, date, hour, msgElem)
{
    var	ret = 0,
	p1 = (player1 == null) ? null : player1.replace(" ", "_"),
	p2 = (player2 == null) ? null : player2.replace(" ", "_"),
	req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'format' : AJAX_FMT,
	'cmd'    : 'reserve',
	'date'   : date,
	'hour'   : hour,
	'resType': resType,
	'player1': p1,
	'player2': p2
    };

     jQuery.ajax({
	type   : 'POST',
	data   : req,
	dataType: 'json',

    	success: function(response) {
	    data = response.data;
	    if (!isNaN(parseInt(data))) {
		if (msgElem == null)
		    message(ERR_NAMES[data]);
		else {
		    msgElem.innerHTML = ERR_NAMES[data];
		    msgElem.style.margin = "10px 0px 10px 0px";
		}
	    }
	    else {
	    	cell.innerHTML = data;
		if (data == "")
		    resType = 0;
		cell.className = RES_TYPE_CLASS[resType];

		if (msgElem != null)
		    updateCalendar('refreshCal', parseInt(width, 10));
	    }
	},

	error: function(response) {
	    debug && console.log("ajax failed:");
	    debug && console.log(response);
	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

function showSelectPlayers(cell, date, hour)
{
    filldataList();

    var	req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'format' : AJAX_FMT,
	'cmd'    : 'showSelectPlayers',
	'date'   : date,
	'hour'   : hour,
    };

    jQuery.ajax({
	type   : 'POST',
	data   : req,
	dataType: 'json',

    	success: function(response) {
	    data = response.data;
	    if (!isNaN(parseInt(data)))
		message(ERR_NAMES[data]);
	    else {

		/* replace calendar with player selection form */
		var elem = document.getElementById("calendar");
		debug && console.log(data);
		elem.innerHTML = data; 

		jQuery("#reserveBtn").click(function(event) {
		    reserveReq(RES_TYPE_NORMAL,
			       document.getElementById("player1").value,
			       document.getElementById("player2").value,
			       cell, date, hour,
			       document.getElementById("SPmsg"));
		})
		
		jQuery("#cancelBtn").click(function(event) {
		    updateCalendar('refreshCal', parseInt(width, 10));
		})
	    }
	},

	error: function(response) {
	    debug && console.log("ajax failed:");
	    debug && console.log(response);
	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

/* called when cell selected in the calendar */
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
	reserveReq(resType, null, null, cell, date, hour, null);
	return;
    }

    if (cell.innerHTML == '') {
	
	showSelectPlayers(cell, date, hour);

    } else {
	debug && console.log("cancel reservation");

	reserveReq(RES_TYPE_NONE, null, null, cell, date, hour, null);
    }
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
	    debug && console.log("ajax updateCalendar failed");
	    debug && console.log(response);
    	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

var width;

// Fill dataList with all user names
function filldataList()
{
    var dataList = document.getElementById('userlist');
    if (dataList.childNodes.length)
	return;

    var req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'cmd'    : 'getUsersName',
	'format' : AJAX_FMT,
    };
    
    jQuery.ajax({
	type   : 'POST',
	data : req,

	success: function (response) {
	    debug && console.log(response.data);
	    
	    response.data.forEach(function(item) {
		var option = document.createElement('option');
		option.value = item;
		dataList.appendChild(option);
	    });
	},

	error: function(response) {
	    debug && console.log("ajax getUsersName failed");
	    debug && console.log(response);
    	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}


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
