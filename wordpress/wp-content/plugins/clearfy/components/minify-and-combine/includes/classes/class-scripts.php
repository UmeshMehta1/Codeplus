<?php
/**
 * Operations with scripts
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WMAC_PluginScripts
 */
class WMAC_PluginScripts extends WMAC_PluginBase {

	private $scripts = [];
	private $move = [
		'first' => [],
		'last'  => []
	];

	private $dontmove = [
		'document.write',
		'html5.js',
		'show_ads.js',
		'google_ad',
		'histats.com/js',
		'statcounter.com/counter/counter.js',
		'ws.amazon.com/widgets',
		'media.fastclick.net',
		'/ads/',
		'comment-form-quicktags/quicktags.php',
		'edToolbar',
		'intensedebate.com',
		'scripts.chitika.net/',
		'_gaq.push',
		'jotform.com/',
		'admin-bar.min.js',
		'GoogleAnalyticsObject',
		'plupload.full.min.js',
		'syntaxhighlighter',
		'adsbygoogle',
		'gist.github.com',
		'_stq',
		'nonce',
		'post_id',
		'data-noptimize',
		'logHuman'
	];
	private $domove = [
		'gaJsHost',
		'load_cmc',
		'jd.gallery.transitions.js',
		'swfobject.embedSWF(',
		'tiny_mce.js',
		'tinyMCEPreInit.go'
	];
	private $domovelast = [
		'addthis.com',
		'/afsonline/show_afs_search.js',
		'disqus.js',
		'networkedblogs.com/getnetworkwidget',
		'infolinks.com/js/',
		'jd.gallery.js.php',
		'jd.gallery.transitions.js',
		'swfobject.embedSWF(',
		'linkwithin.com/widget.js',
		'tiny_mce.js',
		'tinyMCEPreInit.go'
	];

	private $aggregate = true;
	private $trycatch = false;
	private $alreadyminified = false;
	private $forcehead = true;
	private $include_inline = false;
	private $jscode = '';
	private $url = '';
	private $md5hash = '';
	private $whitelist = [];
	private $jsremovables = [];
	private $inject_min_late = '';

