<?php

/*
 * API Functions
 */

function tagpig_api_check_result_is_pass($check_result) {
  $result = false;

  if($check_result) {
    # A list of subscriptions for this plugin...
    $pass_array = array(
      'bronze',   # to be removed soon
      'free',
      'tagpig',
      '.*?pro',
    );

    # Compare...
    $tmp_list = strtolower(':' . str_replace(',', ':', str_replace(' ', '', $check_result)) . ':');
    foreach($pass_array as $pattern) {
      $result = preg_match("/:$pattern:/", $tmp_list);
      if($result) {
        break;
      }
    }

    unset($pass_array);
  }

  return $result;
}

function tagpig_api_check($force = false) {
  $result = false;

  #echo "tagpig_api_check:: here <BR />\n";
  if($_REQUEST['blogpig_api_key']) {
    $api_key = $_REQUEST['blogpig_api_key'];
  }
  else {
    $api_key = get_option('blogpig_api_key');
  }
  if($api_key) {
    $api_check_result = get_option('blogpig_api_check_result');
    $old_api_key = get_option('blogpig_old_api_key');
    $api_key_changed = $api_key != $old_api_key;
    $yesterday = time() - 24 * 60 * 60;
    if($api_key_changed ||                                              # api key changed since the last check or
       //!tagpig_api_check_result_is_pass($api_check_result) ||         # api key did not pass or
       $force ||                                                        # forced refresh
       get_option('blogpig_api_check_date') < $yesterday) {             # the last check was more than 24h ago...
      $api_check_url = "http://blogpig.com/api_check_new.php?key={$api_key}&id=1";
      if(function_exists('curl_init')) { # try for CURL first...
        #echo "tagpig_api_check:: curl <BR />\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, "TagPiG/2.2");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $api_check_url);

        # Proxy
        if(class_exists('WP_HTTP_Proxy')) {
          $proxy = new WP_HTTP_Proxy();
          if($proxy->is_enabled() && $proxy->send_through_proxy($api_check_url)) {
            $isPHP5 = version_compare(PHP_VERSION, '5.0.0', '>=');
            if ($isPHP5) {
              curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
              curl_setopt($ch, CURLOPT_PROXY, $proxy->host());
              curl_setopt($ch, CURLOPT_PROXYPORT, $proxy->port());
            }
            else {
              curl_setopt($ch, CURLOPT_PROXY, $proxy->host() .':'. $proxy->port());
            }

            if($proxy->use_authentication()) {
              if ($isPHP5) {
                curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
              }
              curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy->authentication());
            }
          }
        }

        $api_check_result = curl_exec($ch);
        curl_close($ch);
        unset($ch);
      }
      else {
        $reply = false;
        if(!class_exists('WP_Http')) {
          @include_once(ABSPATH . WPINC. '/class-http.php');
        }
        if(class_exists('WP_Http')) {
          $request = new WP_Http;
          $reply = $request->request($api_check_url, array('user-agent' => 'TagPiG/2.2'));
        }
        if($reply && is_array($reply)) {
          $api_check_result = $reply['body'];
        }
        else {
          $api_check_result = file_get_contents($api_check_url);
        }
      }
      # Does it have the ID?
      $idx = strpos($api_check_result, '|');
      if($idx !== false) {
        $api_member_id = substr($api_check_result, 0, $idx);
        update_option('blogpig_api_member_id', $api_member_id);
        $api_check_result = substr($api_check_result, $idx + 1);
      }
      update_option('blogpig_api_check_result', $api_check_result);
      update_option('blogpig_api_check_date', time());
      update_option('blogpig_old_api_key', $api_key);
    }
    $result = tagpig_api_check_result_is_pass($api_check_result);
  }
  else {
    update_option('blogpig_api_check_result', '[ no key ]');
  }

  return trim($result);
}


/*
 * Options Functions
 */

