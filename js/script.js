jQuery(document).ready(function($) {
	$("#load-more").on('click', function(e) {
		e.preventDefault();
		$(".spinner").show();
		
		page = $(this).data('page');
		type = $(this).data('type');

		var data = {
			action: 'wusm_archive_load_more',
			page:   page,
			type:   type
		};

		$.post(ajax_object.ajax_url, data, function(response) {
			if( response !== "false" ) {
				$(".spinner").hide();
				$(".custom-archive").append(response);
				$("#load-more").data('page', parseInt(page, 10) + 1);
			} else {
				$("#load-more").hide();
				$("#no-more").show();
			}
		});
	});
});