	/**
	 * Reads the page and collects script tags
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function read( $options ) {
		$noptimizeJS = apply_filters( 'wmac_filter_js_noptimize', false, $this->content );
		if ( $noptimizeJS ) {
			return false;
		}

		// only optimize known good JS?
		$whitelistJS = apply_filters( 'wmac_filter_js_whitelist', '', $this->content );
		if ( ! empty( $whitelistJS ) ) {
			$this->whitelist = array_filter( array_map( 'trim', explode( ',', $whitelistJS ) ) );
		}

		// is there JS we should simply remove
		$removableJS = apply_filters( 'wmac_filter_js_removables', '', $this->content );
		if ( ! empty( $removableJS ) ) {
			$this->jsremovables = array_filter( array_map( 'trim', explode( ',', $removableJS ) ) );
		}

		// Determine whether we're doing JS-files aggregation or not.
		if ( ! $options['aggregate'] ) {
			$this->aggregate = false;
		}
		// Returning true for "dontaggregate" turns off aggregation.
		if ( $this->aggregate && apply_filters( 'wmac_filter_js_dontaggregate', false ) ) {
			$this->aggregate = false;
		}

		// include inline?
		if ( apply_filters( 'wmac_js_include_inline', $options['include_inline'] ) ) {
			$this->include_inline = true;
		}

		// filter to "late inject minified JS", default to true for now (it is faster)
		$this->inject_min_late = apply_filters( 'wmac_filter_js_inject_min_late', true );

		// filters to override hardcoded do(nt)move(last) array contents (array in, array out!)
		$this->dontmove   = apply_filters( 'wmac_filter_js_dontmove', $this->dontmove );
		$this->domovelast = apply_filters( 'wmac_filter_js_movelast', $this->domovelast );
		$this->domove     = apply_filters( 'wmac_filter_js_domove', $this->domove );

		// get extra exclusions settings or filter
		$excludeJS = $options['js_exclude'];
		$excludeJS = apply_filters( 'wmac_filter_js_exclude', $excludeJS, $this->content );

		if ( '' !== $excludeJS ) {
			if ( is_array( $excludeJS ) ) {
				if ( ( $removeKeys = array_keys( $excludeJS, 'remove' ) ) !== false ) {
					foreach ( $removeKeys as $removeKey ) {
						unset( $excludeJS[ $removeKey ] );
						$this->jsremovables[] = $removeKey;
					}
				}
				$exclJSArr = array_keys( $excludeJS );
			} else {
				$exclJSArr = array_filter( array_map( 'trim', explode( ',', $excludeJS ) ) );
			}
			$this->dontmove = array_merge( $exclJSArr, $this->dontmove );
		}

		// Should we add try-catch?
		if ( $options['trycatch'] ) {
			$this->trycatch = true;
		}

		// force js in head?
		if ( $options['forcehead'] ) {
			$this->forcehead = true;
		} else {
			$this->forcehead = false;
		}

		$this->forcehead = apply_filters( 'wmac_filter_js_forcehead', $this->forcehead );

		// noptimize me
		$this->content = $this->hideNoptimize( $this->content );

		// Save IE hacks
		$this->content = $this->hideIEhacks( $this->content );

		// comments
		$this->content = $this->hideComments( $this->content );

		// Get script files
		if ( preg_match_all( '#<script.*</script>#Usmi', $this->content, $matches ) ) {
			foreach ( $matches[0] as $tag ) {
				// only consider script aggregation for types whitelisted in should_aggregate-function
				$should_aggregate = $this->shouldAggregate( $tag );
				if ( ! $should_aggregate ) {
					$tag = '';
					continue;
				}

				if ( preg_match( '#<script[^>]*src=("|\')([^>]*)("|\')#Usmi', $tag, $source ) ) {
					// non-inline script
					if ( $this->isremovable( $tag, $this->jsremovables ) ) {
						$this->content = str_replace( $tag, '', $this->content );
						continue;
					}

					$origTag = null;
					$url     = current( explode( '?', $source[2], 2 ) );
					$path    = $this->getPath( $url );
					if ( false !== $path && preg_match( '#\.js$#', $path ) && $this->isMergeable( $tag ) ) {
						// ok to optimize, add to array
						$this->scripts[] = $path;
					} else {
						$origTag = $tag;
						$newTag  = $tag;

						// non-mergeable script (excluded or dynamic or external)
						if ( is_array( $excludeJS ) ) {
							// should we add flags?
							foreach ( $excludeJS as $exclTag => $exclFlags ) {
								if ( false !== strpos( $origTag, $exclTag ) && in_array( $exclFlags, [
										'async',
										'defer'
									] ) ) {
									$newTag = str_replace( '<script ', '<script ' . $exclFlags . ' ', $newTag );
								}
							}
						}

						// Should we minify the non-aggregated script?
						if ( $path && apply_filters( 'wmac_filter_js_minify_excluded', true, $url ) ) {
							$minified_url = $this->minifySingle( $path );
							// replace orig URL with minified URL from cache if so
							if ( ! empty( $minified_url ) ) {
								$newTag = str_replace( $url, $minified_url, $newTag );
							}

							// remove querystring from URL in newTag
							if ( ! empty( $explUrl[1] ) ) {
								$newTag = str_replace( '?' . $explUrl[1], '', $newTag );
							}
						}

						if ( $this->isMovable( $newTag ) ) {
							// can be moved, flags and all
							if ( $this->moveToLast( $newTag ) ) {
								$this->move['last'][] = $newTag;
							} else {
								$this->move['first'][] = $newTag;
							}
						} else {
							// cannot be moved, so if flag was added re-inject altered tag immediately
							if ( $origTag !== $newTag ) {
								$this->content = str_replace( $origTag, $newTag, $this->content );
							}
							// and forget about the $tag (not to be touched any more)
							$tag = '';
						}
					}
				} else {
					// Inline script
					if ( $this->isremovable( $tag, $this->jsremovables ) ) {
						$this->content = str_replace( $tag, '', $this->content );
						continue;
					}

					// unhide comments, as javascript may be wrapped in comment-tags for old times' sake
					$tag = $this->restoreComments( $tag );
					if ( $this->isMergeable( $tag ) && $this->include_inline ) {
						preg_match( '#<script.*>(.*)</script>#Usmi', $tag, $code );
						$code            = preg_replace( '#.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*#sm', '$1', $code[1] );
						$code            = preg_replace( '/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $code );
						$this->scripts[] = 'INLINE;' . $code;
					} else {
						// Can we move this?
						$wmac_js_moveable = apply_filters( 'wmac_js_moveable', '', $tag );
						if ( $this->isMovable( $tag ) || '' !== $wmac_js_moveable ) {
							if ( $this->moveToLast( $tag ) || 'last' === $wmac_js_moveable ) {
								$this->move['last'][] = $tag;
							} else {
								$this->move['first'][] = $tag;
							}
						} else {
							// We shouldn't touch this
							$tag = '';
						}
					}
					// Re-hide comments to be able to do the removal based on tag from $this->content
					$tag = $this->hideComments( $tag );
				}

				//Remove the original script tag
				$this->content = str_replace( $tag, '', $this->content );
			}

			return true;
		}

		return false;
	}

	/**
	 * Determines wheter a certain `<script>` $tag should be aggregated or not.
	 *
	 * We consider these as "aggregation-safe" currently:
	 * - script tags without a `type` attribute
	 * - script tags with these `type` attribute values: `text/javascript`, `text/ecmascript`, `application/javascript`,
	 * and `application/ecmascript`
	 *
	 * Everything else should return false.
	 *
	 * @link https://developer.mozilla.org/en/docs/Web/HTML/Element/script#attr-type
	 *
	 * @param string $tag
	 *
	 * @return bool
	 */
	public function shouldAggregate( $tag ) {
		// We're only interested in the type attribute of the <script> tag itself, not any possible
		// inline code that might just contain the 'type=' string...
		$tag_parts = [];
		preg_match( '#<(script[^>]*)>#i', $tag, $tag_parts );
		$tag_without_contents = null;
		if ( ! empty( $tag_parts[1] ) ) {
			$tag_without_contents = $tag_parts[1];
		}

		$has_type = ( strpos( $tag_without_contents, 'type' ) !== false );

		$type_valid = false;
		if ( $has_type ) {
			$type_valid = (bool) preg_match( '/type\s*=\s*[\'"]?(?:text|application)\/(?:javascript|ecmascript)[\'"]?/i', $tag_without_contents );
		}

		$should_aggregate = false;
		if ( ! $has_type || $type_valid ) {
			$should_aggregate = true;
		}

		return $should_aggregate;
	}

