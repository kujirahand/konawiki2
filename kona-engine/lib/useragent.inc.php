<?php

/**
 * UserAgent を返す
 * @return string
 */
function kona_getUserAgent()
{
  $ua = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : '';
  return $ua;
}

function useragent_is_galapagos()
{
  return useragent_is_docomo() || useragent_is_softbank() || useragent_is_au();
}

function useragent_is_smartphone()
{
  return 
    konawiki_public('mobile', false) ||
    useragent_is_iPhone() || 
    useragent_is_android();
}

function useragent_is_iPad()
{
  $ua = kona_getUserAgent();
  return (strpos($ua, "iPad") >= 1);
}

function useragent_is_iPhone()
{
  $ua = kona_getUserAgent();
  return (strpos($ua, "iPhone") >= 1) || (strpos($ua, "iPod") >= 1);
}

function useragent_is_android()
{
  $ua = kona_getUserAgent();
  return (strpos($ua, "Android") >= 1);
}

function useragent_is_docomo()
{
  $ua = kona_getUserAgent();
  return (preg_match('/^DoCoMo/', $ua) === 1);
}

function useragent_is_softbank()
{
  $ua = kona_getUserAgent();
  return (preg_match('/^(J-PHONE|Vodafone|SoftBank)/', $ua) === 1);
}

function useragent_is_au()
{
  $ua = kona_getUserAgent();
  return (preg_match('/^(UP\.Browser|KDDI)/', $ua) === 1);
}



