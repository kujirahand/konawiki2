<?php

function meta_table_template($tpl_file, $values) {
  // check cache
  global $kona_meta_template_cache;
  if (empty($kona_meta_template_cache)) {
    $kona_meta_template_cache = [];
  }
  if (isset($kona_meta_template_cache[$tpl_file])) {
    $template = $kona_meta_template_cache[$tpl_file];
  } else {
    $file = dirname(__FILE__).'/'.$tpl_file;
    if (!file_exists($file)) {
      return "<!-- #meta_table.error file not found: $tpl_file -->\n";
    }
    $template = file_get_contents($file);
    $kona_meta_template_cache[$tpl_file] = $template;
  }
  // replace all
  $filters = [
    "html" => function ($s) { return htmlspecialchars($s, ENT_QUOTES); },
    "raw"  => function ($s) { return $s; }
  ];
  $html = preg_replace_callback_array([
    // filter
    '#\{\{\s*\$([a-zA-Z0-9_]+)\s*\|\s*([a-zA-Z0-9_]+)\}\}#' =>
    function ($m) use ($values, $filters) {
      $key = trim($m[1]);
      $filter = trim($m[2]);
      // get param
      if (isset($values[$key])) {
        $val = $values[$key];
        if (is_bool($val)) {
          $val = $val ? "true" : "false";
        }
        // filter
        if (isset($filters[$filter])) {
          $val = $filters[$filter]($val);
        } else {
          $val = htmlspecialchars($val, ENT_QUOTES);
        }
      } else {
        $val = "{{$key}}"; // そのまま
      }
      return $val;
    },
    // simple
    '#\{\{\s*\$([a-zA-Z0-9_]+)\s*\}\}#' =>
    function ($m) use ($values) {
      $key = trim($m[1]);
      if (isset($values[$key])) {
        $val = $values[$key];
        if (is_bool($val)) {
          $val = $val ? "true" : "false";
        }
        $val = htmlspecialchars($val, ENT_QUOTES);
      } else {
        $val = "{{$key}}";
      }
      return $val;
    }],
    $template);
  return $html;
}


