<?php
/*
  Plugin Name: Ajax Archive Calendar
  Plugin URI: http://fb.me/osmansorkar
  Description:Ajax Archive Calendar is not only Calendar is also Archive. It is making by customize WordPress default calendar. I hope every body enjoy this plugin.
  Author: osmansorkar
  Version: 2.6.8
  Author URI: http://fb.me/osmansorkar
 */

/**
 * Enqueue frontend scripts.
 */
add_action('wp_enqueue_scripts', 'ajax_ac_enqueue_scripts');

/**
 * Make sure we have jquery available.
 */
function ajax_ac_enqueue_scripts() {
	wp_enqueue_script('jquery');
}

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action('widgets_init', 'ajax_ac_int');

/**
 * Register our widget.
 * 'Example_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function ajax_ac_int() {
	register_widget('ajax_ac_widget');
}

/* * ******************************************************** */

class ajax_ac_widget extends WP_Widget {

	function __construct() {

		parent::__construct(
			'ajax_ac_widget', // Base ID
			'Ajax Archive calendar', // Name
			array('description' => 'It is Ajax Archive Calendar', 'text_domain') // Args
		);
	}

	/********************** It will be sow home page**************** */
	function widget($args, $instance) {
		extract($args);

		$defaults = array('title' => 'Archive Calendar','start_year' => date("Y"));
		$instance = wp_parse_args((array) $instance, $defaults);

		$title = apply_filters('widget_title', $instance['title']);
		$bengali=$instance['bangla'];

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* $title define by from */
		if ($title)
		/* after title and before title defince by thime */
			echo $before_title . $title . $after_title;
		/* end title */

        /*
         * Calender Output
         * */
		echo  $this->calender_html($bengali,$instance["start_year"]);
		?>

		<?php

        /*arter_widget define by theme */
		echo $after_widget;
	}

