jQuery(function ($) {

	var myPalette =  ['#B30000','#3300CC','#B3B300','#59B300','#B30059','#F00000','#FF2E2E','#00B300','#B300B3','#2EFFFF','#AFE495','#00B359','#FF66A3','#0000B3','#0059B3','#00B3B3'];

	$('table.pie-chart').visualize({
		type: 'pie',
		pieMargin: 10,
		width: '350',
		height: '350',
		colors: myPalette
	});


	$('table.line-chart').visualize({
		type: 'line',
		width: '600',
		colors: myPalette
	});


	$('table.area-chart').visualize({
		type: 'area',
		width: '600',
		colors: myPalette
	});


	$('table.bar-chart').visualize({
		type: 'bar',
		width: '600',
		colors: myPalette
	});

});