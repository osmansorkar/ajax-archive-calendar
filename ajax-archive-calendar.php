<?php
/*
  Plugin Name: Ajax Archive Calendar
  Plugin URI: http://fb.me/osmansorkar
  Description: Ajax Archive Calendar is not only a Calendar but also an Archive. It is built by customizing the WordPress default calendar. I hope everybody enjoys this plugin.
  Author: osmansorkar
  Version: 3.0.0
  Author URI: http://fb.me/osmansorkar
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue frontend scripts (only jQuery now, as custom JS is inline).
 */
add_action('wp_enqueue_scripts', 'ajax_ac_enqueue_scripts');

function ajax_ac_enqueue_scripts()
{
    // Ensure jQuery is enqueued as it's a dependency for the inline AJAX script
    wp_enqueue_script('jquery');
}

/**
 * Add function to widgets_init that'll load our widget.
 */
add_action('widgets_init', 'ajax_ac_register_widget');

function ajax_ac_register_widget()
{
    register_widget('Ajax_AC_Widget');
}

/**
 * Main Widget Class for Ajax Archive Calendar.
 */
class Ajax_AC_Widget extends WP_Widget
{
    /**
     * Bengali number find array
     * @var array
     */
    public static $find = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0");

    /**
     * Bengali number replace array
     * @var array
     */
    public static $replace = array("১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০");

    /**
     * Bengali month array
     * @var array
     */
    public static $month = array(
        '01' => 'জানুয়ারী',
        '02' => 'ফেব্রুয়ারী',
        '03' => 'মার্চ',
        '04' => 'এপ্রিল',
        '05' => 'মে',
        '06' => 'জুন',
        '07' => 'জুলাই',
        '08' => 'আগষ্ট',
        '09' => 'সেপ্টেম্বর',
        '10' => 'অক্টোবর',
        '11' => 'নভেম্বর',
        '12' => 'ডিসেম্বর'
    );

