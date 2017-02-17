$j(document).ready(function () {

	var $links = $j('.js-bannerize').find('a');

	$links.each(function(){

		$link = $j(this);
		$image = $link.find('img');

		if ($image) {
			$div = $link.closest('.newsflash-frontpage');
			$div.css('background-image', 'url('+$image.attr('src')+')');
			$div.removeClass('col-md-8 col-sm-12 match-height');
			$div.wrap('<a href="'+$link.attr('href')+'" class="col-md-8 col-sm-12 nw-desktop"></a>');

			$link.closest('div').remove();
			$link.remove();
			$image.remove();
		}

	});

});