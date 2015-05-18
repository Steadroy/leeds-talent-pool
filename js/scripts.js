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
		if ( hasFilters() ) {
			var filters = $('#profile-filters').data('filters');
			//console.log(filters);
			$('.ltp-profiles').isotope({filter:function(){
				//console.log(this.className);
				//console.log(filters);
				var hits = 0,
					toShow = false,
					filterCount = 0;
				for (var filter in filters) {
					filterCount++;
					toShow = false;
					for ( var i = 0; i < filters[filter].length; i++ ) {
						if ($(this).hasClass(filters[filter][i])) {
							toShow = true;
						}
					}
					if (toShow) {
						hits++;
					}
				}
				//console.log(hits,filterCount);
				if (hits == filterCount) {
					return true;
				} else {
					return false;
				}
			}});
			$('.ltp-profiles').isotope('once', 'arrangeComplete', function( filteredItems ) {
				if ( ! filteredItems.length) {
					//console.log('no profiles');
					$('#no-filtered-profiles').show();
				}
			});
		}
	},
	removeFilters = function()
	{
		$('.ltp-profiles p.message').remove();
		$('.ltp-profiles').isotope({filter:'*'});
		rearrangeProfiles('removeFilters');
	},
	loadFilters = function()
	{
		var filters = $.cookie('ltp-filters');
		if ( typeof(filters) != 'undefined' ) {
			// uncheck all filters
			$('#profile-filters :checkbox').prop('checked', false);
			// check relevant filter checkboxes
			for (var f in filters) {
				for (var i = 0; i < filters[f].length; i++) {
					$('#'+filters[f][i]).prop('checked', true);
				}
			}
		} else {
			filters = {};
		}
		$('#profile-filters').data('filters', filters);
		updateCurrentFilters();
	},
	hasFilters = function()
	{
		var filters = $('#profile-filters').data('filters');
		if ( typeof(filters) == 'undefined' || $.isEmptyObject(filters) ) {
			return false;
		}
		return true;
	},
	hasSaved = function()
	{
		if ( $('.ltp-profile-wrap.saved').length ) {
			return true;
		} else {
			return false;
		}
	},
	hasLatest = function()
	{
		if ( $('.ltp-profile-wrap.latest').length ) {
			return true;
		} else {
			return false;
		}
	},
	updateCurrentFilters = function()
	{
		// make sure lists are formatted correctly
		checkCheckboxLists();

		// store filters and labels in here
		var filters = {},
			labels = {};

		// go through checkboxes looking for active items
		$('#profile-filters :checked').each(function(){
			if ( ! filters[$(this).attr('name')] ) {
				filters[$(this).attr('name')] = [];
				labels[$(this).attr('name')] = [];
			}
			filters[$(this).attr('name')].push($(this).attr('id'));
			labels[$(this).attr('name')].push($(this).data('filter-label'));
		});

		// clear existing text
		$('.current-filters-list').each(function(){
			$(this).text($(this).data('no-selection'));
		});

		// set the items in the lists
		if ( ! $.isEmptyObject(filters)) {
			for (var f in filters) {
				$('#current-filters-'+f).text(labels[f].join(', '));
			}
			$('#delete-filters,#apply-filters').show();
		} else {
			$('#delete-filters,#apply-filters').hide();
		}
		// store as data and in cookie
		$('#profile-filters').data('filters', filters);
		$.cookie('ltp-filters', filters, { 'expires': 30 });
	},
	// goes through visible profiles assigning 'left' and 'right' classes to them
	rearrangeProfiles = function(msg)
	{
		//console.log(msg);
		$('.ltp-profiles').isotope('layout');
	},
	showSavedProfiles = function()
	{
		$('p.message').hide();
		if ($('.ltp-profile-wrap.saved').length) {
			$('.ltp-profiles').isotope({filter:'.saved'});
		} else {
			$('.ltp-profiles').isotope({filter:'*'});
			$('#no-saved-profiles').show();
		}
		//rearrangeProfiles('showSavedProfiles');
		if ( hasFilters() ) {
			$('#view-all,#view-filtered,#edit-filters').show();
			$('#view-saved,#apply-filters,#remove-filters,#show-filters').hide();
		} else {
			$('#view-all,#show-filters').show();
			$('#view-saved,#apply-filters,#remove-filters,#view-filtered,#edit-filters').hide();
		}
		if ( hasLatest() ) {
			$('#view-latest').show();
		} else {
			$('#view-latest').hide();
		}
		window.location.hash = 'saved';
		$('#profile-filters').data('showing', 'saved');
	},
	showFilteredProfiles = function()
	{
		$('p.message').hide();
		if ( hasSaved() ) {
			$('#view-saved').show();
		} else {
			$('#view-saved').hide();
		}
		if ( hasLatest() ) {
			$('#view-latest').show();
		} else {
			$('#view-latest').hide();
		}
		if ( hasFilters() ) {
			applyFilters();
			$('#view-all,#edit-filters,#remove-filters').show();
			$('#view-filtered,#apply-filters,#show-filters').hide();
			$('#profile-filters').data('showing', 'filtered');
			window.location.hash = 'filtered';
		} else {
			showAllProfiles();
		}
	},
	showLatestProfiles = function()
	{
		$('p.message').hide();
		$('#view-latest').hide();
		if ( hasLatest() ) {
			$('.ltp-profiles').isotope({filter:'.latest'});
		} else {
			showAllProfiles();
			return;
		}
		if ( hasSaved() ) {
			$('#view-saved').show();
		} else {
			$('#view-saved').hide();
		}
		if ( hasFilters() ) {
			$('#view-filtered,#edit-filters').show();
			$('#view-all,#remove-filters,#show-filters,#apply-filters').hide();
		} else {
			$('#show-filters').show();
			$('#view-all,#view-filtered,#apply-filters,#edit-filters,#remove-filters').hide();
		}
		$('#profile-filters').data('showing', 'latest');
		window.location.hash = 'latest';
	},
	showAllProfiles = function()
	{
		$('p.message').hide();
		removeFilters();
		if ( hasSaved() ) {
			$('#view-saved').show();
		} else {
			$('#view-saved').hide();
		}
		if ( hasLatest() ) {
			$('#view-latest').show();
		} else {
			$('#view-latest').hide();
		}
		if ( hasFilters() ) {
			$('#view-filtered,#edit-filters').show();
			$('#view-all,#remove-filters,#show-filters,#apply-filters').hide();
		} else {
			$('#show-filters').show();
			$('#view-all,#view-filtered,#apply-filters,#edit-filters,#remove-filters').hide();
		}
		$('#profile-filters').data('showing', 'all');
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
			case '#latest':
				showLatestProfiles();
				break;
			default:
				showAllProfiles();
				break;
		}

	};

	$('.ltp-profiles').isotope({
		itemSelector: '.ltp-profile-wrap',
		masonry: {
			columnwidth: function( containerWidth ) {
				return containerWidth / 3;
   			}
   		}
    });

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

		// button to show saved profiles
		$('#view-saved').on('click', function(e){
			e.preventDefault();
			$('#profile-filters').slideUp();
			showSavedProfiles();
		});

		// button to show all profiles
		$('#view-all').on('click', function(e){
			e.preventDefault();
			$('#profile-filters').slideUp();
			showAllProfiles();
		});

		// button to show filtered profiles
		$('#view-filtered').on('click', function(e){
			e.preventDefault();
			$('#profile-filters').slideUp();
			showFilteredProfiles();
		});
		
		// buttons to show profile filter controls
		$('#show-filters,#edit-filters').on('click', function(e){
			e.preventDefault();
			updateCurrentFilters();
			$('#profile-filters').slideDown(function(){
				if ( hasFilters() ) {
					$('#apply-filters').show();
					$('#delete-filters').show();
				} else {
					$('#apply-filters').hide();
					$('#delete-filters').hide();
				}
			});
			// hide the button
			$('#edit-filters,#show-filters').hide();
			// show active list (fired only for the first time this is called)
			$('.checkbox-list.active').show().jScrollPane({verticalGutter:0}).removeClass('active');
		});

		// button to apply filters to list
		$('#apply-filters').on('click', function(e){
			e.preventDefault();
			showFilteredProfiles();
			window.location.hash = 'filtered';
			$('#profile-filters').slideUp();
		});

		// button to delete all filters
		$('#delete-filters').on('click', function(e){
			e.preventDefault();
			$('#profile-filters :checkbox').prop('checked', false);
			updateCurrentFilters();
			$('#profile-filters').slideUp(function(){
				showAllProfiles();
			});
		});

		$('#cancel-filters').on('click', function(e){
			e.preventDefault();
			$('#profile-filters').slideUp();
		});

		// when a filter is updated, make sure class is applied and current filters displayed
		$('#profile-filters .checkbox-list').on('click', 'label,input', function(){
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

		$('.select-filters-button').on('click', function(e){
			e.preventDefault();
			if ($(this).hasClass('all')) {
				$('#'+$(this).data('selectid')+' :checkbox').prop('checked', true);
			} else {
				$('#'+$(this).data('selectid')+' :checkbox').prop('checked', false);
			}
			checkCheckboxLists();
		});
		// links which toggle display of filter controls
		$('.show-filter-controls').on('click', function(e){
			e.preventDefault();
			$('.show-filter-controls').removeClass('active');
			$(this).addClass('active');
			$('.checkbox-list').hide();
			$($(this).attr('href')).show().jScrollPane({verticalGutter:0,autoReinitialise:true});
			checkCheckboxLists();
		});

		// save / remove profile from list view via ajax
		$('.ajax-button').on('click', function(e){
			e.preventDefault();
			var ajax_action = $(this).data('ajax_action'),
				profile_page_id = $(this).data('profile_page_id'),
				user_id = $(this).data('user_id'),
				url = $(this).attr('href');
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
						$('#view-saved').show();
					}
					if ( data.ajax_action === 'remove' ) {
						$('#save_'+data.profile_page_id).text('Save');
						$('#save_'+data.profile_page_id).data('ajax_action', 'save');
						$('#ltp_profile_wrap_'+data.profile_page_id).removeClass('saved');
						if ( ! hasSaved() ) {
							$('#view-saved').hide();
						}
						if ($('#profile-filters').data('showing') === 'saved') {
							$('.ltp-profiles').isotope({filter:'.saved'});
							if ( ! hasSaved() ) {
								$('#no-saved-profiles').show();
							}
						}
					}
					if ( url.indexOf('#') === -1 ) {
						window.location.href = url;
					}
				},
				'json'
			);
		});
		// reload interface when hash changes
		window.onhashchange = loadProfiles;
	}
	// retrieve history via ajax and display in colorbox
	$(document).on('click', '.history', function(e){
		e.preventDefault();
		var start = $(this).data('start'),
			num = $(this).data('num'),
			user_id = $(this).data('user_id');
		$.post(
			ppt.ajaxurl,
			{
				'datanonce': ppt.datanonce,
				'action': 'ltp_data',
				'ajax_action': 'history',
				'start': start,
				'num': num,
				'user_id': user_id
			},
			function( data, textstatus ) {
				$.colorbox({
					html:data.result,
					width:'90%',
					maxWidth:'540px',
					height:'80%',
					maxHeight:'500px'
				});
			},
			'json'
		);
	});
	$('.sticky').sticky();
	$('.showcase-button').colorbox({
		inline:true,
		width:'90%',
		maxWidth:'940px',
		current:"Showcase {current} of {total}"
	});
})(jQuery);