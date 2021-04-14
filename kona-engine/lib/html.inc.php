<?php
/**
 * html library
 */

// --- form tag ---
function form_tag($action, $method="post")
{
  return "<form action='$action' method='$method'>\n";
}
function form_input_hidden($name, $value)
{
  return "<input type='hidden' name='$name' value='$value'/>";
}
function form_input_text($name, $value, $opt_array = null)
{
  $opt = "";
  if ($opt_array != null) {
    foreach ($opt_array as $key => $val) {
      $opt .= "$key='$val' ";
    }
  }
  return "<input type='text' id='$name' name='$name' value='$value' $opt/>";
}
function form_input_password($name, $value, $opt_array = null)
{
  $opt = "";
  if ($opt_array != null) {
    foreach ($opt_array as $key => $val) {
      $opt .= "$key='$val' ";
    }
  }
  return "<input type='password' id='$name' name='$name' value='$value' $opt/>";
}
function form_label($name, $caption)
{
  return "<label for='$name'>$caption</label>";
}
function form_input_submit($caption, $opt_array = null)
{
  $opt = "";
  if ($opt_array != null) {
    foreach ($opt_array as $key => $val) {
      $opt .= "$key='$val' ";
    }
  }
  return 
    "<input type='submit' ".
    " class='pure-button pure-button-primary' ".
    " value='$caption' $opt />";
}

// --- style tag
/* --- example ---
html_css_add(".shopbox", array(
  "border"        => "1px solid silver",
  "padding"       => "8px",
  "margin-bottom" => "14px",
));
html_css_add(".shopbox_btn", array(
  "width"   => "94%",
  "margin"  => "5px",
));
echo html_css_out("CSS-Test");
*/
global $html_css_cache, $html_css_unique;
$html_css_cache = array();
$html_css_unique = array();
function html_css_add($name, $att_list) {
  global $html_css_cache;
  $html_css_cache[$name] = $att_list;
}
function html_css_out($uid) {
  global $html_css_cache, $html_css_unique;
  if (isset($html_css_unique[$uid])) return "";
  $html_css_unique[$uid] = true;
  $css = "";
  foreach ($html_css_cache as $c => $a) {
    $css .= $c . " { ";
    foreach ($a as $key => $val) {
      $css .= "  " . $key . ":" . $val . ";\n";
    }
    $css .= " }\n";
  }
  $html_css_cache = array();
  return
    "<!-- CSS($uid).begin -->\n".
    "<style>\n".
    $css."\n".
    "</style>\n".
    "<!-- CSS($uid).end -->\n";
}

global $html_js_cache;
function html_js_out($uid, $code) {
  global $html_js_cache;
  if (isset($html_js_cache[$uid])) return "";
  $html_js_cache[$uid] = true;
  return 
    "<!-- js($uid).begin -->\n".
    "<script type='text/javascript'>\n".
    $code.
    "\n</script>\n".
    "<!-- js($uid).end -->\n";
}






