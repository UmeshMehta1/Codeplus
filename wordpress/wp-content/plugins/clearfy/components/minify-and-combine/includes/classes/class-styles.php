<?php
/**
 * Operations with styles
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */

if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Class WMAC_PluginStyles
 */
class WMAC_PluginStyles extends WMAC_PluginBase {

	const ASSETS_REGEX = '/url\s*\(\s*(?!["\']?data:)(?![\'|\"]?[\#|\%|])([^)]+)\s*\)([^;},\s]*)/i';

	/**
	 * Font-face regex-fu from HamZa at: https://stackoverflow.com/a/21395083
	 * ~
	 *
	 * @font-face\s* # Match @font-face and some spaces
	 * (             # Start group 1
	 * \{            # Match {
	 * (?:           # A non-capturing group
	 * [^{}]+        # Match anything except {} one or more times
	 * |             # Or
	 * (?1)          # Recurse/rerun the expression of group 1
	 * )*            # Repeat 0 or more times
	 * \}            # Match }
	 * )             # End group 1
	 * ~xs';
	 */
	const FONT_FACE_REGEX = '~@font-face\s*(\{(?:[^{}]+|(?1))*\})~xsi'; // added `i` flag for case-insensitivity.

	private $options = [];
	private $dontmove = [];
	private $css = [];
	private $csscode = [];
	private $url = [];
	private $restofcontent = '';
	private $datauris = false;
	private $hashmap = [];
	private $alreadyminified = false;
	private $aggregate = true;
	private $inline = false;
	private $defer = false;
	//private $defer_inline    = false;
	private $whitelist = [];
	private $cssinlinesize = '';
	private $cssremovables = [];
	private $include_inline = false;
	private $inject_min_late = '';
	private $css_critical_style = '';
	private $css_critical = [];

	/**
	 * Reads the page and collects style tags.
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function read($options)
	{
		$noptimizeCSS = apply_filters('wmac_filter_css_noptimize', false, $this->content);
		if( $noptimizeCSS ) {
			return false;
		}

		$whitelistCSS = apply_filters('wmac_filter_css_whitelist', '', $this->content);
		if( !empty($whitelistCSS) ) {
			$this->whitelist = array_filter(array_map('trim', explode(',', $whitelistCSS)));
		}

		$removableCSS = apply_filters('wmac_filter_css_removables', '');
		if( !empty($removableCSS) ) {
			$this->cssremovables = array_filter(array_map('trim', explode(',', $removableCSS)));
		}

		$this->cssinlinesize = apply_filters('wmac_filter_css_inlinesize', 256);

		// filter to "late inject minified CSS", default to true for now (it is faster).
		$this->inject_min_late = apply_filters('wmac_filter_css_inject_min_late', true);

		// Remove everything that's not the header.
		/*if ( apply_filters( 'wmac_filter_css_justhead', $options['justhead'] ) ) {
			$content             = explode( '</head>', $this->content, 2 );
			$this->content       = $content[0] . '</head>';
			$this->restofcontent = $content[1];
		}*/

		// Determine whether we're doing CSS-files aggregation or not.
		if( isset($options['aggregate']) && !$options['aggregate'] ) {
			$this->aggregate = false;
		}
		// Returning true for "dontaggregate" turns off aggregation.
		if( $this->aggregate && apply_filters('wmac_filter_css_dontaggregate', false) ) {
			$this->aggregate = false;
		}

		// include inline?
		if( apply_filters('wmac_css_include_inline', $options['include_inline']) ) {
			$this->include_inline = true;
		}

		// List of CSS strings which are excluded from autoptimization.
		$excludeCSS = apply_filters('wmac_filter_css_exclude', $options['css_exclude'], $this->content);
		if( '' !== $excludeCSS ) {
			$this->dontmove = array_filter(array_map('trim', explode(',', $excludeCSS)));
		} else {
			$this->dontmove = [];
		}

		// forcefully exclude CSS with data-noptimize attrib.
		$this->dontmove[] = 'data-noptimize';

		// Should we defer css?
		// value: true / false.
		$this->defer = $options['defer'];
		$this->defer = apply_filters('wmac_filter_css_defer', $this->defer, $this->content);

		// Should we inline while deferring?
		// value: inlined CSS.
		//$this->defer_inline = apply_filters( 'wmac_filter_css_defer_inline', $options['defer_inline'], $this->content );

		// Should we inline?
		// value: true / false.
		$this->inline = $options['inline'];
		$this->inline = apply_filters('wmac_filter_css_inline', $this->inline, $this->content);

