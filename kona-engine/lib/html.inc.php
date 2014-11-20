<?php
/**
 * html library
 */

// --- form tag ---
function form_tag($action, $method="post")
{
  return "<form actoin='{$action}' method='{$method}'>";
}
function form_input_hidden($name, $value)
{
  return "<input type='hidden' name='$name' value='$value'/>";
}
function form_input_submit($caption, $opt_array = null)
{
  $opt = "";
  if ($opt_array != null) {
    foreach ($opt_array as $key => $val) {
      $opt .= "$key='$val' ";
    }
  }
  return "<input type='submit' value='$caption' $opt />";
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