function wptagpig_default_options() {
  $options = array(
                   'wptagpig_sug_enabled' => 'on',
                   'wptagpig_sug_type_yahoo' => 'on',
                   'wptagpig_sug_yahookey' => 'YahooDemo',
                   'wptagpig_sug_maxtags' => '0', // '5',
                   'wptagpig_sug_minwords' => '2',
                   'wptagpig_sug_badwords' => 'off',
                   'wptagpig_sug_badwords_list' => '',

                   'wptagpig_lnk_enabled' => 'no',
                   'wptagpig_lnk_case' => 'no',
                   'wptagpig_lnk_nofollow' => 'no',
                   'wptagpig_lnk_mintags' => '5',

                   'wptagpig_cnt_posts' => 'yes',
                   'wptagpig_cnt_feeds' => 'yes',
                   'wptagpig_cnt_cats' => 'no',
                   'wptagpig_template' => 'Tags: %Repeat_Begin% %Tag%%Separator% %Repeat_End% &lt;BR/&gt;',
                   'wptagpig_cnt_separator' => ',',

                   'wptagpig_cnt_notags' => '',
                   'wptagpig_cnt_maxtags' => '3-7',

                   # Removed from UI...
                   'wptagpig_emb_prefix' => '[tags]',
                   'wptagpig_emb_sufix' => '[/tags]',
                  );
  update_option('wptagpig_emb_prefix', $options['wptagpig_emb_prefix']);
  update_option('wptagpig_emb_sufix', $options['wptagpig_emb_sufix']);
  return $options;
}

function wptagpig_read_default_settings() {
  $result = false;

  $options = wptagpig_default_options();
  if(function_exists('wptagpig_pro_add_options')) {
    wptagpig_pro_add_options($options);
  }

  foreach($options as $name => $value) {
    if($name) {
      # Write it to the DB... Should I check something first?
      update_option($name, $value);
    }
  }

  return $result;
}


/*
 * Other FUnctions
 */

function wptagpig_shuffle_assoc(&$array) {
  $keys = array_keys($array);

  shuffle($keys);

  foreach($keys as $key) {
      $new[$key] = $array[$key];
  }

  $array = $new;

  return true;
}

/*
 * Remove this plugin from the update list...
 */

