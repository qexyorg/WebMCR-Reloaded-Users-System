$(function(){

	$('.search-user').typeahead({
		items: 10,
		minLength: 2,

		source: function(query, process){
			$.ajax({
				url: "?mode=ajax&do=users&act=typeahead",
				dataType: "json",
				type: 'GET',
				async: true,
				data: "query=" + query,

				success: function(data){
					if(!data._type){ return; }

					process(data._data);
				}
			});
		},

		matcher: function (param){
			return true
		},
	});

	$('body').on('click', '.search-user-btn', function(){

		var val = $('.search-user').val();

		mcr.changeUrlParam({gid:false, pid:false, search: val});

		return false;
	});

	$('body').on('click', '#add_comment', function(){

		var message = $('textarea[name="message"]').val();

		message = $.trim(message);

		if(message==''){ return mcr.notify(lng.error, lng_us.e_empty_comment); }

		mcr.loading();

		var formdata = new FormData();
		
		formdata.append('login', mcr.getUrlParam('uid'));
		formdata.append('message', message);
		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=users&act=add_comment",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function(data){
				mcr.logger(data);
				mcr.notify(lng.error, lng_us.e_add_comment);
			},

			success: function(data){

				if(!data._type){ return mcr.notify(data._title, data._message); }

				$(data._data).hide().prependTo('.mod-users-comments').fadeIn('normal');

				mcr.notify(data._title, data._message);
			}
		});

		return false;
	});

	$('body').on('click', '.comment-remove', function(){

		if(!confirm(lng_us.del_accept)){ return false; }

		var that = $(this);

		var id = that.closest('.comment-id').attr('id').split('-')[1];

		mcr.loading();

		var formdata = new FormData();
		
		formdata.append('id', id);
		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=users&act=del_comment",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function(data){
				mcr.logger(data);
				mcr.notify(lng.error, lng_us.e_del_comment);
			},

			success: function(data){
				if(!data._type){ return mcr.notify(data._title, data._message); }

				that.closest('.comment-id').fadeOut('normal', function(){
					$(this).remove();

					mcr.loading(false);
				});
			}
		});

		return false;
	});
});