	/**
	 * Joins and optimizes JS
	 *
	 * @return bool
	 */
	public function minify() {
		foreach ( $this->scripts as $script ) {
			// TODO/FIXME: some duplicate code here, can be reduced/simplified
			if ( preg_match( '#^INLINE;#', $script ) ) {
				// Inline script
				$script = preg_replace( '#^INLINE;#', '', $script );
				$script = rtrim( $script, ";\n\t\r" ) . ';';
				// Add try-catch?
				if ( $this->trycatch ) {
					$script = 'try{' . $script . '}catch(e){}';
				}
				$tmpscript = apply_filters( 'wmac_js_individual_script', $script, '' );
				if ( has_filter( 'wmac_js_individual_script' ) && ! empty( $tmpscript ) ) {
					$script                = $tmpscript;
					$this->alreadyminified = true;
				}
				$this->jscode .= "\n" . $script;
			} else {
				// External script
				if ( false !== $script && file_exists( $script ) && is_readable( $script ) ) {
					$scriptsrc = file_get_contents( $script );
					$scriptsrc = preg_replace( '/\x{EF}\x{BB}\x{BF}/', '', $scriptsrc );
					$scriptsrc = rtrim( $scriptsrc, ";\n\t\r" ) . ';';
					// Add try-catch?
					if ( $this->trycatch ) {
						$scriptsrc = 'try{' . $scriptsrc . '}catch(e){}';
					}
					$tmpscriptsrc = apply_filters( 'wmac_js_individual_script', $scriptsrc, $script );
					if ( has_filter( 'wmac_js_individual_script' ) && ! empty( $tmpscriptsrc ) ) {
						$scriptsrc             = $tmpscriptsrc;
						$this->alreadyminified = true;
					} else if ( $this->canInjectLate( $script ) ) {
						$scriptsrc = self::buildInjectlaterMarker( $script, md5( $scriptsrc ) );
					}
					$this->jscode .= "\n" . $scriptsrc;
				}/*else{
                    //Couldn't read JS. Maybe getPath isn't working?
                }*/
			}
		}

		// Check for already-minified code
		$this->md5hash = md5( $this->jscode );
		$ccheck        = new WMAC_PluginCache( $this->md5hash, 'js' );
		if ( $ccheck->check() ) {
			$this->jscode = $ccheck->retrieve();

			return true;
		}
		unset( $ccheck );

		// $this->jscode has all the uncompressed code now.
		if ( true !== $this->alreadyminified ) {
			if ( apply_filters( 'wmac_js_do_minify', true ) ) {
				$tmp_jscode = trim( WMAC\JSMin::minify( $this->jscode ) );
				if ( ! empty( $tmp_jscode ) ) {
					$this->jscode = $tmp_jscode;
					unset( $tmp_jscode );
				}
				$this->jscode = $this->injectMinified( $this->jscode );
				$this->jscode = apply_filters( 'wmac_js_after_minify', $this->jscode );

				return true;
			} else {
				$this->jscode = $this->injectMinified( $this->jscode );

				return false;
			}
		}

		$this->jscode = apply_filters( 'wmac_js_after_minify', $this->jscode );

		return true;
	}

