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
			vals = ['#firstname','#surname','#photo','#gender','#experience','#region','#statement','#showcase1_title','#showcase1_text','#showcase2_title','#showcase2_text','#showcase3_title','#showcase3_text'],
			checks_count = 0;

		for (var i = 0; i < vals.length; i++) {
			checks_count++;
			if ($(vals[i]).val() !== '') {
				complete_count++;
			} else {
				console.log(vals[i]+' empty');
			}
		}
		$('.checkbox-list').each(function(){
			checks_count++;
			if ($(':checkbox:checked', this).length) {
				complete_count++;
			}
		});
		var complete_pc = ( complete_count === checks_count ) ? '100%': Math.floor((complete_count/checks_count)*100)+'%';
		$('.completion-meter span').css({width:complete_pc});

	};
	if ($('.ltp-profile-builder').length) {
		$('.completion-meter span').css({width:'50%'});
		$('.checkbox-list').jScrollPane({verticalGutter:0});
		$('.checkbox-list label').on('click', checkCheckboxLists);
		checkCheckboxLists();
		$('input,textarea,select').on('change', function(){
			checkCompletion();
		});
		checkCompletion();
		$('.sticky').sticky();
	}

})(jQuery);