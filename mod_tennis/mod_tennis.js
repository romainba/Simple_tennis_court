
/* For the current user,
 * oper = "add": add reservation if date & hour is not busy
 * oper = "del": delete reservation
 * Database and cell content is updated accordingly.
 */

function reserve_day(cmd, date, jour, hour)
{
    var str = 'cell_' + jour + '_' + hour,
	cell = document.getElementById(str),
	req = {
	    'option' : 'com_ajax',
	    'module' : 'tennis',
	    'cmd'    : cmd,
	    'date'   : date,
	    'hour'   : hour,
	    'format' : 'debug'
	};

    jQuery.ajax({
	type   : 'POST',
	data   : req,

    	success: function (response) {
	    console.log(cmd + ' ' + str);
	    if (response == 0) {
		if (cmd == 'add') {
		    cell.innerHTML = 'reserved';
		}
		else {
		    cell.innerHTML = '';
		}
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

jQuery(document).ready(function() {
    addEvent();
})
