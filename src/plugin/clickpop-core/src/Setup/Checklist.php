<?php
declare( strict_types=1 );

namespace ClickPop\Core\Setup;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Gateways\GatewayManager;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Repositories\ServiceRepository;
use ClickPop\Core\Support\Encryption;
use ClickPop\Core\Sync\Scheduler;

defined( 'ABSPATH' ) || exit;

/**
 * چک‌لیست آماده‌بودن نصب.
 *
 * هر ردیف یک وضعیت واقعی را می‌سنجد، نه یک تیک تزئینی: اگر قرمز است،
 * یعنی همان لحظه چیزی در سایت کار نمی‌کند.
 */
final class Checklist {

	/**
	 * @return array<int,array{key:string,label:string,state:string,detail:string,action:string,action_label:string,blocking:bool}>
	 */
	public static function items(): array {
		$items = [];

		/* ۱. موتور جدول */
		$bad_engine = Installer::nonInnodbTables();
		$items[]    = self::item(
			'engine',
			__( 'موتور جدول‌ها روی InnoDB', 'clickpop-core' ),
			$bad_engine ? 'bad' : 'ok',
			$bad_engine
				? sprintf(
					/* translators: %d: table count */
					__( '%d جدول روی InnoDB نیست. کیف پول بدون تراکنش و قفل ردیفی کار می‌کند.', 'clickpop-core' ),
					count( $bad_engine )
				)
				: __( 'همهٔ جدول‌های کلیک‌پاپ InnoDB هستند.', 'clickpop-core' ),
			$bad_engine ? wp_nonce_url( admin_url( 'admin-post.php?action=clickpop_convert_innodb' ), 'clickpop_convert_innodb' ) : '',
			__( 'تبدیل به InnoDB', 'clickpop-core' ),
			true
		);

		/* ۲. کلید رمزنگاری */
		$items[] = self::item(
			'key',
			__( 'کلید رمزنگاری اختصاصی', 'clickpop-core' ),
			Encryption::hasDedicatedKey() ? 'ok' : 'warn',
			Encryption::hasDedicatedKey()
				? __( 'کلید در wp-config.php تعریف شده است.', 'clickpop-core' )
				: __( 'کلید API با salt وردپرس رمز می‌شود؛ با تغییر salt غیرقابل بازیابی خواهد بود.', 'clickpop-core' ),
			'',
			'',
			false
		);

		/* ۳. سرویس‌دهنده */
		$provider = ProviderManager::primaryRow();
		$has_key  = $provider && '' !== Encryption::decrypt( (string) $provider->api_key_enc );
		$items[]  = self::item(
			'provider',
			__( 'اتصال به سرویس‌دهنده', 'clickpop-core' ),
			$has_key ? 'ok' : 'bad',
			$has_key
				? __( 'کلید API ذخیره شده است.', 'clickpop-core' )
				: __( 'کلید API وارد نشده؛ هیچ سرویسی همگام نمی‌شود.', 'clickpop-core' ),
			admin_url( 'admin.php?page=clickpop-settings' ),
			__( 'تنظیم کلید API', 'clickpop-core' ),
			true
		);

		/* ۴. همگام‌سازی سرویس‌ها */
		$counts = ( new ServiceRepository() )->statusCounts();
		$active = (int) ( $counts['active'] ?? 0 );
		$items[] = self::item(
			'services',
			__( 'همگام‌سازی سرویس‌ها', 'clickpop-core' ),
			$active > 0 ? 'ok' : 'bad',
			$active > 0
				? sprintf(
					/* translators: %s: active service count */
					__( '%s سرویس فعال در دیتابیس.', 'clickpop-core' ),
					number_format_i18n( $active )
				)
				: __( 'هنوز سرویسی همگام نشده است.', 'clickpop-core' ),
			wp_nonce_url( admin_url( 'admin-post.php?action=clickpop_sync_services' ), 'clickpop_sync' ),
			__( 'همگام‌سازی', 'clickpop-core' ),
			true
		);

		/* ۵. کرون */
		$cron_off  = defined( 'DISABLE_WP_CRON' ) && constant( 'DISABLE_WP_CRON' );
		$scheduled = (bool) wp_next_scheduled( Scheduler::HOOK_ORDERS );
		$items[]   = self::item(
			'cron',
			__( 'زمان‌بند وضعیت سفارش‌ها', 'clickpop-core' ),
			$scheduled ? ( $cron_off ? 'ok' : 'warn' ) : 'bad',
			! $scheduled
				? __( 'کار زمان‌بندی‌شده ثبت نشده است.', 'clickpop-core' )
				: ( $cron_off
					? __( 'کرون مرورگری خاموش است؛ مطمئن شوید کرون سیستمی هر دقیقه اجرا می‌شود.', 'clickpop-core' )
					: __( 'کرون مرورگری فعال است. برای اتکاپذیری، DISABLE_WP_CRON را true کنید و کرون سیستمی بگذارید.', 'clickpop-core' ) ),
			'',
			'',
			false
		);

		/* ۶. درگاه پرداخت */
		$gateways = GatewayManager::enabled();
		$items[]  = self::item(
			'gateway',
			__( 'درگاه پرداخت', 'clickpop-core' ),
			$gateways ? 'ok' : 'warn',
			$gateways
				? sprintf(
					/* translators: %s: gateway names */
					__( 'فعال: %s', 'clickpop-core' ),
					implode( '، ', array_map( static fn( $g ): string => $g->label(), $gateways ) )
				)
				: __( 'بدون درگاه، کاربر نمی‌تواند کیف پول را شارژ کند.', 'clickpop-core' ),
			admin_url( 'admin.php?page=clickpop-settings' ),
			__( 'فعال‌سازی درگاه', 'clickpop-core' ),
			false
		);

		/* ۷. صفحهٔ داشبورد */
		$dash    = (int) get_option( 'clickpop_dashboard_page_id', 0 );
		$dash_ok = $dash > 0 && 'publish' === get_post_status( $dash );
		$items[] = self::item(
			'dashboard',
			__( 'صفحهٔ داشبورد کاربر', 'clickpop-core' ),
			$dash_ok ? 'ok' : 'bad',
			$dash_ok
				? sprintf(
					/* translators: %s: page title */
					__( 'صفحهٔ «%s» ساخته و متصل شده است.', 'clickpop-core' ),
					(string) get_the_title( $dash )
				)
				: __( 'صفحهٔ داشبورد ساخته نشده است.', 'clickpop-core' ),
			wp_nonce_url( admin_url( 'admin-post.php?action=clickpop_install_pages' ), 'clickpop_install_pages' ),
			__( 'ساخت خودکار صفحه‌ها', 'clickpop-core' ),
			true
		);

		/* ۸. تم */
		$theme    = wp_get_theme();
		$theme_ok = 'ClickPop' === $theme->get( 'Name' ) || 'clickpop' === $theme->get_template();
		$items[]  = self::item(
			'theme',
			__( 'تم کلیک‌پاپ', 'clickpop-core' ),
			$theme_ok ? 'ok' : 'warn',
			$theme_ok
				? __( 'تم فعال است.', 'clickpop-core' )
				: __( 'تم کلیک‌پاپ فعال نیست؛ ظاهر داشبورد ممکن است ناهماهنگ باشد.', 'clickpop-core' ),
			admin_url( 'themes.php' ),
			__( 'مدیریت پوسته‌ها', 'clickpop-core' ),
			false
		);

		return $items;
	}

	/** آیا چیزی مانع شروع فروش هست؟ */
	public static function blockers(): int {
		$count = 0;

		foreach ( self::items() as $item ) {
			if ( $item['blocking'] && 'ok' !== $item['state'] ) {
				++$count;
			}
		}

		return $count;
	}

	/** @return array{key:string,label:string,state:string,detail:string,action:string,action_label:string,blocking:bool} */
	private static function item( string $key, string $label, string $state, string $detail, string $action, string $action_label, bool $blocking ): array {
		return compact( 'key', 'label', 'state', 'detail', 'action', 'action_label', 'blocking' );
	}
}
