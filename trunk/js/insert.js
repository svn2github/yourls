// Init some stuff
$(document).ready(function(){
	$('#add-url, #add-keyword').keypress(function(e){
		if (e.which == 13) {add();}
	});
	reset_url();
	$('#new_url_form').attr('action', 'javascript:add();');
	
	$('input.text').click(function(){
		$(this).select();
	});	
	
});

// Create new link and add to table
function add() {
	var newurl = $("#add-url").val();
	if ( !newurl || newurl == 'http://' || newurl == 'https://' ) {
		return;
	}
	var keyword = $("#add-keyword").val();
	add_loading("#add-button");
	$.getJSON(
		"index_ajax.php",
		{mode:'add', url: newurl, keyword: keyword},
		function(data){
			if(data.status == 'success') {
				$('#tblUrl tbody').prepend( data.html ).trigger("update");
				$('.nourl_found').remove();
				zebra_table();
				reset_url();
				increment();
			}
			
			$('#copylink').val( data.shorturl );
			$('#origlink').attr( 'href', data.url.url ).html( data.url.url );
			$('#statlink').attr( 'href', data.shorturl+'+' ).html( data.shorturl+'+' );
			$('#tweet_body').val( data.shorturl ).keypress();
			$('#shareboxes').slideDown();		

			end_loading("#add-button");
			end_disable("#add-button");

			feedback(data.message, data.status);
		}
	);
}

// Display the edition interface
function edit(id) {
	add_loading('#actions-'+id+' .button');
	var keyword = $('#keyword_'+id).val();
	$.getJSON(
		"index_ajax.php",
		{ mode: "edit_display", keyword: keyword },
		function(data){
			$("#id-" + id).after( data.html );
			$("#edit-url-"+ id).focus();
			end_loading('#actions-'+id+' .button');
		}
	);
}

// Delete a link
function remove(id) {
	if (!confirm('Really delete?')) {
		return;
	}
	var keyword = $('#keyword_'+id).val();
	$.getJSON(
		"index_ajax.php",
		{ mode: "delete", keyword: keyword },
		function(data){
			if (data.success == 1) {
				$("#id-" + id).fadeOut(function(){$(this).remove();zebra_table();});
				decrement();
			} else {
				alert('something wrong happened while deleting :/');
			}
		}
	);
}

// Redirect to stat page
function stats(link) {
	window.location=link;
}

// Cancel edition of a link
function hide_edit(id) {
	$("#edit-" + id).fadeOut(200, function(){
		end_disable('#actions-'+id+' .button');
	});
}

// Save edition of a link
function edit_save(id) {
	add_loading("#edit-close-" + id);
	var newurl = $("#edit-url-" + id).val();
	var newkeyword = $("#edit-keyword-" + id).val();
	var keyword = $('#old_keyword_'+id).val();
	var www = $('#yourls-site').val();
	$.getJSON(
		"index_ajax.php",
		{mode:'edit_save', url: newurl, keyword: keyword, newkeyword: newkeyword },
		function(data){
			if(data.status == 'success') {
				$("#url-" + id).html('<a href="' + data.url.url + '" title="' + data.url.url + '">' + data.url.display_url + '</a>');
				$("#keyword-" + id).html('<a href="' + data.url.shorturl + '" title="' + data.url.shorturl + '">' + data.url.keyword + '</a>');
				$("#timestamp-" + id).html(data.url.date);
				$("#edit-" + id).fadeOut(200, function(){
					$('#tblUrl tbody').trigger("update");
				});
				$('#keyword_'+id).val( newkeyword );
				$('#statlink-'+id).attr( 'href', data.url.shorturl+'+' );
			}
			feedback(data.message, data.status);
			end_disable("#edit-close-" + id);
			end_loading("#edit-close-" + id);
			end_disable("#edit-button-" + id);
			end_disable("#delete-button-" + id);
		}
	);
}

// Prettify table with odd & even rows
function zebra_table() {
	$("#tblUrl tbody tr:even").removeClass('odd').addClass('even');
	$("#tblUrl tbody tr:odd").removeClass('even').addClass('odd');
	$('#tblUrl tbody').trigger("update");
}

// Ready to add another URL
function reset_url() {
	$('#add-url').val('http://').focus();
	$('#add-keyword').val('');
}

// Increment URL counters
function increment() {
	$('.increment').each(function(){
		$(this).html( parseInt($(this).html()) + 1);
	});
}

// Decrement URL counters
function decrement() {
	$('.increment').each(function(){
		$(this).html( parseInt($(this).html()) - 1 );
	});
}

