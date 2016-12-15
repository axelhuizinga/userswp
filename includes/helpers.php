<?php
function uwp_get_page_link($page) {

    $link = "";
    $page_id = false;

    switch ($page) {
        case 'register':
            $page_id = uwp_get_option('register_page', false);
            break;

        case 'login':
            $page_id = uwp_get_option('login_page', false);
            break;

        case 'forgot':
            $page_id = uwp_get_option('forgot_page', false);
            break;

        case 'account':
            $page_id = uwp_get_option('account_page', false);
            break;

        case 'profile':
            $page_id = uwp_get_option('profile_page', false);
            break;

        case 'users':
            $page_id = uwp_get_option('users_page', false);
            break;
    }

    if ($page_id) {
        $link = get_permalink($page_id);
    }

    return $link;
}

function uwp_get_users() {

    $uwp_users = array();

    $keyword = false;
    if (isset($_GET['uwps']) && $_GET['uwps'] != '') {
        $keyword = sanitize_title($_GET['uwps']);
    }

    $sort_by = false;
    if (isset($_GET['uwp_sort_by']) && $_GET['uwp_sort_by'] != '') {
        $sort_by = sanitize_title($_GET['uwp_sort_by']);
    }

    $args = array(
        'number' => 20,
        'search'         => "*{$keyword}*",
        'search_columns' => array(
            'user_login',
            'user_nicename',
            'user_email',
        ),
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key'     => 'first_name',
                'value'   => $keyword,
                'compare' => 'LIKE'
            ),
            array(
                'key'     => 'last_name',
                'value'   => $keyword,
                'compare' => 'LIKE'
            )
        )
    );

    if ($sort_by) {
        switch ($sort_by) {
            case "newer":
                $args['orderby'] = 'registered';
                $args['order'] = 'desc';
                break;
            case "older":
                $args['orderby'] = 'registered';
                $args['order'] = 'asc';
                break;
            case "alpha_asc":
                $args['orderby'] = 'display_name';
                $args['order'] = 'asc';
                break;
            case "alpha_desc":
                $args['orderby'] = 'display_name';
                $args['order'] = 'desc';
                break;

        }
    }

    $users_query = new WP_User_Query($args);
    $users = $users_query->get_results();

    foreach ( $users as $user ) {
        $uwp_users[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'avatar' => get_avatar( $user->user_email, 128 ),
            'link'  => get_author_posts_url($user->ID),
            'facebook' => '',
            'twitter'  => '',
            'description'  => ''
        );
    }

    return $uwp_users;
}

function uwp_post_count($user_id, $post_type, $extra_post_status = '') {
    global $wpdb;

    $post_status = "";
    if ($user_id == get_current_user_id()) {
        $post_status = ' OR post_status = "draft" OR post_status = "private"';
    }
    
    if (!empty($extra_post_status)) {
        $post_status .= $extra_post_status;
    }

    $post_status_where = ' AND ( post_status = "publish" ' . $post_status . ' )';

    $count = $wpdb->get_var('
             SELECT COUNT(ID)
             FROM ' . $wpdb->posts. '
             WHERE post_author = "' . $user_id . '"
             ' . $post_status_where . '
             AND post_type = "' . $post_type . '"'
    );
    return $count;
}

function uwp_comment_count($user_id) {
    global $wpdb;

    $count = $wpdb->get_var('
             SELECT COUNT(comment_ID)
             FROM ' . $wpdb->comments. '
             WHERE user_id = "' . $user_id . '"'
    );
    return $count;
}

function uwp_missing_callback($args) {
    printf(
        __( 'The callback function used for the %s setting is missing.', 'uwp' ),
        '<strong>' . $args['id'] . '</strong>'
    );
}

