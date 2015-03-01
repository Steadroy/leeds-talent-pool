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
	applyFilters = function()
	{
		var filters = $('#profile-filters').data('filters');
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
		}
	},
	removeFilters = function()
	{
		$('.ltp-profiles p.message').remove();
		$('.ltp-profile-wrap').show();
		rearrangeProfiles();
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
		}
		$('#profile-filters').data('filters', filters);
	},
	hasFilters = function()
	{
		if ( ! $.isEmptyObject($('#profile-filters').data('filters')) ) {
			return true;
		} else {
			return false;
		}
	},
	updateCurrentFilters = function()
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
			$('#apply-filters,#delete-filters').show();
		} else {
			$('#apply-filters,#delete-filters').hide();
		}
		// store as data and in cookie
		$('#profile-filters').data('filters', filters);
		$.cookie('ltp-filters', filters, { 'expires': 30 });
	},
	// goes through visible profiles assigning 'left' and 'right' classes to them
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
		if ( hasFilters() ) {
			$('#view-all,#view-filtered,#edit-filters').show();
			$('#view-saved,#apply-filters,#remove-filters,#filter-profiles').hide();
		} else {
			$('#view-all,#filter-profiles').show();
			$('#view-saved,#apply-filters,#remove-filters,#view-filtered,#edit-filters').hide();
		}
		window.location.hash = 'saved';
	},
	showFilteredProfiles = function()
	{
		if ( hasFilters() ) {
			applyFilters();
			$('#view-all,#view-saved,#edit-filters,#remove-filters').show();
			$('#view-filtered,#apply-filters,#filter-profiles').hide();
			window.location.hash = 'filtered';
		} else {
			showAllProfiles();
		}
	},
	showAllProfiles = function()
	{
		removeFilters();
		if ( hasFilters() ) {
			$('#view-saved,#view-filtered,#apply-filters,#edit-filters').show();
			$('#view-all,#remove-filters,#filter-profiles').hide();
		} else {
			$('#view-saved,#filter-profiles').show();
			$('#view-all,#view-filtered,#apply-filters,#edit-filters,#remove-filters').hide();
		}
		window.location.hash = 'all';
	},
	loadProfiles = function()
	{
		switch (window.location.hash) {
			case '#saved':
				showSavedProfiles();
				break;
			case '#filtered':
				showFilteredProfiles();
				break;
			default:
				showAllProfiles();
				break;
		}

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

		// load profiles
		loadProfiles();

		// apply formatting to checkbox lists
		checkCheckboxLists();

		// button to show saved profiles
		$('#view-saved').on('click', function(e){
			e.preventDefault();
			showSavedProfiles();
		});

		// button to show all profiles
		$('#view-all').on('click', function(e){
			e.preventDefault();
			showAllProfiles();
		}

		// button to show filtered profiles
		$('#view-filtered').on('click', function(e){
			e.preventDefault();
			showFilteredProfiles();
		});
		
		// buttons to show profile filter controls
		$('#filter-profiles,#edit-filters').on('click', function(e){
			e.preventDefault();
			updateCurrentFilters();
			$('#profile-filters').slideDown(function(){
				if ( hasFilters() ) {
					$('#apply-filters').show();
					$('#delete-filters').show();
				}
			}
			// hide the button
			$('#edit-filters,#filter-profiles').hide();
			// show active list (fired only for the first time this is called)
			$('.checkbox-list.active').show().jScrollPane({verticalGutter:0}).removeClass('active');
		});

		// button to apply filters to list
		$('#apply-filters').on('click', function(e){
			showFilteredProfiles();
			window.location.hash = 'filtered';
			$('#profile-filters').slideUp();
		});

		// button to delete all filters
		$('#delete-filters').on('click', function(e){
			$('#profile-filters :checkbox').prop('checked', false);
			checkCheckboxLists();
			updateCurrentFilters();
			$('#profile-filters').slideUp(function(){
				showAllProfiles();
			});
		});

		// when a filter is updated, make sure class is applied and current filters displayed
		$('#profile-filters .checkbox-list').on('click', 'label,input', function(){
			checkCheckboxLists();
			updateCurrentFilters();
		});
		
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

		// links which toggle display of filter controls
		$('.show-filter-controls').on('click', function(e){
			e.preventDefault();
			$('.show-filter-controls').removeClass('active');
			$(this).addClass('active');
			$('.checkbox-list').hide();
			$($(this).attr('href')).show().jScrollPane({verticalGutter:0});
			checkCheckboxLists();
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
						$('#show-saved').show();
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
	}
	$('.sticky').sticky();
	$('.showcase-button').colorbox({
		inline:true,
		width:'90%',
		maxWidth:'940px',
		current:"Showcase {current} of {total}"
	});
})(jQuery);