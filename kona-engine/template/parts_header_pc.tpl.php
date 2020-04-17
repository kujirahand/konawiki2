<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $pagetitle ?></title>

<?php /* --- NoRobots or MetaInfo ---*/ ?>
<?php if ($norobot): ?>
<!-- norobot -->
<meta name="Keywords" content="norobot" />
<meta name="Robots" content="noindex,nofollow" />
<?php else: ?>
<!-- meta info -->
<meta name="keywords" content="<?php 
  echo konawiki_getKeywords($page,$rawtag) ?>" />
<meta name="description" content="<?php
  echo htmlspecialchars(konawiki_public('description'),ENT_QUOTES) ?>" />
<link rel="canonical" href="<?php echo $baseuri ?>"/>
<meta property="og:title" content="<?php echo $pagetitle ?>" />
<meta property="og:type" content="<?php echo $ogtype ?> "/>
<meta property="og:url" content="<?php echo $baseuri ?>" />
<meta property="og:image" content="<?php echo $ogimage; ?>" />
<meta property="og:description" content="<?php echo $ogdesc;?>" />
<meta property="og:site_name" content="<?php echo $title ?>" />
<?php endif // -------------------------- ?>

<!-- CSS_JS -->
<?php
// CSS and JavaScript code
$type_css = 1;
$type_jss = 2;
$css_list = [
  [$type_css, 'pure-min.css', FALSE],
  [$type_css, 'grids-responsive-min.css', FALSE],
  [$type_css, 'konawiki.css', TRUE],
  [$type_css, $skin_css, TRUE],
  [$type_css, $theme_css, TRUE],
  [$type_jss, 'jquery-3.4.1.min.js', FALSE],
  [$type_css, 'drawer.css', TRUE],
];
foreach ($css_list as $f) {
  $type  = $f[0]; $name  = $f[1]; $mtime = $f[2];
  if (!$name) continue;
  $path = getResourceURL($name, $mtime);
  if ($type == $type_css) {
    echo '<link rel="stylesheet" type="text/css" '."\n".
         ' href="'.$path.'" />'."\n";
  } else {
    echo '<script type="text/javascript" '."\n".
         ' src="'.$path.'"></script>'."\n";
  }
}
?><!-- end of CSS_JS -->

<?php if (isset($include_js_css)): ?>
<!-- include js css -->
<?php echo $include_js_css ?>
<?php endif ?>
<script type="text/javascript"
 src="<?php echo getResourceURL('drawer.js');?>"></script>

<!-- favicon.ico -->
<link rel="shortcut icon" href="<?php echo $favicon ?>" />
</head>

<body>
<?php include(getSkinPath('parts_header_title.tpl.php')) ?>