function uwp_select_callback($args) {
    global $uwp_options;

    if ( isset( $uwp_options[ $args['id'] ] ) ) {
        $value = $uwp_options[ $args['id'] ];
    } else {
        $value = isset( $args['std'] ) ? $args['std'] : '';
    }

    if ( isset( $args['placeholder'] ) ) {
        $placeholder = $args['placeholder'];
    } else {
        $placeholder = '';
    }

    if ( isset( $args['chosen'] ) ) {
        $chosen = ($args['multiple'] ? '[]" multiple="multiple" class="uwp_chosen_select" style="height:auto"' : "'");
    } else {
        $chosen = '';
    }

    $html = '<select id="uwp_settings[' . $args['id'] . ']" name="uwp_settings[' . $args['id'] . ']' . $chosen . ' data-placeholder="' . $placeholder . '" />';

    foreach ( $args['options'] as $option => $name ) {
        if (is_array($value)) {
            if (in_array($option, $value)) {
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }
        } else {
            $selected = selected( $option, $value, false );
        }
        $html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
    }

    $html .= '</select>';
    $html .= '<label for="uwp_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

    echo $html;
}

function uwp_text_callback( $args ) {
    global $uwp_options;

    if ( isset( $uwp_options[ $args['id'] ] ) ) {
        $value = $uwp_options[ $args['id'] ];
    } else {
        $value = isset( $args['std'] ) ? $args['std'] : '';
    }

    if ( isset( $args['faux'] ) && true === $args['faux'] ) {
        $args['readonly'] = true;
        $value = isset( $args['std'] ) ? $args['std'] : '';
        $name  = '';
    } else {
        $name = 'name="uwp_settings[' . $args['id'] . ']"';
    }

    $readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
    $size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
    $html     = '<input type="text" class="' . $size . '-text" id="uwp_settings[' . $args['id'] . ']"' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $readonly . '/>';
    $html    .= '<label for="uwp_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

    echo $html;
}

function uwp_textarea_callback( $args ) {
    global $uwp_options;

    if ( isset( $uwp_options[ $args['id'] ] ) ) {
        $value = $uwp_options[ $args['id'] ];
    } else {
        $value = isset( $args['std'] ) ? $args['std'] : '';
    }

    $html = '<textarea class="large-text" cols="50" rows="5" id="uwp_settings[' . $args['id'] . ']" name="uwp_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
    $html .= '<label for="uwp_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

    echo $html;
}

function uwp_checkbox_callback( $args ) {
    global $uwp_options;

    if ( isset( $args['faux'] ) && true === $args['faux'] ) {
        $name = '';
    } else {
        $name = 'name="uwp_settings[' . $args['id'] . ']"';
    }

    $checked = isset( $uwp_options[ $args['id'] ] ) ? checked( 1, $uwp_options[ $args['id'] ], false ) : '';
    $html = '<input type="checkbox" id="uwp_settings[' . $args['id'] . ']"' . $name . ' value="1" ' . $checked . '/>';
    $html .= '<label for="uwp_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

    echo $html;
}

function uwp_number_callback( $args ) {
    global $uwp_options;

    if ( isset( $uwp_options[ $args['id'] ] ) ) {
        $value = $uwp_options[ $args['id'] ];
    } else {
        $value = isset( $args['std'] ) ? $args['std'] : '';
    }

    if ( isset( $args['faux'] ) && true === $args['faux'] ) {
        $args['readonly'] = true;
        $value = isset( $args['std'] ) ? $args['std'] : '';
        $name  = '';
    } else {
        $name = 'name="uwp_settings[' . $args['id'] . ']"';
    }

    $max  = isset( $args['max'] ) ? $args['max'] : 999999;
    $min  = isset( $args['min'] ) ? $args['min'] : 0;
    $step = isset( $args['step'] ) ? $args['step'] : 1;

    $size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
    $html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="uwp_settings[' . $args['id'] . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"/>';
    $html .= '<label for="uwp_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

    echo $html;
}

function uwp_build_profile_tab_url($user_id, $tab = false, $subtab = false) {

    $link = apply_filters('uwp_profile_link', get_author_posts_url($user_id), $user_id);

    if ($link != '') {
        if (isset($_REQUEST['page_id'])) {
            $permalink_structure = 'DEFAULT';
        } else {
            $permalink_structure = 'CUSTOM';
            $link = rtrim($link, '/') . '/';
        }

        if ('DEFAULT' == $permalink_structure) {
            $link = add_query_arg(
                array(
                    'uwp_tab' => $tab,
                    'uwp_subtab' => $subtab
                ),
                $link
            );
        } else {
            if ($tab) {
                $link = $link . $tab;
            }

            if ($subtab) {
                $link = $link .'/'.$subtab;
            }
        }
    }

    return $link;

}

