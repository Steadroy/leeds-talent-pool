/**
 * Leeds talent pool scripts
 */
;(function($){
	var checkCheckboxLists = function() {
		$('.checkbox-list label').each(function(){
			if ($(':checked', this).length) {
				$(this).addClass('active');
			} else {
				$(this).removeClass('active');
			}
		});
	},
	checkCompletion = function()
	{
		var complete_count = 0,
			vals = [
				'#firstname',
				'#surname',
				'#photo',
				'#gender',
				'#experience',
				'#region',
				'#achievements',
				'#statement'
			],
			checks_count = 0;

		for (var i = 0; i < vals.length; i++) {
			checks_count++;
			var v = $(vals[i]).val();
			if ( v !== '' && v !== 'null' ) {
				complete_count++;
			}
		}
		// check lists of checkboxes to make sure at least one is checked
		$('.checkbox-list').each(function(){
			checks_count++;
			if ($(':checkbox:checked', this).length) {
				complete_count++;
			}
		});
		// go through showcases - each needs title, text and one form of media
		for (var j = 1; j <= 3; j++) {
			checks_count++;
			if ($('#showcase'+j+'_title').val() !== '') {
				complete_count++;
			}
			checks_count++;
			if ($('#showcase'+j+'_text').val() !== '') {
				complete_count++;
			}
			checks_count++;
			if ($('#showcase'+j+'_image').val() !== '' || $('#showcase'+j+'_file').val() !== '' || $('#showcase'+j+'_video').val() !== '') {
				complete_count++;
			}
		}
		var complete_pc = ( complete_count === checks_count ) ? '100%': Math.floor((complete_count/checks_count)*100)+'%';
		$('.completion-meter span').css({width:complete_pc});
	},
	checkName = function()
	{
		if ($.trim($('#firstname').val()) === '' || $.trim($('#surname').val()) === '') {
			return false;
		}
		return true;
	},
	removeFilters = function()
	{
		$('#profile-filters :checkbox').prop('checked', false);
		checkCheckboxLists();
		if ($('#profile-filters').data('showing-filters')) {
			$('#profile-filters').slideUp(function(){
				$('#profile-filters').data('showing-filters', false);
			});
		}
		$('#profile-filter').text('Filter Profiles');
		$('.ltp-profile-wrap').show();
		rearrangeProfiles();
		showCurrentFilters();
		$('.ltp-profiles p.message').remove();
		$.cookie('ltp-filters', {});
	},
	applyFilters = function( filters )
	{
		if ($.isEmptyObject(filters)){
			removeFilters();
		} else {
			$('.ltp-profile-wrap').each(function(){
				var hits = 0;
				var filterCount = 0;
				for (var filter in filters) {
					filterCount++;
					var toShow = false;
					for ( var i = 0; i < filters[filter].length; i++ ) {
						if ($(this).hasClass(filters[filter][i])) {
							toShow = true;
						}
					}
					if (toShow) {
						hits++;
					}
				}
				if (hits == filterCount) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
			if (!$('.ltp-profile-wrap:visible').length) {
				$('.ltp-profiles').append('<p class="message">No profiles match your search criteria</p>');
			} else {
				rearrangeProfiles();
			}
			// change the button text
			$('#profile-filter').text('Edit filters');
			// add button to remove filters
			$('#remove-filters').show();
			// show current filters
			showCurrentFilters();
		}
		$.cookie('ltp-filters', filters, { 'expires': 30 });
	},
	loadFilters = function()
	{
		var filters = $.cookie('ltp-filters');
		if ( ! $.isEmptyObject(filters)) {
			// uncheck all filters
			$('#profile-filters :checkbox').prop('checked', false);
			// check relevant filter checkboxes
			for (var f in filters) {
				for (var i = 0; i < filters[f].length; i++) {
					$('#'+filters[f][i]).prop('checked', true);
				}
			}
			// apply to profile list
			applyFilters(filters);
		}
	},
	showCurrentFilters = function()
	{
		var filters = {};
		// clear existing text
		$('.current-filters-list').each(function(){
			$(this).text($(this).data('no-selection'));
		});
		// go through labels looking for active items
		// must be called after checkCheckboxLists(!)
		$('.checkbox-list label.active').each(function(){
			if ( ! filters[$(this).data('filterid')] ) {
				filters[$(this).data('filterid')] = [];
			}
			filters[$(this).data('filterid')].push($(this).attr('title'));
		});
		// set the items in the lists
		if ( ! $.isEmptyObject(filters)) {
			for (var f in filters) {
				$('#current-'+f).text(filters[f].join(', '));
			}
		}
	},
	rearrangeProfiles = function()
	{
		var count = 0;
		$('.ltp-profile-wrap:visible').each(function(){
			if (count % 2 === 0) {
				$(this).removeClass('right').addClass('left');
			} else {
				$(this).removeClass('left').addClass('right');
			}
			count++;
		});
	},
	showSavedProfiles = function()
	{
		$('#profile-filter').hide();
		if ($('.ltp-profile-wrap.saved').length) {
			$('.ltp-profile-wrap').each(function(){
				if ($(this).hasClass('saved')) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		} else {
			$('.ltp-profile-wrap').hide();
			$('.ltp-profiles').append('<p class="message">No profiles have been saved</p>');
		}
		rearrangeProfiles();
		$('#saved-filter').text('Show all profiles');
		$('#profile-filters').data('showing-saved', true);
	};

	// set cookies to store JSON objects
	$.cookie.json = true;

	// bootstrap profile builder scripts
	if ($('.ltp-profile-builder').length) {
		//make nice scrolling checkbox lists
		$('.checkbox-list').jScrollPane({verticalGutter:0});
		// ensure checkbox lists are styled appropriately when clicked
		$('.checkbox-list label').on('click', checkCheckboxLists);
		checkCheckboxLists();
		// check completion of form
		$('input,textarea,select').on('change', function(){
			checkCompletion();
		});
		checkCompletion();
		$('.ppt-preview-button, .ppt-publish-button').on('click', function(e){
			if ( ! checkName() ) {
				e.preventDefault();
				alert('Please fill in your name');
				$('#firstname').focus();
				return false;
			}
		});
	}

	// bootstrap profile filtering scripts
	if ($('#profile-filters').length) {

		// load any existing filters from a cookie
		loadFilters();

		// auto-check experience with greater value
		$('input[name=experience]').on('click', function(){
			var selectThis = false,
				selectNext = false,
				clickedID = $(this).attr('id');
			if ($(this).is(':checked')) {
				selectThis = true;
			}
			$('input[name=experience]').each(function(){
				$(this).prop('checked', false);
				if (clickedID == $(this).attr('id')) {
					if (selectThis) {
						$(this).prop('checked', true);
					}
					selectNext = true;
				} else {
					if (selectNext) {
						$(this).prop('checked', true);
					}
				}
			});
			checkCheckboxLists();
		});
		
		// when a filter is updated, make sure class is applied and current filters displayed
		$('#profile-filters .checkbox-list label').on('click', function(){
			checkCheckboxLists();
			showCurrentFilters();
		});
		
		// button which shows saved/all profiles
		$('#saved-filter').on('click', function(e){
			e.preventDefault();
			removeFilters();
			if ($('#profile-filters').data('showing-saved')) {
				$('.ltp-profile-wrap').show();
				rearrangeProfiles();
				$(this).text('View saved profiles');
				$('#profile-filters').data('showing-saved', false);
				$('#profile-filter').show();
				window.location.hash = 'all';
			} else {
				showSavedProfiles();
			}
		});
		
		// save profile from list view via ajax
		$('.ajax-button').on('click', function(e){
			e.preventDefault();
			var ajax_action = $(this).data('ajax_action'),
				profile_page_id = $(this).data('profile_page_id'),
				user_id = $(this).data('user_id');
			$.post(
				ppt.ajaxurl,
				{
					'datanonce': ppt.datanonce,
					'action': 'ltp_data',
					'ajax_action': ajax_action,
					'profile_page_id': profile_page_id,
					'user_id': user_id
				},
				function( data, textstatus ) {
					if ( data.ajax_action === 'save' && data.result ) {
						$('#save_'+data.profile_page_id).text('Remove');
						$('#save_'+data.profile_page_id).data('ajax_action', 'remove');
						$('#ltp_profile_wrap_'+data.profile_page_id).addClass('saved');
						$('#saved-filter').show();
						if ($('#profile-filters').data('showing-saved')) {
							$('#ltp_profile_wrap_'+data.profile_page_id).show();
						}
					}
					if ( data.ajax_action === 'remove' ) {
						$('#save_'+data.profile_page_id).text('Save');
						$('#save_'+data.profile_page_id).data('ajax_action', 'save');
						$('#ltp_profile_wrap_'+data.profile_page_id).removeClass('saved');
						if ($('#profile-filters').data('showing-saved')) {
							$('#ltp_profile_wrap_'+data.profile_page_id).hide();
							if (!$('.ltp-profile-wrap.saved').length) {
								$('.ltp-profiles').append('<p class="message">No profiles have been saved</p>');
							}
						}
					}
					rearrangeProfiles();
				},
				'json'
			);
		});
		// button used to filter profiles
		$('#profile-filter').on('click', function(e){
			e.preventDefault();
			showCurrentFilters();
			if ($('#profile-filters').data('showing-filters')) {
				$('.ltp-profiles p.message').remove();
				// Apply filters button has been clicked
				if ( ! $('#profile-filters :checked').length ) {
					removeFilters();
					return;
				} else {
					// see if any filters need to be applied
					var filters = {};
					$('#profile-filters :checked').each(function(){
						if (!filters[$(this).attr('name')]){
							filters[$(this).attr('name')] = [];
						}
						filters[$(this).attr('name')].push($(this).attr('id'));
					});
					applyFilters(filters);
					// hide filter controls
					$('#profile-filters').slideUp(function(){
						$('#profile-filters').data('showing-filters', false);
					});
				}
			} else {
				// add/edit filters button has been clicked
				$('#profile-filters').show();
				$('#remove-filters').hide();
				$(this).text('Apply filters');
				// show active list for first time
				$('.checkbox-list.active').show().jScrollPane({verticalGutter:0}).removeClass('active');
				$('#profile-filters').data('showing-filters', true);
			}
		});
		$('#remove-filters').on('click', function(e){
			e.preventDefault();
			removeFilters();
			$(this).hide();
		});
		$('.show-filter-controls').on('click', function(e){
			e.preventDefault();
			$('.show-filter-controls').removeClass('active');
			$(this).addClass('active');
			$('.checkbox-list').hide();
			$($(this).attr('href')).show().jScrollPane({verticalGutter:0});
			checkCheckboxLists();
		});
		if (window.location.hash === '#saved') {
			showSavedProfiles();
		}
		checkCheckboxLists();
		showCurrentFilters();
	}
	$('.sticky').sticky();
	$('.showcase-button').colorbox({
		inline:true,
		width:'90%',
		maxWidth:'940px',
		current:"Showcase {current} of {total}"
	});
})(jQuery);