    function __construct()
    {
        parent::__construct(
            'ajax_ac_widget', // Base ID
            esc_html__('Ajax Archive Calendar', 'ajax-archive-calendar'), // Name
            array('description' => esc_html__('Displays an AJAX-powered archive calendar.', 'ajax-archive-calendar')) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance)
    {
        // Extract widget arguments (before_widget, after_widget, before_title, after_title)
        echo $args['before_widget'];

        $title = apply_filters('widget_title', $instance['title'] ?? 'Archive Calendar', $instance, $this->id_base);
        $bengali_enabled = (bool) ($instance['bangla'] ?? false);
        $start_year = absint($instance['start_year'] ?? date("Y"));
        $post_type = sanitize_key($instance['post_type'] ?? 'post'); // Get selected single post type, default to 'post'

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Output the calendar HTML and the inline JavaScript
        echo $this->generate_calendar_html($bengali_enabled, $start_year, $post_type);

        echo $args['after_widget'];
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['bangla'] = (isset($new_instance['bangla'])) ? (bool) $new_instance['bangla'] : false;
        $instance['start_year'] = absint($new_instance['start_year']);
        
        // Sanitize single post type
        $instance['post_type'] = sanitize_key($new_instance['post_type'] ?? 'post');

        return $instance;
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Current settings.
     */
    public function form($instance)
    {
        $defaults = array(
            'title'      => esc_html__('Archive Calendar', 'ajax-archive-calendar'),
            'start_year' => date("Y"),
            'bangla'     => '0',
            'post_type'  => 'post' // Default single post type
        );
        $instance = wp_parse_args((array) $instance, $defaults);

        $title = esc_attr($instance['title']);
        $bengali = esc_attr($instance['bangla']);
        $start_year = esc_attr($instance['start_year']);
        $selected_post_type = sanitize_key($instance['post_type']);

        // Get all public post types
        $all_post_types = get_post_types(array('public' => true), 'objects');
    ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'ajax-archive-calendar'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('bangla')); ?>"><?php esc_html_e('Select Version:', 'ajax-archive-calendar'); ?></label>
            <select class="widefat" name="<?php echo esc_attr($this->get_field_name('bangla')); ?>" id="<?php echo esc_attr($this->get_field_id('bangla')); ?>">
                <option value="0" <?php selected($bengali, '0'); ?>><?php esc_html_e('English/WPML', 'ajax-archive-calendar'); ?></option>
                <option value="1" <?php selected($bengali, '1'); ?>><?php esc_html_e('Bengali', 'ajax-archive-calendar'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('start_year')); ?>"><?php esc_html_e('Start Year (e.g., 2010):', 'ajax-archive-calendar'); ?></label>
            <input class="widefat" type="number" id="<?php echo esc_attr($this->get_field_id('start_year')); ?>" name="<?php echo esc_attr($this->get_field_name('start_year')); ?>" value="<?php echo $start_year; ?>" min="1900" max="<?php echo date('Y'); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('post_type')); ?>"><?php esc_html_e('Select Post Type:', 'ajax-archive-calendar'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('post_type')); ?>" name="<?php echo esc_attr($this->get_field_name('post_type')); ?>">
                <?php foreach ($all_post_types as $post_type_obj) : ?>
                    <option value="<?php echo esc_attr($post_type_obj->name); ?>" <?php selected($post_type_obj->name, $selected_post_type); ?>>
                        <?php echo esc_html($post_type_obj->labels->singular_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('shortcode')); ?>"><?php esc_html_e('Shortcode:', 'ajax-archive-calendar'); ?></label>
            <input type="text" class="widefat" id="<?php echo esc_attr($this->get_field_id('shortcode')); ?>" value='[ajax_archive_calendar bengali="<?php echo $bengali; ?>" start="<?php echo $start_year; ?>" post_type="<?php echo esc_attr($selected_post_type); ?>"]' readonly />
        </p>
    <?php
    }

    /**
     * Generates the HTML structure for the calendar widget, including dropdowns and the calendar table.
     * This function is called by the widget and the shortcode.
     *
     * @param bool  $bengali_enabled Whether to display month/year/day names in Bengali.
     * @param int   $start_year      The starting year for the year dropdown.
     * @param string $post_type      The single post type slug to include in the calendar.
     * @return string The full HTML output for the calendar widget.
     */
    public function generate_calendar_html($bengali_enabled, $start_year, $post_type)
    {
        global $wp_locale, $m, $monthnum, $year;

        $calender_html = '<div id="ajax_ac_widget" class="ajax-ac-widget">';
        $calender_html .= '<div class="select_ca">';

        // Determine current month and year for dropdown selection
        $current_month_num_for_dropdown = zeroise(intval($monthnum), 2);
        $current_year_for_dropdown = intval($year);

        if (empty($m) || $m == '') {
            if ($monthnum == 0 || $monthnum == null) {
                $current_month_num_for_dropdown = date('m');
            }
            if ($year == 0 || $year == null) {
                $current_year_for_dropdown = date('Y');
            }
        } else {
            // If $m (YYYYMM) is set, use it to determine current month/year
            $current_year_for_dropdown = intval(substr($m, 0, 4));
            $current_month_num_for_dropdown = zeroise(intval(substr($m, 4, 2)), 2);
        }

        // Month Dropdown
        $calender_html .= '<select name="month" id="my_month">';
        $months_to_display = ($bengali_enabled || 'bn' === substr(get_locale(), 0, 2)) ? self::$month : array();

        if (empty($months_to_display)) {
            for ($i = 1; $i <= 12; $i++) {
                $monthnums = zeroise($i, 2);
                $months_to_display[$monthnums] = $wp_locale->get_month($i);
            }
        }

        foreach ($months_to_display as $k => $month_name) {
            $selected = selected($k, $current_month_num_for_dropdown, false);
            $calender_html .= '<option value="' . esc_attr($k) . '" ' . $selected . '>' . esc_html($month_name) . '</option>';
        }
        $calender_html .= '</select>';

        // Year Dropdown
        $calender_html .= '<select name="Year" id="my_year">';
        $current_year_val = date("Y");
        $years_to_display = array();
        for ($y = $start_year; $y <= $current_year_val; $y++) {
            $years_to_display[$y] = ($bengali_enabled || 'bn' === substr(get_locale(), 0, 2)) ? str_replace(self::$find, self::$replace, $y) : $y;
        }

        foreach ($years_to_display as $k => $year_text) {
            $selected = selected($k, $current_year_for_dropdown, false);
            $calender_html .= '<option value="' . esc_attr($k) . '" ' . $selected . '>' . esc_html($year_text) . '</option>';
        }
        $calender_html .= '</select>';
        $calender_html .= '</div><!-- .select_ca -->';
        $calender_html .= '<div class="clear" style="clear:both; margin-bottom: 5px;"></div>';

        $calender_html .= '<div class="ajax-calendar">';
        $calender_html .= '<div class="aj-loging" style="display:none">';
        $loading_gif_url = plugins_url('loading.gif', __FILE__);
        $calender_html .= '<img src="' . esc_url($loading_gif_url) . '" alt="' . esc_attr__('Loading...', 'ajax-archive-calendar') . '" />';
        $calender_html .= '</div>'; // .aj-loging

        $calender_html .= '<div id="satej_it_calender">';
        // Initial calendar load - pass post type
        $calender_html .= ajax_ac_generate_calendar_table($m, $bengali_enabled, $post_type, false);
        $calender_html .= '</div><!-- #satej_it_calender -->';
        $calender_html .= '<div class="clear" style="clear:both; margin-bottom: 5px;"></div>';
        $calender_html .= '</div><!-- .ajax-calendar -->';
        $calender_html .= '</div><!-- #ajax_ac_widget -->';

        // Inline JavaScript for AJAX functionality
        // Pass post_type as a string to JavaScript
        $post_type_js = json_encode($post_type); // Will be a string like "post" or "magazine"

        $calender_html .= '<script type="text/javascript">
            jQuery(document).ready(function ($) {

                const runAjaxCalendar = function(monthYear, isBengali, postType) {
                    $(".aj-loging").css("display", "flex"); // Use flex to center loading gif
                    $("#satej_it_calender").css("opacity", "0.30");
                    
                    var data = {
                        action: "ajax_ac",
                        ma: monthYear,
                        bn: isBengali ? 1 : 0, // Pass 1 for true, 0 for false
                        post_type: postType // Pass the single post type string
                    };
                    
                    // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                    $.get("' . admin_url('admin-ajax.php') . '", data, function (response) {
                        $("#satej_it_calender").html(response); // Update calendar HTML
                        $(".aj-loging").css("display", "none"); // Hide loading indicator
                        $("#satej_it_calender").css("opacity", "1.00"); // Restore opacity

                        // Update the month and year dropdowns to reflect the newly loaded calendar
                        var newYear = monthYear.substring(0, 4);
                        var newMonth = monthYear.substring(4, 6);

                        $("#my_month").val(newMonth);
                        $("#my_year").val(newYear);
                    });
                };
                
                // Event listener for month/year dropdowns
                $(document).on("change", "#my_month, #my_year", function (e) {
                    e.preventDefault();
                    var mon = $("#my_month").val();
                    var year = $("#my_year").val();
                    var to = year + mon;
                    // Pass the current Bengali setting and post type from PHP
                    var isBengali = ' . json_encode($bengali_enabled) . ';
                    var postType = ' . $post_type_js . '; // Use the JSON encoded string
                    runAjaxCalendar(to, isBengali, postType);
                });

                // Event listener for previous/next/current month links (top and bottom)
                $(document).on("click", ".prev-month-link, .next-month-link", function (e) {
                    e.preventDefault(); // Prevent default link behavior (page reload)

                    var linkHref = $(this).attr("href");
                    // Extract YYYYMM from the href. Example: /2023/01/
                    var match = linkHref.match(/\/(\d{4})\/(\d{2})\//);
                    if (match && match.length >= 3) {
                        var year = match[1];
                        var month = match[2];
                        var monthYear = year + month;
                        // Pass the current Bengali setting and post type from PHP
                        var isBengali = ' . json_encode($bengali_enabled) . ';
                        var postType = ' . $post_type_js . '; // Use the JSON encoded string
                        runAjaxCalendar(monthYear, isBengali, postType);
                    } else {
                        console.error("Could not extract month and year from link href:", linkHref);
                    }
                });
            });
        </script>';

        return $calender_html;
    }
} // End class Ajax_AC_Widget