function uwp_get_option( $key = '', $default = false ) {
    global $uwp_options;
    $value = ! empty( $uwp_options[ $key ] ) ? $uwp_options[ $key ] : $default;
    $value = apply_filters( 'uwp_get_option', $value, $key, $default );
    return apply_filters( 'uwp_get_option_' . $key, $value, $key, $default );
}

function uwp_update_usermeta( $user_id = false, $key, $value ) {

    if (!$user_id || !$key ) {
        return false;
    }

    $usermeta = get_user_meta( $user_id, 'uwp_usermeta', true );

    if( !is_array( $usermeta ) ) {
        $usermeta = array();
    }

    $usermeta[ $key ] = $value;

    $usermeta = apply_filters( 'uwp_update_usermeta', $usermeta, $user_id, $key, $value );
    $usermeta =  apply_filters( 'uwp_update_usermeta_' . $key, $usermeta, $user_id, $key, $value );

    update_user_meta($user_id, 'uwp_usermeta', $usermeta);

    return true;
}


function uwp_get_usermeta( $user_id = false, $key = '', $default = false ) {

    if (!$user_id) {
        return $default;
    }

    $usermeta = get_user_meta( $user_id, 'uwp_usermeta', true );

    if( !is_array( $usermeta ) ) {
        $usermeta = array();
    }

    $value = ! empty( $usermeta[ $key ] ) ? $usermeta[ $key ] : $default;
    $value = apply_filters( 'uwp_get_usermeta', $value, $user_id, $key, $default );
    return apply_filters( 'uwp_get_usermeta_' . $key, $value, $user_id, $key, $default );
}

function uwp_date_format_php_to_jqueryui( $php_format ) {
    $symbols = array(
        // Day
        'd' => 'dd',
        'D' => 'D',
        'j' => 'd',
        'l' => 'DD',
        'N' => '',
        'S' => '',
        'w' => '',
        'z' => 'o',
        // Week
        'W' => '',
        // Month
        'F' => 'MM',
        'm' => 'mm',
        'M' => 'M',
        'n' => 'm',
        't' => '',
        // Year
        'L' => '',
        'o' => '',
        'Y' => 'yy',
        'y' => 'y',
        // Time
        'a' => 'tt',
        'A' => 'TT',
        'B' => '',
        'g' => 'h',
        'G' => 'H',
        'h' => 'hh',
        'H' => 'HH',
        'i' => 'mm',
        's' => '',
        'u' => ''
    );

    $jqueryui_format = "";
    $escaping = false;

    for ( $i = 0; $i < strlen( $php_format ); $i++ ) {
        $char = $php_format[$i];

        // PHP date format escaping character
        if ( $char === '\\' ) {
            $i++;

            if ( $escaping ) {
                $jqueryui_format .= $php_format[$i];
            } else {
                $jqueryui_format .= '\'' . $php_format[$i];
            }

            $escaping = true;
        } else {
            if ( $escaping ) {
                $jqueryui_format .= "'";
                $escaping = false;
            }

            if ( isset( $symbols[$char] ) ) {
                $jqueryui_format .= $symbols[$char];
            } else {
                $jqueryui_format .= $char;
            }
        }
    }

    return $jqueryui_format;
}

function uwp_date($date_input, $date_to, $date_from = '') {
    if (empty($date_input) || empty($date_to)) {
        return NULL;
    }

    $date = '';
    if (!empty($date_from)) {
        $datetime = date_create_from_format($date_from, $date_input);

        if (!empty($datetime)) {
            $date = $datetime->format($date_to);
        }
    }

    if (empty($date)) {
        $date = strpos($date_input, '/') !== false ? str_replace('/', '-', $date_input) : $date_input;
        $date = date_i18n($date_to, strtotime($date));
    }

    $date = uwp_maybe_untranslate_date($date);

    return apply_filters('uwp_date', $date, $date_input, $date_to, $date_from);
}

