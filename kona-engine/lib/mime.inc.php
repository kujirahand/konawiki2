<?php

/**
 * 拡張子から MIME タイプを取得する
 */

function mime_content_type_e( $filename )
{
    if (!preg_match("#(\.[a-zA-Z0-9_]+)$#",$filename,$m)) {
        return "application/octet-stream";
    }
    $ext = $m[1];
    $ext = strtolower($ext);
    $list = array(
        '.jar'=>'application/java-archive',
        '.doc'=>'application/msword',
        '.pdf'=>'application/pdf',
        '.rtf'=>'application/rtf',
        '.xls'=>'application/vnd.ms-excel',
        '.ppt'=>'application/vnd.ms-powerpoint',
        '.swf'=>'application/x-shockwave-flash',
        '.js'=>'application/x-javascript',
        '.flv'=>'video/x-flv',
        '.wmv'=>'video/x-ms-wmv',
        '.avi'=>'video/msvideo',
        '.mov'=>'video/quicktime',
        '.mp4'=>'video/mp4',
        '.xml'=>'text/xml',
        '.htm'=>'text/html',
        '.html'=>'text/html',
        '.txt'=>'text/plain',
        '.csv'=>'text/comma-separated-values',
        '.css'=>'text/css',
        '.vcf'=>'text/x-vcard',
        '.bmp'=>'image/bmp',
        '.png'=>'image/png',
        '.gif'=>'image/gif',
        '.jpeg'=>'image/jpeg',
        '.jpg'=>'image/jpeg',
        '.ico'=>'image/x-icon',
        '.tif'=>'image/tiff',
        '.tiff'=>'image/tiff',
        '.mid'=>'audio/midi',
        '.mp3'=>'audio/mp3',
        '.wav'=>'audio/wav',
    );
    $mime = isset($list[$ext]) ? $list[$ext] : "application/octet-stream";
    return $mime;
}


