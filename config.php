<?php

  global $wp_version;

  global $wpdb;

  @set_time_limit(43200); # 12 hours

  $proceed = true;
  $my_product = 'tagpig';


  if($proceed) {
    $plugin_dir = basename(dirname(__FILE__)) . "/";
    $plugin_file = "tagpig.php";
    $plugin_name = $plugin_dir . $plugin_file;
    $plugin_url = get_option('siteurl') . "/wp-content/plugins/{$plugin_dir}";
    $config_url = "?page={$plugin_name}";
    $my_version = 'unknown';
    $plugins = get_plugins();
    if(is_array($plugins)) {
      $my_version = $plugins[$plugin_dir . $plugin_file]['Version'];
      $plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
      $my_version = wp_kses($my_version, $plugins_allowedtags);
      unset($plugins_allowedtags);
    }

    $pro_link = "
      <A href=\"http://www.blogpig.com/api_key?type=tagpigpro\" title=\"Upgrade to TagPiG PRO\" target=\"_blank\" >
        <IMG alt=\"pro\" src=\"{$plugin_url}images/pro_icon.png\" style=\"vertical-align:middle\" />
      </A>
      ";

    $options = wptagpig_default_options();
    if(function_exists('tagpig_pro_add_options')) {
      tagpig_pro_add_options($options);
    }

  ?>

    <?php
    if (isset($_POST['wptagpig_submit'])) {
      if (function_exists('current_user_can') && !current_user_can('manage_options'))
        die(__('What are you doing here?!'));

      foreach($options as $key => $default) {
        if(isset($_POST[$key])) {
          update_option($key, $_POST[$key]);
        }
        else {
          if($default == 'on') {
            update_option($key, 'off');
          }
          else if($default == 'yes') {
            update_option($key, 'no');
          }
          else {
            if(!in_array($key, array('wptagpig_emb_prefix', 'wptagpig_emb_sufix'))) {
              update_option($key, '');
            }
          }
        }
      }
    ?>

    <div id='message' class='updated fade' style='padding:4px;'><strong><?php _e(' Options saved.') ?></strong></div>

    <?php
    }

    # Get the options...
    $my_options = array();
    foreach($options as $key => $default) {
      $value = get_option($key);
      if(isset($value) && $value != '') {
        $my_options[$key] = $value;
      }
      else {
        $my_options[$key] = $default;
      }
    }

    ?>

    <div class="wrap">
      <div id="icon-plugins" class="icon32"><br /></div>
      <?php
        if(function_exists('tagpig_pro_show_title') && @tagpig_pro_api_check()) {
          tagpig_pro_show_title();
        }
        else {
          ?>
          <h2>TagPiG</h2>
          <?php
        }
      ?>
      <form action="<?php echo $config_url; ?>" method="post" id="wpbabelpig-conf" enctype="multipart/form-data">

        <div id="poststuff" class="metabox-holder has-right-sidebar">

          <div id="side-info-column" class="inner-sidebar">

            <div id='side-sortables' class='meta-box-sortables'>

              <?php
                if(!class_exists('WP_Http')) {
                  @include_once(ABSPATH . WPINC. '/class-http.php');
                }
                $http = new WP_Http;
              ?>

              <div id="pagesubmitdiv" class="postbox " >
                <h3 class='hndle' style='cursor:default;'>
                  <span>Status</span>
                </h3>

                <div class="inside">

                  <div class="misc-pub-section misc-pub-section-first">
                    <label for="post_status">Your TagPiG Version:</label>
                    <b><span id="post-status-display" style="vertical-align:middle;"><?php echo $my_version; ?></span></b>
                  </div>

                  <div class="misc-pub-section">
                    <label for="post_status">Current TagPiG Version:</label>
                    <b><span id="post-status-display" style="vertical-align:middle;">
                    <?php
                      if($http) {
                        $reply = $http->request('http://www.blogpig.com/includes/version.php?p=' . $my_product);
                        echo ($reply && is_array($reply) ? $reply['body'] : '');
                      }
                    ?>
                    </span></b>
                  </div>

                  <div class="misc-pub-section ">
                    <label for="post_status">Pro File:</label>
                      <b><span id="post-status-display" style="vertical-align:middle;"><?php
                       echo file_exists(ABSPATH . "wp-content/plugins/{$plugin_dir}tagpig_pro.php") ? "Found" : "Not Found";
                      ?></span></b>
                  </div>
                  <?php
                  tagpig_load_ioncube();
                  ?>
                  <div class="misc-pub-section misc-pub-section-last">
                    <label for="post_status">ionCube Loaders:</label>
                    <b><span id="post-status-display" style="vertical-align:middle;"><?php echo extension_loaded('ionCube Loader') ? "" : "Not"; ?> Found</span></b>
                    &nbsp; [ <a href="<?php echo get_option('siteurl') . "/wp-content/plugins/{$plugin_dir}ioncube/"; ?>">more info</a> ]
                  </div>

                </div> <!--- class="inside" --->
              </div> <!--- class="postbox " --->

              <div id="pageparentdiv" class="postbox " >
                <h3 class='hndle' style='cursor:default;'>
                  <span>BlogPiG Members</span>
                </h3>

                <div class="inside">
                  <p>
                    <ul>
                      <li><a href="http://blogpig.com/" target="_blank">BlogPiG Home</a></li>
                      <?php
                        if($http) {
                          $reply = $http->request('http://www.blogpig.com/includes/pages.php?xml=1');
                          if($reply && is_array($reply)) {
                            $pages = array();
                            $pages_count = preg_match_all('/<title>(.*?)<\/title>.*?<link>(.*?)<\/link>/is', $reply['body'], $pages);
                            if($pages_count > 0) {
                              $idx = 0;
                              while($idx < count($pages[0])) {
                                echo '<li><a href="' . $pages[2][$idx] . '" target="_blank">BlogPiG ' . $pages[1][$idx]. '</a></li>';
                                $idx++;
                              }
                            }
                          }

                        }
                      ?>
                    </ul>
                  </p>
                </div> <!--- class="inside" --->
              </div> <!--- class="postbox " --->

              <div id="pageparentdiv" class="postbox " >
                <h3 class='hndle' style='cursor:default;'>
                  <span>BlogPiG News</span>
                </h3>

                <div class="inside">
                  <p>
                    <ul>
                      <?php
                        if($http) {
                          $reply = $http->request('http://feeds.feedburner.com/blogpigcom');
                          if($reply && is_array($reply)) {
                            $news = array();
                            $news_count = preg_match_all('/<title>(.*?)<\/title>.*?<link>(.*?)<\/link>/is', $reply['body'], $news);
                            if($news_count > 1) {
                              $idx = 1;
                              while($idx < count($news[0])) {
                                echo '<li><a href="' . $news[2][$idx] . '" target="_blank">' . $news[1][$idx]. '</a></li>';
                                $idx++;
                              }
                            }
                          }

                        }
                      ?>
                    </ul>
                  </p>
                </div> <!--- class="inside" --->
              </div> <!--- class="postbox " --->

              <div id="pageparentdiv" class="postbox " >
                <h3 class='hndle' style='cursor:default;'>
                  <span>BlogPiG Software</span>
                </h3>
                <div class="inside">
                  <p>
                    <ul>
                      <?php
                        if($http) {
                          $reply = $http->request('http://blogpig.com/includes/products.php?xml=1');
                          if($reply && is_array($reply)) {
                            $products = array();
                            $products_count = preg_match_all('/<title>(.*?)<\/title>.*?<link>(.*?)<\/link>/is', $reply['body'], $products);
                            if($products_count > 0) {
                              $idx = 0;
                              while($idx < count($products[0])) {
                                echo '<li><a href="' . $products[2][$idx] . '" target="_blank">' . $products[1][$idx]. '</a></li>';
                                $idx++;
                              }
                            }
                          }

                        }
                      ?>
                    </ul>
                  </p>
                </div> <!--- class="inside" --->
              </div> <!--- class="postbox " --->

              <?php
                unset($http);
              ?>

            </div> <!---  class='meta-box-sortables' --->

          </div> <!--- class="inner-sidebar" --->



          <div id="post-body" class="has-sidebar">

            <div id="post-body-content" class="has-sidebar-content">

              <div id='normal-sortables' class='meta-box-sortables'>

                <!--- Tooltips --->
                <link type="text/css" media="screen" rel="stylesheet" href="<?php echo $plugin_url; ?>js/tooltips.css" />
                <!--- Colorboxes --->
                <link type="text/css" media="screen" rel="stylesheet" href="<?php echo $plugin_url; ?>js/colorbox.css" />
                <script type="text/javascript" src="<?php echo $plugin_url; ?>js/jquery.colorbox.js"></script>
                <script type="text/javascript">
                  jQuery(document).ready(function(){
                    jQuery(".colorboxtips").colorbox({innerWidth:"853px", innerHeight:"510px", iframe:true});
                  });
                </script>

                <!--- Adding new reg functions --->
                <?php tagpig_api_show_field($plugin_dir); ?>

                <!--- Content... --->
                <div id="pagecommentstatusdiv" class="postbox " >
                  <h3 class='hndle' style='cursor:default;'>
                    <span>Read Me</span>
                  </h3>
                  <div class="inside">
                    <p>
                      <TABLE width="100%" style="margin-top:12px;">
                        <TR valign="top">
                          <TD width="100%">
                            <P>
                              Congratulations on activating your TagPiG plugin from BlogPiG.
                            </P>
                            <P>
                              <?php
                                if(!trim(get_option('blogpig_api_key'))) {
                                ?>
                                  Don't forget to enter your BlogPiG API Key in the box above. You can get it from your BlogPiG members area <a href="http://blogpig.com/members/">here</a>.
                                <?php
                                }
                                else if(!function_exists('tagpig_pro_api_check') || !@tagpig_pro_api_check()) {
                                ?>
                                  The features marked with <?php echo $pro_link; ?> icons are only available to TagPiG Pro license holders. You can instantly upgrade to a TagPiG Pro license <a href="http://blogpig.com/products/tagpig">here</a>.
                                <?php
                                }
                                ?>
                            </P>
                            <!---
                            <P>
                              You can view a short tutorial video for each section by clicking on the <IMG src="<?php echo $plugin_url . '/images/camera.png'; ?>" style="vertical-align:middle; " />  icon.
                            </P>
                            --->
                            <P>
                              You can <!---also---> mouseover the <IMG src="<?php echo $plugin_url . '/images/tooltip.png'; ?>" style="vertical-align:middle; " /> icons for a short summary of each individual feature.
                            </P>
                            <P>
                              If you have any other questions just head on over to our help desk <a href="http://blogpig.com/help/">here</a> and we'll be more than happy to help.
                            </P>
                            <BR />
                          </TD>
                        </TR>
                      </TABLE>
                    </p>


                  </div>
                </div>

                <!--- Content... --->
                <div id="pagecommentstatusdiv" class="postbox " >
                  <h3 class='hndle' style='cursor:default;'>
                    <span style='vertical-align: top;'>Auto Tag Sources</span><?php tagpig_show_header_link('tagpig-auto-tag-sources', $plugin_dir); ?>
                  </h3>
                  <div class="inside">

                    <p>

                      <TABLE width="100%" style="margin-top:12px;">
                        <TR valign="top">
                          <TD width="40%">
                            Auto Tag All New Posts: <?php tagpig_show_tooltip('auto tag all new posts', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="checkbox" name="wptagpig_sug_enabled" id="wptagpig_sug_enabled" value="on" <?php if($my_options['wptagpig_sug_enabled'] == 'on') echo "checked" ?> >
                            <P style="font-size:80%; ">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        <TR valign="top">
                          <TD width="40%">
                            Tag Sources: <?php tagpig_show_tooltip('tag sources', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="checkbox" name="wptagpig_sug_type_yahoo" id="wptagpig_sug_type_yahoo" value="on" <?php if($my_options['wptagpig_sug_type_yahoo'] == 'on') echo "checked" ?> >
                              <LABEL for="wptagpig_sug_type_yahoo">Yahoo</LABEL>
                              <P style="font-size:80%; margin-left:20px; margin-top:0px;">
                                AppID:
                                <INPUT name="wptagpig_sug_yahookey" id="wptagpig_sug_yahookey" type="text" size="15" value="<?php echo $my_options['wptagpig_sug_yahookey']; ?>" title="<?php echo $my_options['wptagpig_sug_yahookey']; ?>" />
                                (get one <A href="http://developer.yahoo.com/wsregapp/" target="_blank">here</A>)
                              </P>
                            <BR />
                            <?php
                              if(function_exists('tagpig_pro_show_opencalais') && @tagpig_pro_api_check()) {
                                tagpig_pro_show_opencalais();
                              }
                              else {
                                ?>
                                <INPUT disabled type="checkbox" name="wptagpig_sug_type_opencalais" id="wptagpig_sug_type_opencalais" value="on" <?php if($my_options['wptagpig_sug_type_opencalais'] == 'on') echo "checked" ?> >
                                  <LABEL disabled for="wptagpig_sug_type_opencalais">OpenCalais</LABEL>
                                  <?php echo $pro_link; ?>
                                <P style="font-size:80%; margin-left:20px; margin-top:0px;">
                                  API Key:
                                  <INPUT disabled name="wptagpig_sug_ockey" id="wptagpig_sug_ockey" type="text" size="15" value="<?php echo $my_options['wptagpig_sug_ockey']; ?>" title="<?php echo $my_options['wptagpig_sug_ockey']; ?>" />
                                  (get one <A href="http://www.opencalais.com/APIkey" target="_blank">here</A>)
                                  <BR />
                                  <!---
                                  <TABLE style="font-size:80%; margin-left:20px; margin-top:0px;" width="100%">
                                    <TR>
                                      <TD width="25%" style="font-size:100%; vertical-align:top; ">
                                        Also Use as Tags:
                                      </TD>
                                      <TD width="75%"style="font-size:100%; vertical-align:top; ">
                                        <INPUT disabled name="wptagpig_sug_ocsocial" id="wptagpig_sug_ocsocial" type="checkbox"  value="on" />
                                          <LABEL for="wptagpig_sug_ocsocial">Social Tags</LABEL>
                                        <BR />
                                        <INPUT disabled name="wptagpig_sug_octopics" id="wptagpig_sug_octopics" type="checkbox"  value="on" />
                                          <LABEL for="wptagpig_sug_octopics">Topics</LABEL>
                                      </TD>
                                    </TR>
                                  </TABLE>
                                  --->
                                </P>
                                <?php
                              }
                            ?>
                            <BR />
                            <?php
                              if(function_exists('tagpig_pro_show_zemanta') && @tagpig_pro_api_check()) {
                                tagpig_pro_show_zemanta();
                              }
                              else {
                                ?>
                                <INPUT disabled type="checkbox" name="wptagpig_sug_type_zemanta" id="wptagpig_sug_type_zemanta" value="on" <?php if($my_options['wptagpig_sug_type_zemanta'] == 'on') echo "checked" ?> >
                                  <LABEL disabled for="wptagpig_sug_type_zemanta">Zemanta</LABEL>
                                  <?php echo $pro_link; ?>
                                <P style="font-size:80%; margin-left:20px; margin-top:0px;">
                                  API Key:
                                  <INPUT disabled name="wptagpig_sug_zemantakey" id="wptagpig_sug_zemantakey" type="text" size="15" value="<?php echo $my_options['wptagpig_sug_zemantakey']; ?>" title="<?php echo $my_options['wptagpig_sug_zemantakey']; ?>" />
                                  (get one <A href="http://developer.zemanta.com/apps/register" target="_blank">here</A>)
                                  <!---
                                  <BR />
                                  <INPUT disabled type="checkbox" name="wptagpig_sug_zemanta_no_signature" id="wptagpig_sug_zemanta_no_signature" value="on" >
                                    remove signature on publish
                                  --->
                                </P>
                                <?php
                              }
                            ?>
                            <BR />
                            <?php
                              if(function_exists('tagpig_pro_show_alchemy') && @tagpig_pro_api_check()) {
                                tagpig_pro_show_alchemy();
                              }
                              else {
                                ?>
                                <INPUT disabled type="checkbox" name="wptagpig_sug_type_alchemy" id="wptagpig_sug_type_alchemy" value="on" <?php if($my_options['wptagpig_sug_type_alchemy'] == 'on') echo "checked" ?> >
                                  <LABEL disabled for="wptagpig_sug_type_alchemy">Alchemy</LABEL>
                                  <?php echo $pro_link; ?>
                                <P style="font-size:80%; margin-left:20px; margin-top:0px;">
                                  API Key:
                                  <INPUT disabled name="wptagpig_sug_alchemykey" id="wptagpig_sug_alchemykey" type="text" size="15" value="<?php echo $my_options['wptagpig_sug_alchemykey']; ?>" title="<?php echo $my_options['wptagpig_sug_alchemykey']; ?>" />
                                  (get one <A href="http://www.alchemyapi.com/api/register.html" target="_blank">here</A>)
                                </P>
                                <?php
                              }
                            ?>
                            <BR />
                            <?php
                              if(function_exists('tagpig_pro_show_localdb') && @tagpig_pro_api_check()) {
                                tagpig_pro_show_localdb();
                              }
                              else {
                                ?>
                                <INPUT disabled type="checkbox" name="wptagpig_sug_type_localdb" id="wptagpig_sug_type_localdb" value="on" <?php if($my_options['wptagpig_sug_type_localdb'] == 'on') echo "checked" ?> >
                                  <LABEL disabled for="wptagpig_sug_type_localdb">Local DB</LABEL>
                                  <?php echo $pro_link; ?>
                                <P style="font-size:80%; margin-left:20px; margin-top:0px;">
                                </P>
                                <BR />
                                <?php
                              }
                            ?>
                            <P style="font-size:80%">
                            </P>
                          </TD>
                        </TR>
                        <TR valign="top">
                          <TD colspan="2" align="right">
                            <input type="submit" class="button" name="wptagpig_submit" value="Update &raquo;" />
                          </TD>
                        </TR>

                      </TABLE>
                    </p>

                  </div>
                </div>

                <!--- Content... --->
                <div id="pagecommentstatusdiv" class="postbox " >
                  <h3 class='hndle' style='cursor:default;'>
                    <span style='vertical-align: top;'>Filter Tags</span><?php tagpig_show_header_link('tagpig-filter-tags', $plugin_dir); ?>
                  </h3>
                  <div class="inside">

                    <p>
                      <TABLE width="100%" style="margin-top:12px;">
                        <!---
                        <TR valign="top">
                          <TD width="40%">
                            Maximum Tags: <?php tagpig_show_tooltip('maximum tags', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT name="wptagpig_sug_maxtags" id="wptagpig_sug_maxtags" type="text" size="4" value="<?php echo $my_options['wptagpig_sug_maxtags']; ?>" />
                            <P style="font-size:80%; margin-top:0px;">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        --->
                        <TR valign="top">
                          <TD width="40%">
                            Minimum Words Per Tag: <?php tagpig_show_tooltip('minimum words per tag', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT name="wptagpig_sug_minwords" id="wptagpig_sug_minwords" type="text" size="4" value="<?php echo $my_options['wptagpig_sug_minwords']; ?>" />
                            <P style="font-size:80%; margin-top:0px;">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        <TR valign="top">
                          <TD width="40%">
                            Word Filter: <?php tagpig_show_tooltip('word filter', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="radio" id="wptagpig_sug_badwords" name="wptagpig_sug_badwords" value="on" <?php if($my_options['wptagpig_sug_badwords'] == 'on') { echo "checked"; } ?> ></INPUT>
                              <LABEL for="wptagpig_sug_badwords">on</LABEL>
                            <INPUT type="radio" id="wptagpig_sug_badwords" name="wptagpig_sug_badwords" value="off" <?php if($my_options['wptagpig_sug_badwords'] == 'off') { echo "checked"; } ?> ></INPUT>
                              <LABEL for="wptagpig_sug_badwords">off</LABEL>
                            <BR />
                            <BR />
                            filter words: <BR />
                            <TEXTAREA id="wptagpig_sug_badwords_list" name="wptagpig_sug_badwords_list" rows="10" cols="24"><?php echo $my_options['wptagpig_sug_badwords_list']; ?></TEXTAREA>
                            <P style="font-size:80%; margin-top:0px;">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        <?php
                          if(function_exists('tagpig_pro_show_preferred') && @tagpig_pro_api_check()) {
                            tagpig_pro_show_preferred($plugin_dir);
                          }
                          else {
                            ?>
                            <TR valign="top">
                              <TD width="40%">
                                Preferred Tags: <?php tagpig_show_tooltip('preferred tags', $plugin_dir); ?> <BR />
                              </TD>
                              <TD width="60%">
                                <INPUT disabled type="radio" id="wptagpig_sug_preferred" name="wptagpig_sug_preferred" value="on" <?php if($my_options['wptagpig_sug_preferred'] == 'on') { echo "checked"; } ?> />
                                  <LABEL for="wptagpig_sug_preferred">on</LABEL>
                                <INPUT disabled type="radio" id="wptagpig_sug_preferred" name="wptagpig_sug_preferred" value="off" <?php if($my_options['wptagpig_sug_preferred'] == 'off') { echo "checked"; } ?> />
                                  <LABEL for="wptagpig_sug_preferred">off</LABEL>
                                  <?php echo $pro_link; ?>
                                <BR />
                                <BR />
                                list of preferred tags: <BR />
                                <TEXTAREA disabled id="wptagpig_sug_preferred_list" name="wptagpig_sug_preferred_list" rows="10" cols="24"><?php echo $my_options['wptagpig_sug_preferred_list']; ?></TEXTAREA>
                                <P style="font-size:80%; margin-top:0px;">
                                </P>
                                <BR />
                              </TD>
                            </TR>
                            <?php
                          }
                        ?>
                        <TR valign="top">
                          <TD colspan="2" align="right">
                            <input type="submit" class="button" name="wptagpig_submit" value="Update &raquo;" />
                          </TD>
                        </TR>

                      </TABLE>
                    </p>

                  </div>
                </div>

                <!--- Content... --->
                <div id="pagecommentstatusdiv" class="postbox " >
                  <h3 class='hndle' style='cursor:default;'>
                    <span style='vertical-align: top;'>Auto Tag Linking</span><?php tagpig_show_header_link('tagpig-auto-tag-linking', $plugin_dir); ?>
                  </h3>
                  <div class="inside">

                    <p>
                      <TABLE width="100%" style="margin-top:12px;">
                        <TR valign="top">
                          <TD width="40%">
                            Auto Link Tags: <?php tagpig_show_tooltip('auto link tags', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="checkbox" name="wptagpig_lnk_enabled" id="wptagpig_lnk_enabled" value="yes" <?php echo 'yes' == $my_options['wptagpig_lnk_enabled'] ? "checked" : ""; ?> >
                            <P style="font-size:80%">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        <!---
                        <TR valign="top">
                          <TD width="40%">
                            Match Tag Case: <?php tagpig_show_tooltip('match tag case', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="checkbox" name="wptagpig_lnk_case" id="wptagpig_lnk_case" value="yes" <?php echo 'yes' == $my_options['wptagpig_lnk_case'] ? "checked" : ""; ?> >
                            <P style="font-size:80%">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        --->
                        <TR valign="top">
                          <TD width="40%">
                            No-Follow All Tags: <?php tagpig_show_tooltip('no-follow all tags', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="checkbox" name="wptagpig_lnk_nofollow" id="wptagpig_lnk_nofollow" value="yes" <?php echo 'yes' == $my_options['wptagpig_lnk_nofollow'] ? "checked" : ""; ?> >
                            <P style="font-size:80%">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        <TR valign="top">
                          <TD width="40%">
                            Minimum Tag Frequency: <?php tagpig_show_tooltip('minimum tag frequency', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT name="wptagpig_lnk_mintags" id="wptagpig_lnk_mintags" type="text" size="4" value="<?php echo $my_options['wptagpig_lnk_mintags']; ?>" />
                            <P style="font-size:80%">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>

                        <!--- Adding the PRO options --->
                        <?php
                          if(function_exists('tagpig_pro_show_minwords') && @tagpig_pro_api_check()) {
                            tagpig_pro_show_minwords($plugin_dir);
                          }
                          else {
                        ?>
                          <TR valign="top">
                            <TD width="40%">
                              Minimum Words per Linked Tag: <?php tagpig_show_tooltip('minimum words per linked tag', $plugin_dir); ?> <BR />
                            </TD>
                            <TD width="60%">
                              <INPUT disabled name="wptagpig_lnk_minwords" id="wptagpig_lnk_minwords" type="text" size="4" value="<?php echo $my_options['wptagpig_lnk_minwords']; ?>" />
                              <?php echo $pro_link; ?>
                              <P style="font-size:80%">
                              </P>
                              <BR />
                              <BR />
                            </TD>
                          </TR>
                        <?php
                          }
                        ?>

                        <?php
                          if(function_exists('tagpig_pro_show_maxlinks') && @tagpig_pro_api_check()) {
                            tagpig_pro_show_maxlinks($plugin_dir);
                          }
                          else {
                        ?>
                          <TR valign="top">
                            <TD width="40%">
                              Maximum Links per Tag: <?php tagpig_show_tooltip('maximum links per tag', $plugin_dir); ?> <BR />
                            </TD>
                            <TD width="60%">
                              <INPUT disabled name="wptagpig_lnk_maxlinkspertag" id="wptagpig_lnk_maxlinkspertag" type="text" size="4" value="<?php echo $my_options['wptagpig_lnk_maxlinkspertag']; ?>" />
                              <?php echo $pro_link; ?>
                              <P style="font-size:80%">
                              </P>
                              <BR />
                              <BR />
                            </TD>
                          </TR>
                          <TR valign="top">
                            <TD width="40%">
                              Maximum Links per Post: <?php tagpig_show_tooltip('maximum links per post', $plugin_dir); ?> <BR />
                            </TD>
                            <TD width="60%">
                              <INPUT disabled name="wptagpig_lnk_maxlinksperpost" id="wptagpig_lnk_maxlinksperpost" type="text" size="4" value="<?php echo $my_options['wptagpig_lnk_maxlinksperpost']; ?>" />
                              <?php echo $pro_link; ?>
                              <P style="font-size:80%">
                              </P>
                              <BR />
                              <BR />
                            </TD>
                          </TR>
                        <?php
                          }
                        ?>

                        <?php
                          if(function_exists('tagpig_pro_show_neverlink') && @tagpig_pro_api_check()) {
                            tagpig_pro_show_neverlink($plugin_dir);
                          }
                          else {
                            ?>
                            <TR valign="top">
                              <TD width="40%">
                                Never Link These Words: <?php tagpig_show_tooltip('never link these words', $plugin_dir); ?> <BR />
                              </TD>
                              <TD width="60%">
                                <INPUT disabled type="radio" id="wptagpig_lnk_neverlink" name="wptagpig_lnk_neverlink" value="on" <?php if($my_options['wptagpig_lnk_neverlink'] == 'on') { echo "checked"; } ?> />
                                  <LABEL for="wptagpig_lnk_neverlink">on</LABEL>
                                <INPUT disabled type="radio" id="wptagpig_lnk_neverlink" name="wptagpig_lnk_neverlink" value="off" <?php if($my_options['wptagpig_lnk_neverlink'] == 'off') { echo "checked"; } ?> />
                                  <LABEL for="wptagpig_lnk_neverlink">off</LABEL>
                                  <?php echo $pro_link; ?>
                                <BR />
                                <BR />
                                list of words: <BR />
                                <TEXTAREA disabled id="wptagpig_lnk_neverlink_list" name="wptagpig_lnk_neverlink_list" rows="10" cols="24"><?php echo $my_options['wptagpig_lnk_neverlink_list']; ?></TEXTAREA>
                                <P style="font-size:80%; margin-top:0px;">
                                  One word/phrase per line
                                </P>
                                <BR />
                              </TD>
                            </TR>
                            <?php
                          }
                        ?>

                        <TR valign="top">
                          <TD colspan="2" align="right">
                            <input type="submit" class="button" name="wptagpig_submit" value="Update &raquo;" />
                          </TD>
                        </TR>
                      </TABLE>
                    </p>

                  </div>
                </div>

                <!--- Content... --->
                <div id="pagecommentstatusdiv" class="postbox " >
                  <h3 class='hndle' style='cursor:default;'>
                    <span style='vertical-align: top;'>Publish Tags</span><?php tagpig_show_header_link('tagpig-publish-tags', $plugin_dir); ?>
                  </h3>
                  <div class="inside">

                    <p>
                      <TABLE width="100%" style="margin-top:12px;">
                        <TR valign="top">
                          <TD width="40%">
                            Publish in Posts: <?php tagpig_show_tooltip('publish in posts', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="checkbox" name="wptagpig_cnt_posts" id="wptagpig_cnt_posts" value="yes" <?php if($my_options['wptagpig_cnt_posts'] == 'yes') echo "checked" ?> >
                            <P style="font-size:80%">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>

                        <?php
                          if(function_exists('tagpig_pro_show_suppress') && @tagpig_pro_api_check()) {
                            tagpig_pro_show_suppress($plugin_dir);
                          }
                          else {
                            ?>
                            <TR valign="top">
                              <TD width="40%">
                                Suppress Theme Tags: <?php tagpig_show_tooltip('suppress theme tags', $plugin_dir); ?> <BR />
                              </TD>
                              <TD width="60%">
                                <INPUT disabled type="checkbox" name="wptagpig_cnt_suppress" id="wptagpig_cnt_suppress" value="yes" <?php if($my_options['wptagpig_cnt_suppress'] == 'yes') echo "checked" ?> >
                                <?php echo $pro_link; ?>
                                <P style="font-size:80%">
                                </P>
                                <BR />
                                <BR />
                              </TD>
                            </TR>
                            <?php
                          }
                        ?>

                        <TR valign="top">
                          <TD width="40%">
                            Publish in Feeds: <?php tagpig_show_tooltip('publish in feeds', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="checkbox" name="wptagpig_cnt_feeds" id="wptagpig_cnt_feeds" value="yes" <?php if($my_options['wptagpig_cnt_feeds'] == 'yes') echo "checked" ?> >
                            <P style="font-size:80%">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        <!---
                        <TR valign="top">
                          <TD width="40%">
                            Categories to Tags: <?php tagpig_show_tooltip('categories to tags', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT type="checkbox" name="wptagpig_cnt_cats" id="wptagpig_cnt_cats" value="yes" <?php echo 'yes' == $my_options['wptagpig_cnt_cats'] ? "checked" : ""; ?> >
                            <P style="font-size:80%">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        --->
                        <TR valign="top">
                          <TD width="40%">
                            Tag Template: <?php tagpig_show_tooltip('tag template', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <TEXTAREA id="wptagpig_template" name="wptagpig_template" cols="24" rows="15"><?php echo stripslashes($my_options['wptagpig_template']); ?></TEXTAREA>
                            <P style="font-size:80%; margin-top:0px;">
                              Allowed variables:<BR />
                              <STRONG>%Repeat_Begin%</STRONG> - start of the loop;<BR />
                              <STRONG>%Repeat_End%</STRONG> - end of the loop;<BR />
                              <STRONG>%Tag%</STRONG> - the tag itself;<BR />
                              <STRONG>%Separator%</STRONG> - tag separator:
                                <INPUT name="wptagpig_cnt_separator" id="wptagpig_cnt_separator" type="text" size="4" value="<?php echo $my_options['wptagpig_cnt_separator']; ?>" /><BR />
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        <!---
                        <TR valign="top">
                          <TD width="40%">
                            No Tags String: <?php tagpig_show_tooltip('no tags string', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT name="wptagpig_cnt_notags" id="wptagpig_cnt_notags" type="text" size="15" value="<?php echo $my_options['wptagpig_cnt_notags']; ?>" />
                            <P style="font-size:80%">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        --->
                        <TR valign="top">
                          <TD width="40%">
                            Maximum Tags per Post: <?php tagpig_show_tooltip('maximum tags per post', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width="60%">
                            <INPUT name="wptagpig_cnt_maxtags" id="wptagpig_cnt_maxtags" type="text" size="4" value="<?php echo $my_options['wptagpig_cnt_maxtags']; ?>" />
                            <P style="font-size:80%; margin-top:0px;">
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>

                        <?php
                          if(function_exists('tagpig_pro_show_offsite') && @tagpig_pro_api_check()) {
                            tagpig_pro_show_offsite($plugin_dir);
                          }
                          else {
                            ?>
                            <TR valign="top">
                              <TD width="40%">
                                Link to Off-Site Content: <?php tagpig_show_tooltip('link to off-site content', $plugin_dir); ?> <BR />
                              </TD>
                              <TD width="60%">
                                Link
                                <INPUT disabled type="text" size="3" id="wptagpig_cnt_offsite_technorati" name="wptagpig_cnt_offsite_technorati" value="<?php echo $my_options['wptagpig_cnt_offsite_technorati']; ?>" />%
                                  of tags to Technorati
                                  <?php echo $pro_link; ?>
                                <BR />
                                <!---
                                <INPUT disabled type="text" size="3" id="wptagpig_cnt_offsite_delicious" name="wptagpig_cnt_offsite_delicious" value="<?php echo $my_options['wptagpig_cnt_offsite_delicious']; ?>" />%
                                  <LABEL for="wptagpig_lnk_neverlink">to Delicious</LABEL>
                                <BR />
                                <INPUT disabled type="text" size="3" id="wptagpig_cnt_offsite_furl" name="wptagpig_cnt_offsite_furl" value="<?php echo $my_options['wptagpig_cnt_offsite_furl']; ?>" />%
                                  <LABEL for="wptagpig_lnk_neverlink">to Diigo</LABEL>
                                <BR />
                                <INPUT disabled type="text" size="3" id="wptagpig_cnt_offsite_flickr" name="wptagpig_cnt_offsite_flickr" value="<?php echo $my_options['wptagpig_cnt_offsite_flickr']; ?>" />%
                                  <LABEL for="wptagpig_lnk_neverlink">to Flickr</LABEL>
                                <BR />
                                <P style="font-size:80%; margin-top:0px;">
                                </P>
                                <BR />
                                --->
                              </TD>
                            </TR>
                            <?php
                          }
                        ?>


                        <TR valign="top">
                          <TD colspan="2" align="right">
                            <input type="submit" class="button" name="wptagpig_submit" value="Update &raquo;" />
                          </TD>
                        </TR>
                      </TABLE>
                    </p>

                  </div>
                </div>

                <!--- Adding the PRO options --->
                <?php
                  # Tag Exisitng Posts...
                  if(function_exists('tagpig_pro_show_box') && @tagpig_pro_api_check()) {
                    tagpig_pro_show_box($plugin_dir);
                  }
                  else {
                ?>
                <div class='postbox ' style='border-color: #298cba !important; ' >
                  <h3 class='hndle' style='cursor:default; border-color: #298cba !important; background: #21759B url(../images/button-grad.png) repeat-x scroll left top; color: #FFF !important; font-weight: bold;' >
                    <span style='vertical-align: top;'>Auto Tag Existing Posts</span><?php tagpig_show_header_link('tagpig-auto-tag-existing-posts', $plugin_dir); ?>
                  </h3>
                  <div class='inside'>

                    <p>
                      <TABLE width='100%' style='margin-top:12px;'>
                        <TR>
                          <TD width='20%' valign='top'>
                            Status: <?php tagpig_show_tooltip('status', $plugin_dir); ?>
                          </TD>
                          <TD width='80%'>
                            <STRONG>DISABLED</STRONG> <?php echo $pro_link; ?>
                            <P style='font-size:80%; margin-top:8px;'>
                            </P>
                          </TD>
                        </TR>
                        <TR valign='top'>
                          <TD width='20%'>
                            <BR />
                          </TD>
                          <TD width='80%'>
                            <INPUT disabled type='checkbox' name='wptagpig_pro_maxtags' id='wptagpig_pro_maxtags' value='on' >
                              <LABEL for='wptagpig_pro_maxtags'>Only Auto Tag Posts with Less Than</LABEL>
                              <INPUT disabled name='wptagpig_pro_maxtags_value' id='wptagpig_pro_maxtags_value' type='text' size='3' value='' />
                              Tags
                            <BR />
                            <INPUT disabled type='checkbox' name='wptagpig_pro_allposts' id='wptagpig_pro_allposts' value='on' >
                              <LABEL for='wptagpig_pro_allposts'>Auto Tag Previously Auto Tagged Posts</LABEL>
                            <P style='font-size:80%'>
                            </P>
                            <BR />
                            <BR />
                          </TD>
                        </TR>
                        <TR valign='top'>
                          <TD width='20%'>
                            Delay: <?php tagpig_show_tooltip('delay', $plugin_dir); ?> <BR />
                          </TD>
                          <TD width='80%'>
                            <INPUT disabled name='wptagpig_pro_delay' id='wptagpig_pro_delay' type='text' size='3' value='' />
                              seconds
                            <P style='font-size:80%'>
                            </P>
                            <BR />
                          </TD>
                        </TR>
                        <TR>
                          <TD width='20%'>
                            &nbsp;
                          </TD>
                          <TD width='80%' align='right'>
                            <input type='submit' class='button' disabled name='wptagpig_submit' value='Start &raquo;' />
                            <input type='submit' class='button' disabled name='wptagpig_submit' value='Stop &raquo;' />
                          </TD>
                        </TR>
                      </TABLE>
                    </p>

                  </div> <!--- class='inside' --->
                </div> <!--- class='postbox ' --->
                <?php
                  }
                ?>

                <?php
                  # Clone Settings...
                  if(function_exists('tagpig_pro_show_clone') && @tagpig_pro_api_check()) {
                    tagpig_pro_show_clone($plugin_dir);
                  }
                  else {
                ?>
                <div class='postbox ' style='border-color: #298cba !important; ' >
                  <h3 class='hndle' style='cursor:default; border-color: #298cba !important; background: #21759B url(../images/button-grad.png) repeat-x scroll left top; color: #FFF !important; font-weight: bold;' >
                    <span style='vertical-align: top;'>Clone</span><?php tagpig_show_header_link('blogpig-clone-settings', $plugin_dir, '', '#AAAAAA'); ?>
                  </h3>
                  <div class='inside'>

                    <p>
                      <TABLE width='100%' style='margin-top:12px;'>
                        <TR>
                          <TD width='40%' valign='top'>
                            Initial Settings: <?php tagpig_show_tooltip('initial settings', $plugin_dir); ?>
                          </TD>
                          <TD width='60%'>
                            <INPUT type="button" disabled class="button" name="wptagpig_pro_clone_setings" value="Export Settings &raquo;" />
                              <?php echo $pro_link; ?>
                            <P style='font-size:80%; margin-top:8px;'>
                            </P>
                          </TD>
                        </TR>
                      </TABLE>
                    </p>

                  </div> <!--- class='inside' --->
                </div> <!--- class='postbox ' --->
                <?php
                  }
                ?>

                <!--- Content... --->
                <div id="pagecommentstatusdiv" class="postbox " >
                  <h3 class='hndle' style='cursor:default;'>
                    <span>Log</span>
                  </h3>
                  <div class="inside">

                    <p>
                      <!--- Displaying the log file --->
                      <?php if(function_exists('tagpig_log_show')) { tagpig_log_show(); } ?>
                    </p>

                  </div>
                </div>

                <input type="hidden" name="wpblogpig_active" value="wptagpig" />
                <BR /><BR />

              </div> <!--- class='meta-box-sortables' --->

            </div> <!--- class="has-sidebar-content" --->

          </div> <!--- class="has-sidebar" --->

        </div> <!--- class="metabox-holder" --->

      </form>

  <?php
  }

?>