	/*	 * ****************** It Update widget ****************************** */

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['bangla'] = strip_tags($new_instance['bangla']);
		$instance['start_year'] = strip_tags($new_instance['start_year']);
		return $instance;
	}

	/*	 * ********************** It is sow only admin menu********************************* */

	function form($instance) {
		$defaults = array('title' => 'Archive Calendar','start_year' => date("Y"));
		$instance = wp_parse_args((array) $instance, $defaults);
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'ajax_archive_calendar'); ?></label>
			<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />

		</p>
        <p>
        	<label for="<?php echo $this->get_field_id( 'bangla' ); ?>"><?php _e('Select Version', 'ajax_archive_calendar'); ?></label>
            <select name="<?php echo $this->get_field_name( 'bangla' ); ?>" id="<?php echo $this->get_field_id( 'bangla' ); ?>">
            	<option value="0" <?php selected( $instance['bangla'], '0') ?> >English/WPML</option>
                <option value="1" <?php selected( $instance['bangla'], '1') ?> >Bengali</option>
                
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('start_year'); ?>"><?php _e('Start Year:', 'ajax_archive_calendar'); ?></label>
            <input type="number"  id="<?php echo $this->get_field_id('start_year'); ?>" name="<?php echo $this->get_field_name('start_year'); ?>" value="<?php echo $instance['start_year']; ?>" style="width:100%;" />

        </p>

        <p>
            <label for="<?php echo $this->get_field_id('shortcode'); ?>"><?php _e('shortcode', 'ajax_archive_calendar'); ?></label>
            <input   id="<?php echo $this->get_field_id('shortcode'); ?>"  value='[ajax_archive_calendar  bengali="<?php echo $instance['bangla']; ?>" start="<?php echo $instance['start_year']; ?>"]' style="width:100%;" />

        </p>


		<?php
	}
    // end from function

    function calender_html($bengali,$start_year){
	    global $wp_locale,$m, $monthnum, $year;

	    $calender_html = '';
        $calender_html.= '<div id="ajax_ac_widget">';
            $calender_html.= '<div class="select_ca">';
                $calender_html.= '<select name="month" id="my_month" >';
				    if ('bn' === substr(get_locale(), 0, 2) || $bengali==1) {
					    $month=array(
						    '01'=>'জানুয়ারী',
						    '02'=>'ফেব্রুয়ারী',
						    '03'=>'মার্চ',
						    '04'=>'এপ্রিল',
						    '05'=>'মে',
						    '06'=>'জুন',
						    '07'=>'জুলাই',
						    '08'=>'আগষ্ট',
						    '09'=>'সেপ্টেম্বর',
						    '10'=>'অক্টোবর',
						    '11'=>'নভেম্বর',
						    '12'=>'ডিসেম্বর'
					    );
				    } else{
					    $month = array();
					    for ($i = 1; $i <= 12; $i++) {
						    $monthnums = zeroise($i, 2);
						    $month[$monthnums] = $wp_locale->get_month($i);
					    }
				    }


				    if (empty($m) || $m == '') {
					    $nowm = $monthnum;
					    $nowyear = $year;
					    if($monthnum==0 || $monthnum==null){
						    $nowm=date('m');
					    }
					    if($nowyear==0 || $nowyear==null){
						    $nowyear=date('Y');
					    }
				    } else {
					    $mmm = str_split($m, 2);
					    $nowm = zeroise(intval(substr($m, 4, 2)), 2);
					    $nowyear = $mmm['0'] . $mmm['1'];
				    }


				    foreach ($month as $k => $mu) {
					    if ($k == $nowm) {
						    $calender_html.= '<option value="' . $k . '" selected="selected" >' . $mu . '</option>';
					    } else {
						    $calender_html.= '<option value="' . $k . '">' . $mu . '</option>';
					    }
				    }

                $calender_html.= '</select>';

			    $find = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0",);
			    $replace = array("১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০",);


			    $taryear = date("Y");
			    $yeararr = array();
			    $lassyear = $start_year;
			    for ($nowyearrr = $lassyear; $nowyearrr <= $taryear; $nowyearrr++) {
				    $yeararr[$nowyearrr] = $nowyearrr;
			    }



                $calender_html.= '<select name="Year" id="my_year" >';

				    foreach ($yeararr as $k => $years) {
					    if ('bn' === substr(get_locale(), 0, 2) || $bengali==1) {
						    $years = str_replace($find, $replace, $years);
					    }
					    if ($k == $nowyear) {
						    $calender_html.= '<option value="' . $k . '" selected="selected" >' . $years . '</option>';
					    } else {
						    $calender_html.= '<option value="' . $k . '">' . $years . '</option>';
					    }
				    }

                $calender_html.= '</select>';
            $calender_html.= '</div><!--select ca -->';
            $calender_html.= '<div class="clear" style="clear:both; margin-bottom: 5px;"></div>';
            $calender_html.= '<div class="ajax-calendar">';
	    $calender_html.='<div class="aj-loging" style="left: 49%;position: absolute;top: 50%; display:none">';
	    $url = plugin_dir_url( __FILE__ );
	    $calender_html.='<img src="';
	    $calender_html.=$url . 'loading.gif';
	    $calender_html.='" /></div>';

                $calender_html.= '<div id="my_calender">';
				     $calender_html.= ajax_ac_calendar('', $bengali,false);
                $calender_html.= '</div><!--my_calender -->';
                $calender_html.= '<div class="clear" style="clear:both; margin-bottom: 5px;"></div>';
            $calender_html.= '</div>';

        $calender_html.='</div>';


	    $calender_html.='<script type="text/javascript" >
	                 jQuery(document).on("change","#my_month,#my_year", function (e) {
                         jQuery(".aj-loging").css("display", "block");
                         jQuery("#my_calender").css("opacity", "0.30");
                         
                         var bna='.$bengali.'
                         var mon = jQuery("#my_month").val();
                         var year = jQuery("#my_year").val();
                         var to = year + mon;
                         var data = {
                         action: "ajax_ac",
                         ma: to,
                         bn:bna,

                         };

                         // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                         jQuery.get(ajaxurl, data, function (response) {
                         jQuery("#my_calender").html(response);
                         jQuery(".aj-loging").css("display", "none");
                         jQuery("#my_calender").css("opacity", "1.00");
                         });

                         });

                         </script>';

        return $calender_html;

    }
}

// end widget class


add_action('wp_ajax_ajax_ac', 'ajax_ac_callback');
add_action('wp_ajax_nopriv_ajax_ac', 'ajax_ac_callback');

function ajax_ac_callback() {
	$ma = $_GET['ma'];
	$bn = $_GET['bn'];
	ajax_ac_calendar($ma,$bn);
	die(); // this is required to return a proper result
}

