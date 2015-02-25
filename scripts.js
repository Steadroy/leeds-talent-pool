/*!
 * people post type plugin
 * Javascript for the user profile form
 */
;(function($){

	/**
 	 * configuration
 	 */
	var thumb_size = 'thumbnail';

	/**
 	 * make sure the previews are sortable 
 	 */
	$('.ppt-multiple').sortable({
		update:function(){
			setInputValue( $(this).data('inputid'), $(this).sortable("toArray", {attribute:"data-imageid"}) );
		},
		helper: 'clone',
		items: 'div.ppt-image-container'
	});


	/**
	 * open media browser to select a single file and insert its URL
	 */
	$(document).on('click', '.ppt-media-file', function(e) {
		e.preventDefault();
		/* if there is a frame created, re-open it */
		if ( typeof(ppt_file_frame) !== 'undefined' ) {
			ppt_file_frame.open();
			return;
		}

		/* get the hidden input ID from the button's inputid data attribute */
		var inputID = $(this).data('inputid'),
		/* open the wp.media frame with our localised title */
		ppt_file_frame = wp.media.frames.ppt_file_frame = wp.media({
			title : ppt.file_select,
			multiple : false,
			button : { 
				text : ppt.file_select
			}
		});
		/* use the select event to update the page in real time */
 		ppt_file_frame.on('select', function() {
 			/* get selection and save to hidden input field */
			var file = ppt_file_frame.state().get('selection').first().toJSON();
			if ( file ) {
				var newFile = '<div class="ppt-file-container"><p><a data-inputid="'+inputID+'" data-fileid="'+file.id+'" class="ppt-file-link" href="'+file.url+'">'+file.title+'</a></p> <a class="ppt-remove-file" href="#" title="'+ppt.file_remove+'" data-inputid="'+inputID+'">&#61826;</a></div>';
				setInputValue( inputID, file.id );
				$('#'+inputID+'-preview').html(newFile);
			} else {
				setInputValue( inputID, '' );
				$('#'+inputID+'-preview').html('<div class="ppt-file-container"><p>'+ppt.file_empty+'</p></div>');
			}
		});
		ppt_file_frame.on('open', function() {
			/* pre-select image */
			var fileID = $('#'+inputID).val();
			if (fileID !== '' && parseInt(fileID) > 0) {
				var selection = ppt_file_frame.state().get('selection');
				attachment = wp.media.attachment(fileID);
				attachment.fetch();
				selection.add( attachment ? [ attachment ] : [] );
			}
		});
		ppt_file_frame.open();
	});

	/**
	 * activate media uploader to select multiple images for a slideshow
	 */
	$(document).on('click', '.ppt-media-multiple-images', function(e) {
		e.preventDefault();
		/* if there is a frame created, use it */
		if ( typeof(ppt_imgs_frame) !== 'undefined' ) {
			ppt_imgs_frame.open();
			return;
		}
		/* get the hidden input ID from the button's inputid data attribute */
		var inputID = $(this).data('inputid'),
		/* open the wp.media frame with our localised title */
		ppt_imgs_frame = wp.media.frames.ppt_imgs_frame = wp.media({
			title : ppt.img_select_multiple,
			multiple : true,
			button : { text : ppt.img_select_multiple },
			library: {
				type: 'image'
			}
		});
		/* set the handler for the close event which gets the selection and saves the IDs to the hidden field */
 		ppt_imgs_frame.on('close',function() {
 			/* get the selection object */
			var selection = ppt_imgs_frame.state().get('selection'),
			/* array variable to hold new image IDs */
			imageIDs = [],
			/* variable to hold new HTML for the preview */
			newImages = '';
			/* maps a function to each selected image which constructs the preview and saves the ID */
			selection.map( function( attachment ) {
				var image = attachment.toJSON(),
				imageURL = (image.sizes && image.sizes[thumb_size])? image.sizes[thumb_size].url: image.url;
				if (image.id) {
					newImages += '<div class="ppt-image-container"><img src="'+imageURL+'" /><a class="ppt-remove-image ppt-multiple" href="#" title="'+ppt.img_remove+'">&#61826;</a></div>';
					imageIDs.push(image.id);
				}
			});
			if (imageIDs.length) {
				/* populate hidden input and preview */
				setInputValue( inputID, imageIDs.join(',') );
				$('#'+inputID+'-preview').html(newImages).sortable("refresh");
			} else {
				/* reset hidden input and empty preview */
				setInputValue( inputID, '' );
				$('#'+inputID+'-preview').html('<div class="ppt-image-container"><p>'+ppt.img_empty_multiple+'</p></div>');
			}
		});
		/* opens the wp.media frame and selects the appropriate images */
		ppt_imgs_frame.on('open', function() {
			/* get the image IDs from the hidden input */
			var imgIDs = $('#'+inputID).val().split(',');
			/* get the selection object for the wp.media frame */
			var selection = ppt_imgs_frame.state().get('selection');
			if (imgIDs && imgIDs.length) {
				/* add each image to the selection */
				$.each(imgIDs, function(idx, val) {
					attachment = wp.media.attachment(val);
					attachment.fetch();
					selection.add( attachment ? [ attachment ] : [] );
				});
			}
		});
		ppt_imgs_frame.open();
	});

	/**
	 * open media browser to select a single image and insert its URL
	 */
	$(document).on('click', '.ppt-media-single-image', function(e) {
		e.preventDefault();
		/* if there is a frame created, use it */
		if ( typeof(ppt_img_frame) !== 'undefined' ) {
			ppt_img_frame.open();
			return;
		}
		/* get the hidden input ID from the button's inputid data attribute */
		var inputID = $(this).data('inputid'),
		/* open the wp.media frame with our localised title */
		ppt_img_frame = wp.media.frames.ppt_img_frame = wp.media({
			title : ppt.img_select_single,
			multiple : false,
			button : { 
				text : ppt.img_select_single
			},
			frame: 'select',
			library: {
				type: 'image'
			}
		});
		/* use the select event to update the preview */
 		ppt_img_frame.on('select', function() {
 			/* get selection and save to hidden input field */
			var image = ppt_img_frame.state().get('selection').first().toJSON();
			if ( image ) {
				var imageURL = (image.sizes && image.sizes[thumb_size])? image.sizes[thumb_size].url: image.url,
					newImage = '<div class="ppt-image-container"><img src="'+imageURL+'" /><a class="ppt-remove-image ppt-single" href="#" title="'+ppt.img_remove+'" data-imageid="'+image.id+'" data-inputid="'+inputID+'">&#61826;</a></div>';
				setInputValue( inputID, image.id );
				$('#'+inputID+'-preview').html(newImage);
			} else {
				setInputValue( inputID, '' );
				$('#'+inputID+'-preview').html('<p>'+ppt.img_empty_single+'</p>');
			}
		});
		ppt_img_frame.on('open', function() {
			/* pre-select image */
			var imgID = $('#'+inputID).val();
			if (imgID !== '' && parseInt(imgID) > 0) {
				var selection = ppt_img_frame.state().get('selection');
				attachment = wp.media.attachment(imgID);
				attachment.fetch();
				selection.add( attachment ? [ attachment ] : [] );
			}
		});
		ppt_img_frame.open();
	});
	/* removes an image from a selection */
	$(document).on('click', 'a.ppt-remove-image', function(e){
		e.preventDefault();
		var inputID = $(this).data('inputid');
		var imageID = $(this).data('imageid');
		var currentImages = $('#'+inputID).val().split(','),
			newSlides = [];
		for (i = 0; i < currentImages.length; i++) {
			if (currentImages[i] != imageID) {
				newSlides.push(currentImages[i]);
			}
		}
		if ( ! newSlides.length ) {
			setInputValue( inputID, '' );
			var emptymsg = $(this).hasClass('ppt-multiple') ? ppt.img_empty_multiple : ppt.img_empty_single;
			$('#'+inputID+'-preview').html('<div class="ppt-image-container"><p>'+emptymsg+'</p></div>');
		} else {
			console.log(newSlides);
			setInputValue( inputID, newSlides.join(',') );
		}
		$(this).parents('.ppt-image-container').remove();
	});
	/* removes a file */
	$(document).on('click', 'a.ppt-remove-file', function(e){
		e.preventDefault();
		var inputID = $(this).data('inputid');
		setInputValue( inputID, '' );
		$('#'+inputID+'-preview').html('<div class="ppt-file-container"><p>'+ppt.file_empty+'</p></div>');
	});

	/* this is so I can hook into where the input is set */
	var setInputValue = function(inputID, value)
	{
		/* this provides a live display of any changes */
		$('#'+inputID+'_value').text(value);
		/* this sets the input value */
		$('#'+inputID).val(value);
	};
})(jQuery);
/*!
	Colorbox 1.5.14
	license: MIT
	http://www.jacklmoore.com/colorbox
*/
(function ($, document, window) {
	var
	// Default settings object.
	// See http://jacklmoore.com/colorbox for details.
	defaults = {
		// data sources
		html: false,
		photo: false,
		iframe: false,
		inline: false,

		// behavior and appearance
		transition: "elastic",
		speed: 300,
		fadeOut: 300,
		width: false,
		initialWidth: "600",
		innerWidth: false,
		maxWidth: false,
		height: false,
		initialHeight: "450",
		innerHeight: false,
		maxHeight: false,
		scalePhotos: true,
		scrolling: true,
		opacity: 0.9,
		preloading: true,
		className: false,
		overlayClose: true,
		escKey: true,
		arrowKey: true,
		top: false,
		bottom: false,
		left: false,
		right: false,
		fixed: false,
		data: undefined,
		closeButton: true,
		fastIframe: true,
		open: false,
		reposition: true,
		loop: true,
		slideshow: false,
		slideshowAuto: true,
		slideshowSpeed: 2500,
		slideshowStart: "start slideshow",
		slideshowStop: "stop slideshow",
		photoRegex: /\.(gif|png|jp(e|g|eg)|bmp|ico|webp|jxr|svg)((#|\?).*)?$/i,

		// alternate image paths for high-res displays
		retinaImage: false,
		retinaUrl: false,
		retinaSuffix: '@2x.$1',

		// internationalization
		current: "image {current} of {total}",
		previous: "previous",
		next: "next",
		close: "close",
		xhrError: "This content failed to load.",
		imgError: "This image failed to load.",

		// accessbility
		returnFocus: true,
		trapFocus: true,

		// callbacks
		onOpen: false,
		onLoad: false,
		onComplete: false,
		onCleanup: false,
		onClosed: false,

		rel: function() {
			return this.rel;
		},
		href: function() {
			// using this.href would give the absolute url, when the href may have been inteded as a selector (e.g. '#container')
			return $(this).attr('href');
		},
		title: function() {
			return this.title;
		}
	},

	// Abstracting the HTML and event identifiers for easy rebranding
	colorbox = 'colorbox',
	prefix = 'cbox',
	boxElement = prefix + 'Element',
	
	// Events
	event_open = prefix + '_open',
	event_load = prefix + '_load',
	event_complete = prefix + '_complete',
	event_cleanup = prefix + '_cleanup',
	event_closed = prefix + '_closed',
	event_purge = prefix + '_purge',

	// Cached jQuery Object Variables
	$overlay,
	$box,
	$wrap,
	$content,
	$topBorder,
	$leftBorder,
	$rightBorder,
	$bottomBorder,
	$related,
	$window,
	$loaded,
	$loadingBay,
	$loadingOverlay,
	$title,
	$current,
	$slideshow,
	$next,
	$prev,
	$close,
	$groupControls,
	$events = $('<a/>'), // $({}) would be prefered, but there is an issue with jQuery 1.4.2
	
	// Variables for cached values or use across multiple functions
	settings,
	interfaceHeight,
	interfaceWidth,
	loadedHeight,
	loadedWidth,
	index,
	photo,
	open,
	active,
	closing,
	loadingTimer,
	publicMethod,
	div = "div",
	requests = 0,
	previousCSS = {},
	init;

	// ****************
	// HELPER FUNCTIONS
	// ****************
	
	// Convenience function for creating new jQuery objects
	function $tag(tag, id, css) {
		var element = document.createElement(tag);

		if (id) {
			element.id = prefix + id;
		}

		if (css) {
			element.style.cssText = css;
		}

		return $(element);
	}
	
	// Get the window height using innerHeight when available to avoid an issue with iOS
	// http://bugs.jquery.com/ticket/6724
	function winheight() {
		return window.innerHeight ? window.innerHeight : $(window).height();
	}

	function Settings(element, options) {
		if (options !== Object(options)) {
			options = {};
		}

		this.cache = {};
		this.el = element;

		this.value = function(key) {
			var dataAttr;

			if (this.cache[key] === undefined) {
				dataAttr = $(this.el).attr('data-cbox-'+key);

				if (dataAttr !== undefined) {
					this.cache[key] = dataAttr;
				} else if (options[key] !== undefined) {
					this.cache[key] = options[key];
				} else if (defaults[key] !== undefined) {
					this.cache[key] = defaults[key];
				}
			}

			return this.cache[key];
		};

		this.get = function(key) {
			var value = this.value(key);
			return $.isFunction(value) ? value.call(this.el, this) : value;
		};
	}

	// Determine the next and previous members in a group.
	function getIndex(increment) {
		var
		max = $related.length,
		newIndex = (index + increment) % max;
		
		return (newIndex < 0) ? max + newIndex : newIndex;
	}

	// Convert '%' and 'px' values to integers
	function setSize(size, dimension) {
		return Math.round((/%/.test(size) ? ((dimension === 'x' ? $window.width() : winheight()) / 100) : 1) * parseInt(size, 10));
	}
	
	// Checks an href to see if it is a photo.
	// There is a force photo option (photo: true) for hrefs that cannot be matched by the regex.
	function isImage(settings, url) {
		return settings.get('photo') || settings.get('photoRegex').test(url);
	}

	function retinaUrl(settings, url) {
		return settings.get('retinaUrl') && window.devicePixelRatio > 1 ? url.replace(settings.get('photoRegex'), settings.get('retinaSuffix')) : url;
	}

	function trapFocus(e) {
		if ('contains' in $box[0] && !$box[0].contains(e.target) && e.target !== $overlay[0]) {
			e.stopPropagation();
			$box.focus();
		}
	}

	function setClass(str) {
		if (setClass.str !== str) {
			$box.add($overlay).removeClass(setClass.str).addClass(str);
			setClass.str = str;
		}
	}

	function getRelated(rel) {
		index = 0;
		
		if (rel && rel !== false && rel !== 'nofollow') {
			$related = $('.' + boxElement).filter(function () {
				var options = $.data(this, colorbox);
				var settings = new Settings(this, options);
				return (settings.get('rel') === rel);
			});
			index = $related.index(settings.el);
			
			// Check direct calls to Colorbox.
			if (index === -1) {
				$related = $related.add(settings.el);
				index = $related.length - 1;
			}
		} else {
			$related = $(settings.el);
		}
	}

	function trigger(event) {
		// for external use
		$(document).trigger(event);
		// for internal use
		$events.triggerHandler(event);
	}

	var slideshow = (function(){
		var active,
			className = prefix + "Slideshow_",
			click = "click." + prefix,
			timeOut;

		function clear () {
			clearTimeout(timeOut);
		}

		function set() {
			if (settings.get('loop') || $related[index + 1]) {
				clear();
				timeOut = setTimeout(publicMethod.next, settings.get('slideshowSpeed'));
			}
		}

		function start() {
			$slideshow
				.html(settings.get('slideshowStop'))
				.unbind(click)
				.one(click, stop);

			$events
				.bind(event_complete, set)
				.bind(event_load, clear);

			$box.removeClass(className + "off").addClass(className + "on");
		}

		function stop() {
			clear();
			
			$events
				.unbind(event_complete, set)
				.unbind(event_load, clear);

			$slideshow
				.html(settings.get('slideshowStart'))
				.unbind(click)
				.one(click, function () {
					publicMethod.next();
					start();
				});

			$box.removeClass(className + "on").addClass(className + "off");
		}

		function reset() {
			active = false;
			$slideshow.hide();
			clear();
			$events
				.unbind(event_complete, set)
				.unbind(event_load, clear);
			$box.removeClass(className + "off " + className + "on");
		}

		return function(){
			if (active) {
				if (!settings.get('slideshow')) {
					$events.unbind(event_cleanup, reset);
					reset();
				}
			} else {
				if (settings.get('slideshow') && $related[1]) {
					active = true;
					$events.one(event_cleanup, reset);
					if (settings.get('slideshowAuto')) {
						start();
					} else {
						stop();
					}
					$slideshow.show();
				}
			}
		};

	}());


	function launch(element) {
		var options;

		if (!closing) {

			options = $(element).data(colorbox);

			settings = new Settings(element, options);
			
			getRelated(settings.get('rel'));

			if (!open) {
				open = active = true; // Prevents the page-change action from queuing up if the visitor holds down the left or right keys.

				setClass(settings.get('className'));
				
				// Show colorbox so the sizes can be calculated in older versions of jQuery
				$box.css({visibility:'hidden', display:'block', opacity:''});
				
				$loaded = $tag(div, 'LoadedContent', 'width:0; height:0; overflow:hidden; visibility:hidden');
				$content.css({width:'', height:''}).append($loaded);

				// Cache values needed for size calculations
				interfaceHeight = $topBorder.height() + $bottomBorder.height() + $content.outerHeight(true) - $content.height();
				interfaceWidth = $leftBorder.width() + $rightBorder.width() + $content.outerWidth(true) - $content.width();
				loadedHeight = $loaded.outerHeight(true);
				loadedWidth = $loaded.outerWidth(true);

				// Opens inital empty Colorbox prior to content being loaded.
				var initialWidth = setSize(settings.get('initialWidth'), 'x');
				var initialHeight = setSize(settings.get('initialHeight'), 'y');
				var maxWidth = settings.get('maxWidth');
				var maxHeight = settings.get('maxHeight');

				settings.w = (maxWidth !== false ? Math.min(initialWidth, setSize(maxWidth, 'x')) : initialWidth) - loadedWidth - interfaceWidth;
				settings.h = (maxHeight !== false ? Math.min(initialHeight, setSize(maxHeight, 'y')) : initialHeight) - loadedHeight - interfaceHeight;

				$loaded.css({width:'', height:settings.h});
				publicMethod.position();

				trigger(event_open);
				settings.get('onOpen');

				$groupControls.add($title).hide();

				$box.focus();
				
				if (settings.get('trapFocus')) {
					// Confine focus to the modal
					// Uses event capturing that is not supported in IE8-
					if (document.addEventListener) {

						document.addEventListener('focus', trapFocus, true);
						
						$events.one(event_closed, function () {
							document.removeEventListener('focus', trapFocus, true);
						});
					}
				}

				// Return focus on closing
				if (settings.get('returnFocus')) {
					$events.one(event_closed, function () {
						$(settings.el).focus();
					});
				}
			}

			var opacity = parseFloat(settings.get('opacity'));
			$overlay.css({
				opacity: opacity === opacity ? opacity : '',
				cursor: settings.get('overlayClose') ? 'pointer' : '',
				visibility: 'visible'
			}).show();
			
			if (settings.get('closeButton')) {
				$close.html(settings.get('close')).appendTo($content);
			} else {
				$close.appendTo('<div/>'); // replace with .detach() when dropping jQuery < 1.4
			}

			load();
		}
	}

	// Colorbox's markup needs to be added to the DOM prior to being called
	// so that the browser will go ahead and load the CSS background images.
	function appendHTML() {
		if (!$box) {
			init = false;
			$window = $(window);
			$box = $tag(div).attr({
				id: colorbox,
				'class': $.support.opacity === false ? prefix + 'IE' : '', // class for optional IE8 & lower targeted CSS.
				role: 'dialog',
				tabindex: '-1'
			}).hide();
			$overlay = $tag(div, "Overlay").hide();
			$loadingOverlay = $([$tag(div, "LoadingOverlay")[0],$tag(div, "LoadingGraphic")[0]]);
			$wrap = $tag(div, "Wrapper");
			$content = $tag(div, "Content").append(
				$title = $tag(div, "Title"),
				$current = $tag(div, "Current"),
				$prev = $('<button type="button"/>').attr({id:prefix+'Previous'}),
				$next = $('<button type="button"/>').attr({id:prefix+'Next'}),
				$slideshow = $tag('button', "Slideshow"),
				$loadingOverlay
			);

			$close = $('<button type="button"/>').attr({id:prefix+'Close'});
			
			$wrap.append( // The 3x3 Grid that makes up Colorbox
				$tag(div).append(
					$tag(div, "TopLeft"),
					$topBorder = $tag(div, "TopCenter"),
					$tag(div, "TopRight")
				),
				$tag(div, false, 'clear:left').append(
					$leftBorder = $tag(div, "MiddleLeft"),
					$content,
					$rightBorder = $tag(div, "MiddleRight")
				),
				$tag(div, false, 'clear:left').append(
					$tag(div, "BottomLeft"),
					$bottomBorder = $tag(div, "BottomCenter"),
					$tag(div, "BottomRight")
				)
			).find('div div').css({'float': 'left'});
			
			$loadingBay = $tag(div, false, 'position:absolute; width:9999px; visibility:hidden; display:none; max-width:none;');
			
			$groupControls = $next.add($prev).add($current).add($slideshow);
		}
		if (document.body && !$box.parent().length) {
			$(document.body).append($overlay, $box.append($wrap, $loadingBay));
		}
	}

	// Add Colorbox's event bindings
	function addBindings() {
		function clickHandler(e) {
			// ignore non-left-mouse-clicks and clicks modified with ctrl / command, shift, or alt.
			// See: http://jacklmoore.com/notes/click-events/
			if (!(e.which > 1 || e.shiftKey || e.altKey || e.metaKey || e.ctrlKey)) {
				e.preventDefault();
				launch(this);
			}
		}

		if ($box) {
			if (!init) {
				init = true;

				// Anonymous functions here keep the public method from being cached, thereby allowing them to be redefined on the fly.
				$next.click(function () {
					publicMethod.next();
				});
				$prev.click(function () {
					publicMethod.prev();
				});
				$close.click(function () {
					publicMethod.close();
				});
				$overlay.click(function () {
					if (settings.get('overlayClose')) {
						publicMethod.close();
					}
				});
				
				// Key Bindings
				$(document).bind('keydown.' + prefix, function (e) {
					var key = e.keyCode;
					if (open && settings.get('escKey') && key === 27) {
						e.preventDefault();
						publicMethod.close();
					}
					if (open && settings.get('arrowKey') && $related[1] && !e.altKey) {
						if (key === 37) {
							e.preventDefault();
							$prev.click();
						} else if (key === 39) {
							e.preventDefault();
							$next.click();
						}
					}
				});

				if ($.isFunction($.fn.on)) {
					// For jQuery 1.7+
					$(document).on('click.'+prefix, '.'+boxElement, clickHandler);
				} else {
					// For jQuery 1.3.x -> 1.6.x
					// This code is never reached in jQuery 1.9, so do not contact me about 'live' being removed.
					// This is not here for jQuery 1.9, it's here for legacy users.
					$('.'+boxElement).live('click.'+prefix, clickHandler);
				}
			}
			return true;
		}
		return false;
	}

	// Don't do anything if Colorbox already exists.
	if ($[colorbox]) {
		return;
	}

	// Append the HTML when the DOM loads
	$(appendHTML);


	// ****************
	// PUBLIC FUNCTIONS
	// Usage format: $.colorbox.close();
	// Usage from within an iframe: parent.jQuery.colorbox.close();
	// ****************
	
	publicMethod = $.fn[colorbox] = $[colorbox] = function (options, callback) {
		var settings;
		var $obj = this;

		options = options || {};

		if ($.isFunction($obj)) { // assume a call to $.colorbox
			$obj = $('<a/>');
			options.open = true;
		} else if (!$obj[0]) { // colorbox being applied to empty collection
			return $obj;
		}


		if (!$obj[0]) { // colorbox being applied to empty collection
			return $obj;
		}
		
		appendHTML();

		if (addBindings()) {

			if (callback) {
				options.onComplete = callback;
			}

			$obj.each(function () {
				var old = $.data(this, colorbox) || {};
				$.data(this, colorbox, $.extend(old, options));
			}).addClass(boxElement);

			settings = new Settings($obj[0], options);
			
			if (settings.get('open')) {
				launch($obj[0]);
			}
		}
		
		return $obj;
	};

	publicMethod.position = function (speed, loadedCallback) {
		var
		css,
		top = 0,
		left = 0,
		offset = $box.offset(),
		scrollTop,
		scrollLeft;
		
		$window.unbind('resize.' + prefix);

		// remove the modal so that it doesn't influence the document width/height
		$box.css({top: -9e4, left: -9e4});

		scrollTop = $window.scrollTop();
		scrollLeft = $window.scrollLeft();

		if (settings.get('fixed')) {
			offset.top -= scrollTop;
			offset.left -= scrollLeft;
			$box.css({position: 'fixed'});
		} else {
			top = scrollTop;
			left = scrollLeft;
			$box.css({position: 'absolute'});
		}

		// keeps the top and left positions within the browser's viewport.
		if (settings.get('right') !== false) {
			left += Math.max($window.width() - settings.w - loadedWidth - interfaceWidth - setSize(settings.get('right'), 'x'), 0);
		} else if (settings.get('left') !== false) {
			left += setSize(settings.get('left'), 'x');
		} else {
			left += Math.round(Math.max($window.width() - settings.w - loadedWidth - interfaceWidth, 0) / 2);
		}
		
		if (settings.get('bottom') !== false) {
			top += Math.max(winheight() - settings.h - loadedHeight - interfaceHeight - setSize(settings.get('bottom'), 'y'), 0);
		} else if (settings.get('top') !== false) {
			top += setSize(settings.get('top'), 'y');
		} else {
			top += Math.round(Math.max(winheight() - settings.h - loadedHeight - interfaceHeight, 0) / 2);
		}

		$box.css({top: offset.top, left: offset.left, visibility:'visible'});
		
		// this gives the wrapper plenty of breathing room so it's floated contents can move around smoothly,
		// but it has to be shrank down around the size of div#colorbox when it's done.  If not,
		// it can invoke an obscure IE bug when using iframes.
		$wrap[0].style.width = $wrap[0].style.height = "9999px";
		
		function modalDimensions() {
			$topBorder[0].style.width = $bottomBorder[0].style.width = $content[0].style.width = (parseInt($box[0].style.width,10) - interfaceWidth)+'px';
			$content[0].style.height = $leftBorder[0].style.height = $rightBorder[0].style.height = (parseInt($box[0].style.height,10) - interfaceHeight)+'px';
		}

		css = {width: settings.w + loadedWidth + interfaceWidth, height: settings.h + loadedHeight + interfaceHeight, top: top, left: left};

		// setting the speed to 0 if the content hasn't changed size or position
		if (speed) {
			var tempSpeed = 0;
			$.each(css, function(i){
				if (css[i] !== previousCSS[i]) {
					tempSpeed = speed;
					return;
				}
			});
			speed = tempSpeed;
		}

		previousCSS = css;

		if (!speed) {
			$box.css(css);
		}

		$box.dequeue().animate(css, {
			duration: speed || 0,
			complete: function () {
				modalDimensions();
				
				active = false;
				
				// shrink the wrapper down to exactly the size of colorbox to avoid a bug in IE's iframe implementation.
				$wrap[0].style.width = (settings.w + loadedWidth + interfaceWidth) + "px";
				$wrap[0].style.height = (settings.h + loadedHeight + interfaceHeight) + "px";
				
				if (settings.get('reposition')) {
					setTimeout(function () {  // small delay before binding onresize due to an IE8 bug.
						$window.bind('resize.' + prefix, publicMethod.position);
					}, 1);
				}

				if ($.isFunction(loadedCallback)) {
					loadedCallback();
				}
			},
			step: modalDimensions
		});
	};

	publicMethod.resize = function (options) {
		var scrolltop;
		
		if (open) {
			options = options || {};
			
			if (options.width) {
				settings.w = setSize(options.width, 'x') - loadedWidth - interfaceWidth;
			}

			if (options.innerWidth) {
				settings.w = setSize(options.innerWidth, 'x');
			}

			$loaded.css({width: settings.w});
			
			if (options.height) {
				settings.h = setSize(options.height, 'y') - loadedHeight - interfaceHeight;
			}

			if (options.innerHeight) {
				settings.h = setSize(options.innerHeight, 'y');
			}

			if (!options.innerHeight && !options.height) {
				scrolltop = $loaded.scrollTop();
				$loaded.css({height: "auto"});
				settings.h = $loaded.height();
			}

			$loaded.css({height: settings.h});

			if(scrolltop) {
				$loaded.scrollTop(scrolltop);
			}
			
			publicMethod.position(settings.get('transition') === "none" ? 0 : settings.get('speed'));
		}
	};

	publicMethod.prep = function (object) {
		if (!open) {
			return;
		}
		
		var callback, speed = settings.get('transition') === "none" ? 0 : settings.get('speed');

		$loaded.remove();

		$loaded = $tag(div, 'LoadedContent').append(object);
		
		function getWidth() {
			settings.w = settings.w || $loaded.width();
			settings.w = settings.mw && settings.mw < settings.w ? settings.mw : settings.w;
			return settings.w;
		}
		function getHeight() {
			settings.h = settings.h || $loaded.height();
			settings.h = settings.mh && settings.mh < settings.h ? settings.mh : settings.h;
			return settings.h;
		}
		
		$loaded.hide()
		.appendTo($loadingBay.show())// content has to be appended to the DOM for accurate size calculations.
		.css({width: getWidth(), overflow: settings.get('scrolling') ? 'auto' : 'hidden'})
		.css({height: getHeight()})// sets the height independently from the width in case the new width influences the value of height.
		.prependTo($content);
		
		$loadingBay.hide();
		
		// floating the IMG removes the bottom line-height and fixed a problem where IE miscalculates the width of the parent element as 100% of the document width.
		
		$(photo).css({'float': 'none'});

		setClass(settings.get('className'));

		callback = function () {
			var total = $related.length,
				iframe,
				complete;
			
			if (!open) {
				return;
			}
			
			function removeFilter() { // Needed for IE8 in versions of jQuery prior to 1.7.2
				if ($.support.opacity === false) {
					$box[0].style.removeAttribute('filter');
				}
			}
			
			complete = function () {
				clearTimeout(loadingTimer);
				$loadingOverlay.hide();
				trigger(event_complete);
				settings.get('onComplete');
			};

			
			$title.html(settings.get('title')).show();
			$loaded.show();
			
			if (total > 1) { // handle grouping
				if (typeof settings.get('current') === "string") {
					$current.html(settings.get('current').replace('{current}', index + 1).replace('{total}', total)).show();
				}
				
				$next[(settings.get('loop') || index < total - 1) ? "show" : "hide"]().html(settings.get('next'));
				$prev[(settings.get('loop') || index) ? "show" : "hide"]().html(settings.get('previous'));
				
				slideshow();
				
				// Preloads images within a rel group
				if (settings.get('preloading')) {
					$.each([getIndex(-1), getIndex(1)], function(){
						var img,
							i = $related[this],
							settings = new Settings(i, $.data(i, colorbox)),
							src = settings.get('href');

						if (src && isImage(settings, src)) {
							src = retinaUrl(settings, src);
							img = document.createElement('img');
							img.src = src;
						}
					});
				}
			} else {
				$groupControls.hide();
			}
			
			if (settings.get('iframe')) {
				iframe = document.createElement('iframe');
				
				if ('frameBorder' in iframe) {
					iframe.frameBorder = 0;
				}
				
				if ('allowTransparency' in iframe) {
					iframe.allowTransparency = "true";
				}

				if (!settings.get('scrolling')) {
					iframe.scrolling = "no";
				}
				
				$(iframe)
					.attr({
						src: settings.get('href'),
						name: (new Date()).getTime(), // give the iframe a unique name to prevent caching
						'class': prefix + 'Iframe',
						allowFullScreen : true // allow HTML5 video to go fullscreen
					})
					.one('load', complete)
					.appendTo($loaded);
				
				$events.one(event_purge, function () {
					iframe.src = "//about:blank";
				});

				if (settings.get('fastIframe')) {
					$(iframe).trigger('load');
				}
			} else {
				complete();
			}
			
			if (settings.get('transition') === 'fade') {
				$box.fadeTo(speed, 1, removeFilter);
			} else {
				removeFilter();
			}
		};
		
		if (settings.get('transition') === 'fade') {
			$box.fadeTo(speed, 0, function () {
				publicMethod.position(0, callback);
			});
		} else {
			publicMethod.position(speed, callback);
		}
	};

	function load () {
		var href, setResize, prep = publicMethod.prep, $inline, request = ++requests;
		
		active = true;
		
		photo = false;
		
		trigger(event_purge);
		trigger(event_load);
		settings.get('onLoad');
		
		settings.h = settings.get('height') ?
				setSize(settings.get('height'), 'y') - loadedHeight - interfaceHeight :
				settings.get('innerHeight') && setSize(settings.get('innerHeight'), 'y');
		
		settings.w = settings.get('width') ?
				setSize(settings.get('width'), 'x') - loadedWidth - interfaceWidth :
				settings.get('innerWidth') && setSize(settings.get('innerWidth'), 'x');
		
		// Sets the minimum dimensions for use in image scaling
		settings.mw = settings.w;
		settings.mh = settings.h;
		
		// Re-evaluate the minimum width and height based on maxWidth and maxHeight values.
		// If the width or height exceed the maxWidth or maxHeight, use the maximum values instead.
		if (settings.get('maxWidth')) {
			settings.mw = setSize(settings.get('maxWidth'), 'x') - loadedWidth - interfaceWidth;
			settings.mw = settings.w && settings.w < settings.mw ? settings.w : settings.mw;
		}
		if (settings.get('maxHeight')) {
			settings.mh = setSize(settings.get('maxHeight'), 'y') - loadedHeight - interfaceHeight;
			settings.mh = settings.h && settings.h < settings.mh ? settings.h : settings.mh;
		}
		
		href = settings.get('href');
		
		loadingTimer = setTimeout(function () {
			$loadingOverlay.show();
		}, 100);
		
		if (settings.get('inline')) {
			var $target = $(href);
			// Inserts an empty placeholder where inline content is being pulled from.
			// An event is bound to put inline content back when Colorbox closes or loads new content.
			$inline = $('<div>').hide().insertBefore($target);

			$events.one(event_purge, function () {
				$inline.replaceWith($target);
			});

			prep($target);
		} else if (settings.get('iframe')) {
			// IFrame element won't be added to the DOM until it is ready to be displayed,
			// to avoid problems with DOM-ready JS that might be trying to run in that iframe.
			prep(" ");
		} else if (settings.get('html')) {
			prep(settings.get('html'));
		} else if (isImage(settings, href)) {

			href = retinaUrl(settings, href);

			photo = new Image();

			$(photo)
			.addClass(prefix + 'Photo')
			.bind('error',function () {
				prep($tag(div, 'Error').html(settings.get('imgError')));
			})
			.one('load', function () {
				if (request !== requests) {
					return;
				}

				// A small pause because some browsers will occassionaly report a 
				// img.width and img.height of zero immediately after the img.onload fires
				setTimeout(function(){
					var percent;

					$.each(['alt', 'longdesc', 'aria-describedby'], function(i,val){
						var attr = $(settings.el).attr(val) || $(settings.el).attr('data-'+val);
						if (attr) {
							photo.setAttribute(val, attr);
						}
					});

					if (settings.get('retinaImage') && window.devicePixelRatio > 1) {
						photo.height = photo.height / window.devicePixelRatio;
						photo.width = photo.width / window.devicePixelRatio;
					}

					if (settings.get('scalePhotos')) {
						setResize = function () {
							photo.height -= photo.height * percent;
							photo.width -= photo.width * percent;
						};
						if (settings.mw && photo.width > settings.mw) {
							percent = (photo.width - settings.mw) / photo.width;
							setResize();
						}
						if (settings.mh && photo.height > settings.mh) {
							percent = (photo.height - settings.mh) / photo.height;
							setResize();
						}
					}
					
					if (settings.h) {
						photo.style.marginTop = Math.max(settings.mh - photo.height, 0) / 2 + 'px';
					}
					
					if ($related[1] && (settings.get('loop') || $related[index + 1])) {
						photo.style.cursor = 'pointer';
						photo.onclick = function () {
							publicMethod.next();
						};
					}

					photo.style.width = photo.width + 'px';
					photo.style.height = photo.height + 'px';
					prep(photo);
				}, 1);
			});
			
			photo.src = href;

		} else if (href) {
			$loadingBay.load(href, settings.get('data'), function (data, status) {
				if (request === requests) {
					prep(status === 'error' ? $tag(div, 'Error').html(settings.get('xhrError')) : $(this).contents());
				}
			});
		}
	}
		
	// Navigates to the next page/image in a set.
	publicMethod.next = function () {
		if (!active && $related[1] && (settings.get('loop') || $related[index + 1])) {
			index = getIndex(1);
			launch($related[index]);
		}
	};
	
	publicMethod.prev = function () {
		if (!active && $related[1] && (settings.get('loop') || index)) {
			index = getIndex(-1);
			launch($related[index]);
		}
	};

	// Note: to use this within an iframe use the following format: parent.jQuery.colorbox.close();
	publicMethod.close = function () {
		if (open && !closing) {
			
			closing = true;
			open = false;
			trigger(event_cleanup);
			settings.get('onCleanup');
			$window.unbind('.' + prefix);
			$overlay.fadeTo(settings.get('fadeOut') || 0, 0);
			
			$box.stop().fadeTo(settings.get('fadeOut') || 0, 0, function () {
				$box.hide();
				$overlay.hide();
				trigger(event_purge);
				$loaded.remove();
				
				setTimeout(function () {
					closing = false;
					trigger(event_closed);
					settings.get('onClosed');
				}, 1);
			});
		}
	};

	// Removes changes Colorbox made to the document, but does not remove the plugin.
	publicMethod.remove = function () {
		if (!$box) { return; }

		$box.stop();
		$[colorbox].close();
		$box.stop(false, true).remove();
		$overlay.remove();
		closing = false;
		$box = null;
		$('.' + boxElement)
			.removeData(colorbox)
			.removeClass(boxElement);

		$(document).unbind('click.'+prefix).unbind('keydown.'+prefix);
	};

	// A method for fetching the current element Colorbox is referencing.
	// returns a jQuery object.
	publicMethod.element = function () {
		return $(settings.el);
	};

	publicMethod.settings = defaults;

}(jQuery, document, window));

// Sticky Plugin v1.0.0 for jQuery
// =============
// Author: Anthony Garand
// Improvements by German M. Bravo (Kronuz) and Ruud Kamphuis (ruudk)
// Improvements by Leonardo C. Daronco (daronco)
// Created: 2/14/2011
// Date: 2/12/2012
// Website: http://labs.anthonygarand.com/sticky
// Description: Makes an element on the page stick on the screen as you scroll
//       It will only set the 'top' and 'position' of your element, you
//       might need to adjust the width in some cases.

(function($) {
  var defaults = {
      topSpacing: 0,
      bottomSpacing: 0,
      className: 'is-sticky',
      wrapperClassName: 'sticky-wrapper',
      center: false,
      getWidthFrom: '',
      responsiveWidth: false
    },
    $window = $(window),
    $document = $(document),
    sticked = [],
    windowHeight = $window.height(),
    scroller = function() {
      var scrollTop = $window.scrollTop(),
        documentHeight = $document.height(),
        dwh = documentHeight - windowHeight,
        extra = (scrollTop > dwh) ? dwh - scrollTop : 0;

      for (var i = 0; i < sticked.length; i++) {
        var s = sticked[i],
          elementTop = s.stickyWrapper.offset().top,
          etse = elementTop - s.topSpacing - extra;

        if (scrollTop <= etse) {
          if (s.currentTop !== null) {
            s.stickyElement
              .css('position', '')
              .css('top', '');
            s.stickyElement.trigger('sticky-end', [s]).parent().removeClass(s.className);
            s.currentTop = null;
          }
        }
        else {
          var newTop = documentHeight - s.stickyElement.outerHeight()
            - s.topSpacing - s.bottomSpacing - scrollTop - extra;
          if (newTop < 0) {
            newTop = newTop + s.topSpacing;
          } else {
            newTop = s.topSpacing;
          }
          if (s.currentTop != newTop) {
            s.stickyElement
              .css('position', 'fixed')
              .css('top', newTop);

            if (typeof s.getWidthFrom !== 'undefined') {
              s.stickyElement.css('width', $(s.getWidthFrom).width());
            }

            s.stickyElement.trigger('sticky-start', [s]).parent().addClass(s.className);
            s.currentTop = newTop;
          }
        }
      }
    },
    resizer = function() {
      windowHeight = $window.height();

      for (var i = 0; i < sticked.length; i++) {
        var s = sticked[i];
        if (typeof s.getWidthFrom !== 'undefined' && s.responsiveWidth === true) {
          s.stickyElement.css('width', $(s.getWidthFrom).width());
        }
      }
    },
    methods = {
      init: function(options) {
        var o = $.extend({}, defaults, options);
        return this.each(function() {
          var stickyElement = $(this);

          var stickyId = stickyElement.attr('id');
          var wrapperId = stickyId ? stickyId + '-' + defaults.wrapperClassName : defaults.wrapperClassName 
          var wrapper = $('<div></div>')
            .attr('id', stickyId + '-sticky-wrapper')
            .addClass(o.wrapperClassName);
          stickyElement.wrapAll(wrapper);

          if (o.center) {
            stickyElement.parent().css({width:stickyElement.outerWidth(),marginLeft:"auto",marginRight:"auto"});
          }

          if (stickyElement.css("float") == "right") {
            stickyElement.css({"float":"none"}).parent().css({"float":"right"});
          }

          var stickyWrapper = stickyElement.parent();
          stickyWrapper.css('height', stickyElement.outerHeight());
          sticked.push({
            topSpacing: o.topSpacing,
            bottomSpacing: o.bottomSpacing,
            stickyElement: stickyElement,
            currentTop: null,
            stickyWrapper: stickyWrapper,
            className: o.className,
            getWidthFrom: o.getWidthFrom,
            responsiveWidth: o.responsiveWidth
          });
        });
      },
      update: scroller,
      unstick: function(options) {
        return this.each(function() {
          var unstickyElement = $(this);

          var removeIdx = -1;
          for (var i = 0; i < sticked.length; i++)
          {
            if (sticked[i].stickyElement.get(0) == unstickyElement.get(0))
            {
                removeIdx = i;
            }
          }
          if(removeIdx != -1)
          {
            sticked.splice(removeIdx,1);
            unstickyElement.unwrap();
            unstickyElement.removeAttr('style');
          }
        });
      }
    };

  // should be more efficient than using $window.scroll(scroller) and $window.resize(resizer):
  if (window.addEventListener) {
    window.addEventListener('scroll', scroller, false);
    window.addEventListener('resize', resizer, false);
  } else if (window.attachEvent) {
    window.attachEvent('onscroll', scroller);
    window.attachEvent('onresize', resizer);
  }

  $.fn.sticky = function(method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (typeof method === 'object' || !method ) {
      return methods.init.apply( this, arguments );
    } else {
      $.error('Method ' + method + ' does not exist on jQuery.sticky');
    }
  };

  $.fn.unstick = function(method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (typeof method === 'object' || !method ) {
      return methods.unstick.apply( this, arguments );
    } else {
      $.error('Method ' + method + ' does not exist on jQuery.sticky');
    }

  };
  $(function() {
    setTimeout(scroller, 0);
  });
})(jQuery);

/*! Copyright (c) 2013 Brandon Aaron (http://brandon.aaron.sh)
 * Licensed under the MIT License (LICENSE.txt).
 *
 * Version: 3.1.9
 *
 * Requires: jQuery 1.2.2+
 */

(function (factory) {
    if ( typeof define === 'function' && define.amd ) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS style for Browserify
        module.exports = factory;
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {

    var toFix  = ['wheel', 'mousewheel', 'DOMMouseScroll', 'MozMousePixelScroll'],
        toBind = ( 'onwheel' in document || document.documentMode >= 9 ) ?
                    ['wheel'] : ['mousewheel', 'DomMouseScroll', 'MozMousePixelScroll'],
        slice  = Array.prototype.slice,
        nullLowestDeltaTimeout, lowestDelta;

    if ( $.event.fixHooks ) {
        for ( var i = toFix.length; i; ) {
            $.event.fixHooks[ toFix[--i] ] = $.event.mouseHooks;
        }
    }

    var special = $.event.special.mousewheel = {
        version: '3.1.9',

        setup: function() {
            if ( this.addEventListener ) {
                for ( var i = toBind.length; i; ) {
                    this.addEventListener( toBind[--i], handler, false );
                }
            } else {
                this.onmousewheel = handler;
            }
            // Store the line height and page height for this particular element
            $.data(this, 'mousewheel-line-height', special.getLineHeight(this));
            $.data(this, 'mousewheel-page-height', special.getPageHeight(this));
        },

        teardown: function() {
            if ( this.removeEventListener ) {
                for ( var i = toBind.length; i; ) {
                    this.removeEventListener( toBind[--i], handler, false );
                }
            } else {
                this.onmousewheel = null;
            }
        },

        getLineHeight: function(elem) {
            return parseInt($(elem)['offsetParent' in $.fn ? 'offsetParent' : 'parent']().css('fontSize'), 10);
        },

        getPageHeight: function(elem) {
            return $(elem).height();
        },

        settings: {
            adjustOldDeltas: true
        }
    };

    $.fn.extend({
        mousewheel: function(fn) {
            return fn ? this.bind('mousewheel', fn) : this.trigger('mousewheel');
        },

        unmousewheel: function(fn) {
            return this.unbind('mousewheel', fn);
        }
    });


    function handler(event) {
        var orgEvent   = event || window.event,
            args       = slice.call(arguments, 1),
            delta      = 0,
            deltaX     = 0,
            deltaY     = 0,
            absDelta   = 0;
        event = $.event.fix(orgEvent);
        event.type = 'mousewheel';

        // Old school scrollwheel delta
        if ( 'detail'      in orgEvent ) { deltaY = orgEvent.detail * -1;      }
        if ( 'wheelDelta'  in orgEvent ) { deltaY = orgEvent.wheelDelta;       }
        if ( 'wheelDeltaY' in orgEvent ) { deltaY = orgEvent.wheelDeltaY;      }
        if ( 'wheelDeltaX' in orgEvent ) { deltaX = orgEvent.wheelDeltaX * -1; }

        // Firefox < 17 horizontal scrolling related to DOMMouseScroll event
        if ( 'axis' in orgEvent && orgEvent.axis === orgEvent.HORIZONTAL_AXIS ) {
            deltaX = deltaY * -1;
            deltaY = 0;
        }

        // Set delta to be deltaY or deltaX if deltaY is 0 for backwards compatabilitiy
        delta = deltaY === 0 ? deltaX : deltaY;

        // New school wheel delta (wheel event)
        if ( 'deltaY' in orgEvent ) {
            deltaY = orgEvent.deltaY * -1;
            delta  = deltaY;
        }
        if ( 'deltaX' in orgEvent ) {
            deltaX = orgEvent.deltaX;
            if ( deltaY === 0 ) { delta  = deltaX * -1; }
        }

        // No change actually happened, no reason to go any further
        if ( deltaY === 0 && deltaX === 0 ) { return; }

        // Need to convert lines and pages to pixels if we aren't already in pixels
        // There are three delta modes:
        //   * deltaMode 0 is by pixels, nothing to do
        //   * deltaMode 1 is by lines
        //   * deltaMode 2 is by pages
        if ( orgEvent.deltaMode === 1 ) {
            var lineHeight = $.data(this, 'mousewheel-line-height');
            delta  *= lineHeight;
            deltaY *= lineHeight;
            deltaX *= lineHeight;
        } else if ( orgEvent.deltaMode === 2 ) {
            var pageHeight = $.data(this, 'mousewheel-page-height');
            delta  *= pageHeight;
            deltaY *= pageHeight;
            deltaX *= pageHeight;
        }

        // Store lowest absolute delta to normalize the delta values
        absDelta = Math.max( Math.abs(deltaY), Math.abs(deltaX) );

        if ( !lowestDelta || absDelta < lowestDelta ) {
            lowestDelta = absDelta;

            // Adjust older deltas if necessary
            if ( shouldAdjustOldDeltas(orgEvent, absDelta) ) {
                lowestDelta /= 40;
            }
        }

        // Adjust older deltas if necessary
        if ( shouldAdjustOldDeltas(orgEvent, absDelta) ) {
            // Divide all the things by 40!
            delta  /= 40;
            deltaX /= 40;
            deltaY /= 40;
        }

        // Get a whole, normalized value for the deltas
        delta  = Math[ delta  >= 1 ? 'floor' : 'ceil' ](delta  / lowestDelta);
        deltaX = Math[ deltaX >= 1 ? 'floor' : 'ceil' ](deltaX / lowestDelta);
        deltaY = Math[ deltaY >= 1 ? 'floor' : 'ceil' ](deltaY / lowestDelta);

        // Add information to the event object
        event.deltaX = deltaX;
        event.deltaY = deltaY;
        event.deltaFactor = lowestDelta;
        // Go ahead and set deltaMode to 0 since we converted to pixels
        // Although this is a little odd since we overwrite the deltaX/Y
        // properties with normalized deltas.
        event.deltaMode = 0;

        // Add event and delta to the front of the arguments
        args.unshift(event, delta, deltaX, deltaY);

        // Clearout lowestDelta after sometime to better
        // handle multiple device types that give different
        // a different lowestDelta
        // Ex: trackpad = 3 and mouse wheel = 120
        if (nullLowestDeltaTimeout) { clearTimeout(nullLowestDeltaTimeout); }
        nullLowestDeltaTimeout = setTimeout(nullLowestDelta, 200);

        return ($.event.dispatch || $.event.handle).apply(this, args);
    }

    function nullLowestDelta() {
        lowestDelta = null;
    }

    function shouldAdjustOldDeltas(orgEvent, absDelta) {
        // If this is an older event and the delta is divisable by 120,
        // then we are assuming that the browser is treating this as an
        // older mouse wheel event and that we should divide the deltas
        // by 40 to try and get a more usable deltaFactor.
        // Side note, this actually impacts the reported scroll distance
        // in older browsers and can cause scrolling to be slower than native.
        // Turn this off by setting $.event.special.mousewheel.settings.adjustOldDeltas to false.
        return special.settings.adjustOldDeltas && orgEvent.type === 'mousewheel' && absDelta % 120 === 0;
    }

}));


/*!
 * jScrollPane - v2.0.20 - 2014-10-23
 * http://jscrollpane.kelvinluck.com/
 *
 * Copyright (c) 2014 Kelvin Luck
 * Dual licensed under the MIT or GPL licenses.
 */

// Script: jScrollPane - cross browser customisable scrollbars
//
// *Version: 2.0.20, Last updated: 2014-10-23*
//
// Project Home - http://jscrollpane.kelvinluck.com/
// GitHub       - http://github.com/vitch/jScrollPane
// Source       - http://github.com/vitch/jScrollPane/raw/master/script/jquery.jscrollpane.js
// (Minified)   - http://github.com/vitch/jScrollPane/raw/master/script/jquery.jscrollpane.min.js
//
// About: License
//
// Copyright (c) 2014 Kelvin Luck
// Dual licensed under the MIT or GPL Version 2 licenses.
// http://jscrollpane.kelvinluck.com/MIT-LICENSE.txt
// http://jscrollpane.kelvinluck.com/GPL-LICENSE.txt
//
// About: Examples
//
// All examples and demos are available through the jScrollPane example site at:
// http://jscrollpane.kelvinluck.com/
//
// About: Support and Testing
//
// This plugin is tested on the browsers below and has been found to work reliably on them. If you run
// into a problem on one of the supported browsers then please visit the support section on the jScrollPane
// website (http://jscrollpane.kelvinluck.com/) for more information on getting support. You are also
// welcome to fork the project on GitHub if you can contribute a fix for a given issue. 
//
// jQuery Versions - tested in 1.4.2+ - reported to work in 1.3.x
// Browsers Tested - Firefox 3.6.8, Safari 5, Opera 10.6, Chrome 5.0, IE 6, 7, 8
//
// About: Release History
//
// 2.0.20 - (2014-10-23) Adds AMD support (thanks @carlosrberto) and support for overflow-x/overflow-y (thanks @darimpulso)
// 2.0.19 - (2013-11-16) Changes for more reliable scroll amount with latest mousewheel plugin (thanks @brandonaaron)
// 2.0.18 - (2013-10-23) Fix for issue with gutters and scrollToElement (thanks @Dubiy)
// 2.0.17 - (2013-08-17) Working correctly when box-sizing is set to border-box (thanks @pieht)
// 2.0.16 - (2013-07-30) Resetting left position when scroll is removed. Fixes #189
// 2.0.15 - (2013-07-29) Fixed issue with scrollToElement where the destX and destY are undefined.
// 2.0.14 - (2013-05-01) Updated to most recent mouse wheel plugin (see #106) and related changes for sensible scroll speed
// 2.0.13 - (2013-05-01) Switched to semver compatible version name
// 2.0.0beta12 - (2012-09-27) fix for jQuery 1.8+
// 2.0.0beta11 - (2012-05-14)
// 2.0.0beta10 - (2011-04-17) cleaner required size calculation, improved keyboard support, stickToBottom/Left, other small fixes
// 2.0.0beta9 - (2011-01-31) new API methods, bug fixes and correct keyboard support for FF/OSX
// 2.0.0beta8 - (2011-01-29) touchscreen support, improved keyboard support
// 2.0.0beta7 - (2011-01-23) scroll speed consistent (thanks Aivo Paas)
// 2.0.0beta6 - (2010-12-07) scrollToElement horizontal support
// 2.0.0beta5 - (2010-10-18) jQuery 1.4.3 support, various bug fixes
// 2.0.0beta4 - (2010-09-17) clickOnTrack support, bug fixes
// 2.0.0beta3 - (2010-08-27) Horizontal mousewheel, mwheelIntent, keyboard support, bug fixes
// 2.0.0beta2 - (2010-08-21) Bug fixes
// 2.0.0beta1 - (2010-08-17) Rewrite to follow modern best practices and enable horizontal scrolling, initially hidden
//							 elements and dynamically sized elements.
// 1.x - (2006-12-31 - 2010-07-31) Initial version, hosted at googlecode, deprecated

(function (plugin, window) {
	var factory = function($){
		return plugin($, window);
	}
  if ( typeof define === 'function' && define.amd ) {
      // AMD. Register as an anonymous module.
      define(['jquery'], factory);
  } else if (typeof exports === 'object') {
      // Node/CommonJS style for Browserify
      module.exports = factory;
  } else {
      // Browser globals
      factory(jQuery);
  }
}(function($,window,undefined){

	$.fn.jScrollPane = function(settings)
	{
		// JScrollPane "class" - public methods are available through $('selector').data('jsp')
		function JScrollPane(elem, s)
		{
			var settings, jsp = this, pane, paneWidth, paneHeight, container, contentWidth, contentHeight,
				percentInViewH, percentInViewV, isScrollableV, isScrollableH, verticalDrag, dragMaxY,
				verticalDragPosition, horizontalDrag, dragMaxX, horizontalDragPosition,
				verticalBar, verticalTrack, scrollbarWidth, verticalTrackHeight, verticalDragHeight, arrowUp, arrowDown,
				horizontalBar, horizontalTrack, horizontalTrackWidth, horizontalDragWidth, arrowLeft, arrowRight,
				reinitialiseInterval, originalPadding, originalPaddingTotalWidth, previousContentWidth,
				wasAtTop = true, wasAtLeft = true, wasAtBottom = false, wasAtRight = false,
				originalElement = elem.clone(false, false).empty(),
				mwEvent = $.fn.mwheelIntent ? 'mwheelIntent.jsp' : 'mousewheel.jsp';

			if (elem.css('box-sizing') === 'border-box') {
				originalPadding = 0;
				originalPaddingTotalWidth = 0;
			} else {
				originalPadding = elem.css('paddingTop') + ' ' +
									elem.css('paddingRight') + ' ' +
									elem.css('paddingBottom') + ' ' +
									elem.css('paddingLeft');	
				originalPaddingTotalWidth = (parseInt(elem.css('paddingLeft'), 10) || 0) +
											(parseInt(elem.css('paddingRight'), 10) || 0);
			}

			function initialise(s)
			{

				var /*firstChild, lastChild, */isMaintainingPositon, lastContentX, lastContentY,
						hasContainingSpaceChanged, originalScrollTop, originalScrollLeft,
						maintainAtBottom = false, maintainAtRight = false;

				settings = s;

				if (pane === undefined) {
					originalScrollTop = elem.scrollTop();
					originalScrollLeft = elem.scrollLeft();

					elem.css(
						{
							overflow: 'hidden',
							padding: 0
						}
					);
					// TODO: Deal with where width/ height is 0 as it probably means the element is hidden and we should
					// come back to it later and check once it is unhidden...
					paneWidth = elem.innerWidth() + originalPaddingTotalWidth;
					paneHeight = elem.innerHeight();

					elem.width(paneWidth);
					
					pane = $('<div class="jspPane" />').css('padding', originalPadding).append(elem.children());
					container = $('<div class="jspContainer" />')
						.css({
							'width': paneWidth + 'px',
							'height': paneHeight + 'px'
						}
					).append(pane).appendTo(elem);

					/*
					// Move any margins from the first and last children up to the container so they can still
					// collapse with neighbouring elements as they would before jScrollPane 
					firstChild = pane.find(':first-child');
					lastChild = pane.find(':last-child');
					elem.css(
						{
							'margin-top': firstChild.css('margin-top'),
							'margin-bottom': lastChild.css('margin-bottom')
						}
					);
					firstChild.css('margin-top', 0);
					lastChild.css('margin-bottom', 0);
					*/
				} else {
					elem.css('width', '');

					maintainAtBottom = settings.stickToBottom && isCloseToBottom();
					maintainAtRight  = settings.stickToRight  && isCloseToRight();

					hasContainingSpaceChanged = elem.innerWidth() + originalPaddingTotalWidth != paneWidth || elem.outerHeight() != paneHeight;

					if (hasContainingSpaceChanged) {
						paneWidth = elem.innerWidth() + originalPaddingTotalWidth;
						paneHeight = elem.innerHeight();
						container.css({
							width: paneWidth + 'px',
							height: paneHeight + 'px'
						});
					}

					// If nothing changed since last check...
					if (!hasContainingSpaceChanged && previousContentWidth == contentWidth && pane.outerHeight() == contentHeight) {
						elem.width(paneWidth);
						return;
					}
					previousContentWidth = contentWidth;
					
					pane.css('width', '');
					elem.width(paneWidth);

					container.find('>.jspVerticalBar,>.jspHorizontalBar').remove().end();
				}

				pane.css('overflow', 'auto');
				if (s.contentWidth) {
					contentWidth = s.contentWidth;
				} else {
					contentWidth = pane[0].scrollWidth;
				}
				contentHeight = pane[0].scrollHeight;
				pane.css('overflow', '');

				percentInViewH = contentWidth / paneWidth;
				percentInViewV = contentHeight / paneHeight;
				isScrollableV = percentInViewV > 1;

				isScrollableH = percentInViewH > 1;

				//console.log(paneWidth, paneHeight, contentWidth, contentHeight, percentInViewH, percentInViewV, isScrollableH, isScrollableV);

				if (!(isScrollableH || isScrollableV)) {
					elem.removeClass('jspScrollable');
					pane.css({
            top: 0,
            left: 0,
						width: container.width() - originalPaddingTotalWidth
					});
					removeMousewheel();
					removeFocusHandler();
					removeKeyboardNav();
					removeClickOnTrack();
				} else {
					elem.addClass('jspScrollable');

					isMaintainingPositon = settings.maintainPosition && (verticalDragPosition || horizontalDragPosition);
					if (isMaintainingPositon) {
						lastContentX = contentPositionX();
						lastContentY = contentPositionY();
					}

					initialiseVerticalScroll();
					initialiseHorizontalScroll();
					resizeScrollbars();

					if (isMaintainingPositon) {
						scrollToX(maintainAtRight  ? (contentWidth  - paneWidth ) : lastContentX, false);
						scrollToY(maintainAtBottom ? (contentHeight - paneHeight) : lastContentY, false);
					}

					initFocusHandler();
					initMousewheel();
					initTouch();
					
					if (settings.enableKeyboardNavigation) {
						initKeyboardNav();
					}
					if (settings.clickOnTrack) {
						initClickOnTrack();
					}
					
					observeHash();
					if (settings.hijackInternalLinks) {
						hijackInternalLinks();
					}
				}

				if (settings.autoReinitialise && !reinitialiseInterval) {
					reinitialiseInterval = setInterval(
						function()
						{
							initialise(settings);
						},
						settings.autoReinitialiseDelay
					);
				} else if (!settings.autoReinitialise && reinitialiseInterval) {
					clearInterval(reinitialiseInterval);
				}

				originalScrollTop && elem.scrollTop(0) && scrollToY(originalScrollTop, false);
				originalScrollLeft && elem.scrollLeft(0) && scrollToX(originalScrollLeft, false);

				elem.trigger('jsp-initialised', [isScrollableH || isScrollableV]);
			}

			function initialiseVerticalScroll()
			{
				if (isScrollableV) {

					container.append(
						$('<div class="jspVerticalBar" />').append(
							$('<div class="jspCap jspCapTop" />'),
							$('<div class="jspTrack" />').append(
								$('<div class="jspDrag" />').append(
									$('<div class="jspDragTop" />'),
									$('<div class="jspDragBottom" />')
								)
							),
							$('<div class="jspCap jspCapBottom" />')
						)
					);

					verticalBar = container.find('>.jspVerticalBar');
					verticalTrack = verticalBar.find('>.jspTrack');
					verticalDrag = verticalTrack.find('>.jspDrag');

					if (settings.showArrows) {
						arrowUp = $('<a class="jspArrow jspArrowUp" />').bind(
							'mousedown.jsp', getArrowScroll(0, -1)
						).bind('click.jsp', nil);
						arrowDown = $('<a class="jspArrow jspArrowDown" />').bind(
							'mousedown.jsp', getArrowScroll(0, 1)
						).bind('click.jsp', nil);
						if (settings.arrowScrollOnHover) {
							arrowUp.bind('mouseover.jsp', getArrowScroll(0, -1, arrowUp));
							arrowDown.bind('mouseover.jsp', getArrowScroll(0, 1, arrowDown));
						}

						appendArrows(verticalTrack, settings.verticalArrowPositions, arrowUp, arrowDown);
					}

					verticalTrackHeight = paneHeight;
					container.find('>.jspVerticalBar>.jspCap:visible,>.jspVerticalBar>.jspArrow').each(
						function()
						{
							verticalTrackHeight -= $(this).outerHeight();
						}
					);


					verticalDrag.hover(
						function()
						{
							verticalDrag.addClass('jspHover');
						},
						function()
						{
							verticalDrag.removeClass('jspHover');
						}
					).bind(
						'mousedown.jsp',
						function(e)
						{
							// Stop IE from allowing text selection
							$('html').bind('dragstart.jsp selectstart.jsp', nil);

							verticalDrag.addClass('jspActive');

							var startY = e.pageY - verticalDrag.position().top;

							$('html').bind(
								'mousemove.jsp',
								function(e)
								{
									positionDragY(e.pageY - startY, false);
								}
							).bind('mouseup.jsp mouseleave.jsp', cancelDrag);
							return false;
						}
					);
					sizeVerticalScrollbar();
				}
			}

			function sizeVerticalScrollbar()
			{
				verticalTrack.height(verticalTrackHeight + 'px');
				verticalDragPosition = 0;
				scrollbarWidth = settings.verticalGutter + verticalTrack.outerWidth();

				// Make the pane thinner to allow for the vertical scrollbar
				pane.width(paneWidth - scrollbarWidth - originalPaddingTotalWidth);

				// Add margin to the left of the pane if scrollbars are on that side (to position
				// the scrollbar on the left or right set it's left or right property in CSS)
				try {
					if (verticalBar.position().left === 0) {
						pane.css('margin-left', scrollbarWidth + 'px');
					}
				} catch (err) {
				}
			}

			function initialiseHorizontalScroll()
			{
				if (isScrollableH) {

					container.append(
						$('<div class="jspHorizontalBar" />').append(
							$('<div class="jspCap jspCapLeft" />'),
							$('<div class="jspTrack" />').append(
								$('<div class="jspDrag" />').append(
									$('<div class="jspDragLeft" />'),
									$('<div class="jspDragRight" />')
								)
							),
							$('<div class="jspCap jspCapRight" />')
						)
					);

					horizontalBar = container.find('>.jspHorizontalBar');
					horizontalTrack = horizontalBar.find('>.jspTrack');
					horizontalDrag = horizontalTrack.find('>.jspDrag');

					if (settings.showArrows) {
						arrowLeft = $('<a class="jspArrow jspArrowLeft" />').bind(
							'mousedown.jsp', getArrowScroll(-1, 0)
						).bind('click.jsp', nil);
						arrowRight = $('<a class="jspArrow jspArrowRight" />').bind(
							'mousedown.jsp', getArrowScroll(1, 0)
						).bind('click.jsp', nil);
						if (settings.arrowScrollOnHover) {
							arrowLeft.bind('mouseover.jsp', getArrowScroll(-1, 0, arrowLeft));
							arrowRight.bind('mouseover.jsp', getArrowScroll(1, 0, arrowRight));
						}
						appendArrows(horizontalTrack, settings.horizontalArrowPositions, arrowLeft, arrowRight);
					}

					horizontalDrag.hover(
						function()
						{
							horizontalDrag.addClass('jspHover');
						},
						function()
						{
							horizontalDrag.removeClass('jspHover');
						}
					).bind(
						'mousedown.jsp',
						function(e)
						{
							// Stop IE from allowing text selection
							$('html').bind('dragstart.jsp selectstart.jsp', nil);

							horizontalDrag.addClass('jspActive');

							var startX = e.pageX - horizontalDrag.position().left;

							$('html').bind(
								'mousemove.jsp',
								function(e)
								{
									positionDragX(e.pageX - startX, false);
								}
							).bind('mouseup.jsp mouseleave.jsp', cancelDrag);
							return false;
						}
					);
					horizontalTrackWidth = container.innerWidth();
					sizeHorizontalScrollbar();
				}
			}

			function sizeHorizontalScrollbar()
			{
				container.find('>.jspHorizontalBar>.jspCap:visible,>.jspHorizontalBar>.jspArrow').each(
					function()
					{
						horizontalTrackWidth -= $(this).outerWidth();
					}
				);

				horizontalTrack.width(horizontalTrackWidth + 'px');
				horizontalDragPosition = 0;
			}

			function resizeScrollbars()
			{
				if (isScrollableH && isScrollableV) {
					var horizontalTrackHeight = horizontalTrack.outerHeight(),
						verticalTrackWidth = verticalTrack.outerWidth();
					verticalTrackHeight -= horizontalTrackHeight;
					$(horizontalBar).find('>.jspCap:visible,>.jspArrow').each(
						function()
						{
							horizontalTrackWidth += $(this).outerWidth();
						}
					);
					horizontalTrackWidth -= verticalTrackWidth;
					paneHeight -= verticalTrackWidth;
					paneWidth -= horizontalTrackHeight;
					horizontalTrack.parent().append(
						$('<div class="jspCorner" />').css('width', horizontalTrackHeight + 'px')
					);
					sizeVerticalScrollbar();
					sizeHorizontalScrollbar();
				}
				// reflow content
				if (isScrollableH) {
					pane.width((container.outerWidth() - originalPaddingTotalWidth) + 'px');
				}
				contentHeight = pane.outerHeight();
				percentInViewV = contentHeight / paneHeight;

				if (isScrollableH) {
					horizontalDragWidth = Math.ceil(1 / percentInViewH * horizontalTrackWidth);
					if (horizontalDragWidth > settings.horizontalDragMaxWidth) {
						horizontalDragWidth = settings.horizontalDragMaxWidth;
					} else if (horizontalDragWidth < settings.horizontalDragMinWidth) {
						horizontalDragWidth = settings.horizontalDragMinWidth;
					}
					horizontalDrag.width(horizontalDragWidth + 'px');
					dragMaxX = horizontalTrackWidth - horizontalDragWidth;
					_positionDragX(horizontalDragPosition); // To update the state for the arrow buttons
				}
				if (isScrollableV) {
					verticalDragHeight = Math.ceil(1 / percentInViewV * verticalTrackHeight);
					if (verticalDragHeight > settings.verticalDragMaxHeight) {
						verticalDragHeight = settings.verticalDragMaxHeight;
					} else if (verticalDragHeight < settings.verticalDragMinHeight) {
						verticalDragHeight = settings.verticalDragMinHeight;
					}
					verticalDrag.height(verticalDragHeight + 'px');
					dragMaxY = verticalTrackHeight - verticalDragHeight;
					_positionDragY(verticalDragPosition); // To update the state for the arrow buttons
				}
			}

			function appendArrows(ele, p, a1, a2)
			{
				var p1 = "before", p2 = "after", aTemp;
				
				// Sniff for mac... Is there a better way to determine whether the arrows would naturally appear
				// at the top or the bottom of the bar?
				if (p == "os") {
					p = /Mac/.test(navigator.platform) ? "after" : "split";
				}
				if (p == p1) {
					p2 = p;
				} else if (p == p2) {
					p1 = p;
					aTemp = a1;
					a1 = a2;
					a2 = aTemp;
				}

				ele[p1](a1)[p2](a2);
			}

			function getArrowScroll(dirX, dirY, ele)
			{
				return function()
				{
					arrowScroll(dirX, dirY, this, ele);
					this.blur();
					return false;
				};
			}

			function arrowScroll(dirX, dirY, arrow, ele)
			{
				arrow = $(arrow).addClass('jspActive');

				var eve,
					scrollTimeout,
					isFirst = true,
					doScroll = function()
					{
						if (dirX !== 0) {
							jsp.scrollByX(dirX * settings.arrowButtonSpeed);
						}
						if (dirY !== 0) {
							jsp.scrollByY(dirY * settings.arrowButtonSpeed);
						}
						scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.arrowRepeatFreq);
						isFirst = false;
					};

				doScroll();

				eve = ele ? 'mouseout.jsp' : 'mouseup.jsp';
				ele = ele || $('html');
				ele.bind(
					eve,
					function()
					{
						arrow.removeClass('jspActive');
						scrollTimeout && clearTimeout(scrollTimeout);
						scrollTimeout = null;
						ele.unbind(eve);
					}
				);
			}

			function initClickOnTrack()
			{
				removeClickOnTrack();
				if (isScrollableV) {
					verticalTrack.bind(
						'mousedown.jsp',
						function(e)
						{
							if (e.originalTarget === undefined || e.originalTarget == e.currentTarget) {
								var clickedTrack = $(this),
									offset = clickedTrack.offset(),
									direction = e.pageY - offset.top - verticalDragPosition,
									scrollTimeout,
									isFirst = true,
									doScroll = function()
									{
										var offset = clickedTrack.offset(),
											pos = e.pageY - offset.top - verticalDragHeight / 2,
											contentDragY = paneHeight * settings.scrollPagePercent,
											dragY = dragMaxY * contentDragY / (contentHeight - paneHeight);
										if (direction < 0) {
											if (verticalDragPosition - dragY > pos) {
												jsp.scrollByY(-contentDragY);
											} else {
												positionDragY(pos);
											}
										} else if (direction > 0) {
											if (verticalDragPosition + dragY < pos) {
												jsp.scrollByY(contentDragY);
											} else {
												positionDragY(pos);
											}
										} else {
											cancelClick();
											return;
										}
										scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.trackClickRepeatFreq);
										isFirst = false;
									},
									cancelClick = function()
									{
										scrollTimeout && clearTimeout(scrollTimeout);
										scrollTimeout = null;
										$(document).unbind('mouseup.jsp', cancelClick);
									};
								doScroll();
								$(document).bind('mouseup.jsp', cancelClick);
								return false;
							}
						}
					);
				}
				
				if (isScrollableH) {
					horizontalTrack.bind(
						'mousedown.jsp',
						function(e)
						{
							if (e.originalTarget === undefined || e.originalTarget == e.currentTarget) {
								var clickedTrack = $(this),
									offset = clickedTrack.offset(),
									direction = e.pageX - offset.left - horizontalDragPosition,
									scrollTimeout,
									isFirst = true,
									doScroll = function()
									{
										var offset = clickedTrack.offset(),
											pos = e.pageX - offset.left - horizontalDragWidth / 2,
											contentDragX = paneWidth * settings.scrollPagePercent,
											dragX = dragMaxX * contentDragX / (contentWidth - paneWidth);
										if (direction < 0) {
											if (horizontalDragPosition - dragX > pos) {
												jsp.scrollByX(-contentDragX);
											} else {
												positionDragX(pos);
											}
										} else if (direction > 0) {
											if (horizontalDragPosition + dragX < pos) {
												jsp.scrollByX(contentDragX);
											} else {
												positionDragX(pos);
											}
										} else {
											cancelClick();
											return;
										}
										scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.trackClickRepeatFreq);
										isFirst = false;
									},
									cancelClick = function()
									{
										scrollTimeout && clearTimeout(scrollTimeout);
										scrollTimeout = null;
										$(document).unbind('mouseup.jsp', cancelClick);
									};
								doScroll();
								$(document).bind('mouseup.jsp', cancelClick);
								return false;
							}
						}
					);
				}
			}

			function removeClickOnTrack()
			{
				if (horizontalTrack) {
					horizontalTrack.unbind('mousedown.jsp');
				}
				if (verticalTrack) {
					verticalTrack.unbind('mousedown.jsp');
				}
			}

			function cancelDrag()
			{
				$('html').unbind('dragstart.jsp selectstart.jsp mousemove.jsp mouseup.jsp mouseleave.jsp');

				if (verticalDrag) {
					verticalDrag.removeClass('jspActive');
				}
				if (horizontalDrag) {
					horizontalDrag.removeClass('jspActive');
				}
			}

			function positionDragY(destY, animate)
			{
				if (!isScrollableV) {
					return;
				}
				if (destY < 0) {
					destY = 0;
				} else if (destY > dragMaxY) {
					destY = dragMaxY;
				}

				// can't just check if(animate) because false is a valid value that could be passed in...
				if (animate === undefined) {
					animate = settings.animateScroll;
				}
				if (animate) {
					jsp.animate(verticalDrag, 'top', destY,	_positionDragY);
				} else {
					verticalDrag.css('top', destY);
					_positionDragY(destY);
				}

			}

			function _positionDragY(destY)
			{
				if (destY === undefined) {
					destY = verticalDrag.position().top;
				}

				container.scrollTop(0);
				verticalDragPosition = destY || 0;

				var isAtTop = verticalDragPosition === 0,
					isAtBottom = verticalDragPosition == dragMaxY,
					percentScrolled = destY/ dragMaxY,
					destTop = -percentScrolled * (contentHeight - paneHeight);

				if (wasAtTop != isAtTop || wasAtBottom != isAtBottom) {
					wasAtTop = isAtTop;
					wasAtBottom = isAtBottom;
					elem.trigger('jsp-arrow-change', [wasAtTop, wasAtBottom, wasAtLeft, wasAtRight]);
				}
				
				updateVerticalArrows(isAtTop, isAtBottom);
				pane.css('top', destTop);
				elem.trigger('jsp-scroll-y', [-destTop, isAtTop, isAtBottom]).trigger('scroll');
			}

			function positionDragX(destX, animate)
			{
				if (!isScrollableH) {
					return;
				}
				if (destX < 0) {
					destX = 0;
				} else if (destX > dragMaxX) {
					destX = dragMaxX;
				}

				if (animate === undefined) {
					animate = settings.animateScroll;
				}
				if (animate) {
					jsp.animate(horizontalDrag, 'left', destX,	_positionDragX);
				} else {
					horizontalDrag.css('left', destX);
					_positionDragX(destX);
				}
			}

			function _positionDragX(destX)
			{
				if (destX === undefined) {
					destX = horizontalDrag.position().left;
				}

				container.scrollTop(0);
				horizontalDragPosition = destX ||0;

				var isAtLeft = horizontalDragPosition === 0,
					isAtRight = horizontalDragPosition == dragMaxX,
					percentScrolled = destX / dragMaxX,
					destLeft = -percentScrolled * (contentWidth - paneWidth);

				if (wasAtLeft != isAtLeft || wasAtRight != isAtRight) {
					wasAtLeft = isAtLeft;
					wasAtRight = isAtRight;
					elem.trigger('jsp-arrow-change', [wasAtTop, wasAtBottom, wasAtLeft, wasAtRight]);
				}
				
				updateHorizontalArrows(isAtLeft, isAtRight);
				pane.css('left', destLeft);
				elem.trigger('jsp-scroll-x', [-destLeft, isAtLeft, isAtRight]).trigger('scroll');
			}

			function updateVerticalArrows(isAtTop, isAtBottom)
			{
				if (settings.showArrows) {
					arrowUp[isAtTop ? 'addClass' : 'removeClass']('jspDisabled');
					arrowDown[isAtBottom ? 'addClass' : 'removeClass']('jspDisabled');
				}
			}

			function updateHorizontalArrows(isAtLeft, isAtRight)
			{
				if (settings.showArrows) {
					arrowLeft[isAtLeft ? 'addClass' : 'removeClass']('jspDisabled');
					arrowRight[isAtRight ? 'addClass' : 'removeClass']('jspDisabled');
				}
			}

			function scrollToY(destY, animate)
			{
				var percentScrolled = destY / (contentHeight - paneHeight);
				positionDragY(percentScrolled * dragMaxY, animate);
			}

			function scrollToX(destX, animate)
			{
				var percentScrolled = destX / (contentWidth - paneWidth);
				positionDragX(percentScrolled * dragMaxX, animate);
			}

			function scrollToElement(ele, stickToTop, animate)
			{
				var e, eleHeight, eleWidth, eleTop = 0, eleLeft = 0, viewportTop, viewportLeft, maxVisibleEleTop, maxVisibleEleLeft, destY, destX;

				// Legal hash values aren't necessarily legal jQuery selectors so we need to catch any
				// errors from the lookup...
				try {
					e = $(ele);
				} catch (err) {
					return;
				}
				eleHeight = e.outerHeight();
				eleWidth= e.outerWidth();

				container.scrollTop(0);
				container.scrollLeft(0);
				
				// loop through parents adding the offset top of any elements that are relatively positioned between
				// the focused element and the jspPane so we can get the true distance from the top
				// of the focused element to the top of the scrollpane...
				while (!e.is('.jspPane')) {
					eleTop += e.position().top;
					eleLeft += e.position().left;
					e = e.offsetParent();
					if (/^body|html$/i.test(e[0].nodeName)) {
						// we ended up too high in the document structure. Quit!
						return;
					}
				}

				viewportTop = contentPositionY();
				maxVisibleEleTop = viewportTop + paneHeight;
				if (eleTop < viewportTop || stickToTop) { // element is above viewport
					destY = eleTop - settings.horizontalGutter;
				} else if (eleTop + eleHeight > maxVisibleEleTop) { // element is below viewport
					destY = eleTop - paneHeight + eleHeight + settings.horizontalGutter;
				}
				if (!isNaN(destY)) {
					scrollToY(destY, animate);
				}
				
				viewportLeft = contentPositionX();
	            maxVisibleEleLeft = viewportLeft + paneWidth;
	            if (eleLeft < viewportLeft || stickToTop) { // element is to the left of viewport
	                destX = eleLeft - settings.horizontalGutter;
	            } else if (eleLeft + eleWidth > maxVisibleEleLeft) { // element is to the right viewport
	                destX = eleLeft - paneWidth + eleWidth + settings.horizontalGutter;
	            }
	            if (!isNaN(destX)) {
	                scrollToX(destX, animate);
	            }

			}

			function contentPositionX()
			{
				return -pane.position().left;
			}

			function contentPositionY()
			{
				return -pane.position().top;
			}

			function isCloseToBottom()
			{
				var scrollableHeight = contentHeight - paneHeight;
				return (scrollableHeight > 20) && (scrollableHeight - contentPositionY() < 10);
			}

			function isCloseToRight()
			{
				var scrollableWidth = contentWidth - paneWidth;
				return (scrollableWidth > 20) && (scrollableWidth - contentPositionX() < 10);
			}

			function initMousewheel()
			{
				container.unbind(mwEvent).bind(
					mwEvent,
					function (event, delta, deltaX, deltaY) {

                        if (!horizontalDragPosition) horizontalDragPosition = 0;
                        if (!verticalDragPosition) verticalDragPosition = 0;

						var dX = horizontalDragPosition, dY = verticalDragPosition, factor = event.deltaFactor || settings.mouseWheelSpeed;
						jsp.scrollBy(deltaX * factor, -deltaY * factor, false);
						// return true if there was no movement so rest of screen can scroll
						return dX == horizontalDragPosition && dY == verticalDragPosition;
					}
				);
			}

			function removeMousewheel()
			{
				container.unbind(mwEvent);
			}

			function nil()
			{
				return false;
			}

			function initFocusHandler()
			{
				pane.find(':input,a').unbind('focus.jsp').bind(
					'focus.jsp',
					function(e)
					{
						scrollToElement(e.target, false);
					}
				);
			}

			function removeFocusHandler()
			{
				pane.find(':input,a').unbind('focus.jsp');
			}
			
			function initKeyboardNav()
			{
				var keyDown, elementHasScrolled, validParents = [];
				isScrollableH && validParents.push(horizontalBar[0]);
				isScrollableV && validParents.push(verticalBar[0]);
				
				// IE also focuses elements that don't have tabindex set.
				pane.focus(
					function()
					{
						elem.focus();
					}
				);
				
				elem.attr('tabindex', 0)
					.unbind('keydown.jsp keypress.jsp')
					.bind(
						'keydown.jsp',
						function(e)
						{
							if (e.target !== this && !(validParents.length && $(e.target).closest(validParents).length)){
								return;
							}
							var dX = horizontalDragPosition, dY = verticalDragPosition;
							switch(e.keyCode) {
								case 40: // down
								case 38: // up
								case 34: // page down
								case 32: // space
								case 33: // page up
								case 39: // right
								case 37: // left
									keyDown = e.keyCode;
									keyDownHandler();
									break;
								case 35: // end
									scrollToY(contentHeight - paneHeight);
									keyDown = null;
									break;
								case 36: // home
									scrollToY(0);
									keyDown = null;
									break;
							}

							elementHasScrolled = e.keyCode == keyDown && dX != horizontalDragPosition || dY != verticalDragPosition;
							return !elementHasScrolled;
						}
					).bind(
						'keypress.jsp', // For FF/ OSX so that we can cancel the repeat key presses if the JSP scrolls...
						function(e)
						{
							if (e.keyCode == keyDown) {
								keyDownHandler();
							}
							return !elementHasScrolled;
						}
					);
				
				if (settings.hideFocus) {
					elem.css('outline', 'none');
					if ('hideFocus' in container[0]){
						elem.attr('hideFocus', true);
					}
				} else {
					elem.css('outline', '');
					if ('hideFocus' in container[0]){
						elem.attr('hideFocus', false);
					}
				}
				
				function keyDownHandler()
				{
					var dX = horizontalDragPosition, dY = verticalDragPosition;
					switch(keyDown) {
						case 40: // down
							jsp.scrollByY(settings.keyboardSpeed, false);
							break;
						case 38: // up
							jsp.scrollByY(-settings.keyboardSpeed, false);
							break;
						case 34: // page down
						case 32: // space
							jsp.scrollByY(paneHeight * settings.scrollPagePercent, false);
							break;
						case 33: // page up
							jsp.scrollByY(-paneHeight * settings.scrollPagePercent, false);
							break;
						case 39: // right
							jsp.scrollByX(settings.keyboardSpeed, false);
							break;
						case 37: // left
							jsp.scrollByX(-settings.keyboardSpeed, false);
							break;
					}

					elementHasScrolled = dX != horizontalDragPosition || dY != verticalDragPosition;
					return elementHasScrolled;
				}
			}
			
			function removeKeyboardNav()
			{
				elem.attr('tabindex', '-1')
					.removeAttr('tabindex')
					.unbind('keydown.jsp keypress.jsp');
			}

			function observeHash()
			{
				if (location.hash && location.hash.length > 1) {
					var e,
						retryInt,
						hash = escape(location.hash.substr(1)) // hash must be escaped to prevent XSS
						;
					try {
						e = $('#' + hash + ', a[name="' + hash + '"]');
					} catch (err) {
						return;
					}

					if (e.length && pane.find(hash)) {
						// nasty workaround but it appears to take a little while before the hash has done its thing
						// to the rendered page so we just wait until the container's scrollTop has been messed up.
						if (container.scrollTop() === 0) {
							retryInt = setInterval(
								function()
								{
									if (container.scrollTop() > 0) {
										scrollToElement(e, true);
										$(document).scrollTop(container.position().top);
										clearInterval(retryInt);
									}
								},
								50
							);
						} else {
							scrollToElement(e, true);
							$(document).scrollTop(container.position().top);
						}
					}
				}
			}

			function hijackInternalLinks()
			{
				// only register the link handler once
				if ($(document.body).data('jspHijack')) {
					return;
				}

				// remember that the handler was bound
				$(document.body).data('jspHijack', true);

				// use live handler to also capture newly created links
				$(document.body).delegate('a[href*=#]', 'click', function(event) {
					// does the link point to the same page?
					// this also takes care of cases with a <base>-Tag or Links not starting with the hash #
					// e.g. <a href="index.html#test"> when the current url already is index.html
					var href = this.href.substr(0, this.href.indexOf('#')),
						locationHref = location.href,
						hash,
						element,
						container,
						jsp,
						scrollTop,
						elementTop;
					if (location.href.indexOf('#') !== -1) {
						locationHref = location.href.substr(0, location.href.indexOf('#'));
					}
					if (href !== locationHref) {
						// the link points to another page
						return;
					}

					// check if jScrollPane should handle this click event
					hash = escape(this.href.substr(this.href.indexOf('#') + 1));

					// find the element on the page
					element;
					try {
						element = $('#' + hash + ', a[name="' + hash + '"]');
					} catch (e) {
						// hash is not a valid jQuery identifier
						return;
					}

					if (!element.length) {
						// this link does not point to an element on this page
						return;
					}

					container = element.closest('.jspScrollable');
					jsp = container.data('jsp');

					// jsp might be another jsp instance than the one, that bound this event
					// remember: this event is only bound once for all instances.
					jsp.scrollToElement(element, true);

					if (container[0].scrollIntoView) {
						// also scroll to the top of the container (if it is not visible)
						scrollTop = $(window).scrollTop();
						elementTop = element.offset().top;
						if (elementTop < scrollTop || elementTop > scrollTop + $(window).height()) {
							container[0].scrollIntoView();
						}
					}

					// jsp handled this event, prevent the browser default (scrolling :P)
					event.preventDefault();
				});
			}
			
			// Init touch on iPad, iPhone, iPod, Android
			function initTouch()
			{
				var startX,
					startY,
					touchStartX,
					touchStartY,
					moved,
					moving = false;
  
				container.unbind('touchstart.jsp touchmove.jsp touchend.jsp click.jsp-touchclick').bind(
					'touchstart.jsp',
					function(e)
					{
						var touch = e.originalEvent.touches[0];
						startX = contentPositionX();
						startY = contentPositionY();
						touchStartX = touch.pageX;
						touchStartY = touch.pageY;
						moved = false;
						moving = true;
					}
				).bind(
					'touchmove.jsp',
					function(ev)
					{
						if(!moving) {
							return;
						}
						
						var touchPos = ev.originalEvent.touches[0],
							dX = horizontalDragPosition, dY = verticalDragPosition;
						
						jsp.scrollTo(startX + touchStartX - touchPos.pageX, startY + touchStartY - touchPos.pageY);
						
						moved = moved || Math.abs(touchStartX - touchPos.pageX) > 5 || Math.abs(touchStartY - touchPos.pageY) > 5;
						
						// return true if there was no movement so rest of screen can scroll
						return dX == horizontalDragPosition && dY == verticalDragPosition;
					}
				).bind(
					'touchend.jsp',
					function(e)
					{
						moving = false;
						/*if(moved) {
							return false;
						}*/
					}
				).bind(
					'click.jsp-touchclick',
					function(e)
					{
						if(moved) {
							moved = false;
							return false;
						}
					}
				);
			}
			
			function destroy(){
				var currentY = contentPositionY(),
					currentX = contentPositionX();
				elem.removeClass('jspScrollable').unbind('.jsp');
				elem.replaceWith(originalElement.append(pane.children()));
				originalElement.scrollTop(currentY);
				originalElement.scrollLeft(currentX);

				// clear reinitialize timer if active
				if (reinitialiseInterval) {
					clearInterval(reinitialiseInterval);
				}
			}

			// Public API
			$.extend(
				jsp,
				{
					// Reinitialises the scroll pane (if it's internal dimensions have changed since the last time it
					// was initialised). The settings object which is passed in will override any settings from the
					// previous time it was initialised - if you don't pass any settings then the ones from the previous
					// initialisation will be used.
					reinitialise: function(s)
					{
						s = $.extend({}, settings, s);
						initialise(s);
					},
					// Scrolls the specified element (a jQuery object, DOM node or jQuery selector string) into view so
					// that it can be seen within the viewport. If stickToTop is true then the element will appear at
					// the top of the viewport, if it is false then the viewport will scroll as little as possible to
					// show the element. You can also specify if you want animation to occur. If you don't provide this
					// argument then the animateScroll value from the settings object is used instead.
					scrollToElement: function(ele, stickToTop, animate)
					{
						scrollToElement(ele, stickToTop, animate);
					},
					// Scrolls the pane so that the specified co-ordinates within the content are at the top left
					// of the viewport. animate is optional and if not passed then the value of animateScroll from
					// the settings object this jScrollPane was initialised with is used.
					scrollTo: function(destX, destY, animate)
					{
						scrollToX(destX, animate);
						scrollToY(destY, animate);
					},
					// Scrolls the pane so that the specified co-ordinate within the content is at the left of the
					// viewport. animate is optional and if not passed then the value of animateScroll from the settings
					// object this jScrollPane was initialised with is used.
					scrollToX: function(destX, animate)
					{
						scrollToX(destX, animate);
					},
					// Scrolls the pane so that the specified co-ordinate within the content is at the top of the
					// viewport. animate is optional and if not passed then the value of animateScroll from the settings
					// object this jScrollPane was initialised with is used.
					scrollToY: function(destY, animate)
					{
						scrollToY(destY, animate);
					},
					// Scrolls the pane to the specified percentage of its maximum horizontal scroll position. animate
					// is optional and if not passed then the value of animateScroll from the settings object this
					// jScrollPane was initialised with is used.
					scrollToPercentX: function(destPercentX, animate)
					{
						scrollToX(destPercentX * (contentWidth - paneWidth), animate);
					},
					// Scrolls the pane to the specified percentage of its maximum vertical scroll position. animate
					// is optional and if not passed then the value of animateScroll from the settings object this
					// jScrollPane was initialised with is used.
					scrollToPercentY: function(destPercentY, animate)
					{
						scrollToY(destPercentY * (contentHeight - paneHeight), animate);
					},
					// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
					// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
					scrollBy: function(deltaX, deltaY, animate)
					{
						jsp.scrollByX(deltaX, animate);
						jsp.scrollByY(deltaY, animate);
					},
					// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
					// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
					scrollByX: function(deltaX, animate)
					{
						var destX = contentPositionX() + Math[deltaX<0 ? 'floor' : 'ceil'](deltaX),
							percentScrolled = destX / (contentWidth - paneWidth);
						positionDragX(percentScrolled * dragMaxX, animate);
					},
					// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
					// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
					scrollByY: function(deltaY, animate)
					{
						var destY = contentPositionY() + Math[deltaY<0 ? 'floor' : 'ceil'](deltaY),
							percentScrolled = destY / (contentHeight - paneHeight);
						positionDragY(percentScrolled * dragMaxY, animate);
					},
					// Positions the horizontal drag at the specified x position (and updates the viewport to reflect
					// this). animate is optional and if not passed then the value of animateScroll from the settings
					// object this jScrollPane was initialised with is used.
					positionDragX: function(x, animate)
					{
						positionDragX(x, animate);
					},
					// Positions the vertical drag at the specified y position (and updates the viewport to reflect
					// this). animate is optional and if not passed then the value of animateScroll from the settings
					// object this jScrollPane was initialised with is used.
					positionDragY: function(y, animate)
					{
						positionDragY(y, animate);
					},
					// This method is called when jScrollPane is trying to animate to a new position. You can override
					// it if you want to provide advanced animation functionality. It is passed the following arguments:
					//  * ele          - the element whose position is being animated
					//  * prop         - the property that is being animated
					//  * value        - the value it's being animated to
					//  * stepCallback - a function that you must execute each time you update the value of the property
					// You can use the default implementation (below) as a starting point for your own implementation.
					animate: function(ele, prop, value, stepCallback)
					{
						var params = {};
						params[prop] = value;
						ele.animate(
							params,
							{
								'duration'	: settings.animateDuration,
								'easing'	: settings.animateEase,
								'queue'		: false,
								'step'		: stepCallback
							}
						);
					},
					// Returns the current x position of the viewport with regards to the content pane.
					getContentPositionX: function()
					{
						return contentPositionX();
					},
					// Returns the current y position of the viewport with regards to the content pane.
					getContentPositionY: function()
					{
						return contentPositionY();
					},
					// Returns the width of the content within the scroll pane.
					getContentWidth: function()
					{
						return contentWidth;
					},
					// Returns the height of the content within the scroll pane.
					getContentHeight: function()
					{
						return contentHeight;
					},
					// Returns the horizontal position of the viewport within the pane content.
					getPercentScrolledX: function()
					{
						return contentPositionX() / (contentWidth - paneWidth);
					},
					// Returns the vertical position of the viewport within the pane content.
					getPercentScrolledY: function()
					{
						return contentPositionY() / (contentHeight - paneHeight);
					},
					// Returns whether or not this scrollpane has a horizontal scrollbar.
					getIsScrollableH: function()
					{
						return isScrollableH;
					},
					// Returns whether or not this scrollpane has a vertical scrollbar.
					getIsScrollableV: function()
					{
						return isScrollableV;
					},
					// Gets a reference to the content pane. It is important that you use this method if you want to
					// edit the content of your jScrollPane as if you access the element directly then you may have some
					// problems (as your original element has had additional elements for the scrollbars etc added into
					// it).
					getContentPane: function()
					{
						return pane;
					},
					// Scrolls this jScrollPane down as far as it can currently scroll. If animate isn't passed then the
					// animateScroll value from settings is used instead.
					scrollToBottom: function(animate)
					{
						positionDragY(dragMaxY, animate);
					},
					// Hijacks the links on the page which link to content inside the scrollpane. If you have changed
					// the content of your page (e.g. via AJAX) and want to make sure any new anchor links to the
					// contents of your scroll pane will work then call this function.
					hijackInternalLinks: $.noop,
					// Removes the jScrollPane and returns the page to the state it was in before jScrollPane was
					// initialised.
					destroy: function()
					{
							destroy();
					}
				}
			);
			
			initialise(s);
		}

		// Pluginifying code...
		settings = $.extend({}, $.fn.jScrollPane.defaults, settings);
		
		// Apply default speed
		$.each(['arrowButtonSpeed', 'trackClickSpeed', 'keyboardSpeed'], function() {
			settings[this] = settings[this] || settings.speed;
		});

		return this.each(
			function()
			{
				var elem = $(this), jspApi = elem.data('jsp');
				if (jspApi) {
					jspApi.reinitialise(settings);
				} else {
					$("script",elem).filter('[type="text/javascript"],:not([type])').remove();
					jspApi = new JScrollPane(elem, settings);
					elem.data('jsp', jspApi);
				}
			}
		);
	};

	$.fn.jScrollPane.defaults = {
		showArrows					: false,
		maintainPosition			: true,
		stickToBottom				: false,
		stickToRight				: false,
		clickOnTrack				: true,
		autoReinitialise			: false,
		autoReinitialiseDelay		: 500,
		verticalDragMinHeight		: 0,
		verticalDragMaxHeight		: 99999,
		horizontalDragMinWidth		: 0,
		horizontalDragMaxWidth		: 99999,
		contentWidth				: undefined,
		animateScroll				: false,
		animateDuration				: 300,
		animateEase					: 'linear',
		hijackInternalLinks			: false,
		verticalGutter				: 4,
		horizontalGutter			: 4,
		mouseWheelSpeed				: 3,
		arrowButtonSpeed			: 0,
		arrowRepeatFreq				: 50,
		arrowScrollOnHover			: false,
		trackClickSpeed				: 0,
		trackClickRepeatFreq		: 70,
		verticalArrowPositions		: 'split',
		horizontalArrowPositions	: 'split',
		enableKeyboardNavigation	: true,
		hideFocus					: false,
		keyboardSpeed				: 0,
		initialDelay                : 300,        // Delay before starting repeating
		speed						: 30,		// Default speed when others falsey
		scrollPagePercent			: .8		// Percent of visible area scrolled when pageUp/Down or track area pressed
	};

},this));
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
		$('.ltp-profiles p.message').remove();
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
		}
	},
	loadFilters = function()
	{

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
	if ($('.ltp-profile-builder').length) {
		$('.checkbox-list').jScrollPane({verticalGutter:0});
		$('.checkbox-list label').on('click', checkCheckboxLists);
		checkCheckboxLists();
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
	if ($('#profile-filters').length) {
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
		checkCheckboxLists();
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
					if (!$('.ltp-profile-wrap:visible').length) {
						$('.ltp-profiles').append('<p class="message">No profiles match your search criteria</p>');
					} else {
						rearrangeProfiles();
					}
					// change the button text
					$(this).text('Edit filters');
					// add button to remove filters
					$('#remove-filters').show();
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
				$('#profile-filters').data('showing-filters', true);
				$('.checkbox-list').jScrollPane({verticalGutter:0});
				$('.checkbox-list label').on('click', checkCheckboxLists);
			}
		});
		$('#remove-filters').on('click', function(e){
			e.preventDefault();
			removeFilters();
			$(this).hide();
		});
		if (window.location.hash === '#saved') {
			showSavedProfiles();
		}
	}
	$('.sticky').sticky();
	$('.showcase-button').colorbox({
		inline:true,
		width:'90%',
		maxWidth:'940px',
		current:"Showcase {current} of {total}"
	});
})(jQuery);