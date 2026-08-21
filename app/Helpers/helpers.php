<?php

function auto_link(?string $str, string $type = 'both', bool $popup = false): string
{
    $str = $str ?? '';

    // Find any URLs, matched against the raw (unescaped) string.
    if ($type === 'email' || ! preg_match_all('#(\w*://|www\.)[^\s()<>;]+\w#i', $str, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
        return e($str);
    }

    // Set our target HTML if using popup links.
    $target = ($popup) ? ' target="_blank"' : '';

    $result = '';
    $cursor = 0;
    foreach ($matches as $match) {
        // $match[0] is the matched string/link
        // $match[1] is either a protocol prefix or 'www.'
        //
        // With PREG_OFFSET_CAPTURE, both of the above is an array,
        // where the actual value is held in [0] and its offset at the [1] index.
        [$url, $offset] = $match[0];
        $prefix = $match[1][0];

        $result .= e(substr($str, $cursor, $offset - $cursor));

        $href = (str_contains($prefix, '/') ? '' : 'http://').$url;
        $result .= '<a href="'.e($href).'"'.$target.'>'.e($url).'</a>';

        $cursor = $offset + strlen($url);
    }
    $result .= e(substr($str, $cursor));

    return $result;
}