	/**
	 * Caches the JS in uncompressed, deflated and gzipped form.
	 */
	public function cache() {
		$cache = new WMAC_PluginCache( $this->md5hash, 'js' );
		if ( ! $cache->check() ) {
			// Cache our code
			$cache->cache( $this->jscode, 'text/javascript' );
		}
		$this->url = WMAC_PluginCache::getCacheUrl() . $cache->getname();
	}

	/**
	 * Returns the content
	 *
	 * @return string
	 */
	public function getContent() {
		// Add the scripts taking forcehead/ deferred (default) into account
		if ( $this->forcehead ) {
			$replaceTag = [ '</head>', 'before' ];
			$defer      = '';
		} else {
			$replaceTag = [ '</body>', 'before' ];
			$defer      = 'defer ';
		}

		$defer = apply_filters( 'wmac_filter_js_defer', $defer );

		$bodyreplacementpayload = '<script type="text/javascript" ' . $defer . 'src="' . $this->url . '"></script>';
		$bodyreplacementpayload = apply_filters( 'wmac_filter_js_bodyreplacementpayload', $bodyreplacementpayload );

		$bodyreplacement = implode( '', $this->move['first'] );
		$bodyreplacement .= $bodyreplacementpayload;
		$bodyreplacement .= implode( '', $this->move['last'] );

		$replaceTag = apply_filters( 'wmac_filter_js_replacetag', $replaceTag );

		if ( strlen( $this->jscode ) > 0 ) {
			$this->injectInHtml( $bodyreplacement, $replaceTag );
		}

		// Restore comments.
		$this->content = $this->restoreComments( $this->content );

		// Restore IE hacks.
		$this->content = $this->restoreIEhacks( $this->content );

		// Restore noptimize.
		$this->content = $this->restoreNoptimize( $this->content );

		// Return the modified HTML.
		return $this->content;
	}