		// Store data: URIs setting for later use.
		$this->datauris = $options['datauris'];

		// noptimize me.
		$this->content = $this->hideNoptimize($this->content);

		// Exclude (no)script, as those may contain CSS which should be left as is.
		$this->content = $this->replaceContentsWithMarkerIfExists('SCRIPT', '<script', '#<(?:no)?script.*?<\/(?:no)?script>#is', $this->content);

		// Save IE hacks.
		$this->content = $this->hideIehacks($this->content);

		// Hide HTML comments.
		$this->content = $this->hideComments($this->content);

		// Get <style> and <link>.
		if( preg_match_all('#(<style[^>]*>.*</style>)|(<link[^>]*stylesheet[^>]*>)#Usmi', $this->content, $matches) ) {

			foreach($matches[0] as $tag) {
				if( $this->isremovable($tag, $this->cssremovables) ) {
					$this->content = str_replace($tag, '', $this->content);
				} else if( $this->isMovable($tag) ) {
					// Get the media.
					if( false !== strpos($tag, 'media=') ) {
						preg_match('#media=(?:"|\')([^>]*)(?:"|\')#Ui', $tag, $medias);
						$medias = explode(',', $medias[1]);
						$media = [];
						foreach($medias as $elem) {
							/* $media[] = current(explode(' ',trim($elem),2)); */
							if( empty($elem) ) {
								$elem = 'all';
							}

							$media[] = $elem;
						}
					} else {
						// No media specified - applies to all.
						$media = ['all'];
					}

					$media = apply_filters('wmac_filter_css_tagmedia', $media, $tag);

					if( preg_match('#<link.*href=("|\')(.*)("|\')#Usmi', $tag, $source) ) {
						// <link>.
						$url = current(explode('?', $source[2], 2));
						$path = $this->getpath($url);

						if( false !== $path && preg_match('#\.css$#', $path) ) {
							// Good link.
							$this->css[] = [$media, $path];
						} else {
							// Link is dynamic (.php etc).
							$tag = '';
						}
					} else {
						// Inline css in style tags can be wrapped in comment tags, so restore comments.
						$tag = $this->restoreComments($tag);
						preg_match('#<style.*>(.*)</style>#Usmi', $tag, $code);

						// And re-hide them to be able to to the removal based on tag.
						$tag = $this->hideComments($tag);

						if( $this->include_inline ) {
							$code = preg_replace('#^.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*$#sm', '$1', $code[1]);
							$this->css[] = [$media, 'INLINE;' . $code];
						} else {
							$tag = '';
						}
					}

					// Remove the original style tag.
					$this->content = str_replace($tag, '', $this->content);
				} else {
					// Excluded CSS, minify if getpath and filter says so...
					if( preg_match('#<link.*href=("|\')(.*)("|\')#Usmi', $tag, $source) ) {
						$exploded_url = explode('?', $source[2], 2);
						$url = $exploded_url[0];
						$path = $this->getpath($url);

						if( $path && apply_filters('wmac_filter_css_minify_excluded', true, $url) ) {
							$minified_url = $this->minifySingle($path);
							if( !empty($minified_url) ) {
								// Replace orig URL with cached minified URL.
								$new_tag = str_replace($url, $minified_url, $tag);
							} else {
								$new_tag = $tag;
							}

							// Removes querystring from URL.
							if( !empty($exploded_url[1]) ) {
								$new_tag = str_replace('?' . $exploded_url[1], '', $new_tag);
							}

							// Defer single CSS if "inline & defer" is on.
							if( $this->defer ) {
								// Get/ set (via filter) the JS to be triggers onload of the preloaded CSS.
								$_preload_onload = apply_filters('wmac_filter_css_preload_onload', "this.onload=null;this.rel='stylesheet'", $url);
								// Adapt original <link> element for CSS to be preloaded and add <noscript>-version for fallback.
								$new_tag = '<noscript>' . $new_tag . '</noscript>' . str_replace([
										"rel='stylesheet'",
										'rel="stylesheet"',
									], "rel='preload' as='style' onload=\"" . $_preload_onload . "\"", $new_tag);
							}

							// And replace!
							$this->content = str_replace($tag, $new_tag, $this->content);
						}
					}
				}
			}

			return true;
		}