/**
 * AJAX Callback function to generate calendar HTML.
 * This function is hooked to 'wp_ajax_ajax_ac' and 'wp_ajax_nopriv_ajax_ac'.
 */
add_action('wp_ajax_ajax_ac', 'ajax_ac_callback');
add_action('wp_ajax_nopriv_ajax_ac', 'ajax_ac_callback');

function ajax_ac_callback()
{
    // Sanitize and validate input
    $month_arg = sanitize_text_field($_GET['ma'] ?? '');
    $is_bengali = (bool) ($_GET['bn'] ?? false);
    
    // Retrieve single post_type
    $post_type = sanitize_key($_GET['post_type'] ?? 'post');

    // Generate and echo the calendar HTML
    echo ajax_ac_generate_calendar_table($month_arg, $is_bengali, $post_type, false); // echo = false, so it returns string

    wp_die(); // Always die at the end of an AJAX callback
}

/**
 * Global variable to hold the current custom post type for permalink filtering.
 */
global $ajax_ac_current_post_type;
$ajax_ac_current_post_type = 'post'; // Default to 'post'

/**
 * Filters the day link to include the custom post type if it's not 'post'.
 *
 * @global string $ajax_ac_current_post_type The current post type for the calendar.
 *
 * @param string $link  The original day link.
 * @param int    $year  The year for the link.
 * @param int    $month The month for the link.
 * @param int    $day   The day for the link.
 * @return string The potentially modified day link with post_type query arg.
 */
function ajax_ac_filter_day_link($link, $year, $month, $day)
{
    global $ajax_ac_current_post_type;
    if ($ajax_ac_current_post_type && 'post' !== $ajax_ac_current_post_type) {
        $link = add_query_arg('post_type', $ajax_ac_current_post_type, $link);
    }
    return $link;
}

