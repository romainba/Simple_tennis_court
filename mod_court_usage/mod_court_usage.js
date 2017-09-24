
const ERR_INVAL = 1;
const ERR_INTERNAL = 2;

const AJAX_FMT = "JSON";

const debug = true;

var width;

google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(draw_charts);

function draw_charts() {
    draw_chart('chart1',
	       'court-usage',
	       "Utilisation du court pour l'annee 2017",
	       '2017-01-01',
	       '2017-12-31',
	       true,
	       '# reservations');
    draw_chart('chart2',
	       'player-histo',
	       "Histogramme d'utilisation du court par joueur pour l'annee 2017",
	       '2017-01-01',
	       '2017-12-31',
	       false,
	      '# reservations');
}

function draw_chart(elem, type, title, begin, end, isStacked, hTitle) {
    var req = {
	'option' : 'com_ajax',
	'module' : 'court_usage',
	'format' : AJAX_FMT,
	'cmd'    : 'chart',
	'type'   : type,
	'begin'  : begin,
	'end'    : end,
    };

    jQuery.ajax({
	type : 'POST',
	data: req,
	
	success: function(response) {
	    var options = {
		'title': title,
		'width': width,
		'height': 300,
		'isStacked': isStacked,
		'hAxis': { 'title': hTitle },
	    };
	    var data = new google.visualization.arrayToDataTable(response.data);
	    var chart = new google.visualization.ColumnChart(document.getElementById(elem));
            chart.draw(data, options);
	},
	error: function(response) {
	    debug && console.log("ajax failed:");
	    debug && console.log(response);
	    alert("internal error");
	}
    })
}

jQuery(document).ready(function() {

    jQuery(".exportBtn").click(function(event) {
	var a = document.getElementById("exportBegin");
	var b = document.getElementById("exportEnd");
	exportEvent(event.target.id, a.value, b.value);
    });

    width = jQuery("#chart1").css("width");
    jQuery(window).on('resize', function() {
	var w = jQuery("#chart1").css("width");
	if (w != width) {
    	    width = w;
	    draw_charts();
	}
    });
})
		       
function exportEvent(cmd, begin, end)
{
    var req = {
	'option' : 'com_ajax',
	'module' : 'court_usage',
	'cmd'    : cmd,
	'format' : AJAX_FMT,
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
    	    alert("internal error");
	}
    })
}