function tagpig_no_updates($r, $url) {
  if(0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check'))
    return $r; // Not a plugin update request. Bail immediately.
  $plugins = unserialize($r['body']['plugins']);
  $file = dirname(__FILE__) . '/tagpig.php';
  unset($plugins->plugins[plugin_basename($file)]);
  unset($plugins->active[array_search(plugin_basename($file), $plugins->active)]);
  $r['body']['plugins'] = serialize($plugins);
  return $r;
}
add_filter('http_request_args', 'tagpig_no_updates', 5, 2);


/*
 * TagPiG Functions
 */

if(!function_exists('tagpig_api_show_field')) {
  function tagpig_api_show_field($plugin_dir = '') {
    $result = false;

    $force = false;
    if($_POST['btnSubmitKey']) {
      update_option('blogpig_api_key', $_POST['blogpig_api_key']);
      $force = true;
    }
    else if($_GET['btnSubmitKey']) {
      $force = true;
    }

    $api_key = get_option('blogpig_api_key');
    $api_check_result = tagpig_api_check($force);
    if($api_key) {
      $api_key_info = trim(get_option('blogpig_api_check_result'));
    }
    else {
      $api_key_info = 'no key';
    }

    global $wp_version;

    echo "
      <div class='postbox ' >
        <h3 class='hndle'>
          <span style='vertical-align: top;'>BlogPiG API Key</span>";
    tagpig_show_header_link('blogpig-api-key', $plugin_dir);
    echo "
        </h3>
        <div class='inside'>

          <p>
            <TABLE width='100%' style='margin-top:12px;'>
              <TR>
                <TD width='20%'>
                  API Key:
                </TD>
                <TD width='80%'>
                  <INPUT type='text' name='blogpig_api_key' id = 'blogpig_api_key' value='{$api_key}' size='25' />
                  <INPUT type='submit' class='button' name='btnSubmitKey' id='btnSubmitKey' value='Save Key' />
                  <BR />
                </TD>
              </TR>
              <TR>
                <TD width='20%'>
                  Your Licenses:
                </TD>
                <TD width='80%'>
    ";
    if(!$api_check_result) {
      echo '<span style="color:red; ">';
    }
    else {
      echo '<span style="color:green; ">';
    }
    echo "
                  {$api_key_info}</span>
                </TD>
              </TR>
            </TABLE>
          </p>
          <BR />

        </div> <!--- class='inside' --->
      </div> <!--- class='postbox ' --->
    ";


    return $result;
  }
}

/*
 * Tooltips...
 */

function tagpig_show_tooltip($param, $plugin_dir = '', $image = 'tooltip') {
  $result = false;
  if($param) {
    @include(ABSPATH . "/wp-content/plugins/{$plugin_dir}tooltips.php");
    if($tooltips[$param]) {
      echo "
        <a href='#' onclick='return false;' class='bptooltips'><img src='" . get_option('siteurl') . "/wp-content/plugins/{$plugin_dir}images/{$image}.png' /><span class='bptooltips'>" . $tooltips[$param] . "</span></a>
      ";
    }
  }
  return $result;
}

function tagpig_show_header_link($section_name, $plugin_dir, $section_ref = '', $color = false, $link_text = 'more info') {
  $result = false;
  if($section_name) {
    /*
    @include(ABSPATH . "/wp-content/plugins/{$plugin_dir}tooltips.php");
    $href = "http://www.youtube.com/v/{$headers[$section_name]}?version=3&enablejsapi=1&fs=1&hd=1&cc_load_policy=1&feature=player_embedded&autoplay=1";
    if(preg_match('@^http://@i', $headers[$section_name])) {
      $href = $headers[$section_name];
    }
    else {
      // Get video title...
      $link_text = get_option('blogpig_header_link_title-' . $headers[$section_name], $link_text);
      if(!$link_text || $link_text == 'more info') {
        if(!class_exists('WP_Http')) {
          include_once(ABSPATH . WPINC. '/class-http.php');
        }
        $http = new WP_Http;
        if($http) {
          $reply = $http->request('http://gdata.youtube.com/feeds/api/videos/' . $headers[$section_name]);
          $gdata = ($reply && is_array($reply) ? $reply['body'] : '');
          if($gdata) {
            $found = array();
            if(preg_match('@<title.*?>(.*?)</title>@i', $gdata, $found)) {
              $link_text = $found[1];
              update_option('blogpig_header_link_title-' . $headers[$section_name], $link_text);
            }
            unset($found);
          }
        }
      }
    }

    echo "
      &nbsp;<a class='colorboxtips' " . ($color ? "style='color:{$color};'" : "") . " href='{$href}' title='{$link_text}'><IMG src='" . get_option('siteurl') . "/wp-content/plugins/{$plugin_dir}images/" . ($color ? "pro_" : "") . "camera.png' ></a>
    ";
    */
  }
  return $result;
}


/*
 * TagPiG Functions
 */

if(tagpig_api_check()) {

  #
  # Logging...
  ###

  include_once('log_functions.php');

  #
  # AUTO-SUGGEST TAGS
  ###

  function autosuggestYahooTermExtraction($my_content, $my_title, $my_tags = '') {
    @set_time_limit(300);

    $result = '';

    // Get data
    $content = stripslashes($my_content) .' '. stripslashes($my_title);
    $content = trim($content);
    $content = strip_tags($content);
    $content = str_replace('&apos;', "'", $content);
    $content = str_replace('&quot;', '"', $content);
    $content = str_replace('&amp;', '&', $content);
    $content = str_replace('&nbsp;', ' ', $content);
    if(!empty($content)) {
      $yahoo_id = get_option('wptagpig_sug_yahookey');

      /*
       *  NEW YQL code...
       */
      $yahoo_api_host = 'query.yahooapis.com';
      $yahoo_api_path = '/v1/public/yql';
      $yahoo_api_query = 'SELECT * FROM search.termextract WHERE context="%context%" AND query="%query%" AND appid="%appid%" ';

      $tags = stripslashes($my_tags);
      $data = '';
      $param_array = array(
        'appid' => $yahoo_id,
        'context' => $content,
        'query' => $tags,
      );
      foreach($param_array as $param => $value) {
        $yahoo_api_query = str_replace('%' . $param . '%', str_replace('"', '\"', $value), $yahoo_api_query);
      }

      $yql_array = array(
        'q=' . urlencode($yahoo_api_query),
        'format=json',
      );

      include_once('tagpig_http.class.php');
      $http = new TagPiGHTTP('', '', false, false);
      if($http) {
        $data = $http->getUrl("http://{$yahoo_api_host}{$yahoo_api_path}?" . implode('&', $yql_array));
      }
      unset($http);

      $data = json_decode($data);
      $terms = array();
      if(is_object($data)) {
        $terms = $data->query->results->Result;
      }

      $terms = array_filter($terms, 'deleteEmptyElement');
      $terms = array_unique($terms);

      $bad_words = get_option('wptagpig_sug_badwords');
      $bad_words_array = array();
      if($bad_words == 'on') {
        $bad_words_list = strtolower(stripslashes(get_option('wptagpig_sug_badwords_list')));
        $bad_words_list = str_replace("\r", "", $bad_words_list);
        $bad_words_array = explode("\n", $bad_words_list);
      }
      foreach($terms as $term) {
        $tmp_term = strtolower(trim($term));
        if($bad_words != 'on' || !in_array($tmp_term, $bad_words_array)) {
          if($result != '') {
            $result .= ', ';
          }
          $result .= "$term";
        }
      }

      unset($bad_words_array);
    }

    return $result;
  }

  function autoSuggestTagsPost( $object, $post_content = '', $post_title = '', $post_excerpt = '' ) {
    $result = false;

    @set_time_limit(43200); # 12 hours

    #$existing_tags = get_the_tags($object->ID);
    $existing_tags = array();
    if($object && $object->ID) {
      $wp_existing_tags = wp_get_post_tags($object->ID);
      if(count($wp_existing_tags) > 0) {
        foreach($wp_existing_tags as $tag) {
          array_push($existing_tags, strtolower($tag->name));
        }
      }
      unset($wp_existing_tags);
    }
    /*
    if (get_the_tags($object->ID) != false) {
      return false; # Skip post with tags, if tag only empty post option is checked
    }
    */

    $tags_to_add = array();

    $post_id = false;
    if($object && $object->ID) {
      $content = $object->post_content. ' ' . $object->post_title. ' ' . $object->post_excerpt;
      $post_content = $object->post_content;
      $post_title = $object->post_title;
      $post_excerpt = $object->post_excerpt;
      $post_id = $object->ID;
    }
    else {
      $content = $post_content. ' ' . $post_title. ' ' . $post_excerpt;
      global $post;
      if($post) {
        $post_id = $post->ID;
      }
    }
    $content = trim($content);
    if (empty($content)) {
      return false;
    }

    $my_auto_suggest_list = '';

    # Preferred tags...
    if(function_exists('tagpig_pro_add_preferred_tags')) {
      tagpig_pro_add_preferred_tags($my_auto_suggest_list, $post_content, $post_title);
    }

    #if(get_option('wptagpig_sug_type') == 'all' || get_option('wptagpig_sug_type') == 'yahoo') {
    if(get_option('wptagpig_sug_type_yahoo') == 'on') {
      $yahoo_tags = autosuggestYahooTermExtraction($post_content, $post_title);
      if($yahoo_tags) {
        if($my_auto_suggest_list != '') {
          $my_auto_suggest_list .= ', ';
        }
        $my_auto_suggest_list .= $yahoo_tags;
      }
    }

    # Other sources - OpenCalais...
    if(get_option('wptagpig_sug_type_opencalais') == 'on') {
      if(function_exists('tagpig_pro_add_opencalais_tags')) {
        tagpig_pro_add_opencalais_tags($my_auto_suggest_list, $post_content, $post_title);
      }
    }

    # Other sources - Zemanta...
    if(get_option('wptagpig_sug_type_zemanta') == 'on') {
      if(function_exists('tagpig_pro_add_zemanta_tags')) {
        tagpig_pro_add_zemanta_tags($my_auto_suggest_list, $post_content, $post_title, '', $post_id);
      }
    }

    # Other sources - Alchemy...
    if(get_option('wptagpig_sug_type_alchemy') == 'on') {
      if(function_exists('tagpig_pro_add_alchemy_tags')) {
        tagpig_pro_add_alchemy_tags($my_auto_suggest_list, $post_content, $post_title);
      }
    }

    # Other sources - Local DB...
    if(get_option('wptagpig_sug_type_localdb') == 'on') {
      if(function_exists('tagpig_pro_add_localdb_tags')) {
        tagpig_pro_add_localdb_tags($my_auto_suggest_list, $post_content, $post_title);
      }
    }

    # Auto tag with suggested auto tags list
    $tags = explode(',', $my_auto_suggest_list);
    foreach((array) $tags as $tag) {
      $tag = trim($tag);
      if(is_string($tag) && !empty($tag)) {
        $tags_to_add[] = $tag;
      }
    }
    unset($tags, $tag);

    #wptagpig_log_message('tags_to_add = ' . print_r($tags_to_add, true), 'DEBUG');
    if (!empty($tags_to_add)) {
      $tags_to_add = array_filter($tags_to_add, 'deleteEmptyElement');
      $tags_to_add = array_unique($tags_to_add);
      # Remove the tags with too few words...
      if(get_option('wptagpig_sug_minwords') > 0) {
        $tags_to_add = array_filter($tags_to_add, 'deleteShortElement');
      }
      # Remove tags found in the $existing_tags array...
      if(count($existing_tags) > 0) {
        $cnt = 0;
        while($cnt < count($tags_to_add)) {
          $tag = strtolower($tags_to_add[$cnt]);
          $pos = array_search($tag, $existing_tags);
          if(!($pos === false)) {
            array_splice($tags_to_add, $cnt, 1);
          }
          else {
            $cnt++;
          }
        }
      }
      shuffle($tags_to_add);

      /*
      $tmp_count = get_option('wptagpig_sug_maxtags');
      if(isset($tmp_count) && $tmp_count != '' && $tmp_count != '0') {
        $tmp_range = split('-', $tmp_count);
        $my_count = 0;
        if(count($tmp_range) == 2) {
          $my_count = rand($tmp_range[0], $tmp_range[1]);
        }
        else {
          $my_count = $tmp_range[0];
        }
        if(isset($my_count) && $my_count != '' && $my_count > 0) {
          $my_count = $my_count - count($existing_tags);
          if($my_count < 0) {
            $my_count = 0;
          }
          $tags_to_add = array_slice($tags_to_add, 0, $my_count);
        }
      }
      */

      $counter = ((int) get_option('wptagpig_sug_tagsst')) + count($tags_to_add);
      update_option('wptagpig_sug_tagsst', $counter);

      if($object && $object->ID) {
        wp_set_object_terms($object->ID, $tags_to_add, 'post_tag', true);
        update_post_meta($object->ID, 'tagpig_processed', true);

        if ('page' == $object->post_type) {
          clean_page_cache($object->ID);
        }
        else {
          clean_post_cache($object->ID);
        }
      }

      $result = '';
      if($tags_to_add && count($tags_to_add) > 0) {
        $result = implode(',', $tags_to_add);
      }
    }
    #wptagpig_log_message('result = ' . print_r($result, true), 'DEBUG');
    return $result;
  }


  function deleteEmptyElement( &$element ) {
    $element = trim($element);
    if (!empty($element)) {
      return $element;
    }
  }

  function deleteShortElement(&$element) {
    $wrd_cnt = count(explode(" ", $element));
    if($wrd_cnt >= get_option('wptagpig_sug_minwords')) {
      return $element;
    }
  }

  function wptagpig_sug_gettags($post_id = null, $post_data = null) {
    if(get_option('wptagpig_sug_enabled', 'on') == 'on') {
      $object = get_post($post_id);
      if ($object == false || $object == null) {
        return false;
      }

      $result = autoSuggestTagsPost($object);
      if($result == true) {
        if ('page' == $object->post_type) {
          clean_page_cache($post_id);
        }
        else {
          clean_post_cache($post_id);
        }
      }
    }

    return true;
  }
  add_action('publish_post', 'wptagpig_sug_gettags', 9);


  #
  # AUTO-LINK TAGS
  ###

  function getTagsFromCurrentPosts($post_id) {
    global $wpdb;
    $sql = "SELECT t.name AS name, t.term_id AS term_id, tt.count AS count
            FROM {$wpdb->posts} AS p
            INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
            INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
            INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
            WHERE " . ($post_id ? " p.ID = {$post_id} AND " : "") . " tt.taxonomy = 'post_tag'
            GROUP BY t.term_id
            ORDER BY tt.count DESC";
    $result = $wpdb->get_results($sql);
    return $result;
  }

  function prepareAutoLinkTags($post_id = false) {
    $tags_currentposts = getTagsFromCurrentPosts($post_id);

    $auto_link_min = (int) get_option('wptagpig_lnk_mintags');
    if($auto_link_min == 0) {
      $auto_link_min = 1;
    }

    $link_tags = array();
    foreach((array)$tags_currentposts as $term) {
      if($term->count >= $auto_link_min) {
        $link_tags[$term->name] = esc_url(get_tag_link($term->term_id));
      }
    }

    if(function_exists('tagpig_pro_limit_minwords') && @tagpig_pro_api_check()) {
      tagpig_pro_limit_minwords($link_tags);
    }

    return $link_tags;
  }

  function build_tag_rel($no_follow = 1) {
    $rel = '';

    global $wp_rewrite;
    $rel .= 'tag'; // (is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?

    $no_follow = (int)$no_follow;
    if($no_follow == 1) { // No follow ?
      $rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';
    }

    if(!empty($rel)) {
      $rel = 'rel="' . $rel . '"'; // Add HTML Tag
    }

    return $rel;
  }

  function wptagpig_lnk_linktags($content = '') {
    global $post;

    if($post && $post->ID) {
      $link_tags = prepareAutoLinkTags($post->ID);
    }
    else {
      $link_tags = prepareAutoLinkTags();
    }

    // HTML Rel (tag/no-follow)
    $no_follow = get_option('wptagpig_lnk_nofollow') == 'yes' ? 1 : 0;
    $rel = build_tag_rel($no_follow);

    // only continue if the database actually returned any links
    if (isset($link_tags) && is_array($link_tags) && count($link_tags) > 0) {
      $must_tokenize = TRUE; // will perform basic tokenization
      $tokens = NULL; // two kinds of tokens: markup and text

      $case = 'i'; // (get_option('wptagpig_lnk_case') == 'no' ) ? 'i' : '';

      # NeverLink?
      if(function_exists('tagpig_pro_neverlink_tags')) {
        tagpig_pro_neverlink_tags($link_tags);
      }

      $limits = array();
      wptagpig_shuffle_assoc($link_tags);

      foreach($link_tags as $term_name => $term_link) {
        $filtered = ""; // will filter text token by token
        $match = "/\b" . preg_quote($term_name, "/") . "\b/" . $case;
        $substitute = '<a href="' . $term_link . '" ' . $rel . " >$0</a>";

        # Find HTML tags and extract them...
        $tag_pattern = "@(<[/!$]?[-a-z0-9:]+\b[^<]*?>)@is";
        $tags_found = array();
        $tags_array = array();
        $cnt = 0;
        while(preg_match($tag_pattern, $content, $tags_found)) {
          $tags_array[$cnt] = $tags_found[1];
          $content = preg_replace($tag_pattern, "%%{$cnt}%%", $content, 1);
          $cnt++;
        }

        # ...then auto link the tags...
        if(preg_match($match, $content) ) {
          $do_link = -1;
          if(function_exists('tagpig_pro_limit_maxlinks')) {
            $do_link = tagpig_pro_limit_maxlinks($limits, $term_name);
          }
          if($do_link != 0) {
            $replaced = 0;
            $isPHP51 = version_compare(PHP_VERSION, '5.1.0', '>=');
            if ($isPHP51) {
              $content = preg_replace($match, $substitute, $content, $do_link, $replaced);
            }
            else {
              $content_new = preg_replace($match, $substitute, $content, $do_link);
              $replaced = round((strlen($content_new) - strlen($content)) / (strlen($substitute) - strlen($term_name)));
              $content = $content_new;
            }

            if(function_exists('tagpig_pro_limit_adjustlimits')) {
              tagpig_pro_limit_adjustlimits($limits, $term_name, $replaced);
            }
          }
        }

        # ...and put the tags back :)
        if(count($tags_array) > 0) {
          foreach($tags_array as $key => $value) {
            $content = str_replace("%%{$key}%%", $value, $content);
          }
        }
      }
    }

    return $content;
  }
  if(get_option('wptagpig_lnk_enabled') == 'yes') {
    add_filter('the_content', 'wptagpig_lnk_linktags', 9);
  }


  #
  # POST CONTENT TAGS
  ###

  function getTagsForCurrentPost($post_id = false, $count = 0) {
    $result = false;

    if($post_id) {
      global $wpdb;
      $sql ="SELECT t.name AS name, t.term_id AS term_id, tt.count AS count, tt.taxonomy as tag_type
              FROM {$wpdb->posts} AS p
              INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
              INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
              INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id) ";
      /*
      if(get_option('wptagpig_cnt_cats') == 'yes') {
        $sql .= " WHERE tt.taxonomy IN ('post_tag', 'category') " ;
      }
      else {
      */
        $sql .= " WHERE tt.taxonomy IN ('post_tag') " ;
      /*
      }
      */
      $sql .= " AND p.ID = '{$post_id}'
                GROUP BY t.term_id
                ORDER By rand() ";
      if($count > 0) {
        $sql .= " LIMIT {$count} ";
      }
      $result = $wpdb->get_results($sql);
    }
    return $result;
  }

  function wptagpig_cnt_contenttags($content = '') {
    global $wpdb;
    global $post;

    if($post) {
      if((is_feed() && get_option('wptagpig_cnt_feeds') == 'yes') || get_option('wptagpig_cnt_posts') == 'yes') {
        $tmp_count = get_option('wptagpig_cnt_maxtags');
        if(isset($tmp_count) && $tmp_count != '' && $tmp_count != '0') {
          $tmp_range = split('-', $tmp_count);
          $my_count = 0;
          if(count($tmp_range) == 2) {
            $my_count = rand($tmp_range[0], $tmp_range[1]);
          }
          else {
            $my_count = $tmp_range[0];
          }
          if(isset($my_count) && $my_count != '' && $my_count > 0) {
            $tmp_count = $my_count;
          }
          else {
            $tmp_count = 0;
          }
        }
        else {
          $tmp_count = 0;
        }

        $my_tags = getTagsForCurrentPost($post->ID, $tmp_count);
        if(!is_array($my_tags)) {
          $my_tags = (array)$my_tags;
        }

        /*
         *  Off-Site Content
         */
        $tag_links = array();
        if(function_exists('tagpig_pro_offsite_tags')) {
          $tag_links = tagpig_pro_offsite_tags($my_tags);
        }

        $template = stripslashes(get_option('wptagpig_template'));
        $template_rows = array();
        $matches = array();
        $match_count = preg_match_all('/%Repeat_Begin%(.*?)%Repeat_End%/is', $template, $matches);
        $cnt = 0;
        while($cnt < $match_count && $cnt < count($matches[1])) {
          array_push($template_rows, $matches[1][$cnt]);
          $cnt++;
        }
        unset($matches);

        if($my_tags) {
          $tag_cnt = 0;
          $total_tags = count($my_tags);
          foreach($my_tags as $tag_id => $tag) {
            $tag_cnt++;
            if(count($template_rows) > 0) {
              foreach($template_rows as $t_row) {
                $t_row_value = $t_row;

                if($tag_links[$tag_id]) {
                  $my_tag = "<A href='" . esc_url($tag_links[$tag_id]) . "' rel='tag'>" . $tag->name . "</A>";
                }
                else {
                  if($tag->tag_type == 'category') {
                    $my_tag = "<A href='" . esc_url(get_category_link($tag->term_id)) . "' rel='tag'>" . $tag->name . "</A>";
                  }
                  else {
                    $my_tag = "<A href='" . esc_url(get_tag_link($tag->term_id)) . "' rel='tag'>" . $tag->name . "</A>";
                  }
                }
                $t_row_value = str_replace('%Tag%', $my_tag, $t_row_value);
                if($tag_cnt < $total_tags) {
                  $t_row_value = str_replace('%Separator%', get_option('wptagpig_cnt_separator'), $t_row_value);
                }
                else {
                  $t_row_value = str_replace('%Separator%', '', $t_row_value);
                }

                $template = str_replace('%Repeat_Begin%' . $t_row . '%Repeat_End%',
                                        '' . $t_row_value . '%Repeat_Begin%' . $t_row . '%Repeat_End%',
                                        $template);
              }
            }
            else {
              # no REPEATs found in the template... replace just the first...
              if($tag_links[$tag_id]) {
                $my_tag = "<A href='" . esc_url($tag_links[$tag_id]) . "' rel='tag'>" . $tag->name . "</A>";
              }
              else {
                if($tag->tag_type == 'category') {
                  $my_tag = "<A href='" . esc_url(get_category_link($tag->term_id)) . "' rel='tag'>" . $tag->name . "</A>";
                }
                else {
                  $my_tag = "<A href='" . esc_url(get_tag_link($tag->term_id)) . "' rel='tag'>" . $tag->name . "</A>";
                }
              }
              $template = str_replace('%Tag%', $my_tag, $template);
            }
          }

          # remove variables names...
          if(count($template_rows) > 0) {
            foreach($template_rows as $t_row) {
              $template = str_replace('%Repeat_Begin%' . $t_row . '%Repeat_End%', '', $template);
            }
          }

          $content .= "\n\n{$template}\n\n";
        }
        else {
          //$content .= "\n\n" . get_option('wptagpig_cnt_notags') . "\n\n";
          $content .= "\n\n";
        }
      }
    }
    else {
      //$content .= "\n\n" . get_option('wptagpig_cnt_notags') . "\n\n";
      $content .= "\n\n";
    }

    return $content;
  }
  if(get_option('wptagpig_cnt_posts') == 'yes' || get_option('wptagpig_cnt_feeds') == 'yes') {
    add_filter('the_content', 'wptagpig_cnt_contenttags', 10);
  }

}

function tagpig_load_ioncube() {
  if(!extension_loaded('ionCube Loader')){
    $__oc=strtolower(substr(php_uname(),0,3));
    $__ln='/ioncube/ioncube_loader_'.$__oc.'_'.substr(phpversion(),0,3).(($__oc=='win')?'.dll':'.so');
    $__oid=$__id=realpath(ini_get('extension_dir'));
    $__here=dirname(__FILE__);
    if((@$__id[1])==':'){
      $__id=str_replace('\\','/',substr($__id,2));
      $__here=str_replace('\\','/',substr($__here,2));
    }
    $__rd=str_repeat('/..',substr_count($__id,'/')).$__here.'/';
    $__i=strlen($__rd);
    while($__i--){
      if($__rd[$__i]=='/'){
        $__lp=substr($__rd,0,$__i).$__ln;
        if(file_exists($__oid.$__lp)){
          $__ln=$__lp;
          break;
        }
      }
    }
    if(function_exists('dl')) {
      @dl($__ln);
    }
  }
}

?>