function ajax_ac_calendar($ma=null,$bn, $echo = true) {
	global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;
	if($ma!=null){
		$m=$ma;
	}
	$cache = array();
	$key = md5(get_locale() . $m . $monthnum . $year);

	if ($cache = wp_cache_get('get_calendar', 'calendar')) {
		if (is_array($cache) && isset($cache[$key])) {
			if ($echo) {
				echo apply_filters('get_calendar', $cache[$key]);
				return;
			} else {
				return apply_filters('get_calendar', $cache[$key]);
			}
		}
	}
	if (!is_array($cache))
		$cache = array();

	// Quick check. If we have no posts at all, abort!
	if (!$posts) {
		$gotsome = $wpdb->get_var("SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 1");
		if (!$gotsome) {
			$cache[$key] = '';
			wp_cache_set('get_calendar', $cache, 'calendar');
			return;
		}
	}

	if (isset($_GET['w']))
		$w = '' . intval($_GET['w']);

	// week_begins = 0 stands for Sunday
	$week_begins = intval(get_option('start_of_week'));

	// Let's figure out when we are
	if (!empty($monthnum) && !empty($year)) {
		$thismonth = '' . zeroise(intval($monthnum), 2);
		$thisyear = '' . intval($year);
	} elseif (!empty($w)) {
		// We need to get the month from MySQL
		$thisyear = '' . intval(substr($m, 0, 4));
		$d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
		$thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')");
	} elseif (!empty($m)) {
		$thisyear = '' . intval(substr($m, 0, 4));
		if (strlen($m) < 6)
			$thismonth = '01';
		else
			$thismonth = '' . zeroise(intval(substr($m, 4, 2)), 2);
	} else {
		$thisyear = gmdate('Y', current_time('timestamp'));
		$thismonth = gmdate('m', current_time('timestamp'));
	}

	$unixmonth = mktime(0, 0, 0, $thismonth, 1, $thisyear);
	$last_day = date('t', $unixmonth);

	$calendar_output = '<table id="my-calendar">
	<thead>
	<tr>';

	$myweek = array();

	for ($wdcount = 0; $wdcount <= 6; $wdcount++) {
		$myweek[] = $wp_locale->get_weekday(($wdcount + $week_begins) % 7);
	}

	$barr = array('Saturday' => 'শনি', 'Sunday' => 'রবি', 'Monday' => 'সোম', 'Tuesday' => 'মঙ্গল', 'Wednesday' => 'বুধ', 'Thursday' => 'বৃহ', 'Friday' => 'শুক্র');
	foreach ($myweek as $wd) {
		if ('bn' === substr(get_locale(), 0, 2) || $bn==1) {
			$day_name = $barr[$wd];
		} else {
			$day_name = $wp_locale->get_weekday_abbrev($wd);
		}
		$wd = esc_attr($wd);
		$calendar_output .= "\n\t\t<th class=\"$day_name\" scope=\"col\" title=\"$wd\">$day_name</th>";
	}

	$calendar_output .= '
	</tr>
	</thead>

	<tbody>
	<tr>';

    // Get days with posts
    $dayswithposts = get_posts(array(
        'suppress_filters' => false,
        //'post_type' => 'post',
        'post_type' => 'post',
        'post_status' => 'publish',
        'monthnum' => $thismonth,
        'year' => $thisyear,
        'numberposts' => -1,
    ));
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false)
        $ak_title_separator = "\n";
    else
        $ak_title_separator = ', ';
    $daywithpost = array();
    $ak_titles_for_day = array();
    if ($dayswithposts) {
        foreach ((array) $dayswithposts as $ak_post) {
            $daywith = date('d', strtotime($ak_post->post_date));
            if (!in_array($daywith, $daywithpost)) {
                $daywithpost[] = $daywith;
            }
            $post_title = esc_attr(get_the_title($ak_post));
            if (empty($ak_titles_for_day[$daywith])) // first one
                $ak_titles_for_day[$daywith] = $post_title;
            else
                $ak_titles_for_day[$daywith] .= $ak_title_separator . $post_title;
        }
    }

    //print_r($daywithpost);
    //print_r($ak_titles_for_day);
    // See how much we should pad in the beginning
    $pad = calendar_week_mod(date('w', $unixmonth) - $week_begins);
    if (0 != $pad)
        $calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr($pad) . '" class="pad">&nbsp;</td>';
    $daysinmonth = intval(date('t', $unixmonth));
    for ($day = 1; $day <= $daysinmonth; ++$day) {
        if ('bn' === substr(get_locale(), 0, 2) || $bn==1) {
            $dayrrr = array(
                '1' => '১',
                '2' => '২',
                '3' => '৩',
                '4' => '৪',
                '5' => '৫',
                '6' => '৬',
                '7' => '৭',
                '8' => '৮',
                '9' => '৯',
                '10' => '১০',
                '11' => '১১',
                '12' => '১২',
                '13' => '১৩',
                '14' => '১৪',
                '15' => '১৫',
                '16' => '১৬',
                '17' => '১৭',
                '18' => '১৮',
                '19' => '১৯',
                '20' => '২০',
                '21' => '২১',
                '22' => '২২',
                '23' => '২৩',
                '24' => '২৪',
                '25' => '২৫',
                '26' => '২৬',
                '27' => '২৭',
                '28' => '২৮',
                '29' => '২৯',
                '30' => '৩০',
                '31' => '৩১',
            );
        } else {
            $dayrrr = array(
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
                '7' => '7',
                '8' => '8',
                '9' => '9',
                '10' => '10',
                '11' => '11',
                '12' => '12',
                '13' => '13',
                '14' => '14',
                '15' => '15',
                '16' => '16',
                '17' => '17',
                '18' => '18',
                '19' => '19',
                '20' => '20',
                '21' => '21',
                '22' => '22',
                '23' => '23',
                '24' => '24',
                '25' => '25',
                '26' => '26',
                '27' => '27',
                '28' => '28',
                '29' => '29',
                '30' => '30',
                '31' => '31',
            );
        }
        $addzeor=array(
            '1' => '01',
            '2' => '02',
            '3' => '03',
            '4' => '04',
            '5' => '05',
            '6' => '06',
            '7' => '07',
            '8' => '08',
            '9' => '09',
            '10' => '10',
            '11' => '11',
            '12' => '12',
            '13' => '13',
            '14' => '14',
            '15' => '15',
            '16' => '16',
            '17' => '17',
            '18' => '18',
            '19' => '19',
            '20' => '20',
            '21' => '21',
            '22' => '22',
            '23' => '23',
            '24' => '24',
            '25' => '25',
            '26' => '26',
            '27' => '27',
            '28' => '28',
            '29' => '29',
            '30' => '30',
            '31' => '31',
        );
        if (isset($newrow) && $newrow)
            $calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
        $newrow = false;
        if ($day == gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m', current_time('timestamp')) && $thisyear == gmdate('Y', current_time('timestamp')))
            $calendar_output .= '<td id="today"  >';
        else
            $calendar_output .= '<td class="notday">';
        if (in_array($day, $daywithpost)) // any posts today?
            $calendar_output .= '<a class="has-post" href="' . get_day_link($thisyear, $thismonth, $day) . '" title="' . esc_attr($ak_titles_for_day[$addzeor[$day]]) . "\">$dayrrr[$day]</a>";
        else
            $calendar_output .= '<span class="notpost">' . $dayrrr[$day] . '</span>';
        $calendar_output .= '</td>';
        if (6 == calendar_week_mod(date('w', mktime(0, 0, 0, $thismonth, $day, $thisyear)) - $week_begins))
            $newrow = true;
    }
    $pad = 7 - calendar_week_mod(date('w', mktime(0, 0, 0, $thismonth, $day, $thisyear)) - $week_begins);
    if ($pad != 0 && $pad != 7)
        $calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr($pad) . '">&nbsp;</td>';
    $calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";
    $cache[$key] = $calendar_output;
    wp_cache_set('get_calendar', $cache, 'calendar');
    if ($echo)
        echo apply_filters('get_calendar', $calendar_output);
    else
        return apply_filters('get_calendar', $calendar_output);
}

