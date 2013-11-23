<!DOCTYPE html>
<html><head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="viewport"
        content="width=device-width,user-scalable=yes,initial-scale=1.0,maximum-scale=3.0" />
<?php if ($norobot): ?>
  <meta name="Keywords" content="norobot" />
  <meta name="Robots" content="noindex,nofollow" />
<?php else: ?>
  <meta name="keywords" content="<?php echo konawiki_getKeywords($page,$rawtag)?>" />
  <meta name="description" content="<?php echo htmlspecialchars(konawiki_public('description'),ENT_QUOTES) ?>" />

  <meta property="og:title" content="<?php echo $pagetitle ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="<?php echo $baseuri ?>" />
  <meta property="og:image" content="<?php echo $ogimage; ?>" />
  <meta property="og:description" content="<?php echo $ogdesc; ?>" />
  <meta property="og:site_name" content="<?php echo $title ?>" />
<?php endif ?>

  <!-- css -->
  <link rel="stylesheet" type="text/css" href="<?php echo $skin_css ?>" />
  <link rel="stylesheet" type="text/css" href="<?php echo getResourceURL('konawiki_iphone.css', TRUE)?>" />
  <?php echo $include_js_css ?>

  <title><?php echo $page?> - <?php echo $title?></title>
  
  <!-- rss -->
  <link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo  konawiki_getPageURL('get','rss') ?>" />
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php echo  konawiki_getPageURL('get','rss2')?>" />
  <!-- favicon.ico -->
  <link rel="shortcut icon" href="<?php echo $favicon ?>" />
</head>
<body>
<?php include(getSkinPath('parts_header_title.tpl.php')) ?>

