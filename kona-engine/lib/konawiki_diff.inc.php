<?php
/**
 * konawiki_diff
 */
// mode = diff, diffonly
function konawiki_diff_checkConflict(&$body, $cur_body, $mode = 'diff')
{
    // set FLAG
    $conf_add = ">>>[+] ";
    $conf_sub = ">>>[-] ";
    // CRLF to LF
    $eol = "\n";
    $body_    = str_replace("\r\n","\n", $body);
    $cur_body = str_replace("\r\n","\n", $cur_body);
    $new_ary = explode($eol, $body_);
    $cur_ary = explode($eol, $cur_body);
    $res_ary = array();
    $has_conflict = FALSE;
    $ni = 0;
    $ci = 0;
    while ($ci < count($cur_ary) && $ni < count($new_ary)) {
        $cur_line = $cur_ary[$ci];
        $new_line = $new_ary[$ni];
        if ($cur_line == $new_line) { // same
            if ($mode != 'diffonly') {
                $res_ary[] = $cur_line;
            }
            $ci++;
            $ni++;
            continue;
        }
        // check conflict
        $has_conflict = TRUE;
        $nj = konawiki_array_search($cur_line, $new_ary, $ni);
        if ($nj == FALSE) {
            $j = konawiki_array_search($new_line, $cur_ary, $ci);
            if ($j == FALSE) {
                $res_ary[] = $conf_add.$new_line;
                $ni++;
            }
            else {
                $res_ary[] = $conf_sub.$cur_line;
                $ci++;
            }
            continue;
        }
        else {
            // can insert
            for ($j = $ni; $j < $nj; $j++) {
                $new_line = $new_ary[$j];
                $res_ary[] = $conf_sub.$new_line;
            }
            $ni = $nj;
        }
    }
    for ($j = $ci; $j < count($cur_ary); $j++) {
        $new_line = $cur_ary[$j];
        $res_ary[] = $conf_sub.$new_line;
        $has_conflict = TRUE;
    }
    for ($j = $ni; $j < count($new_ary); $j++) {
        $new_line = $new_ary[$j];
        $res_ary[] = $conf_add.$new_line;
        $has_conflict = TRUE;
    }
    if ($has_conflict) {
        $body = join($eol, $res_ary);
        return TRUE;
    }
    return FALSE;
}

function konawiki_array_search($subline, $array, $from = 0)
{
    for ($i = $from; $i < count($array); $i++) {
        if ($subline === $array[$i]) {
            return $i;
        }
    }
    return FALSE;
}

