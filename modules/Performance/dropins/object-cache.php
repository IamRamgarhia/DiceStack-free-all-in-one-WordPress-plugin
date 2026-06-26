<?php
/**
 * DICESTACK_OBJECT_CACHE_DROPIN
 *
 * DiceStack persistent object cache drop-in for Redis / Memcached.
 *
 * Fail-safe by design: it always keeps a per-request runtime cache, and if the
 * Redis/Memcached backend is unreachable it simply behaves as a normal
 * non-persistent cache — exactly like WordPress with no drop-in — so the site
 * can never white-screen because the cache server is down.
 *
 * Optional wp-config.php constants:
 *   Redis:     WP_REDIS_HOST, WP_REDIS_PORT, WP_REDIS_PASSWORD, WP_REDIS_DATABASE
 *   Memcached: WP_CACHE_HOST, WP_CACHE_PORT
 *   General:   WP_CACHE_KEY_SALT (namespace, to isolate sites sharing a server)
 *
 * @package DiceStack
 */

defined( 'ABSPATH' ) || exit;

// This drop-in MUST define WordPress's own object-cache API, so these global
// function names cannot be prefixed.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

/**
 * Set up the global cache object.
 *
 * @return void
 */
function wp_cache_init() {
	$GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- drop-in defines the global cache API.

/** @return bool */
function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	return $GLOBALS['wp_object_cache']->add( $key, $data, $group, (int) $expire );
}

/** @return bool[] */
function wp_cache_add_multiple( array $data, $group = '', $expire = 0 ) {
	$result = array();
	foreach ( $data as $key => $value ) {
		$result[ $key ] = wp_cache_add( $key, $value, $group, $expire );
	}
	return $result;
}

/** @return bool */
function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	return $GLOBALS['wp_object_cache']->replace( $key, $data, $group, (int) $expire );
}

/** @return bool */
function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	return $GLOBALS['wp_object_cache']->set( $key, $data, $group, (int) $expire );
}

/** @return bool[] */
function wp_cache_set_multiple( array $data, $group = '', $expire = 0 ) {
	$result = array();
	foreach ( $data as $key => $value ) {
		$result[ $key ] = wp_cache_set( $key, $value, $group, $expire );
	}
	return $result;
}

/** @return mixed */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	return $GLOBALS['wp_object_cache']->get( $key, $group, $force, $found );
}

/** @return array */
function wp_cache_get_multiple( $keys, $group = '', $force = false ) {
	return $GLOBALS['wp_object_cache']->get_multiple( $keys, $group, $force );
}

/** @return bool */
function wp_cache_delete( $key, $group = '' ) {
	return $GLOBALS['wp_object_cache']->delete( $key, $group );
}

/** @return bool[] */
function wp_cache_delete_multiple( array $keys, $group = '' ) {
	$result = array();
	foreach ( $keys as $key ) {
		$result[ $key ] = wp_cache_delete( $key, $group );
	}
	return $result;
}

/** @return int|false */
function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	return $GLOBALS['wp_object_cache']->incr( $key, $offset, $group );
}

/** @return int|false */
function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	return $GLOBALS['wp_object_cache']->decr( $key, $offset, $group );
}

/** @return bool */
function wp_cache_flush() {
	return $GLOBALS['wp_object_cache']->flush();
}

/** @return bool */
function wp_cache_flush_runtime() {
	return $GLOBALS['wp_object_cache']->flush_runtime();
}

/** @return bool */
function wp_cache_flush_group( $group ) {
	return $GLOBALS['wp_object_cache']->flush_group( $group );
}

/** @return bool */
function wp_cache_supports( $feature ) {
	return in_array( $feature, array( 'get_multiple', 'set_multiple', 'add_multiple', 'delete_multiple', 'flush_runtime', 'flush_group' ), true );
}

/** @return bool */
function wp_cache_close() {
	return true;
}

/** @return void */
function wp_cache_add_global_groups( $groups ) {
	$GLOBALS['wp_object_cache']->add_global_groups( $groups );
}

/** @return void */
function wp_cache_add_non_persistent_groups( $groups ) {
	$GLOBALS['wp_object_cache']->add_non_persistent_groups( $groups );
}

/** @return void */
function wp_cache_switch_to_blog( $blog_id ) {
	$GLOBALS['wp_object_cache']->switch_to_blog( $blog_id );
}

/** @return void */
function wp_cache_reset() {
	// Deprecated; runtime flush is the modern equivalent.
	$GLOBALS['wp_object_cache']->flush_runtime();
}

// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

/**
 * The drop-in object cache. Runtime array + optional Redis/Memcached backend.
 */
class WP_Object_Cache {