/**
 * Filters the month link to include the custom post type if it's not 'post'.
 *
 * @global string $ajax_ac_current_post_type The current post type for the calendar.
 *
 * @param string $link  The original month link.
 * @param int    $year  The year for the link.
 * @param int    $month The month for the link.
 * @return string The potentially modified month link with post_type query arg.
 */
function ajax_ac_filter_month_link($link, $year, $month)
{
    global $ajax_ac_current_post_type;
    if ($ajax_ac_current_post_type && 'post' !== $ajax_ac_current_post_type) {
        $link = add_query_arg('post_type', $ajax_ac_current_post_type, $link);
    }
    return $link;
}

/**
 * Generates the HTML table for the calendar.
 * This function is called by the widget/shortcode and the AJAX callback.
 *
 * @param string|null $month_arg Optional. The month and year in 'YYYYMM' format (e.g., '202301').
 * If null, it tries to use global $m, $monthnum, $year, or current date.
 * @param bool        $is_bengali_enabled Optional. If true, displays weekdays and day numbers in Bengali.
 * Defaults to false.
 * @param string      $post_type          The single post type slug to include in the calendar.
 * @param bool        $echo       Optional. Whether to echo the calendar HTML directly or return it.
 * Defaults to true.
 * @return string|void HTML string of the calendar if $echo is false, otherwise void.
 */
