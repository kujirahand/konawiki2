<?php
$db = konawiki_getDB();
$sql = "SELECT * FROM logs ".
    " WHERE name<>'FrontPage' AND name<>'MenuBar' AND name<>'SideBar'".
    "   AND name<>'NaviBar'". 
    " ORDER BY ctime DESC LIMIT 20";
$logs = $db->array_query($sql);
$self = konawiki_getPageURL('get','rss2');
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xml:lang="ja">
    <channel>
        <title><![CDATA[<?php echo $title?>]]></title>
        <link><?php echo $baseurl?></link>
        <description><![CDATA[<?php echo $description?>]]></description>
        <dc:creator><![CDATA[<?php echo $author?>]]></dc:creator>
        <image>
            <url><?php echo $resourceurl?>/logo.png</url>
            <title><![CDATA[<?php echo $title?>]]></title>
            <link><?php echo $baseurl?></link>
        </image>
        <atom:link href="<?php echo htmlspecialchars($self); ?>" rel="self" type="application/rss+xml" />

<?php
foreach ($logs as $log) {
    $url   = konawiki_getPageURL($log['name']);
    $name_ = htmlspecialchars($log['name']);
    $body = $log['body'];
    $body = preg_replace('#(\*|\-|\s|\[|\])+#','',$body);
    $desc  = htmlspecialchars(
                mb_strimwidth($body, 0, 254, ".."));
    $date = date("r", $log['ctime']);
    echo <<< EOS__
        <item>
            <title><![CDATA[{$name_}]]></title>
            <link>{$url}</link>
            <guid>{$url}</guid>
            <description><![CDATA[{$desc}]]></description>
            <dc:creator><![CDATA[{$author}]]></dc:creator>
            <pubDate>{$date}</pubDate>
        </item>

EOS__;
}
?>
    </channel>
</rss>