	/** @var array Runtime (per-request) cache. */
	private $cache = array();
	/** @var string[] Groups shared across all sites on a multisite network. */
	private $global_groups = array();
	/** @var string[] Groups never written to the persistent backend. */
	private $non_persistent = array();
	/** @var string Current blog prefix (multisite). */
	private $blog_prefix = '';
	/** @var bool */
	private $multisite = false;
	/** @var \Redis|\Memcached|null */
	private $backend = null;
	/** @var bool */
	private $connected = false;
	/** @var string '' | 'redis' | 'memcached' */
	private $type = '';
	/** @var string Key namespace. */
	private $prefix = '';
	/** @var int */
	public $cache_hits = 0;
	/** @var int */
	public $cache_misses = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->multisite   = function_exists( 'is_multisite' ) && is_multisite();
		$this->blog_prefix = $this->multisite ? ( get_current_blog_id() . ':' ) : '';
		$salt              = defined( 'WP_CACHE_KEY_SALT' ) ? WP_CACHE_KEY_SALT : ( defined( 'DB_NAME' ) ? DB_NAME : 'wp' );
		$this->prefix      = $salt . ':';
		$this->connect();
	}

	/**
	 * Connect to Redis, then Memcached; otherwise stay non-persistent.
	 *
	 * @return void
	 */
	private function connect() {
		if ( class_exists( 'Redis' ) ) {
			try {
				$redis   = new \Redis();
				$host    = defined( 'WP_REDIS_HOST' ) ? WP_REDIS_HOST : '127.0.0.1';
				$port    = defined( 'WP_REDIS_PORT' ) ? (int) WP_REDIS_PORT : 6379;
				$timeout = 1.0;
				if ( @$redis->connect( $host, $port, $timeout ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					if ( defined( 'WP_REDIS_PASSWORD' ) && WP_REDIS_PASSWORD ) {
						@$redis->auth( WP_REDIS_PASSWORD ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					}
					if ( defined( 'WP_REDIS_DATABASE' ) && WP_REDIS_DATABASE ) {
						@$redis->select( (int) WP_REDIS_DATABASE ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					}
					if ( @$redis->ping() ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						$this->backend   = $redis;
						$this->connected = true;
						$this->type      = 'redis';
						return;
					}
				}
			} catch ( \Throwable $e ) {
				$this->connected = false;
			}
		}

		if ( class_exists( 'Memcached' ) ) {
			try {
				$mc   = new \Memcached();
				$host = defined( 'WP_CACHE_HOST' ) ? WP_CACHE_HOST : '127.0.0.1';
				$port = defined( 'WP_CACHE_PORT' ) ? (int) WP_CACHE_PORT : 11211;
				$mc->addServer( $host, $port );
				$stats = @$mc->getStats(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( is_array( $stats ) && ! empty( $stats ) ) {
					$this->backend   = $mc;
					$this->connected = true;
					$this->type      = 'memcached';
					return;
				}
			} catch ( \Throwable $e ) {
				$this->connected = false;
			}
		}

		$this->connected = false;
	}

	/**
	 * Build the namespaced storage key.
	 *
	 * @param string $key   Item key.
	 * @param string $group Group.
	 * @return string
	 */
	private function build_key( $key, $group ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		$blog = in_array( $group, $this->global_groups, true ) ? '' : $this->blog_prefix;
		return $this->prefix . $blog . $group . ':' . $key;
	}

	/**
	 * Should this group be persisted to the backend?
	 *
	 * @param string $group Group.
	 * @return bool
	 */
	private function persistent( $group ) {
		$group = empty( $group ) ? 'default' : $group;
		return $this->connected && ! in_array( $group, $this->non_persistent, true );
	}

	/**
	 * @return bool
	 */
	public function add( $key, $data, $group = 'default', $expire = 0 ) {
		if ( function_exists( 'wp_suspend_cache_addition' ) && wp_suspend_cache_addition() ) {
			return false;
		}
		$id = $this->build_key( $key, $group );
		if ( isset( $this->cache[ $id ] ) ) {
			return false;
		}
		if ( $this->persistent( $group ) && $this->backend_exists( $id ) ) {
			return false;
		}
		return $this->set( $key, $data, $group, $expire );
	}

	/**
	 * @return bool
	 */
	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		$found = false;
		$this->get( $key, $group, true, $found );
		if ( ! $found ) {
			return false;
		}
		return $this->set( $key, $data, $group, $expire );
	}

	/**
	 * @return bool
	 */
	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		$id = $this->build_key( $key, $group );
		if ( is_object( $data ) ) {
			$data = clone $data;
		}
		$this->cache[ $id ] = $data;
		if ( $this->persistent( $group ) ) {
			$this->backend_set( $id, $data, (int) $expire );
		}
		return true;
	}

	/**
	 * @return mixed
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		$id = $this->build_key( $key, $group );
		if ( ! $force && array_key_exists( $id, $this->cache ) ) {
			$found = true;
			++$this->cache_hits;
			$value = $this->cache[ $id ];
			return is_object( $value ) ? clone $value : $value;
		}
		if ( $this->persistent( $group ) ) {
			$hit   = false;
			$value = $this->backend_get( $id, $hit );
			if ( $hit ) {
				$this->cache[ $id ] = $value;
				$found              = true;
				++$this->cache_hits;
				return is_object( $value ) ? clone $value : $value;
			}
		}
		$found = false;
		++$this->cache_misses;
		return false;
	}

	/**
	 * @return array
	 */
	public function get_multiple( $keys, $group = 'default', $force = false ) {
		$result = array();
		foreach ( (array) $keys as $key ) {
			$result[ $key ] = $this->get( $key, $group, $force );
		}
		return $result;
	}

	/**
	 * @return bool
	 */
	public function delete( $key, $group = 'default' ) {
		$id = $this->build_key( $key, $group );
		unset( $this->cache[ $id ] );
		if ( $this->persistent( $group ) ) {
			$this->backend_delete( $id );
		}
		return true;
	}

	/**
	 * @return int|false
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		$found = false;
		$value = $this->get( $key, $group, true, $found );
		if ( ! $found ) {
			return false;
		}
		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}
		$value += $offset;
		if ( $value < 0 ) {
			$value = 0;
		}
		$this->set( $key, $value, $group );
		return $value;
	}

	/**
	 * @return int|false
	 */
	public function decr( $key, $offset = 1, $group = 'default' ) {
		return $this->incr( $key, -abs( $offset ), $group );
	}

	/**
	 * @return bool
	 */
	public function flush() {
		$this->cache = array();
		if ( $this->connected ) {
			try {
				if ( 'redis' === $this->type ) {
					@$this->backend->flushDB(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				} elseif ( 'memcached' === $this->type ) {
					@$this->backend->flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			} catch ( \Throwable $e ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function flush_runtime() {
		$this->cache = array();
		return true;
	}

	/**
	 * Best-effort group flush (runtime only; backends have no cheap group wipe).
	 *
	 * @param string $group Group.
	 * @return bool
	 */
	public function flush_group( $group ) {
		$needle = $group . ':';
		foreach ( array_keys( $this->cache ) as $id ) {
			if ( false !== strpos( $id, $needle ) ) {
				unset( $this->cache[ $id ] );
			}
		}
		return false;
	}

	/**
	 * @return void
	 */
	public function add_global_groups( $groups ) {
		$this->global_groups = array_unique( array_merge( $this->global_groups, (array) $groups ) );
	}

	/**
	 * @return void
	 */
	public function add_non_persistent_groups( $groups ) {
		$this->non_persistent = array_unique( array_merge( $this->non_persistent, (array) $groups ) );
	}

	/**
	 * @return void
	 */
	public function switch_to_blog( $blog_id ) {
		$this->blog_prefix = $this->multisite ? ( (int) $blog_id . ':' ) : '';
	}

	/* ----- Backend helpers (serialise values) ----- */

	/**
	 * @return void
	 */
	private function backend_set( $id, $data, $expire ) {
		try {
			$value = serialize( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			if ( 'redis' === $this->type ) {
				if ( $expire > 0 ) {
					@$this->backend->setex( $id, $expire, $value ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				} else {
					@$this->backend->set( $id, $value ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			} elseif ( 'memcached' === $this->type ) {
				@$this->backend->set( $id, $value, $expire > 0 ? $expire : 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		} catch ( \Throwable $e ) {
			$this->connected = false;
		}
	}

	/**
	 * @param string $id    Storage key.
	 * @param bool   $found Out: whether a value was found.
	 * @return mixed
	 */
	private function backend_get( $id, &$found ) {
		$found = false;
		try {
			if ( 'redis' === $this->type ) {
				$value = @$this->backend->get( $id ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( false !== $value ) {
					$found = true;
					return unserialize( $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.unserialize_unserialize
				}
			} elseif ( 'memcached' === $this->type ) {
				$value = @$this->backend->get( $id ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( \Memcached::RES_SUCCESS === $this->backend->getResultCode() ) {
					$found = true;
					return unserialize( $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.unserialize_unserialize
				}
			}
		} catch ( \Throwable $e ) {
			$this->connected = false;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function backend_exists( $id ) {
		try {
			if ( 'redis' === $this->type ) {
				return (bool) @$this->backend->exists( $id ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			} elseif ( 'memcached' === $this->type ) {
				@$this->backend->get( $id ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return \Memcached::RES_SUCCESS === $this->backend->getResultCode();
			}
		} catch ( \Throwable $e ) {
			$this->connected = false;
		}
		return false;
	}

	/**
	 * @return void
	 */
	private function backend_delete( $id ) {
		try {
			if ( 'redis' === $this->type ) {
				@$this->backend->del( $id ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			} elseif ( 'memcached' === $this->type ) {
				@$this->backend->delete( $id ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		} catch ( \Throwable $e ) {
			$this->connected = false;
		}
	}
}