	/**
	 * Checks against the white- and blacklists
	 *
	 * @param $tag
	 *
	 * @return bool
	 */
	private function isMergeable( $tag ) {
		if ( ! $this->aggregate ) {
			return false;
		}

		if ( ! empty( $this->whitelist ) ) {
			foreach ( $this->whitelist as $match ) {
				if ( false !== strpos( $tag, $match ) ) {
					return true;
				}
			}

			// no match with whitelist
			return false;
		} else {
			foreach ( $this->domove as $match ) {
				if ( false !== strpos( $tag, $match ) ) {
					// Matched something
					return false;
				}
			}

			if ( $this->moveToLast( $tag ) ) {
				return false;
			}

			foreach ( $this->dontmove as $match ) {
				if ( false !== strpos( $tag, $match ) ) {
					// Matched something
					return false;
				}
			}

			// If we're here it's safe to merge
			return true;
		}
	}

	/**
	 * Checks agains the blacklist
	 *
	 * @param $tag
	 *
	 * @return bool
	 */
	private function isMovable( $tag ) {
		if ( true !== $this->include_inline || apply_filters( 'wmac_filter_js_unmovable', true ) ) {
			return false;
		}

		foreach ( $this->domove as $match ) {
			if ( false !== strpos( $tag, $match ) ) {
				// Matched something
				return true;
			}
		}

		if ( $this->moveToLast( $tag ) ) {
			return true;
		}

		foreach ( $this->dontmove as $match ) {
			if ( false !== strpos( $tag, $match ) ) {
				// Matched something
				return false;
			}
		}

		// If we're here it's safe to move
		return true;
	}

	/**
	 * @param $tag
	 *
	 * @return bool
	 */
	private function moveToLast( $tag ) {
		foreach ( $this->domovelast as $match ) {
			if ( false !== strpos( $tag, $match ) ) {
				// Matched, return true
				return true;
			}
		}

		// Should be in 'first'
		return false;
	}

	/**
	 * Determines wheter a <script> $tag can be excluded from minification (as already minified) based on:
	 * - inject_min_late being active
	 * - filename ending in `min.js`
	 * - filename matching `js/jquery/jquery.js` (wordpress core jquery, is minified)
	 * - filename matching one passed in the consider minified filter
	 *
	 * @param string $jsPath
	 *
	 * @return bool
	 */
	private function canInjectLate( $jsPath ) {
		$consider_minified_array = apply_filters( 'wmac_filter_js_consider_minified', false );
		if ( true !== $this->inject_min_late ) {
			// late-inject turned off
			return false;
		} else if ( ( false === strpos( $jsPath, 'min.js' ) ) && ( false === strpos( $jsPath, 'wp-includes/js/jquery/jquery.js' ) ) && ( str_replace( $consider_minified_array, '', $jsPath ) === $jsPath ) ) {
			// file not minified based on filename & filter
			return false;
		} else {
			// phew, all is safe, we can late-inject
			return true;
		}
	}

	/**
	 * Returns whether we're doing aggregation or not.
	 *
	 * @return bool
	 */
	public function aggregating() {
		return $this->aggregate;
	}

	/**
	 * Minifies a single local js file and returns its (cached) url.
	 *
	 * @param string $filepath     Filepath.
	 * @param bool   $cache_miss   Optional. Force a cache miss. Default false.
	 *
	 * @return bool|string Url pointing to the minified js file or false.
	 */
	public function minifySingle( $filepath, $cache_miss = false ) {
		$contents = $this->prepareMinifySingle( $filepath );

		if ( empty( $contents ) ) {
			return false;
		}

		// Check cache.
		$hash  = 'single_' . md5( $contents );
		$cache = new WMAC_PluginCache( $hash, 'js' );

		// If not in cache already, minify...
		if ( ! $cache->check() || $cache_miss ) {
			$contents = trim( WMAC\JSMin::minify( $contents ) );
			// Store in cache.
			$cache->cache( $contents, 'text/javascript' );
		}

		$url = $this->buildMinifySingleUrl( $cache );

		return $url;
	}
}
