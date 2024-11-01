<?php
  $tooltips = array(
    "auto tag all new posts" => "",
    "tag sources" => "",

    "maximum tags" => "<p>single digit or range (e.g. `5-10`)<BR/>(0 = all tags)</p>",
    "minimum words per tag" => "<p>(0 = all words)</p>",
    "word filter" => "<p>One word/phrase per line</p>",
    "preferred tags" => "<p>One tag/keyword per line</p>",

    "auto link tags" => "",
    "match tag case" => "",
    "no-follow all tags" => "",
    "minimum tag frequency" => "",
    "minimum words per linked tag" => "<p>The lower word count limit for linked tags</p>",
    "maximum links per tag" => "<p>Limits the number of links for each tag per post</p>",
    "maximum links per post" => "<p>Limits the number of linked tags per post</p>",
    "never link these words" => "<p>One word/phrase per line</p>",

    "publish in posts" => "",
    "suppress theme tags" => "<p>Hides the default theme tags</p>",
    "publish in feeds" => "",
    "categories to tags" => "",
    "tag template" => "",
    "no tags string" => "",
    "maximum tags per post" => "<p>single digit or range (e.g. `5-10`)<BR />(0 = all tags)</p>",
    "link to off-site content" => "",

    "status" => "<p>This process runs in the background. When started it will append new tags to published posts</p>",
    "delay" => "",

    "initial settings" => "<p>Downloads the current settings for your plugin. Can be used to configure the initial settings on a different site</p>",

  );

  $headers = array(
    'blogpig-api-key' => '_UaNnq2KTRo', // 'how-to-enter-your-blogpig-api-key',
    'tagpig-auto-tag-sources' => 'http://blogpig.com/tagpig-auto-tag-sources/print',
    'tagpig-filter-tags' => 'http://blogpig.com/tagpig-filter-tags/print',
    'tagpig-auto-tag-linking' => 'http://blogpig.com/tagpig-auto-tag-linking/print',
    'tagpig-publish-tags' => 'http://blogpig.com/tagpig-publish-tags/print',
    'tagpig-auto-tag-existing-posts' => 'http://blogpig.com/tagpig-auto-tag-existing-posts/print',
    'blogpig-clone-settings' => 'http://blogpig.com/blogpig-clone-settings/print',
    'blogpig-log' => 'http://blogpig.com/blogpig-log/print',
  );

  $faqs = array(
    'no-api-key' => array(
      'info' => 'http://blogpig.com/help/faqs/general/how-to-enter-your-blogpig-api-key',
      'free_key' => 'http://blogpig.com/api_key/',
      'message' => ': No API key found. You must enter your BlogPiG API key for the plugin to work. ',
    ),
    'invalid-api-key' => array(
      'info' => 'http://blogpig.com/help/faqs/general/invalid-api-key',
      'free_key' => 'http://blogpig.com/api_key/',
      'message' => ': No valid API key found. You must enter your BlogPiG API key for the plugin to work. ',
    ),
    'no-pro-file' => array(
      'info' => 'http://blogpig.com/help/faqs/general/missing-pro-file',
      'message' => ': No Pro file found. Download your Pro version from the BlogPiG member\'s area. ',
      
    ),
    'no-ioncube' => array(
      'info' => 'http://blogpig.com/help/faqs/ioncube/pro-features-not-activated',
      'message' => ': No ionCube loaders found. Pro features currently disabled. ',
    ),
    'no-pro-features' => array(
      'recheck' => '?page=' . $_REQUEST['page'] . '&btnSubmitKey=Save%20Key',
      'upgrade' => 'http://blogpig.com/products/tagpig/',
      'message' => ': Pro features are currently disabled. ',
    ),
  );
?>