function uwp_maybe_untranslate_date($date){
    $english_long_months = array(
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    );

    $non_english_long_months  = array(
        __('January'),
        __('February'),
        __('March'),
        __('April'),
        __('May'),
        __('June'),
        __('July'),
        __('August'),
        __('September'),
        __('October'),
        __('November'),
        __('December'),
    );
    $date = str_replace($non_english_long_months,$english_long_months,$date);


    $english_short_months = array(
        ' Jan ',
        ' Feb ',
        ' Mar ',
        ' Apr ',
        ' May ',
        ' Jun ',
        ' Jul ',
        ' Aug ',
        ' Sep ',
        ' Oct ',
        ' Nov ',
        ' Dec ',
    );

    $non_english_short_months = array(
        ' '._x( 'Jan', 'January abbreviation' ).' ',
        ' '._x( 'Feb', 'February abbreviation' ).' ',
        ' '._x( 'Mar', 'March abbreviation' ).' ',
        ' '._x( 'Apr', 'April abbreviation' ).' ',
        ' '._x( 'May', 'May abbreviation' ).' ',
        ' '._x( 'Jun', 'June abbreviation' ).' ',
        ' '._x( 'Jul', 'July abbreviation' ).' ',
        ' '._x( 'Aug', 'August abbreviation' ).' ',
        ' '._x( 'Sep', 'September abbreviation' ).' ',
        ' '._x( 'Oct', 'October abbreviation' ).' ',
        ' '._x( 'Nov', 'November abbreviation' ).' ',
        ' '._x( 'Dec', 'December abbreviation' ).' ',
    );

    $date = str_replace($non_english_short_months,$english_short_months,$date);


    return $date;
}

function uwp_string_values_to_options($option_values = '', $translated = false)
{
    $options = array();
    if ($option_values == '') {
        return NULL;
    }

    if (strpos($option_values, "{/optgroup}") !== false) {
        $option_values_arr = explode("{/optgroup}", $option_values);

        foreach ($option_values_arr as $optgroup) {
            if (strpos($optgroup, "{optgroup}") !== false) {
                $optgroup_arr = explode("{optgroup}", $optgroup);

                $count = 0;
                foreach ($optgroup_arr as $optgroup_str) {
                    $count++;
                    $optgroup_str = trim($optgroup_str);

                    $optgroup_label = '';
                    if (strpos($optgroup_str, "|") !== false) {
                        $optgroup_str_arr = explode("|", $optgroup_str, 2);
                        $optgroup_label = trim($optgroup_str_arr[0]);
                        if ($translated && $optgroup_label != '') {
                            $optgroup_label = __($optgroup_label, 'uwp');
                        }
                        $optgroup_label = ucfirst($optgroup_label);
                        $optgroup_str = $optgroup_str_arr[1];
                    }

                    $optgroup3 = uwp_string_to_options($optgroup_str, $translated);

                    if ($count > 1 && $optgroup_label != '' && !empty($optgroup3)) {
                        $optgroup_start = array(array('label' => $optgroup_label, 'value' => NULL, 'optgroup' => 'start'));
                        $optgroup_end = array(array('label' => $optgroup_label, 'value' => NULL, 'optgroup' => 'end'));
                        $optgroup3 = array_merge($optgroup_start, $optgroup3, $optgroup_end);
                    }
                    $options = array_merge($options, $optgroup3);
                }
            } else {
                $optgroup1 = uwp_string_to_options($optgroup, $translated);
                $options = array_merge($options, $optgroup1);
            }
        }
    } else {
        $options = uwp_string_to_options($option_values, $translated);
    }

    return $options;
}

function uwp_string_to_options($input = '', $translated = false)
{
    $return = array();
    if ($input != '') {
        $input = trim($input);
        $input = rtrim($input, ",");
        $input = ltrim($input, ",");
        $input = trim($input);
    }

    $input_arr = explode(',', $input);

    if (!empty($input_arr)) {
        foreach ($input_arr as $input_str) {
            $input_str = trim($input_str);

            if (strpos($input_str, "/") !== false) {
                $input_str = explode("/", $input_str, 2);
                $label = trim($input_str[0]);
                if ($translated && $label != '') {
                    $label = __($label, 'uwp');
                }
                $label = ucfirst($label);
                $value = trim($input_str[1]);
            } else {
                if ($translated && $input_str != '') {
                    $input_str = __($input_str, 'uwp');
                }
                $label = ucfirst($input_str);
                $value = $input_str;
            }

            if ($label != '') {
                $return[] = array('label' => $label, 'value' => $value, 'optgroup' => NULL);
            }
        }
    }

    return $return;
}

