<?php
declare( strict_types=1 );

namespace ClickPop\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * رمزنگاری متقارن AES-256-GCM برای کلیدهای API.
 *
 * کلید از ثابت CLICKPOP_ENCRYPTION_KEY در wp-config.php خوانده می‌شود؛ در نبود آن،
 * از salt وردپرس استفاده می‌شود و هشدار دائمی در ادمین نمایش داده می‌شود.
 */
final class Encryption {

	private const CIPHER = 'aes-256-gcm';

	public static function hasDedicatedKey(): bool {
		return defined( 'CLICKPOP_ENCRYPTION_KEY' ) && '' !== (string) constant( 'CLICKPOP_ENCRYPTION_KEY' );
	}

	private static function key(): string {
		$raw = self::hasDedicatedKey()
			? (string) constant( 'CLICKPOP_ENCRYPTION_KEY' )
			: wp_salt( 'secure_auth' );

		return hash( 'sha256', $raw, true );
	}

	public static function encrypt( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}
		$iv     = random_bytes( 12 );
		$tag    = '';
		$cipher = openssl_encrypt( $plain, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag );

		if ( false === $cipher ) {
			return '';
		}

		return base64_encode( $iv . $tag . $cipher );
	}

	public static function decrypt( string $stored ): string {
		if ( '' === $stored ) {
			return '';
		}
		$raw = base64_decode( $stored, true );
		if ( false === $raw || strlen( $raw ) < 29 ) {
			return '';
		}
		$iv     = substr( $raw, 0, 12 );
		$tag    = substr( $raw, 12, 16 );
		$cipher = substr( $raw, 28 );

		$plain = openssl_decrypt( $cipher, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag );

		return is_string( $plain ) ? $plain : '';
	}

	/** نمایش ماسک‌شده در فرم ادمین — کلید خام هرگز به مرورگر نمی‌رود. */
	public static function mask( string $plain ): string {
		$len = strlen( $plain );
		if ( $len < 5 ) {
			return '' === $plain ? '' : str_repeat( '•', $len );
		}
		return str_repeat( '•', 8 ) . substr( $plain, -4 );
	}
}
