<?php
//##copyright##

/**
 * Sanitizer class for Subrion CMS
 */
class iaSanitize extends abstractUtil
{
	/**
	 * Escapes special characters in a string for use in an SQL statement
	 *
	 * @param mixed $string text to be escaped
	 * @param int $level
	 *
	 * @return array|string
	 */
	public static function sql($string, $level = 0)
	{
		// (this function requires database connection)
		// don't worry about slashes, script disables magic_quotes_runtime
		// and appends code to clear GPC from slashes in system.php file
		if (is_array($string) && $string)
		{
			foreach ($string as $k => $v)
			{
				$string[$k] = self::sql($v, $level + 1);
			}
		}
		else
		{
			$string = iaCore::instance()->iaDb->sql($string);
		}

		return $string;
	}

	/**
	 * Converts special characters to HTML entities
	 *
	 * @param string $string text to be converted
	 * @param int $mode mode
	 *
	 * @return array|string
	 */
	public static function html($string, $mode = ENT_QUOTES)
	{
		return htmlspecialchars($string, $mode, 'UTF-8');
	}

	/**
	 * Strips HTML and PHP tags from a string
	 *
	 * @param string $string the input string
	 * @param string|null $tags specify tags which should not be stripped
	 *
	 * @return string
	 */
	public static function tags($string, $tags = null)
	{
		return strip_tags($string, $tags);
	}

	/**
	 * Deletes all non alpha-numeric / underscore symbols in a text
	 *
	 * @param string $string text to be processed
	 *
	 * @return mixed
	 */
	public static function paranoid($string)
	{
		return preg_replace('#[^a-z_0-9]#i', '', $string);
	}

	/*
	* Converts text to snippet
	*
	* The function cuts text to specified length,
	* also it strips all special tags like [b] etc.
	*
	* @params array $params - full text, if 'summary' not used, create snippet from it
	*
	* @return string
	*/
	public static function snippet($text, $length = 600)
	{
		$iaUtil = iaCore::instance()->factory('util');

		iaUtil::loadUTF8Functions();

		// Strip HTML and BB codes
		$pattern = '#(\[\w+[^\]]*?\]|\[\/\w+\]|<\w+[^>]*?>|<\/\w+>)#i';
		$text = preg_replace($pattern, '', $text);

		// remove repeated spaces and new lines
		$text = preg_replace('/\s{2,}/', PHP_EOL, $text);
		$text = trim($text, PHP_EOL);

		if (utf8_strlen($text) > $length)
		{
			$text = utf8_substr($text, 0, $length);
			$_tmp = utf8_decode($text);
			if (preg_match('#.*([\.\s]).*#s', $_tmp, $matches, PREG_OFFSET_CAPTURE))
			{
				$end_pos = $matches[1][1];
				$text = utf8_substr($text, 0, $end_pos + 1);
				$text .= ' ...';
			}
		}

		return $text;
	}

	/**
	 * Converts text to well-formed URL, replaces all non alpha-numeric / underscore symbols to separator
	 *
	 * @param string $string text to be converted
	 * @param string $separator separator symbol used for the conversion
	 *
	 * @return string
	 */
	public static function alias($string, $separator = '-')
	{
		iaCore::instance()->factory('util')->loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

		$string = html_entity_decode($string);
		$string = str_replace(array('&', "'"), array('and', ''), $string);

		$urlEncoded = false;

		if (!utf8_is_ascii($string))
		{
			if (iaCore::instance()->get('alias_urlencode', false))
			{
				$string = preg_replace('#[^0-9\\p{L}]+#ui', $separator, $string);

				$urlEncoded = true;
			}
			else
			{
				$string = utf8_to_ascii($string);
			}
		}

		$string = $urlEncoded ? $string : preg_replace('#[^a-z0-9_]+#i', $separator, $string);
		$string = trim($string, $separator);

		return $string;
	}

	/**
	 * Filters against HTML injection
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function urlInjectionFilter($url)
	{
		return str_replace(array('<', '>', '"', "'", '&'), '', $url);
	}

	/**
	 * Un-quotes a quoted string or array more then one level
	 *
	 * @param array|string $value text to be un-quoted
	 *
	 * @return array|string
	 */
	public static function stripslashes_deep($value)
	{
		$value = is_array($value) ? array_map(array(__CLASS__, __METHOD__), $value) : stripslashes($value);

		return $value;
	}

	/**
	 * Deletes all non-allowed symbols for filename
	 * Сan(should) be used by the array_walk and array_walk_recursive functions
	 *
	 * @param string $item text to be processed
	 *
	 * @return void
	 */
	public static function filenameEscape(&$item)
	{
		$item = str_replace(array('`', '~', '/', "\\"), '', $item);
	}
}