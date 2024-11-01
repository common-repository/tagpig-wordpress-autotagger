<?php

class TagPiGHTTP {
  var $ch = false;
  var $local_cookies = false;
  var $cookies = false;
  var $sessions = false;
  var $current_function = "curl";
  var $user_agent = "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.1) Gecko/2008072820 Firefox/3.0.1";

  # Constructor
  function TagPiGHTTP($user_agent = '', $cookie_file = '', $sessions = false, $headers = true) {
    if($user_agent) {
      $this->user_agent = $user_agent;
    }
    $this->sessions = $sessions;

    if(function_exists('curl_init')) {
      if(!$this->cookies) {
        if($cookie_file) {
          $this->cookies = $cookie_file;
        }
        else {
          if($this->local_cookies) {
            $this->cookies = dirname(__FILE__) . '/tmp/cookie_' . rand(10000, 99999);
          }
          else {
            $this->cookies = @tempnam('/tmp', 'tagpig');
          }
        }
        #$this->log_message("DEBUG:: cookies = `{$this->cookies}`");
        register_shutdown_function(array($this,'TagPiGHTTPShutdown'));
      }
      else {
        if($cookie_file) {
          $this->cookies = $cookie_file;
        }
      }

      $this->ch = curl_init();
      curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
      curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($this->ch, CURLOPT_HEADER, $headers);
      curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);
      curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookies);
      curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookies);
      @curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, false);
      curl_setopt($this->ch, CURLOPT_TIMEOUT, 120);

      # DEBUG
      curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
    }
    else {
      echo "ERROR:: CURL could not be initialized! \n";
    }
  }

  # Destructor
  function TagPiGHTTPShutdown() {
    if($this->cookies && file_exists($this->cookies)) {
      @unlink($this->cookies);
    }

    if($this->ch && function_exists('curl_close')) {
      curl_close($this->ch);
    }
    unset($this->ch);
  }

  #
  # get/set properties...
  ###

  # cookies
  function getCookies() {
    return $this->cookies;
  }

  function setCookies($filename) {
    $this->cookies = $filename;
  }

  # sessions
  function getSessions() {
    return $this->sessions;
  }

  function setSessions($enabled) {
    $this->sessions = $enabled;
  }


  #
  # basic HTTP operations (GET, POST...)
  ###

  function getUrl($url, $username = '', $password = '', $referer = '', $do_follow = false) {
    $result = false;

    $this->log_message('    === getUrl:: start...');
    $this->log_message('        getUrl:: url = `' . print_r($url, true) . '`');
    $this->log_message('        getUrl:: referer = `' . print_r($referer, true) . '`');
    $this->log_message('        getUrl:: do_follow = `' . print_r($do_follow, true) . '`');

    $url = trim($url);
    if($url) {
      $result = "";

      if($this->ch) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        if($username || $password) {
          curl_setopt($this->ch, CURLOPT_USERPWD, "{$username}:{$password}");
        }
        if($referer) {
          curl_setopt($this->ch, CURLOPT_REFERER, $referer);
        }
        @curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $do_follow);

        # Proxy
        if(class_exists('WP_HTTP_Proxy')) {
          $proxy = new WP_HTTP_Proxy();
          if($proxy->is_enabled() && $proxy->send_through_proxy($url)) {
            $isPHP5 = version_compare(PHP_VERSION, '5.0.0', '>=');
            if ($isPHP5) {
              curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
              curl_setopt($this->ch, CURLOPT_PROXY, $proxy->host());
              curl_setopt($this->ch, CURLOPT_PROXYPORT, $proxy->port());
            }
            else {
              curl_setopt($this->ch, CURLOPT_PROXY, $proxy->host() .':'. $proxy->port());
            }

            if($proxy->use_authentication()) {
              if ($isPHP5) {
                curl_setopt($this->ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
              }
              curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $proxy->authentication());
            }
          }
        }

        $result = curl_exec($this->ch);

        $this->log_message('        getUrl:: curl_get_info() = `' . print_r(curl_getinfo($this->ch), true) . '`');
        $this->log_message('        getUrl:: curl_errno() = `' . print_r(curl_errno($this->ch), true) . '`');
      }
    }
    $this->log_message('    === getUrl:: end...');

    return $result;
  }

  function postUrl($url, $params, $username = '', $password = '', $referer = '', $do_follow = false) {
    $result = false;

    $this->log_message('    === postUrl:: start...');
    $this->log_message('        postUrl:: url = `' . print_r($url, true) . '`');
    $this->log_message('        postUrl:: params = `' . print_r($params, true) . '`');
    $this->log_message('        postUrl:: referer = `' . print_r($referer, true) . '`');
    $this->log_message('        postUrl:: do_follow = `' . print_r($do_follow, true) . '`');

    if($url) {
      $result = "";

      if($this->ch) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
        if($referer) {
          curl_setopt($this->ch, CURLOPT_REFERER, $referer);
        }
        @curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $do_follow);

        # Proxy
        if(class_exists('WP_HTTP_Proxy')) {
          $proxy = new WP_HTTP_Proxy();
          if($proxy->is_enabled() && $proxy->send_through_proxy($url)) {
            $isPHP5 = version_compare(PHP_VERSION, '5.0.0', '>=');
            if ($isPHP5) {
              curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
              curl_setopt($this->ch, CURLOPT_PROXY, $proxy->host());
              curl_setopt($this->ch, CURLOPT_PROXYPORT, $proxy->port());
            }
            else {
              curl_setopt($this->ch, CURLOPT_PROXY, $proxy->host() .':'. $proxy->port());
            }

            if($proxy->use_authentication()) {
              if ($isPHP5) {
                curl_setopt($this->ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
              }
              curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $proxy->authentication());
            }
          }
        }

        $result = curl_exec($this->ch);

        $this->log_message('        postUrl:: curl_get_info() = `' . print_r(curl_getinfo($this->ch), true) . '`');
        $this->log_message('        postUrl:: curl_errno() = `' . print_r(curl_errno($this->ch), true) . '`');
      }
    }
    $this->log_message('    === postUrl:: end...');

    return $result;
  }

  function log_message($what_to_write, $clear_file = false) {
    $result = false;
    if($what_to_write) {
      $location = dirname(__FILE__) . '/http.log';
      $mode = "a+";
      if($clear_file) {
        $mode = "w+";
      }
      $fileHandler = @fopen($location, $mode);
      if($fileHandler) {
        fwrite($fileHandler, date("Y-m-d H:i:s") . ":: " . $what_to_write . "\n");
        fclose($fileHandler);
        $result = true;
      }
    }
    return $result;
  }


  #
  # other...
  ###


}

?>