function ajax_ac_generate_calendar_table($month_arg = null, $is_bengali_enabled = false, $post_type = 'post', $echo = true)
{
    global $wpdb, $m, $monthnum, $year, $wp_locale, $posts, $ajax_ac_current_post_type;

    // Set the global variable for the current post type, to be used by permalink filters
    $ajax_ac_current_post_type = $post_type;

    // Add filters for day and month links ONLY when generating the calendar table
    add_filter('day_link', 'ajax_ac_filter_day_link', 10, 4);
    add_filter('month_link', 'ajax_ac_filter_month_link', 10, 3);

    // Override global $m if $month_arg is provided
    if ($month_arg !== null) {
        $m = $month_arg;
    }

    // --- Caching Mechanism ---
    // Include post_type in cache key
    $cache_key = 'ajax_ac_calendar_' . md5(get_locale() . $m . $monthnum . $year . ($is_bengali_enabled ? 'bn' : 'en') . $post_type);
    $calendar_output = wp_cache_get($cache_key, 'calendar');

    if (false !== $calendar_output) { // Check if cache hit
        // Remove filters before returning cached output
        remove_filter('day_link', 'ajax_ac_filter_day_link', 10);
        remove_filter('month_link', 'ajax_ac_filter_month_link', 10);
        if ($echo) {
            echo apply_filters('ajax_ac_calendar_output', $calendar_output);
            return;
        } else {
            return apply_filters('ajax_ac_calendar_output', $calendar_output);
        }
    }

    // --- Early Exit: No Posts Check ---
    // This check is now more accurate for the selected post type.
    if (!$posts) {
        $has_posts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM $wpdb->posts WHERE post_type = %s AND post_status = 'publish' LIMIT 1",
                $post_type // Single post type
            )
        );
        if (!$has_posts) {
            wp_cache_set($cache_key, '', 'calendar'); // Cache empty string for no posts
            // Remove filters before returning empty output
            remove_filter('day_link', 'ajax_ac_filter_day_link', 10);
            remove_filter('month_link', 'ajax_ac_filter_month_link', 10);
            if ($echo) {
                echo '';
                return;
            } else {
                return '';
            }
        }
    }

    // --- Determine Current Month and Year ---
    $current_year_val = '';
    $current_month_num = '';

    if (!empty($monthnum) && !empty($year)) {
        $current_month_num = zeroise(intval($monthnum), 2);
        $current_year_val = intval($year);
    } elseif (!empty($m)) {
        $current_year_val = intval(substr($m, 0, 4));
        $current_month_num = zeroise(intval(substr($m, 4, 2)), 2);
    } else {
        $current_year_val = gmdate('Y', current_time('timestamp'));
        $current_month_num = gmdate('m', current_time('timestamp'));
    }

    // Unix timestamp for the first day of the current month
    $first_day_of_month_unix = mktime(0, 0, 0, $current_month_num, 1, $current_year_val);
    $days_in_current_month = date('t', $first_day_of_month_unix);

    // --- Calculate Previous and Next Month Details for Navigation ---
    $prev_month_unix = strtotime('-1 month', $first_day_of_month_unix);
    $next_month_unix = strtotime('+1 month', $first_day_of_month_unix);

    // get_month_link and get_day_link will now be filtered by ajax_ac_filter_month_link/day_link
    $prev_month_link = get_month_link(date('Y', $prev_month_unix), date('m', $prev_month_unix));
    $next_month_link = get_month_link(date('Y', $next_month_unix), date('m', $next_month_unix));
    $current_month_link = get_month_link($current_year_val, $current_month_num);

    // Get month names based on $is_bengali_enabled flag
    $prev_month_name = $wp_locale->get_month(date('m', $prev_month_unix));
    $next_month_name = $wp_locale->get_month(date('m', $next_month_unix));
    $current_month_name = $wp_locale->get_month($current_month_num);

    if ($is_bengali_enabled || 'bn' === substr(get_locale(), 0, 2)) {
        if (class_exists('Ajax_AC_Widget') && property_exists('Ajax_AC_Widget', 'month')) {
            if (isset(Ajax_AC_Widget::$month[date('m', $prev_month_unix)])) {
                $prev_month_name = Ajax_AC_Widget::$month[date('m', $prev_month_unix)];
            }
            if (isset(Ajax_AC_Widget::$month[date('m', $next_month_unix)])) {
                $next_month_name = Ajax_AC_Widget::$month[date('m', $next_month_unix)];
            }
            if (isset(Ajax_AC_Widget::$month[$current_month_num])) {
                $current_month_name = Ajax_AC_Widget::$month[$current_month_num];
            }
        }
    }

    $current_year_text = ($is_bengali_enabled || 'bn' === substr(get_locale(), 0, 2)) ?
        str_replace(Ajax_AC_Widget::$find, Ajax_AC_Widget::$replace, $current_year_val) : $current_year_val;

    // --- Calendar HTML Generation Start ---
    $calendar_output = '<table id="satej_it_com_my_calendar" class="satej_it_com_ajax-calendar">';
    $calendar_output .= '<thead>';

    // Month Navigation Row (Top)
    $calendar_output .= '<tr>';
    $calendar_output .= '<th colspan="7" class="calendar-nav calendar-nav-top">';
    $calendar_output .= '<div> <a href="' . esc_url($prev_month_link) . '" class="prev-month-link" title="' . esc_attr__('Previous month', 'ajax-archive-calendar') . '">&laquo;</a>';
    $calendar_output .= '<a href="' . esc_url($current_month_link) . '" class="current-month-link" title="' . esc_attr__('Current month', 'ajax-archive-calendar') . '">' . esc_html($current_month_name) . ' ' . esc_html($current_year_text)  . '</a>';
    $calendar_output .= '<a href="' . esc_url($next_month_link) . '" class="next-month-link" title="' . esc_attr__('Next month', 'ajax-archive-calendar') . '">&raquo;</a>';
    $calendar_output .= '</div> </th>';
    $calendar_output .= '</tr>';

    // Weekday Headers Row
    $calendar_output .= '<tr>';
    $week_begins = intval(get_option('start_of_week')); // 0 for Sunday, 1 for Monday, etc.
    $myweek = array();
    for ($wd_count = 0; $wd_count <= 6; $wd_count++) {
        $myweek[] = $wp_locale->get_weekday(($wd_count + $week_begins) % 7);
    }

    // Bengali weekday names mapping
    $bengali_weekdays = array(
        'Saturday'  => 'শনি',
        'Sunday'    => 'রবি',
        'Monday'    => 'সোম',
        'Tuesday'   => 'মঙ্গল',
        'Wednesday' => 'বুধ',
        'Thursday'  => 'বৃহ',
        'Friday'    => 'শুক্র'
    );

    foreach ($myweek as $weekday) {
        $display_weekday_name = ($is_bengali_enabled || 'bn' === substr(get_locale(), 0, 2)) ?
            (isset($bengali_weekdays[$weekday]) ? $bengali_weekdays[$weekday] : $wp_locale->get_weekday_abbrev($weekday)) :
            $wp_locale->get_weekday_abbrev($weekday);
        $calendar_output .= "\n\t\t<th class=\"" . esc_attr(sanitize_title($weekday)) . "\" scope=\"col\" title=\"" . esc_attr($weekday) . "\">" . esc_html($display_weekday_name) . "</th>";
    }
    $calendar_output .= '</tr>';
    $calendar_output .= '</thead>';

    $calendar_output .= '<tbody>';
    $calendar_output .= '<tr>';

    // --- Get Days with Posts ---
    $posts_in_month = get_posts(array(
        'post_type'      => $post_type, // Use the provided single post type
        'post_status'    => 'publish',
        'monthnum'       => $current_month_num,
        'year'           => $current_year_val,
        'numberposts'    => -1, // Get all posts for the month
        'suppress_filters' => false, // Allow filters to run on this query
    ));

    $days_with_posts = array();
    $titles_for_day = array();

    if ($posts_in_month) {
        $title_separator = (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false) ? "\n" : ', ';

        foreach ((array) $posts_in_month as $post_obj) {
            $day_of_post = date('j', strtotime($post_obj->post_date));
            if (!in_array($day_of_post, $days_with_posts)) {
                $days_with_posts[] = $day_of_post;
            }
            $post_title = esc_attr(get_the_title($post_obj));
            if (empty($titles_for_day[$day_of_post])) {
                $titles_for_day[$day_of_post] = $post_title;
            } else {
                $titles_for_day[$day_of_post] .= $title_separator . $post_title;
            }
        }
    }

    // --- Pad Start of Month ---
    $first_day_weekday = date('w', $first_day_of_month_unix); // 0 (for Sunday) through 6 (for Saturday)
    $pad_start = calendar_week_mod($first_day_weekday - $week_begins);

    if ($pad_start != 0) {
        $calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr($pad_start) . '" class="pad">&nbsp;</td>';
    }

    // --- Loop Through Days of the Month ---
    $new_row_needed = false;
    for ($day = 1; $day <= $days_in_current_month; ++$day) {
        // Check if a new row is needed (start of a new week)
        if ($new_row_needed) {
            $calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
            $new_row_needed = false;
        }

        // Apply Bengali number conversion if enabled
        $display_day_number = ($is_bengali_enabled || 'bn' === substr(get_locale(), 0, 2)) ?
            str_replace(Ajax_AC_Widget::$find, Ajax_AC_Widget::$replace, $day) : $day;

        // Determine CSS class for the day cell
        $cell_classes = array('day-cell');
        if ($day == gmdate('j', current_time('timestamp')) && $current_month_num == gmdate('m', current_time('timestamp')) && $current_year_val == gmdate('Y', current_time('timestamp'))) {
            $cell_classes[] = 'today';
        } else {
            $cell_classes[] = 'not-today';
        }

        $calendar_output .= '<td class="' . esc_attr(implode(' ', $cell_classes)) . '">';

        // Check if there are posts for this day
        if (in_array($day, $days_with_posts)) {
            $calendar_output .= '<a class="has-post" href="' . esc_url(get_day_link($current_year_val, $current_month_num, $day)) . '" title="' . esc_attr($titles_for_day[$day]) . '">' . esc_html($display_day_number) . '</a>';
        } else {
            $calendar_output .= '<span class="no-post">' . esc_html($display_day_number) . '</span>';
        }
        $calendar_output .= '</td>';

        // Check if it's the end of the week (and not the last day of the month)
        $current_day_weekday = date('w', mktime(0, 0, 0, $current_month_num, $day, $current_year_val));
        if (6 == calendar_week_mod($current_day_weekday - $week_begins) && $day < $days_in_current_month) {
            $new_row_needed = true;
        }
    }

    // --- Pad End of Month ---
    $last_day_weekday = date('w', mktime(0, 0, 0, $current_month_num, $days_in_current_month, $current_year_val));
    $pad_end = 7 - calendar_week_mod($last_day_weekday - $week_begins) - 1;

    if ($pad_end > 0 && $pad_end < 7) {
        $calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr($pad_end) . '">&nbsp;</td>';
    }

    $calendar_output .= "\n\t</tr>";
    $calendar_output .= "\n\t</tbody>";

    // Month Navigation Row (Bottom)
    $calendar_output .= '<tfoot>';
    $calendar_output .= '<tr>';
    $calendar_output .= '<td colspan="3" class="calendar-nav-bottom nav-prev">';
    $calendar_output .= '<a href="' . esc_url($prev_month_link) . '" title="' . esc_attr__('Previous month', 'ajax-archive-calendar') . '">&laquo; ' . esc_html($prev_month_name) . '</a>';
    $calendar_output .= '</td>';
    $calendar_output .= '<td colspan="4" class="calendar-nav-bottom nav-next">';
    $calendar_output .= '<a href="' . esc_url($next_month_link) . '" title="' . esc_attr__('Next month', 'ajax-archive-calendar') . '">' . esc_html($next_month_name) . ' &raquo;</a>';
    $calendar_output .= '</td>';
    $calendar_output .= '</tr>';
    $calendar_output .= '</tfoot>';
    $calendar_output .= "\n</table>";

    // --- Remove filters after generating output ---
    remove_filter('day_link', 'ajax_ac_filter_day_link', 10);
    remove_filter('month_link', 'ajax_ac_filter_month_link', 10);

    // --- Cache and Return/Echo Output ---
    wp_cache_set($cache_key, $calendar_output, 'calendar');

    if ($echo) {
        echo apply_filters('ajax_ac_calendar_output', $calendar_output);
    } else {
        return apply_filters('ajax_ac_calendar_output', $calendar_output);
    }
}

