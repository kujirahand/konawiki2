<?php
$sql = "SELECT * FROM logs ".
    " WHERE name<>'FrontPage' AND name<>'MenuBar' AND name<>'SideBar'".
    "       AND name<>'NaviBar'".
    " ORDER BY ctime DESC LIMIT 20";
$logs = db_get($sql);
echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
?>
<rdf:RDF
  xmlns="http://purl.org/rss/1.0/"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xml:lang="ja">

<channel rdf:about="<?php echo $baseurl?>?cmd=rss">
  <title><![CDATA[<?php echo $title?>]]></title>
  <link><?php echo $baseurl?></link>
  <dc:date><?php echo konawiki_dcDate(time())?></dc:date>
  <description><![CDATA[<?php echo  $description ?>]]></description>
<items>
<rdf:Seq>
<?php
foreach ($logs as $log) {
    $page = konawiki_getPageURL($log['name']);
    echo "  <rdf:li rdf:resource=\"{$page}\" />\n";
}
?>
</rdf:Seq>
</items>
</channel>

<?php
foreach ($logs as $log) {
    extract($log);
    $url   = konawiki_getPageURL($log['name']);
    $name_ = htmlspecialchars($log['name']);
    $body = $log['body'];
    $body = preg_replace('#(\*|\-|\s|\[|\])+#','',$body);
    $desc  = htmlspecialchars(
                mb_strimwidth($body, 0, 254, ".."));
    $date = date("r", $log['ctime']);
    echo "<item rdf:about=\"$url\">\n";
    echo "<title><![CDATA[{$name_}]]></title>\n";
    echo "<link>{$url}</link>\n";
    echo "<dc:date>{$date}</dc:date>\n";
    echo "<description><![CDATA[{$desc}]]></description>\n";
    echo "</item>\n";
}
?>

</rdf:RDF>
