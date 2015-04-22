<?php

function hm_fragment_cache_template( $file, $vary_keys, $expire = 3600 ) {

	$vary_keys = (array) $vary_keys;
	$vary_keys['file'] = $file;

	hm_fragment_cache( $vary_keys, function() use ( $file ) {
		get_template_part( $file );
	}, $expire );
}

function hm_fragment_cache( $vary_keys, $callback, $expire = 3600 ) {

	$should_cache = ! defined( 'HM_FRAGMENT_CACHE_DISABLE' ) || ! HM_FRAGMENT_CACHE_DISABLE;

	$vary_keys         = (array) $vary_keys;
	$vary_keys['ssl']  = is_ssl();
	$vary_keys['host'] = $_SERVER['HTTP_HOST'];
	$vary_keys['ver']  = 1;

	$show_outlines        = defined( 'HM_FRAGMENT_CACHE_SHOW_DEBUG' ) && HM_FRAGMENT_CACHE_SHOW_DEBUG;
	$show_generation_time = defined( 'HM_FRAGMENT_CACHE_SHOW_DEBUG' ) && HM_FRAGMENT_CACHE_SHOW_DEBUG;

	$key  = md5( serialize( $vary_keys ) );

	if ( $should_cache && $html = wp_cache_get( $key, 'fragment_cache' ) ) {

		if ( $show_outlines ) {
			echo '<span style="outline: green 1px solid; display: block">';	
		}

		if ( $show_generation_time ) {
			hm_fragment_cache_generation_time_element( wp_cache_get( $key . '_time', 'fragment_cache' ) );
		}
		
		echo $html;

		if ( $show_outlines ) {
			echo '</span>';	
		}
		
		return;
	}

	if ( $show_outlines ) {
		echo '<span style="outline: red 1px solid; display: block; overflow: hidden;">';	
	}

	ob_start();

	$start = microtime(true);
	call_user_func( $callback );
	$time_elapsed = number_format( ( microtime(true) - $start ) * 1000 );

	$html = ob_get_clean();

	if ( $should_cache ) {
		wp_cache_set( $key, $html, 'fragment_cache', $expire );	
	}
	
	if ( $show_generation_time ) {
		wp_cache_set( $key . "_time", $time_elapsed, 'fragment_cache', $expire );
		hm_fragment_cache_generation_time_element( $time_elapsed );
	}

	echo $html;

	if ( $show_outlines ) {
		echo '</span>';
	}
}

function hm_fragment_cache_generation_time_element( $ms ) {
	echo '<span style="position:absolute; z-index: 9999; background: rgba(0,0,0,.8); color: #fff; font-size: 11px; padding: 3px; line-height: 11px;">' . $ms . 'ms' . '</span>';
}