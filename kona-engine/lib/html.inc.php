<?php
/**
 * html library
 */

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

