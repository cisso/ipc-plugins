/*
var WebClient = {};
WebClient.refreshWidget = function() {
	var options = [0, 3, 10, 60, 60*60, -1];
	var widget = $('<div class="refresh-widget" />');
	var list = ($('<ul />')).appendTo(widget);
	list.append()
	$.each(options, function(delay, label) {
		var option = $('<li />').text()
	});
}
*/

$().ready(function() {
	
	$('<div class="toggle-help">Toggle help</div>').insertAfter('h1').click(function() {
		$('body').toggleClass('hide-help');
	}).click();
		
	$('body')
	.delegate('form input:submit', 'click', function(e) {
		// Workaround for serializing submit names
		var submit = $(this);
		var form = submit.parents('form:first');
		if(submit.hasClass('no-ajax')) {
			form.addClass('no-ajax');
			return;
		}
		// Don't append submit name if user pressed return on an input field
		if(form.find(':focus:not(:submit)').length) {
			return;
		}
		$('<input />')
			.attr({
				type   : 'hidden',
				name   : submit.attr('name'),
				value  : submit.attr('value'),
				'class': 'form-submit-wa-hidden'
			})
			.insertAfter(submit);
	})
	.delegate('form', 'submit', function(e) {
			var form = $(this);
			if(form.hasClass('no-ajax')) {
				return;
			}
			e.preventDefault();
			var data = form.serialize();
			form.find('.form-submit-wa-hidden').remove();
			jQuery.post('', data, function(data) {
					form.replaceWith($(data));
			} );
	});

});