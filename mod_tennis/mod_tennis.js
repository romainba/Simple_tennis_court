
const RES_TYPE_NONE = 0;
const RES_TYPE_NORMAL = 1;
const RES_TYPE_COURS = 2;
const RES_TYPE_MANIF = 3;

const ERR_INVAL = 1;
const ERR_GUEST = 2;
const ERR_INTERNAL = 3;

const AJAX_FMT = "JSON";

const debug = true;

var width;

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
	async: false,

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


function showCal(cal)
{
    var e = document.getElementById("cal-header");
    e.style.display = cal ? '' : 'none';
    e = document.getElementById("calendar");
    e.style.display = cal ? '' : 'none';
    e = document.getElementById("sel-player");
    e.style.display = cal ? 'none' : '';
    
    cal && showCalendar('currCal', parseInt(width, 10));

    return e;
}

/* For the current user, if date & hour is free then add reservation
 * else remove it.
 */
function reserveReq(resType, player1, player2, date, hour, msgElem, cell)
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

    	success: function(response) {
	    var data = parseInt(response.data);

	    if (isNaN(data)) {
		/* update cell in calendar */
		cell.innerHTML = response.data;
		cell.className = (response.data == "") ?
		    RES_TYPE_CLASS[RES_TYPE_NONE] : RES_TYPE_CLASS[resType];

		if (msgElem)
		    showCal(true);
	    } else {
		/* error */
		if (msgElem == null)
		    message(ERR_NAMES[data]);
		else {
		    msgElem.innerHTML = "Attention: " + ERR_NAMES[data] + ".";
		    msgElem.style.margin = "10px 0px 10px 0px";
		}
	    }
	},

	error: function(response) {
	    debug && console.log("ajax failed:");
	    debug && console.log(response);
	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

function reserveCancel()
{
    var	ret = 0,
	req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'format' : AJAX_FMT,
	'cmd'    : 'reserveCancel',
    };

    debug && console.log("reserveCancel");
    
     jQuery.ajax({
	type   : 'POST',
	data   : req,

    	success: function(response) {
	    var data = parseInt(response.data);
	    if (data)
		message(ERR_NAMES[data]);

	    showCal(true);
	},

	error: function(response) {
	    debug && console.log("ajax failed:");
	    debug && console.log(response);
	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

function showSelPlayer(date, hour, cell)
{
    filldataList();

    var	req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'format' : AJAX_FMT,
	'cmd'    : 'selPlayer',
	'date'   : date,
	'hour'   : hour,
    };

    jQuery.ajax({
	type   : 'POST',
	data   : req,
	dataType: 'json',

    	success: function(response) {
	    var data = response.data;
	    if (!isNaN(parseInt(data)))
		message(ERR_NAMES[data]);
	    else {
		var e = showCal(false);
		e.innerHTML = data;

		jQuery(".player").clearSearch();
		
		jQuery("#reserveBtn").click(function(event) {
		    reserveReq(RES_TYPE_NORMAL,
			       document.getElementById("player1").value,
			       document.getElementById("player2").value,
			       date, hour,
			       document.getElementById("SPmsg"), cell);
		})

		jQuery("#cancelBtn").click(function(event) {
		    reserveCancel();
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
	reserveReq(resType, null, null, date, hour, null, cell);
	return;
    }

    if (cell.innerHTML == '') {

	showSelPlayer(date, hour, cell);

    } else {
	debug && console.log("cancel reservation");

	reserveReq(RES_TYPE_NONE, null, null, date, hour, null, cell);
    }
}

function exportEvent(cmd, begin, end)
{
    var req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'cmd'    : cmd,
	'format' : AJAX_FMT,
	'width'  : null,
	'begin'  : begin,
	'end'    : end
    };

    jQuery.ajax({
	type   : 'POST',
	data : req,

	success: function (response) {
	    var $a = jQuery("<a>");
	    $a.attr("href",response.data.file);
	    $a.attr("download","tclv.xls");
	    jQuery("body").append($a);
	    $a[0].click();
	    $a.remove();
	},

	error: function(response) {
	    debug && console.log("ajax buttonEvent failed");
	    debug && console.log(response);
    	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

function showCalendar(cmd, width)
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
	    var e = document.getElementById("calendar");
	    /* update whole calendar */
	    if (!isNaN(parseInt(response.data)))
		e.innerHTML = ERR_NAMES[response.data];
	    else
		e.innerHTML = response.data;
	    e.style.display = '';

	    jQuery(".exportBtn").click(function(event) {
		a = document.getElementById("exportBegin");
		b = document.getElementById("exportEnd");
		exportEvent(event.target.id, a.value, b.value);
	    })
	},

	error: function(response) {
	    debug && console.log("ajax showCalendar failed");
	    debug && console.log(response);
    	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

function showCalHeader()
{
    var req = {
	'option' : 'com_ajax',
	'module' : 'tennis',
	'cmd'    : 'calHeader',
	'format' : AJAX_FMT,
    };

    jQuery.ajax({
	type   : 'POST',
	data : req,

	success: function (response) {
	    var e = document.getElementById("cal-header");
	    /* update whole calendar */
	    if (!isNaN(parseInt(response.data)))
		w.innerHTML = ERR_NAMES[response.data];
	    else {
		e.innerHTML = response.data;
		
	    }
	    e.style.display = '';

	    jQuery(".weekBtn").click(function(event) {
		showCalendar(event.target.id, width);
	    })
	},

	error: function(response) {
	    debug && console.log("ajax showCalHeader failed");
	    debug && console.log(response);
    	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

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



function detectMob() { 
    return (navigator.userAgent.match(/Android/i)
	    || navigator.userAgent.match(/webOS/i)
	    || navigator.userAgent.match(/iPhone/i)
	    || navigator.userAgent.match(/iPad/i)
	    || navigator.userAgent.match(/iPod/i)
	    || navigator.userAgent.match(/BlackBerry/i)
	    || navigator.userAgent.match(/Windows Phone/i)) != null;
}

/* when document ready, add event */
jQuery(document).ready(function() {
    getStrings();
    width = jQuery("#calendar").css("width");
    showCalHeader();
    showCal(true);

    jQuery(window).on('resize', function() {
	var e = document.getElementById("calendar");
	if (e.style.display == 'none')
	    return;
	var w = jQuery("#calendar").css("width");
	/* check calendar shown */
	if (w != width) {
    	    width = w;
    	    showCalendar('currCal', parseInt(w, 10));
	}
    });
})