/**
 * Adds custom CSS to the WordPress head.
 */
add_action('wp_head', 'ajax_ac_head');
function ajax_ac_head()
{
    ?>
    <style type="text/css">
        /* General Calendar Table Styling */
        .satej_it_com_ajax-calendar {
            position: relative;
            width: 100%;
            border-collapse: collapse; /* Ensure borders are collapsed */
            border-radius: 8px; /* Rounded corners for the whole table */
            overflow: hidden; /* Ensures border-radius applies to content */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
            background-color: #ffffff; /* White background for the calendar body */
            table-layout: fixed; /* Crucial for equal column widths */
        }

        /* Table Headers (Weekdays) */
        .satej_it_com_ajax-calendar th {
            background-color: #2cb2bc; /* Consistent background */
            color: #FFFFFF;
            font-weight: 600; /* Slightly bolder for emphasis */
            padding: 10px 5px; /* Increased padding for better spacing */
            text-align: center;
            font-size: 15px; /* Slightly adjusted font size */
            text-transform: uppercase; /* Make weekdays uppercase */
            letter-spacing: 0.5px;
            width: calc(100% / 7); /* Distribute width equally among 7 columns */
        }

        /* Specific top-left and top-right th for rounded corners */
        .satej_it_com_ajax-calendar thead tr:first-child th:first-child {
            border-top-left-radius: 8px;
        }
        .satej_it_com_ajax-calendar thead tr:first-child th:last-child {
            border-top-right-radius: 8px;
        }

        /* Table Cells (Days) */
        .satej_it_com_ajax-calendar td {
            border: 1px solid #e0e0e0; /* Lighter border color */
            padding: 0; /* Remove default padding from td, let inner elements handle it */
            vertical-align: middle; /* Vertically center content */
            height: 50px; /* Give cells a consistent height */
        }

        /* Links for days with posts */
        .satej_it_com_ajax-calendar tbody td a.has-post {
            background-color: #00a000; /* A slightly brighter green */
            color: #FFFFFF;
            display: flex; /* Keep flex for inner centering */
            align-items: center; /* Vertically center content */
            justify-content: center; /* Horizontally center content */
            padding: 6px 0;
            width: 100%;
            height: 100%; /* Make the link fill the cell */
            text-decoration: none; /* Remove underline */
            font-weight: bold;
            transition: background-color 0.2s ease-in-out; /* Smooth transition on hover */
        }

        .satej_it_com_ajax-calendar tbody td a.has-post:hover {
            background-color: #006400; /* Darker green on hover */
        }

        /* Spans for days without posts */
        .satej_it_com_ajax-calendar span.no-post {
            display: flex; /* Keep flex for inner centering */
            align-items: center; /* Vertically center content */
            justify-content: center; /* Horizontally center content */
            padding: 6px 0;
            width: 100%;
            height: 100%; /* Make the span fill the cell */
            color: #555555; /* Softer text color for days without posts */
        }

        /* Padding cells (empty cells) */
        .satej_it_com_ajax-calendar .pad {
            background-color: #f9f9f9; /* Slightly different background for padding cells */
        }

        /* Today's Date Styling */
        .satej_it_com_ajax-calendar td.today {
            border: 2px solid #2cb2bc; /* More prominent border for today */
        }

        .satej_it_com_ajax-calendar td.today a,
        .satej_it_com_ajax-calendar td.today span {
            background-color: #2cb2bc !important; /* Keep important to override other backgrounds */
            color: #FFFFFF;
            font-weight: bold;
        }

        /* Navigation (Top) */
        .satej_it_com_ajax-calendar .calendar-nav-top {
            background-color: #2cb2bc; /* Consistent background */
            padding: 10px 0; /* Add padding */
            border-bottom: 1px solid #259fa8; /* Subtle separator */
        }

        .satej_it_com_ajax-calendar .calendar-nav-top div {
            display: flex;
            justify-content: space-between; /* Changed to space-between for better distribution */
            align-items: center;
            padding: 0 15px; /* Add horizontal padding inside the nav */
        }

        .satej_it_com_ajax-calendar .calendar-nav-top a {
            color: #FFFFFF;
            font-size: 20px; /* Slightly smaller for better balance */
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.2s ease-in-out;
        }

        .satej_it_com_ajax-calendar .calendar-nav-top a:hover {
            background-color: rgba(255, 255, 255, 0.2); /* Subtle hover effect */
        }

        .satej_it_com_ajax-calendar .calendar-nav-top .current-month-link {
            font-size: 22px; /* Emphasize current month */
            font-weight: bold;
            color: #FFFFFF;
            text-decoration: none;
            cursor: pointer; /* Indicate it's clickable */
        }

        /* Navigation (Bottom) */
        .satej_it_com_ajax-calendar tfoot td {
            border: none; /* Remove borders from footer cells */
            padding: 0; /* Remove default padding */
        }

        .satej_it_com_ajax-calendar tfoot td a {
            background-color: #2cb2bc; /* Consistent background */
            color: #FFFFFF;
            display: block;
            padding: 10px 0; /* More padding for better touch targets */
            width: 100% !important;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s ease-in-out;
        }

        .satej_it_com_ajax-calendar tfoot td a:hover {
            background-color: #259fa8; /* Slightly darker on hover */
        }

        .satej_it_com_ajax-calendar tfoot .nav-prev {
            text-align: left;
            border-bottom-left-radius: 8px; /* Rounded corner */
            overflow: hidden; /* Ensure radius applies */
        }

        .satej_it_com_ajax-calendar tfoot .nav-next {
            text-align: right;
            border-bottom-right-radius: 8px; /* Rounded corner */
            overflow: hidden; /* Ensure radius applies */
        }

        .satej_it_com_ajax-calendar tfoot .nav-prev a {
            padding-left: 15px; /* Adjust padding for text alignment */
        }

        .satej_it_com_ajax-calendar tfoot .nav-next a {
            padding-right: 15px; /* Adjust padding for text alignment */
        }


        /* Dropdown Selectors */
        #ajax_ac_widget .select_ca {
            margin-bottom: 10px; /* Add some space below dropdowns */
            display: flex; /* Use flexbox for better alignment of dropdowns */
            justify-content: space-between; /* Distribute items */
            gap: 10px; /* Space between dropdowns */
            flex-wrap: wrap; /* Allow wrapping on small screens */
        }

        #ajax_ac_widget #my_month,
        #ajax_ac_widget #my_year {
            /* Remove floats as flexbox is used on parent */
            float: none;
            flex-grow: 1; /* Allow dropdowns to grow and fill space */
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            font-size: 16px;
            cursor: pointer;
            -webkit-appearance: none; /* Remove default dropdown arrow */
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000%22%20d%3D%22M287%2C114.7L158.4%2C243.3c-2.8%2C2.8-6.1%2C4.2-9.5%2C4.2s-6.7-1.4-9.5-4.2L5.4%2C114.7C2.6%2C111.9%2C1.2%2C108.6%2C1.2%2C105.2s1.4-6.7%2C4.2-9.5l14.7-14.7c2.8-2.8%2C6.1-4.2%2C9.5-4.2s6.7%2C1.4%2C9.5%2C4.2l111.2%2C111.2L253.3%2C81c2.8-2.8%2C6.1-4.2%2C9.5-4.2s6.7%2C1.4%2C9.5%2C4.2l14.7%2C14.7c2.8%2C2.8%2C4.2%2C6.1%2C4.2%2C9.5S289.8%2C111.9%2C287%2C114.7z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
            padding-right: 30px; /* Make space for the custom arrow */
        }

        /* Clearfix for floats (if still needed, though flexbox mitigates) */
        .clear {
            clear: both;
        }

        /* Loading Indicator */
        .aj-loging {
            position: absolute;
            top: 0; /* Cover the whole calendar area */
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7); /* Semi-transparent white overlay */
            display: flex; /* Use flexbox to center content */
            align-items: center;
            justify-content: center;
            z-index: 10; /* Ensure it's on top */
            border-radius: 8px; /* Match calendar border-radius */
        }

        .aj-loging img {
            max-width: 50px; /* Adjust size of loading GIF */
            max-height: 50px;
        }
    </style>
    <?php
}

