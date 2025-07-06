<?php


function auto_link($str, $type = 'both', $popup = FALSE)
{
  // Find and replace any URLs.
  if ($type !== 'email' && preg_match_all('#(\w*://|www\.)[^\s()<>;]+\w#i', $str, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
    // Set our target HTML if using popup links.
    $target = ($popup) ? ' target="_blank"' : '';

    // We process the links in reverse order (last -> first) so that
    // the returned string offsets from preg_match_all() are not
    // moved as we add more HTML.
    foreach (array_reverse($matches) as $match) {
      // $match[0] is the matched string/link
      // $match[1] is either a protocol prefix or 'www.'
      //
      // With PREG_OFFSET_CAPTURE, both of the above is an array,
      // where the actual value is held in [0] and its offset at the [1] index.
      $a = '<a href="' . (strpos($match[1][0], '/') ? '' : 'http://') . $match[0][0] . '"' . $target . '>' . $match[0][0] . '</a>';
      $str = substr_replace($str, $a, $match[0][1], strlen($match[0][0]));
    }
  }

  return $str;
}