function uwp_allowed_mime_types() {
    return apply_filters( 'uwp_allowed_mime_types', array(
            'Image'       => array( // Image formats.
                'jpg'  => 'image/jpeg',
                'jpe'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'png'  => 'image/png',
                'bmp'  => 'image/bmp',
                'ico'  => 'image/x-icon',
            ),
            'Video'       => array( // Video formats.
                'asf'  => 'video/x-ms-asf',
                'avi'  => 'video/avi',
                'flv'  => 'video/x-flv',
                'mkv'  => 'video/x-matroska',
                'mp4'  => 'video/mp4',
                'mpeg' => 'video/mpeg',
                'mpg'  => 'video/mpeg',
                'wmv'  => 'video/x-ms-wmv',
                '3gp'  => 'video/3gpp',
            ),
            'Audio'       => array( // Audio formats.
                'ogg' => 'audio/ogg',
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'wma' => 'audio/x-ms-wma',
            ),
            'Text'        => array( // Text formats.
                'css'  => 'text/css',
                'csv'  => 'text/csv',
                'htm'  => 'text/html',
                'html' => 'text/html',
                'txt'  => 'text/plain',
                'rtx'  => 'text/richtext',
                'vtt'  => 'text/vtt',
            ),
            'Application' => array( // Application formats.
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'exe'  => 'application/x-msdownload',
                'js'   => 'application/javascript',
                'odt'  => 'application/vnd.oasis.opendocument.text',
                'pdf'  => 'application/pdf',
                'pot'  => 'application/vnd.ms-powerpoint',
                'ppt'  => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.ms-powerpoint',
                'psd'  => 'application/octet-stream',
                'rar'  => 'application/rar',
                'rtf'  => 'application/rtf',
                'swf'  => 'application/x-shockwave-flash',
                'tar'  => 'application/x-tar',
                'xls'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'zip'  => 'application/zip',
            )
        )
    );
}

function uwp_get_file_type($ext) {
    $allowed_file_types = uwp_allowed_mime_types();
    $file_types = array();
    foreach ( $allowed_file_types as $format => $types ) {
        $file_types = array_merge($file_types, $types);
    }
    $file_types = array_flip($file_types);
    return $file_types[$ext];
}

function uwp_resizeImage($image,$width,$height,$scale) {
    list($imagewidth, $imageheight, $imageType) = getimagesize($image);
    $imageType = image_type_to_mime_type($imageType);
    $newImageWidth = ceil($width * $scale);
    $newImageHeight = ceil($height * $scale);
    $newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
    $source = false;
    switch($imageType) {
        case "image/gif":
            $source=imagecreatefromgif($image);
            break;
        case "image/pjpeg":
        case "image/jpeg":
        case "image/jpg":
            $source=imagecreatefromjpeg($image);
            break;
        case "image/png":
        case "image/x-png":
            $source=imagecreatefrompng($image);
            break;
    }
    imagecopyresampled($newImage,$source,0,0,0,0,$newImageWidth,$newImageHeight,$width,$height);

    switch($imageType) {
        case "image/gif":
            imagegif($newImage,$image);
            break;
        case "image/pjpeg":
        case "image/jpeg":
        case "image/jpg":
            imagejpeg($newImage,$image,90);
            break;
        case "image/png":
        case "image/x-png":
            imagepng($newImage,$image);
            break;
    }

    chmod($image, 0777);
    return $image;
}

