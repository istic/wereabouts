<?php

/**
 * Best-effort read of a free-text sheet cell as a yes/no flag, matching if
 * the (trimmed, lowercased) value starts with any of the given prefixes.
 *
 * @param  array<int, string>  $truthyPrefixes
 */
function venue_sheet_flag(?string $value, array $truthyPrefixes = ['y']): bool
{
    $normalized = strtolower(trim($value ?? ''));

    foreach ($truthyPrefixes as $prefix) {
        if (str_starts_with($normalized, $prefix)) {
            return true;
        }
    }

    return false;
}

function auto_link(?string $str, string $type = 'both', bool $popup = false): string
{
    $str = $str ?? '';

    // Find any URLs, matched against the raw (unescaped) string. Only http(s)
    // and bare "www." links are linked - any other scheme (e.g. javascript:)
    // is left as plain, escaped text.
    if ($type === 'email' || ! preg_match_all('#(https?://|www\.)[^\s()<>;]+\w#i', $str, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
        return e($str);
    }

    // Set our target HTML if using popup links. rel="noopener noreferrer"
    // prevents the linked page from tabnabbing via window.opener.
    $target = ($popup) ? ' target="_blank" rel="noopener noreferrer"' : '';

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