/**
 * Workaround WPML bug with get_day_link() function.
 *
 * @param string $url
 * @return string
 */
add_filter('day_link', 'ajax_ac_permalinks');
function ajax_ac_permalinks($url)
{
    return apply_filters('wpml_permalink',  $url);
}

/**
 * Create WP short code for Ajax Archive Calendar
 *
 * @param array $atts Shortcode attributes.
 * @return string The HTML output for the calendar.
 */
add_shortcode('ajax_archive_calendar', 'ajax_archive_calendar_shortcode');

function ajax_archive_calendar_shortcode($atts)
{
    $atts = shortcode_atts(
        array(
            'bengali' => 0, // Default to 0 (false)
            'start'   => date("Y"), // Default to current year
            'post_type' => 'post' // Default to 'post' (single)
        ),
        $atts,
        'ajax_archive_calendar'
    );

    $bengali_enabled = (bool) $atts['bengali'];
    $start_year = absint($atts['start']);
    
    // Ensure post_type is a single sanitized string
    $post_type = sanitize_key($atts['post_type']);
    if (empty($post_type)) {
        $post_type = 'post'; // Fallback if empty
    }

    $ajax_ac_widget = new Ajax_AC_Widget();
    return $ajax_ac_widget->generate_calendar_html($bengali_enabled, $start_year, $post_type);
}
