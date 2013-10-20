<?php
/**
 * @text    text
 * @sub     find text
 * @return  token
 */
function strunit_token(&$text, $sub)
{
    $p = strpos($text, $sub);
    if ($p) {
        $token = substr($text, 0, $p);
        $text = substr($text, $p + strlen($sub));
    } else {
        $token = $text;
        $text = "";
    }
    return $token;
}

?>
