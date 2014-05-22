jQuery(document).ready(function($) {
	$("#load-more").on('click', function(e) {
		e.preventDefault();
		$(".spinner").show();
		
		page = $(this).data('page');
		type = $(this).data('type');

		// This does the ajax request
		$.ajax({
			type : 'post',
			url: '/wp-content/plugins/wusm-archives/get_archive_posts.php',
			data: {
				action   : 'wusm_archive_load_more',
				page:   page,
				post_type:   type
			},
			success:function(data) {
				$(".spinner").hide();
				$(".custom-archive").append(data);
				$("#load-more").data('page', parseInt(page, 10) + 1);
			},
			error:function(data) {
				$("#load-more").hide();
				$("#no-more").show();
			}
		});
	});
});