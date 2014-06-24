jQuery(document).ready(function($) {
	$("#load-more").on('click', function(e) {
		e.preventDefault();
		$(".spinner").show();
		
		page = $(this).data('page');
		type = $(this).data('type');
		if( $("#research-news-expertise")[0] ) {
			category = $("#research-news-expertise option:selected").val();
		} else {
			category = '';
		}

		// This does the ajax request
		$.ajax({
			type : 'post',
			url: '/wp-content/plugins/wusm-archives/get_archive_posts.php',
			data: {
				action   : 'wusm_archive_load_more',
				page     : page,
				post_type: type,
				cat      : category
			},
			success:function(data) {
				if(data !== 'false') {
					$(".spinner").hide();
					$(".custom-archive").append(data);
					$("#load-more").data('page', parseInt(page, 10) + 1);
				} else {
					$("#load-more").hide();
					$("#no-more").show();
				}
			},
			error:function(data) {
				$("#load-more").hide();
				$("#no-more").show();
			}
		});
	});

	$("#research-news-expertise").change(function() {
		category = $("#research-news-expertise option:selected").val();
		$.ajax({
			type : 'post',
			url: '/wp-content/plugins/wusm-archives/get_archive_posts.php',
			data: {
				action   : 'wusm_archive_load_more',
				page     : 1,
				post_type: 'research_news',
				cat      : category
			},
			success:function(data) {
				if( data !== 'false') {
					$(".custom-archive").html(data);
					if( category === '' ) {
						$("#load-more").show();
						$("#no-more").hide();
					}
					$("#load-more").data('page', 2);
					$("#load-more").data('type', 'research_news');
				} else {
					$(".custom-archive").html("No stories found for " + $("#research-news-expertise option:selected").text());
					$("#load-more").hide();
				}
			},
			error:function(data) {
				$("#load-more").hide();
				$("#no-more").show();
			}
		});
	});
});