function uwp_resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale){
    // ignore image createion warnings
    @ini_set('gd.jpeg_ignore_warning', 1);
    list($imagewidth, $imageheight, $imageType) = getimagesize($image);
    $imageType = image_type_to_mime_type($imageType);

    $newImageWidth = ceil($width * $scale);
    $newImageHeight = ceil($height * $scale);
    $newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
    $source = false;
    switch($imageType) {
        case "image/gif":
            $source=imagecreatefromgif($image);
            break;
        case "image/pjpeg":
        case "image/jpeg":
        case "image/jpg":
            $source=imagecreatefromjpeg($image);
            break;
        case "image/png":
        case "image/x-png":
            $source=imagecreatefrompng($image);
            break;
    }
    imagecopyresampled($newImage,$source,0,0,$start_width,$start_height,$newImageWidth,$newImageHeight,$width,$height);
    switch($imageType) {
        case "image/gif":
            imagegif($newImage,$thumb_image_name);
            break;
        case "image/pjpeg":
        case "image/jpeg":
        case "image/jpg":
            imagejpeg($newImage,$thumb_image_name,90);
            break;
        case "image/png":
        case "image/x-png":
            imagepng($newImage,$thumb_image_name);
            break;
    }

    chmod($thumb_image_name, 0777);
    return $thumb_image_name;
}

function is_uwp_page($type = false) {
    if (is_page()) {
        global $post;
        $current_page_id = $post->ID;
        if ($type) {
            $uwp_page = uwp_get_option($type, false);
            if ( $uwp_page && ((int) $uwp_page ==  $current_page_id ) ) {
                return true;
            } else {
                return false;
            }    
        } else {
            if (is_uwp_register_page()) {
                return true;
            } elseif (is_uwp_login_page()) {
                return true;
            } elseif (is_uwp_forgot_page()) {
                return true;
            } elseif (is_uwp_reset_page()) {
                return true;
            } elseif (is_uwp_account_page()) {
                return true;
            } elseif (is_uwp_profile_page()) {
                return true;
            } elseif (is_uwp_users_page()) {
                return true;
            } else {
                return false;
            }
        }

    } else {
        return false;
    }
}

function is_uwp_register_page() {
    return is_uwp_page('register_page');
}

function is_uwp_login_page() {
    return is_uwp_page('login_page');
}

function is_uwp_forgot_page() {
    return is_uwp_page('forgot_page');
}

function is_uwp_reset_page() {
    return is_uwp_page('reset_page');
}

function is_uwp_account_page() {
    return is_uwp_page('account_page');
}

function is_uwp_profile_page() {
    return is_uwp_page('profile_page');
}

function is_uwp_users_page() {
    return is_uwp_page('users_page');
}

function uwp_generic_tab_content($user, $post_type, $title) {
    ?>
    <h3><?php echo $title; ?></h3>
    <div class="uwp-profile-item-block">
        <?php
        $paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => uwp_get_option('profile_no_of_items', 10),
            'author' => $user->ID,
            'paged' => $paged,
        );
        // The Query
        $the_query = new WP_Query($args);

        // The Loop
        if ($the_query->have_posts()) {
            echo '<ul class="uwp-profile-item-ul">';
            while ($the_query->have_posts()) {
                $the_query->the_post();
                ?>
                <li class="uwp-profile-item-li uwp-profile-item-clearfix">
                    <a class="uwp-profile-item-img" href="<?php echo get_the_permalink(); ?>">
                        <?php
                        if ( has_post_thumbnail() ) {
                            $thumb_url = get_the_post_thumbnail_url(get_the_ID(), array(80, 80));
                        } else {
                            $thumb_url = plugins_url()."/userswp/public/assets/images/no_thumb.png";
                        }
                        ?>
                        <img class="uwp-profile-item-alignleft uwp-profile-item-thumb" src="<?php echo $thumb_url; ?>">
                    </a>

                    <h3 class="uwp-profile-item-title">
                        <a href="<?php echo get_the_permalink(); ?>"><?php echo get_the_title(); ?></a>
                    </h3>
                    <time class="uwp-profile-item-time published" datetime="<?php echo get_the_time('c'); ?>">
                        <?php echo get_the_date(); ?>
                    </time>
                    <div class="uwp-profile-item-summary">
                        <?php
                        $excerpt = strip_shortcodes(wp_trim_words( get_the_excerpt(), 15, '...' ));
                        echo $excerpt;
                        if ($excerpt) {
                            ?>
                            <a href="<?php echo get_the_permalink(); ?>" class="more-link">Read More »</a>
                            <?php
                        }
                        ?>
                    </div>
                </li>
                <?php
            }
            echo '</ul>';
            /* Restore original Post Data */
            wp_reset_postdata();
        } else {
            // no posts found
            echo "<p>".__('No '.$title.' Found', 'uwp')."</p>";
        }
        do_action('uwp_profile_pagination', $the_query->max_num_pages);
        ?>
    </div>
    <?php
}