		// Really, no styles?
		return false;
	}

	/**
	 * Checks if the local file referenced by $path is a valid
	 * candidate for being inlined into a data: URI
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	private function isDatauriCandidate($path)
	{
		// Call only once since it's called from a loop.
		static $max_size = null;
		if( null === $max_size ) {
			$max_size = $this->getDatauriMaxsize();
		}

		if( $path && preg_match('#\.(jpe?g|png|gif|webp|bmp)$#i', $path) && file_exists($path) && is_readable($path) && filesize($path) <= $max_size ) {

			// Seems we have a candidate.
			$is_candidate = true;
		} else {
			// Filter allows overriding default decision (which checks for local file existence).
			$is_candidate = apply_filters('wmac_filter_css_is_datauri_candidate', false, $path);
		}

		return $is_candidate;
	}

	/**
	 * Returns the amount of bytes that shouldn't be exceeded if a file is to
	 * be inlined into a data: URI. Defaults to 4096, passed through
	 * `wmac_filter_css_datauri_maxsize` filter.
	 *
	 * @return mixed
	 */
	private function getDatauriMaxsize()
	{
		static $max_size = null;

		/**
		 * No need to apply the filter multiple times in case the
		 * method itself is invoked multiple times during a single request.
		 * This prevents some wild stuff like having different maxsizes
		 * for different files/site-sections etc. But if you're into that sort
		 * of thing you're probably better of building assets completely
		 * outside of WordPress anyway.
		 */
		if( null === $max_size ) {
			$max_size = (int)apply_filters('wmac_filter_css_datauri_maxsize', 4096);
		}

		return $max_size;
	}

	/**
	 * @param $url
	 *
	 * @return bool
	 */
	private function checkDatauriExcludeList($url)
	{
		static $exclude_list = null;
		$no_datauris = [];

		// Again, skip doing certain stuff repeatedly when loop-called.
		if( null === $exclude_list ) {
			$exclude_list = apply_filters('wmac_filter_css_datauri_exclude', '');
			$no_datauris = array_filter(array_map('trim', explode(',', $exclude_list)));
		}

		$matched = false;

		if( !empty($exclude_list) ) {
			foreach($no_datauris as $no_datauri) {
				if( false !== strpos($url, $no_datauri) ) {
					$matched = true;
					break;
				}
			}
		}

		return $matched;
	}

	/**
	 * @param $path
	 *
	 * @return array
	 */
	private function buildOrGetDatauriImage($path)
	{
		/**
		 * TODO/FIXME: document the required return array format, or better yet,
		 * use a string, since we don't really need an array for this. That would, however,
		 * require changing even more code, which is not happening right now...
		 */

		// Allows short-circuiting datauri generation for an image.
		$result = apply_filters('wmac_filter_css_datauri_image', [], $path);
		if( !empty($result) ) {
			if( is_array($result) && isset($result['full']) && isset($result['base64data']) ) {
				return $result;
			}
		}

		$hash = md5($path);
		$check = new WMAC_PluginCache($hash, 'img');
		if( $check->check() ) {
			// we have the base64 image in cache.
			$headAndData = $check->retrieve();
			$_base64data = explode(';base64,', $headAndData);
			$base64data = $_base64data[1];
			unset($_base64data);
		} else {
			// It's an image and we don't have it in cache, get the type by extension.
			$exploded_path = explode('.', $path);
			$type = end($exploded_path);

			switch( $type ) {
				case 'jpg':
				case 'jpeg':
					$dataurihead = 'data:image/jpeg;base64,';
					break;
				case 'gif':
					$dataurihead = 'data:image/gif;base64,';
					break;
				case 'png':
					$dataurihead = 'data:image/png;base64,';
					break;
				case 'bmp':
					$dataurihead = 'data:image/bmp;base64,';
					break;
				case 'webp':
					$dataurihead = 'data:image/webp;base64,';
					break;
				default:
					$dataurihead = 'data:application/octet-stream;base64,';
			}

			// Encode the data.
			$base64data = base64_encode(file_get_contents($path));
			$headAndData = $dataurihead . $base64data;

			// Save in cache.
			$check->cache($headAndData, 'text/plain');
		}
		unset($check);

		return ['full' => $headAndData, 'base64data' => $base64data];
	}

	/**
	 * Given an array of key/value pairs to replace in $string,
	 * it does so by replacing the longest-matching strings first.
	 *
	 * @param string $string
	 * @param array $replacements
	 *
	 * @return string
	 */
	protected static function replaceLongestMatchesFirst($string, $replacements = [])
	{
		if( !empty($replacements) ) {
			// Sort the replacements array by key length in desc order (so that the longest strings are replaced first).
			$keys = array_map('strlen', array_keys($replacements));
			array_multisort($keys, SORT_DESC, $replacements);
			$string = str_replace(array_keys($replacements), array_values($replacements), $string);
		}

		return $string;
	}

	/**
	 * Rewrites/Replaces any ASSETS_REGEX-matching urls in a string.
	 * Replacements are performed in a `longest-match-replaced-first` way.
	 *
	 * @param string $code CSS code.
	 *
	 * @return string
	 */
	public function replaceUrls($code = '')
	{
		$replacements = [];

		preg_match_all(self::ASSETS_REGEX, $code, $url_src_matches);
		if( is_array($url_src_matches) && !empty($url_src_matches) ) {
			foreach($url_src_matches[1] as $count => $original_url) {
				// Removes quotes and other cruft.
				$url = trim($original_url, " \t\n\r\0\x0B\"'");

				// Prepare replacements array.
				$replacements[$url_src_matches[1][$count]] = str_replace($original_url, $url, $url_src_matches[1][$count]);
			}
		}

		$code = self::replaceLongestMatchesFirst($code, $replacements);

		return $code;
	}

	/**
	 * "Hides" @font-face declarations by replacing them with `%%FONTFACE%%` markers.
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public function hideFontface($code)
	{
		// Proceed only if @font-face declarations exist within $code.
		preg_match_all(self::FONT_FACE_REGEX, $code, $fontfaces);
		if( isset($fontfaces[0]) ) {

			foreach($fontfaces[0] as $full_match) {
				// Keep original match so we can search/replace it.
				$match_search = $full_match;

				// Replace declaration with its base64 encoded string.
				$replacement = self::buildMarker('FONTFACE', $full_match);
				$code = str_replace($match_search, $replacement, $code);
			}
		}

		return $code;
	}

	/**
	 * Restores original @font-face declarations that have been "hidden"
	 * using `hideFontface()`.
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public function restoreFontface($code)
	{
		return $this->restoreMarkedContent('FONTFACE', $code);
	}

	/**
	 * Re-write (and/or inline) referenced assets.
	 *
	 * @param $code
	 *
	 * @return string
	 */
	public function rewriteAssets($code)
	{
		// Handle @font-face rules by hiding and processing them separately.
		$code = $this->hideFontface($code);

		/**
		 * TODO/FIXME:
		 * Certain code parts below are kind-of repeated now in `replaceUrls()`, which is not ideal.
		 * There is maybe a way to separate/refactor things and then be able to keep
		 * the ASSETS_REGEX rewriting/handling logic in a single place (along with removing quotes/cruft from matched urls).
		 * See comments in `replaceUrls()` regarding this. The idea is to extract the inlining
		 * logic out (which is the only real difference between replaceUrls() and the code below), but still
		 * achieve identical results as before.
		 */

		// Re-write (and/or inline) URLs
		$url_src_matches = [];
		$imgreplace = [];
		// Matches and captures anything specified within the literal `url()` and excludes those containing data: URIs.
		preg_match_all(self::ASSETS_REGEX, $code, $url_src_matches);
		if( is_array($url_src_matches) && !empty($url_src_matches) ) {
			foreach($url_src_matches[1] as $count => $original_url) {
				// Removes quotes and other cruft.
				$url = trim($original_url, " \t\n\r\0\x0B\"'");

				// If datauri inlining is turned on, do it.
				if( $this->datauris ) {
					$iurl = $url;
					if( false !== strpos($iurl, '?') ) {
						$iurl = strtok($iurl, '?');
					}

					$ipath = $this->getpath($iurl);

					$excluded = $this->checkDatauriExcludeList($ipath);
					if( !$excluded ) {
						$is_datauri_candidate = $this->isDatauriCandidate($ipath);
						if( $is_datauri_candidate ) {
							$datauri = $this->buildOrGetDatauriImage($ipath);
							//$base64data  = $datauri['base64data'];
							// Add it to the list for replacement.
							$imgreplace[$url_src_matches[1][$count]] = str_replace($original_url, $datauri['full'], $url_src_matches[1][$count]);
						}
					}
				}

				$imgreplace[$url_src_matches[1][$count]] = str_replace($original_url, $url, $url_src_matches[1][$count]);
			}
		}

		$code = self::replaceLongestMatchesFirst($code, $imgreplace);

		// Replace back font-face markers with actual font-face declarations.
		$code = $this->restoreFontface($code);

		return $code;
	}

	/**
	 * Joins and optimizes CSS.
	 *
	 * @return bool
	 */
	public function minify()
	{
		foreach($this->css as $group) {
			list($media, $css) = $group;
			if( preg_match('#^INLINE;#', $css) ) {
				// <style>.
				$css = preg_replace('#^INLINE;#', '', $css);
				$css = self::fixUrls(ABSPATH . 'index.php', $css); // ABSPATH already contains a trailing slash.
				$tmpstyle = apply_filters('wmac_css_individual_style', $css, '');
				if( has_filter('wmac_css_individual_style') && !empty($tmpstyle) ) {
					$css = $tmpstyle;
					$this->alreadyminified = true;
				}
			} else {
				// <link>
				if( false !== $css && file_exists($css) && is_readable($css) ) {
					$cssPath = $css;
					$css = self::fixUrls($cssPath, file_get_contents($cssPath));
					$css = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $css);
					$tmpstyle = apply_filters('wmac_css_individual_style', $css, $cssPath);
					if( has_filter('wmac_css_individual_style') && !empty($tmpstyle) ) {
						$css = $tmpstyle;
						$this->alreadyminified = true;
					} else if( $this->canInjectLate($cssPath, $css) ) {
						$css = self::buildInjectlaterMarker($cssPath, md5($css));
					}
				} else {
					// Couldn't read CSS. Maybe getpath isn't working?
					$css = '';
				}
			}

			foreach($media as $elem) {
				if( !empty($css) ) {
					if( !isset($this->csscode[$elem]) ) {
						$this->csscode[$elem] = '';
					}
					$this->csscode[$elem] .= "\n/*FILESTART*/" . $css;
				}
			}
		}

		// Check for duplicate code.
		$md5list = [];
		$tmpcss = $this->csscode;
		foreach($tmpcss as $media => $code) {
			$md5sum = md5($code);
			$medianame = $media;
			foreach($md5list as $med => $sum) {
				// If same code.
				if( $sum === $md5sum ) {
					// Add the merged code.
					$medianame = $med . ', ' . $media;
					$this->csscode[$medianame] = $code;
					$md5list[$medianame] = $md5list[$med];
					unset($this->csscode[$med], $this->csscode[$media], $md5list[$med]);
				}
			}
			$md5list[$medianame] = $md5sum;
		}
		unset($tmpcss);

		// Manage @imports, while is for recursive import management.
		foreach($this->csscode as &$thiscss) {
			// Flag to trigger import reconstitution and var to hold external imports.
			$fiximports = false;
			$external_imports = '';

			// remove comments to avoid importing commented-out imports.
			$thiscss_nocomments = preg_replace('#/\*.*\*/#Us', '', $thiscss);
			while( preg_match_all('#@import +(?:url)?(?:(?:\((["\']?)(?:[^"\')]+)\1\)|(["\'])(?:[^"\']+)\2)(?:[^,;"\']+(?:,[^,;"\']+)*)?)(?:;)#mi', $thiscss_nocomments, $matches) ) {
				foreach($matches[0] as $import) {
					if( $this->isremovable($import, $this->cssremovables) ) {
						$thiscss = str_replace($import, '', $thiscss);
						$import_ok = true;
					} else {
						$url = trim(preg_replace('#^.*((?:https?:|ftp:)?//.*\.css).*$#', '$1', trim($import)), " \t\n\r\0\x0B\"'");
						$path = $this->getpath($url);
						$import_ok = false;
						if( file_exists($path) && is_readable($path) ) {
							$code = addcslashes(self::fixUrls($path, file_get_contents($path)), "\\");
							$code = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $code);
							$tmpstyle = apply_filters('wmac_css_individual_style', $code, '');
							if( has_filter('wmac_css_individual_style') && !empty($tmpstyle) ) {
								$code = $tmpstyle;
								$this->alreadyminified = true;
							} else if( $this->canInjectLate($path, $code) ) {
								$code = self::buildInjectlaterMarker($path, md5($code));
							}

							if( !empty($code) ) {
								$tmp_thiscss = preg_replace('#(/\*FILESTART\*/.*)' . preg_quote($import, '#') . '#Us', '/*FILESTART2*/' . $code . '$1', $thiscss);
								if( !empty($tmp_thiscss) ) {
									$thiscss = $tmp_thiscss;
									$import_ok = true;
									unset($tmp_thiscss);
								}
							}
							unset($code);
						}
					}
					if( !$import_ok ) {
						// External imports and general fall-back.
						$external_imports .= $import;

						$thiscss = str_replace($import, '', $thiscss);
						$fiximports = true;
					}
				}
				$thiscss = preg_replace('#/\*FILESTART\*/#', '', $thiscss);
				$thiscss = preg_replace('#/\*FILESTART2\*/#', '/*FILESTART*/', $thiscss);

				// and update $thiscss_nocomments before going into next iteration in while loop.
				$thiscss_nocomments = preg_replace('#/\*.*\*/#Us', '', $thiscss);
			}
			unset($thiscss_nocomments);

			// Add external imports to top of aggregated CSS.
			if( $fiximports ) {
				$thiscss = $external_imports . $thiscss;
			}
		}
		unset($thiscss);

		// $this->csscode has all the uncompressed code now.
		foreach($this->csscode as &$code) {
			// Check for already-minified code.
			$hash = md5($code);
			do_action('wmac_action_css_hash', $hash);
			$ccheck = new WMAC_PluginCache($hash, 'css');
			if( $ccheck->check() ) {
				$code = $ccheck->retrieve();
				$this->hashmap[md5($code)] = $hash;
				continue;
			}
			unset($ccheck);

			// Rewrite and/or inline referenced assets.
			$code = $this->rewriteAssets($code);

			// Minify.
			$code = $this->runMinifierOn($code);

			// Bring back INJECTLATER stuff.
			$code = $this->injectMinified($code);

			// Filter results.
			$tmp_code = apply_filters('wmac_css_after_minify', $code);
			if( !empty($tmp_code) ) {
				$code = $tmp_code;
				unset($tmp_code);
			}

			$this->hashmap[md5($code)] = $hash;
		}

		unset($code);

		return true;
	}

	/**
	 * @param $code
	 *
	 * @return string
	 */
	public function runMinifierOn($code)
	{
		if( !$this->alreadyminified ) {
			$do_minify = apply_filters('wmac_css_do_minify', true);

			if( $do_minify ) {
				$cssmin = new WMAC_PluginCSSmin();
				$tmp_code = trim($cssmin->run($code));

				if( !empty($tmp_code) ) {
					$code = $tmp_code;
					unset($tmp_code);
				}
			}
		}

		return $code;
	}

	/**
	 * Caches the CSS in uncompressed, deflated and gzipped form.
	 */
	public function cache()
	{
		// CSS cache.
		foreach($this->csscode as $media => $code) {
			$md5 = $this->hashmap[md5($code)];
			$cache = new WMAC_PluginCache($md5, 'css');
			if( !$cache->check() ) {
				// Cache our code.
				$cache->cache($code, 'text/css');
			}
			$this->url[$media] = WMAC_PluginCache::getCacheUrl() . $cache->getname();
		}
	}

	/**
	 * Returns the content.
	 *
	 * @return string
	 */
	public function getContent()
	{
		// restore IE hacks.
		$this->content = $this->restoreIehacks($this->content);

		// restore comments.
		$this->content = $this->restoreComments($this->content);

		// restore (no)script.
		$this->content = $this->restoreMarkedContent('SCRIPT', $this->content);

		// Restore noptimize.
		$this->content = $this->restoreNoptimize($this->content);

		$preloadCssBlock = '';
		$noScriptCssBlock = '';

		// Restore the full content.
		if( !empty($this->restofcontent) ) {
			$this->content .= $this->restofcontent;
			$this->restofcontent = '';
		}

		// Inject the new stylesheets.
		$replaceTag = ['<title', 'before'];
		$replaceTag = apply_filters('wmac_filter_css_replacetag', $replaceTag, $this->content);

		if( $this->inline ) {
			foreach($this->csscode as $media => $code) {
				$this->injectInHtml('<style type="text/css" media="' . $media . '">' . $code . '</style>', $replaceTag);
			}
		} else {
			if( $this->defer ) {
				$preloadCssBlock = '';
				$noScriptCssBlock = "<noscript id=\"aonoscrcss\">";
			}

			foreach($this->url as $media => $url) {
				// Add the stylesheet either deferred (import at bottom) or normal links in head.
				if( $this->defer ) {
					$preloadOnLoad = $this->getAoCssPreloadOnload();

					$preloadCssBlock .= '<link rel="preload" as="style" media="' . $media . '" href="' . $url . '" onload="' . $preloadOnLoad . '" />';
					$noScriptCssBlock .= '<link type="text/css" media="' . $media . '" href="' . $url . '" rel="stylesheet" />';
				} else {
					if( strlen($this->csscode[$media]) > $this->cssinlinesize ) {
						$this->injectInHtml('<link type="text/css" media="' . $media . '" href="' . $url . '" rel="stylesheet" />', $replaceTag);
					} else if( strlen($this->csscode[$media]) > 0 ) {
						$this->injectInHtml('<style type="text/css" media="' . $media . '">' . $this->csscode[$media] . '</style>', $replaceTag);
					}
				}
			}

			if( $this->defer ) {
				$preload_polyfill = $this->getAoCssPreloadPolyfill();
				$noScriptCssBlock .= '</noscript>';
				$this->injectInHtml($preloadCssBlock . $noScriptCssBlock, $replaceTag);

				// Adds preload polyfill at end of body tag.
				$this->injectInHtml(apply_filters('wmac_css_preload_polyfill', $preload_polyfill), [
					'</body>',
					'before'
				]);
			}
		}

		if( !empty($this->css_critical_style) ) {
			$this->injectInHtml("<style type='text/css'>{$this->css_critical_style}</style>", $replaceTag);
		}

		// Return the modified stylesheet.
		return $this->content;
	}

	/**
	 * @param $file
	 * @param $code
	 *
	 * @return mixed|string
	 */
	static function fixUrls($file, $code)
	{
		// Switch all imports to the url() syntax.
		$code = preg_replace('#@import ("|\')(.+?)\.css.*?("|\')#', '@import url("${2}.css")', $code);

		if( preg_match_all(self::ASSETS_REGEX, $code, $matches) ) {
			$file = str_replace(WMAC_ROOT_DIR, '/', $file);
			/**
			 * rollback as per https://github.com/futtta/autoptimize/issues/94
			 * $file = str_replace( WMAC_WP_CONTENT_NAME, '', $file );
			 */
			$dir = dirname($file); // Like /themes/expound/css.

			/**
			 * $dir should not contain backslashes, since it's used to replace
			 * urls, but it can contain them when running on Windows because
			 * fixUrls() is sometimes called with `ABSPATH . 'index.php'`
			 */
			$dir = str_replace('\\', '/', $dir);
			unset($file); // not used below at all.

			$replace = [];
			foreach($matches[1] as $k => $url) {
				// Remove quotes.
				$url = trim($url, " \t\n\r\0\x0B\"'");
				$noQurl = trim($url, "\"'");
				if( $url !== $noQurl ) {
					$removedQuotes = true;
				} else {
					$removedQuotes = false;
				}

				if( '' === $noQurl ) {
					continue;
				}

				$url = $noQurl;

				if( '/' === $url[0] || preg_match('#^(https?://|ftp://|data:)#i', $url) ) {
					// URL is protocol-relative, host-relative or something we don't touch.
					continue;
				} else {
					// Relative URL.
					/**
					 * rollback as per https://github.com/futtta/autoptimize/issues/94
					 * $newurl = preg_replace( '/https?:/', '', str_replace( ' ', '%20', WMAC_PluginMain::getContentUrl() . str_replace( '//', '/', $dir . '/' . $url ) ) );
					 */
					$newurl = preg_replace('/https?:/', '', str_replace(' ', '%20', WMAC_WP_ROOT_URL . str_replace('//', '/', $dir . '/' . $url)));
					$newurl = apply_filters('wbcr/mac/css_fixurl_newurl', $newurl);

					/**
					 * Hash the url + whatever was behind potentially for replacement
					 * We must do this, or different css classes referencing the same bg image (but
					 * different parts of it, say, in sprites and such) loose their stuff...
					 */
					$hash = md5($url . $matches[2][$k]);
					$code = str_replace($matches[0][$k], $hash, $code);

					if( $removedQuotes ) {
						$replace[$hash] = "url('" . $newurl . "')" . $matches[2][$k];
					} else {
						$replace[$hash] = 'url(' . $newurl . ')' . $matches[2][$k];
					}
				}
			}

			$code = self::replaceLongestMatchesFirst($code, $replace);
		}

		return $code;
	}

	/**
	 * @param $tag
	 *
	 * @return bool
	 */
	private function isMovable($tag)
	{
		if( !$this->aggregate ) {
			return false;
		}

		if( !empty($this->whitelist) ) {
			foreach($this->whitelist as $match) {
				if( false !== strpos($tag, $match) ) {
					return true;
				}
			}

			// no match with whitelist.
			return false;
		} else {
			if( is_array($this->dontmove) && !empty($this->dontmove) ) {
				foreach($this->dontmove as $match) {
					if( false !== strpos($tag, $match) ) {
						// Matched something.
						return false;
					}
				}
			}

			// If we're here it's safe to move.
			return true;
		}
	}

	/**
	 * @param $cssPath
	 * @param $css
	 *
	 * @return bool
	 */
	private function canInjectLate($cssPath, $css)
	{
		$consider_minified_array = apply_filters('wmac_filter_css_consider_minified', false, $cssPath);
		if( true !== $this->inject_min_late ) {
			// late-inject turned off.
			return false;
		} else if( (false === strpos($cssPath, 'min.css')) && (str_replace($consider_minified_array, '', $cssPath) === $cssPath) ) {
			// file not minified based on filename & filter.
			return false;
		} else if( false !== strpos($css, '@import') ) {
			// can't late-inject files with imports as those need to be aggregated.
			return false;
		} else if( ($this->datauris == true) && preg_match('#background[^;}]*url\(#Ui', $css) ) {
			// don't late-inject CSS with images if image inlining is on.
			return false;
		} else {
			// phew, all is safe, we can late-inject.
			return true;
		}
	}

	/**
	 * Minifies a single local css file
	 * and returns its (cached) url.
	 *
	 * @param string $filepath Filepath.
	 * @param bool $cache_miss Optional. Force a cache miss. Default false.
	 *
	 * @return bool|string Url pointing to the minified css file or false.
	 */
	public function minifySingle($filepath, $cache_miss = false)
	{
		$contents = $this->prepareMinifySingle($filepath);

		if( empty($contents) ) {
			return false;
		}

		// Check cache.
		$hash = 'single_' . md5($contents);
		$cache = new WMAC_PluginCache($hash, 'css');

		// If not in cache already, minify...
		if( !$cache->check() || $cache_miss ) {
			// Fixurls...
			$contents = self::fixUrls($filepath, $contents);
			// replace any referenced assets if needed...
			$contents = $this->replaceUrls($contents);
			// Now minify...
			$cssmin = new WMAC_PluginCSSmin();
			$contents = trim($cssmin->run($contents));
			// Store in cache.
			$cache->cache($contents, 'text/css');
		}

		$url = $this->buildMinifySingleUrl($cache);

		return $url;
	}

	/**
	 * Returns preload JS onload handler.
	 *
	 * @return string
	 */
	public function getAoCssPreloadOnload()
	{
		$preload_onload = apply_filters('wmac_filter_css_preload_onload', "this.onload=null;this.rel='stylesheet'");

		return $preload_onload;
	}

	/**
	 * Returns preload polyfill JS.
	 *
	 * @return string
	 */
	public function getAoCssPreloadPolyfill()
	{
		$preload_poly = apply_filters('wmac_filter_css_preload_polyfill', '<script data-cfasync=\'false\'>!function(t){"use strict";t.loadCSS||(t.loadCSS=function(){});var e=loadCSS.relpreload={};if(e.support=function(){var e;try{e=t.document.createElement("link").relList.supports("preload")}catch(t){e=!1}return function(){return e}}(),e.bindMediaToggle=function(t){function e(){t.media=a}var a=t.media||"all";t.addEventListener?t.addEventListener("load",e):t.attachEvent&&t.attachEvent("onload",e),setTimeout(function(){t.rel="stylesheet",t.media="only x"}),setTimeout(e,3e3)},e.poly=function(){if(!e.support())for(var a=t.document.getElementsByTagName("link"),n=0;n<a.length;n++){var o=a[n];"preload"!==o.rel||"style"!==o.getAttribute("as")||o.getAttribute("data-loadcss")||(o.setAttribute("data-loadcss",!0),e.bindMediaToggle(o))}},!e.support()){e.poly();var a=t.setInterval(e.poly,500);t.addEventListener?t.addEventListener("load",function(){e.poly(),t.clearInterval(a)}):t.attachEvent&&t.attachEvent("onload",function(){e.poly(),t.clearInterval(a)})}"undefined"!=typeof exports?exports.loadCSS=loadCSS:t.loadCSS=loadCSS}("undefined"!=typeof global?global:this);</script>');

		return $preload_poly;
	}

	/**
	 * Returns whether we're doing aggregation or not.
	 *
	 * @return bool
	 */
	public function aggregating()
	{
		return $this->aggregate;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param $options
	 */
	public function replaceOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
		$this->$name = $value;
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function getOption($name)
	{
		return $this->options[$name];
	}
}