function ajax_ac_head() {
	?>

	<script type="text/javascript">
	    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	</script>

	<style type="text/css">
		.ajax-calendar{
			position:relative;
		}

		#ajax_ac_widget th {
		background: none repeat scroll 0 0 #2cb2bc;
		color: #FFFFFF;
		font-weight: normal;
		padding: 5px 1px;
		text-align: center;
		 font-size: 16px;
		}
		#ajax_ac_widget {
			padding: 5px;
		}
		
		#ajax_ac_widget td {
			border: 1px solid #CCCCCC;
			text-align: center;
		}
		
		#my-calendar a {
			background: none repeat scroll 0 0 #008000;
			color: #FFFFFF;
			display: block;
			padding: 6px 0;
			width: 100% !important;
		}
		#my-calendar{
			width:100%;
		}
		
		
		#my_calender span {
			display: block;
			padding: 6px 0;
			width: 100% !important;
		}
		
		#today a,#today span {
			   background: none repeat scroll 0 0 #2cb2bc !important;
			color: #FFFFFF;
		}
		#ajax_ac_widget #my_year {
			float: right;
		}
		.select_ca #my_month {
			float: left;
		}

	</style>
	<?php
}

add_filter('wp_head', 'ajax_ac_head');

/**
 * Workaround WPML bug with get_day_link() function.
 * 
 * @param string $url
 * @return stringe
 */
function ajax_ac_permalinks( $url ) {
	return apply_filters('wpml_permalink',  $url );
}

add_filter('day_link', 'ajax_ac_permalinks');


/**
 * Create WP short code for Ajax Archive Calendar
 *
 * @param array $atts
 * @return void
 */

//[ajax_archive_calendar]
function ajax_archive_calendar( $atts ){

    if(key_exists("bengali",$atts)){
        $bengali = 1;
    } else {
	    $bengali = 0;
    }

	if(key_exists("start",$atts) && is_numeric($atts["start"])){
		$start = $atts["start"];
	} else {
		$start = date("Y");
	}

    $ajax_ac_widget = new ajax_ac_widget();
	return $ajax_ac_widget->calender_html($bengali,$start);
}

add_shortcode( 'ajax_archive_calendar', 'ajax_archive_calendar' );
