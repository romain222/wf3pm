
function addFileInput() {
	
	var prototype = $('#comment_files').data('prototype');
	var count = $('#comment_files > div').length;
	
	var newForm = prototype.replace(/__name__/g, count);
	
	var group = $('input', newForm).parent();
	
	$('#comment_files').append(group);
}

var button = $('<button>Add file</button>');
button.addClass('btn btn-success');
button.attr('type', 'button');
button.on('click', addFileInput);

$("#comment_files").after(button);