function uwp_error_log($log){
    /*
     * A filter to override the WP_DEBUG setting for function uwp_error_log().
     */
    $should_log = apply_filters( 'uwp_log_errors', WP_DEBUG);
    if ( true === $should_log ) {
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( print_r( $log, true ) );
        } else {
            error_log( $log );
        }
    }
}

function uwp_admin_notices() {
    $errors = get_option( 'uwp_admin_notices' );

    if ( ! empty( $errors ) ) {

        echo '<div id="uwp_admin_errors" class="notice-error notice is-dismissible">';
        
        echo '<p>' . $errors . '</p>';

        echo '</div>';

        // Clear
        delete_option( 'uwp_admin_notices' );
    }
}
add_action( 'admin_notices', 'uwp_admin_notices' );

//File uploads
function handle_file_upload( $field, $files ) {

    if ( isset( $files[ $field->htmlvar_name ] ) && ! empty( $files[ $field->htmlvar_name ] ) && ! empty( $files[ $field->htmlvar_name ]['name'] ) ) {

        $extra_fields = unserialize($field->extra_fields);

        $allowed_mime_types = array();
        if (isset($extra_fields['uwp_file_types'])) {
            $allowed_mime_types = $extra_fields['uwp_file_types'];
        }

        $allowed_mime_types = apply_filters('uwp_allowed_mime_types', $allowed_mime_types, $field->htmlvar_name);

        $file_urls       = array();
        $files_to_upload = uwp_prepare_files( $files[ $field->htmlvar_name ] );

        foreach ( $files_to_upload as $file_key => $file_to_upload ) {

            if (!empty($allowed_mime_types)) {
                $ext = uwp_get_file_type($file_to_upload['type']);

                $allowed_error_text = implode(', ', $allowed_mime_types);
                if ( !in_array( $ext , $allowed_mime_types ) )
                    return new WP_Error( 'validation-error', sprintf( __( 'Allowed files types are: %s', 'uwp' ),  $allowed_error_text) );
            }

            $max_upload_size = wp_max_upload_size();
            if ( ! $max_upload_size ) {
                $max_upload_size = 0;
            }
            if ( $file_to_upload['size'] >  $max_upload_size)
                return new WP_Error( 'avatar-too-big', __( 'The uploaded file is too big. Maximum size allowed:'. uwp_formatSizeUnits($max_upload_size), 'uwp' ) );


            $uploaded_file = uwp_upload_file( $file_to_upload, array( 'file_key' => $file_key ) );

            if ( is_wp_error( $uploaded_file ) ) {

                return new WP_Error( 'validation-error', $uploaded_file->get_error_message() );

            } else {

                $file_urls[] = array(
                    'url'  => $uploaded_file->url,
                    'path' => $uploaded_file->path,
                    'size' => $uploaded_file->size,
                    'name' => $uploaded_file->name,
                    'type' => $uploaded_file->type,
                );

            }

        }

        return current( $file_urls );

    }
    return true;

}

function uwp_formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824)
    {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    }
    elseif ($bytes >= 1048576)
    {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    }
    elseif ($bytes >= 1024)
    {
        $bytes = number_format($bytes / 1024, 2) . ' kB';
    }
    elseif ($bytes > 1)
    {
        $bytes = $bytes . ' bytes';
    }
    elseif ($bytes == 1)
    {
        $bytes = $bytes . ' byte';
    }
    else
    {
        $bytes = '0 bytes';
    }

    return $bytes;
}    


