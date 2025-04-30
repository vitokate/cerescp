$( function(){

	var delay  = 4000;
	var index  = 0;

	// Caching dom access (faster)
	var thumb = $('#slider .thumb a');
	var result = $('#slider .result');
	var count = thumb.length
	var list  = new Array(count);
	var result_img  = result.find('img');
	var result_h1   = result.find('.info h1');
	var result_txt  = result.find('.info p');
	var last, current;
	var select = false;


	var tmp;
	var core;
	var preload = new Array(count);

	// Caching thumb list (quick access)
	for ( var i=0; i<count; ++i ) {
		tmp = $(thumb[i]);

		list[i] = {
			img:  tmp.find('img'),
			src:  tmp.attr('href'),
			text: tmp.find('img').attr('alt').match(/\[(.*)\](.*)/),
			a:    thumb[i]
		};

		preload[i]     = new Image();
		preload[i].src = list[i].src;
	}


	// Update image in slider
	function update() {

		if ( !select ) {
			last    = list[index];
			index++;
			index  %= count;
		}
		else {
			select = false;
		}
		current = list[index];


		// Change text opacity
		result.find('.info').animate({ bottom:'-43px' }, { duration:500, complete:function(){
			// Thumb, change opacity
			current.img.css('opacity',1);
			last.img.css('opacity',0.5);

			result_h1.text( current.text[1] );
			result_txt.text( current.text[2] );
			$(this).animate({ bottom:'0px' }, 500 );
		}});

		// Change Image
		result_img[1].src = result_img[0].src+'';
		result_img[0].src = current.src;
		$(result_img[1]).css('opacity', 1.0 ).animate({ opacity: 0.0 }, { duration:1000, complete:function(){
			result_img[1].src = result_img[0].src; // the complete shouldn't be needed, but there is an awesome error in firefox with the swap image.
		}});
		
	}


	// User want to see a special item
	thumb.click(function() {
		// Need to find thumb position in list
		for ( var i=0; i<count; ++i ) {
			if ( list[i].a === this ) {
				// Clean up
				clearInterval(core);
				core  = setInterval( update, delay );
				last  = list[index];
				select = true;
				index = i;
				update();
			}
		}
		// Don't jump to link
		return false;
	});



	// Fixed css hover... :(
	$('#slider .thumb img').
		mouseover(function(){
			$(this).css('opacity', 1.0 );
		}).
		mouseout(function(){
			if ( current.img[0] !== this )
				$(this).css('opacity',0.5);
		});


	list[0].img.css('opacity',1);
	current =  list[index];
	core = setInterval( update, delay );
});