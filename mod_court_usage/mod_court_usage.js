
const ERR_INVAL = 1;
const ERR_INTERNAL = 2;

const AJAX_FMT = "JSON";

var width;

google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(draw_charts);

function draw_charts() {
    usersYearStatus('stats2021','2021-01-01','2021-12-31', 1);
    draw_chart('chart2021a',
	       'court-usage',
	       "Utilisation du court pour l'annee 2021",
	       '2021-01-01',
	       '2021-12-31',
	       true,
 	       '# reservations');
    draw_chart('chart2021b',
	       'player-histo',
	       "Histogramme d'utilisation du court par joueur pour l'annee 2021",
	       '2021-01-01',
	       '2021-12-31',
	       false,
	       '# reservations');
    usersYearStatus('stats2020','2020-01-01','2020-12-31', 1);
    draw_chart('chart2020a',
	       'court-usage',
	       "Utilisation du court pour l'annee 2020",
	       '2020-01-01',
	       '2020-12-31',
	       true,
 	       '# reservations');
    draw_chart('chart2020b',
	       'player-histo',
	       "Histogramme d'utilisation du court par joueur pour l'annee 2020",
	       '2020-01-01',
	       '2020-12-31',
	       false,
	       '# reservations');
    usersYearStatus('stats2019','2019-01-01','2019-12-31', 1);
    draw_chart('chart2019a',
		'court-usage',
	       "Utilisation du court pour l'annee 2019",
	       '2019-01-01',
	       '2019-12-31',
	       true,
	       '# reservations');
    draw_chart('chart2019b',
	       'player-histo',
	       "Histogramme d'utilisation du court par joueur pour l'annee 2019",
	       '2019-01-01',
	       '2019-12-31',
	       false,
	      '# reservations');
    usersYearStatus('stats2018','2018-01-01','2018-12-31', 1);   
    draw_chart('chart2018a',
	       'court-usage',
	       "Utilisation du court pour l'annee 2018",
	       '2018-01-01',
	       '2018-12-31',
	       true,
	       '# reservations');
    draw_chart('chart2018b',
	       'player-histo',
	       "Histogramme d'utilisation du court par joueur pour l'annee 2018",
	       '2018-01-01',
	       '2018-12-31',
	       false,
	      '# reservations');
    usersYearStatus('stats2017','2017-01-01','2017-12-31', 0);   
    draw_chart('chart2017a',
	       'court-usage',
	       "Utilisation du court pour l'annee 2017",
	       '2017-01-01',
	       '2017-12-31',
	       true,
	       '# reservations');
    draw_chart('chart2017b',
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
		'backgroundColor': { 'fill': 'transparent' },
	    };
	    var data = new google.visualization.arrayToDataTable(response.data);
	    var chart = new google.visualization.ColumnChart(document.getElementById(elem));
            chart.draw(data, options);
	},
	error: function(response) {
	    alert("internal error");
	}
    })
}

function usersStatus(elem) {
    var req = {
	'option' : 'com_ajax',
	'module' : 'court_usage',
	'format' : AJAX_FMT,
	'cmd'    : 'usersStatus',
    };

    jQuery.ajax({
	type : 'POST',
	data: req,
	
	success: function(response) {
	    var cell = document.getElementById(elem);
	    cell.innerHTML = response.data;
	},
	error: function(response) {
	    alert("internal error");
	}
    })
}

function usersYearStatus(elem, begin, end, showNewUsers) {
    var req = {
	'option' : 'com_ajax',
	'module' : 'court_usage',
	'format' : AJAX_FMT,
	'cmd'    : 'usersYearStatus',
	'begin'  : begin,
	'end'    : end,
	'showNewUsers': showNewUsers
    };

    jQuery.ajax({
	type : 'POST',
	data: req,
	
	success: function(response) {
	    var cell = document.getElementById(elem);
	    cell.innerHTML = response.data;
	},
	error: function(response) {
	    alert("internal error");
	}
    })
}

function getExportMsg(elem) {
    var req = {
	'option' : 'com_ajax',
	'module' : 'court_usage',
	'format' : AJAX_FMT,
	'cmd'    : 'exportMsg',
    };

    jQuery.ajax({
	type : 'POST',
	data: req,
	
	success: function(response) {
	    if (response.data) {
		var cell = document.getElementById(elem);
		cell.innerHTML = response.data;

		jQuery(".exportBtn").click(function(event) {
		    var a = document.getElementById("exportBegin");
		    var b = document.getElementById("exportEnd");
		    exportEvent(event.target.id, a.value, b.value);
		});
	    }
	},
	error: function(response) {
	    alert("internal error");
	}
    });
}

jQuery(document).ready(function() {

    getExportMsg("excel");

    width = jQuery("#chart1").css("width");
    jQuery(window).on('resize', function() {
	var w = jQuery("#chart1").css("width");
	if (w != width) {
    	    width = w;
	    draw_charts();
	}
    });

    usersStatus('usersStatus'); 
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
    	    alert("internal error");
	}
    })
}