function uwp_upload_file( $file, $args = array() ) {

    include_once ABSPATH . 'wp-admin/includes/file.php';
    include_once ABSPATH . 'wp-admin/includes/media.php';

    $args = wp_parse_args( $args, array(
        'file_key'           => '',
        'file_label'         => '',
        'allowed_mime_types' => get_allowed_mime_types()
    ) );

    $uploaded_file              = new stdClass();

    if ( ! in_array( $file['type'], $args['allowed_mime_types'] ) ) {
        if ( $args['file_label'] ) {
            return new WP_Error( 'upload', sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'uwp' ), $args['file_label'], $file['type'], implode( ', ', array_keys( $args['allowed_mime_types'] ) ) ) );
        } else {
            return new WP_Error( 'upload', sprintf( __( 'Uploaded files need to be one of the following file types: %s', 'uwp' ), implode( ', ', array_keys( $args['allowed_mime_types'] ) ) ) );
        }
    } else {
        $upload = wp_handle_upload( $file, apply_filters( 'uwp_handle_upload_overrides', array( 'test_form' => false ) ) );
        if ( ! empty( $upload['error'] ) ) {
            return new WP_Error( 'upload', $upload['error'] );
        } else {
            $uploaded_file->url       = $upload['url'];
            $uploaded_file->name      = basename( $upload['file'] );
            $uploaded_file->path      = $upload['file'];
            $uploaded_file->type      = $upload['type'];
            $uploaded_file->size      = $file['size'];
            $uploaded_file->extension = substr( strrchr( $uploaded_file->name, '.' ), 1 );
        }
    }


    return $uploaded_file;
}


function uwp_prepare_files( $file_data ) {
    $files_to_upload = array();

    if ( is_array( $file_data['name'] ) ) {
        foreach ( $file_data['name'] as $file_data_key => $file_data_value ) {

            if ( $file_data['name'][ $file_data_key ] ) {
                $files_to_upload[] = array(
                    'name'     => $file_data['name'][ $file_data_key ],
                    'type'     => $file_data['type'][ $file_data_key ],
                    'tmp_name' => $file_data['tmp_name'][ $file_data_key ],
                    'error'    => $file_data['error'][ $file_data_key ],
                    'size'     => $file_data['size'][ $file_data_key ]
                );
            }
        }
    } else {
        $files_to_upload[] = $file_data;
    }

    return $files_to_upload;
}


function uwp_validate_uploads($files, $type, $url_only = true) {

    $validated_data = array();

    if (empty($files)) {
        return $validated_data;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'uwp_form_fields';

    if ($type == 'register') {
        $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE form_type = %s AND field_type = 'file' AND is_active = '1' AND is_register_field = '1' ORDER BY sort_order ASC", array('account')));
    } elseif ($type == 'account') {
        $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE form_type = %s AND field_type = 'file' AND is_active = '1' AND is_register_only_field = '0' ORDER BY sort_order ASC", array('account')));
    } else {
        $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE form_type = %s AND field_type = 'file' AND is_active = '1' ORDER BY sort_order ASC", array($type)));
    }


    if (!empty($fields)) {
        foreach ($fields as $field) {

            if(isset($files[$field->htmlvar_name])) {

                $file_urls = handle_file_upload($field, $files);

                if (is_wp_error($file_urls)) {
                    return $file_urls;
                }

                if ($url_only) {
                    $validated_data[$field->htmlvar_name] = $file_urls['url'];    
                } else {
                    $validated_data[$field->htmlvar_name] = $file_urls;
                }
            }

        }
    }

    return $validated_data;
}

function get_register_form_fields() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'uwp_form_fields';
    $extras_table_name = $wpdb->prefix . 'uwp_form_extras';
    $fields = $wpdb->get_results($wpdb->prepare("SELECT fields.* FROM " . $table_name . " fields JOIN " . $extras_table_name . " extras ON extras.site_htmlvar_name = fields.htmlvar_name WHERE fields.form_type = %s AND fields.is_active = '1' AND fields.is_register_field = '1' ORDER BY extras.sort_order ASC", array('account')));
    return $fields;
}

function get_account_form_fields() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'uwp_form_fields';
    $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE form_type = %s AND is_active = '1' AND is_register_only_field = '0' ORDER BY sort_order ASC", array('account')));
    return $fields;
}