
/* For the current user, if date & hour is free then add reservation
 * else remove it.
 */
function reserve_day(date, jour, hour)
{
    var str = 'cell_' + jour + '_' + hour,
	cell = document.getElementById(str),
	req = {
	    'option' : 'com_ajax',
	    'module' : 'tennis',
	    'cmd'    : 'reserve',
	    'date'   : date,
	    'hour'   : hour,
	    'format' : 'debug'
	};

    jQuery.ajax({
	type   : 'POST',
	data   : req,

    	success: function(response) {
	    switch (response) {
	    case "errInval":
		alert("Invalid request");
		break;
	    case "errGuest":
		alert("Please login first");
		break;
	    case "errInternal":
		alert("Server internal error");
		break;
	    case "errBusy":
		alert("Already reserved");
		break;
	    default:
	    	cell.innerHTML = response;
	    }
	},
	
	error: function(response) {
	    console.log(response);
	}
    })
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
	    console.log("change_week error " + response);
	}
    })
}

function addEvent() {
    jQuery(".day").hover(
	function() {
	    jQuery(this).css("background-color", "lightblue");
	},
	function() {
	    jQuery(this).css("background-color", "white");
	})
    jQuery(".prev").click(function() {
	change_week("prev_week");
    })
    jQuery(".next").click(function() {
	change_week("next_week");
    })
}

/* when document ready, add event */
jQuery(document).ready(function() {
    addEvent();
})
