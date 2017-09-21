
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(draw_charts);

function draw_charts() {
    draw_chart('chart1',
	       'court-usage',
	       "Utilisation du court pour l'annee 2017",
	       '2017-01-01',
	       '2017-12-31',
	       true);
    draw_chart('chart2',
	       'player-histo',
	       "Histogramme d'utilisation du court par joueur pour l'annee 2017",
	       '2017-01-01',
	       '2017-12-31',
	       false);
}

function draw_chart(elem, type, title, begin, end, isStacked) {
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
		'width':800,
		'height':300,
		'isStacked': isStacked,
	    };
	    var data = new google.visualization.arrayToDataTable(response.data);
	    var chart = new google.visualization.ColumnChart(document.getElementById(elem));
            chart.draw(data, options);
	},
	error: function(response) {
	    debug && console.log("ajax failed:");
	    debug && console.log(response);
	    alert(ERR_NAMES[ERR_INTERNAL]);
	}
    })
}

jQuery(".exportBtn").click(function(event) {
    a = document.getElementById("exportBegin");
    b = document.getElementById("exportEnd");
    exportEvent(event.target.id, a.value, b.value);
})

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
