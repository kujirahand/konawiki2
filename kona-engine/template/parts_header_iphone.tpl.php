<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport"
          content="width=device-width,user-scalable=yes,initial-scale=1.0,maximum-scale=3.0" />
    <base href="<?php echo dirname($baseurl)?>/" />
    <?php if ($norobot): ?>
        <meta name="Keywords" content="norobot" />
        <meta name="Robots" content="noindex,nofollow" />
    <?php else: ?>
        <meta name="Keywords" content="<?php echo $page?>,<?php echo $rawtag?>" />
    <?php endif ?>
    <!-- css -->
    <link rel="stylesheet" type="text/css" href="<?php echo getResourceShortURL('konawiki_iphone.css', TRUE)?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo $skin_css ?>" />
    <?php if ($theme): ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $theme_css ?>" />
    <?php endif ?>
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
