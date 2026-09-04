<?php
/**
 * پوستهٔ داشبورد کاربر.
 *
 * @var array<string,mixed> $cp
 * @package ClickPop\Core
 */

defined( 'ABSPATH' ) || exit;

/** @var WP_User $user */
$user       = $cp['user'];
$balance    = (int) $cp['balance'];
$tabs       = (array) $cp['tabs'];
$active     = (string) $cp['active_tab'];
$gateways   = (array) $cp['gateways'];
$depts      = (array) $cp['departments'];
$notice     = $cp['pay_notice'];
$base_url   = get_permalink() ?: home_url( '/' );
$initials   = mb_substr( trim( (string) $user->display_name ), 0, 2 );
?>
<div class="cp-app" id="cp-app">

	<?php if ( is_array( $notice ) ) : ?>
		<div class="cp-note cp-note--<?php echo esc_attr( $notice['tone'] ); ?>" role="status">
			<?php echo esc_html( $notice['text'] ); ?>
		</div>
	<?php endif; ?>

	<div class="cp-layout">
		<aside class="cp-side">
			<div class="cp-who">
				<span class="cp-avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
				<span>
					<span class="cp-who__name"><?php echo esc_html( $user->display_name ); ?></span>
					<span class="cp-who__id"><?php printf( esc_html__( 'کاربر %s', 'clickpop-core' ), esc_html( number_format_i18n( (int) $user->ID ) ) ); ?></span>
				</span>
			</div>

			<nav class="cp-nav" aria-label="<?php esc_attr_e( 'منوی داشبورد', 'clickpop-core' ); ?>">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a
						href="<?php echo esc_url( add_query_arg( 'cp_tab', $key, $base_url ) ); ?>"
						class="cp-nav__item<?php echo $active === $key ? ' is-active' : ''; ?>"
						<?php echo $active === $key ? 'aria-current="page"' : ''; ?>
					><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
				<a class="cp-nav__item cp-nav__item--muted" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
					<?php esc_html_e( 'خروج از حساب', 'clickpop-core' ); ?>
				</a>
			</nav>
		</aside>

		<main class="cp-main">

			<div class="cp-kpis">
				<div class="cp-kpi cp-kpi--wallet">
					<span class="cp-kpi__l"><?php esc_html_e( 'موجودی کیف پول', 'clickpop-core' ); ?></span>
					<strong class="cp-kpi__v" data-cp-balance>
						<?php echo esc_html( \ClickPop\Core\Support\Money::fromRials( $balance )->format() ); ?>
					</strong>
				</div>
				<div class="cp-kpi">
					<span class="cp-kpi__l"><?php esc_html_e( 'در حال انجام', 'clickpop-core' ); ?></span>
					<strong class="cp-kpi__v" data-cp-stat="running">—</strong>
				</div>
				<div class="cp-kpi">
					<span class="cp-kpi__l"><?php esc_html_e( 'تکمیل‌شده', 'clickpop-core' ); ?></span>
					<strong class="cp-kpi__v" data-cp-stat="completed">—</strong>
				</div>
				<div class="cp-kpi">
					<span class="cp-kpi__l"><?php esc_html_e( 'کل سفارش‌ها', 'clickpop-core' ); ?></span>
					<strong class="cp-kpi__v" data-cp-stat="total">—</strong>
				</div>
			</div>

			<?php if ( in_array( $active, [ 'overview', 'new' ], true ) ) : ?>
				<?php include __DIR__ . '/new-order.php'; ?>
			<?php endif; ?>

			<?php if ( in_array( $active, [ 'overview', 'orders' ], true ) ) : ?>
				<section class="cp-panel" aria-labelledby="cp-orders-h">
					<header class="cp-panel__h">
						<h2 id="cp-orders-h"><?php esc_html_e( 'سفارش‌های من', 'clickpop-core' ); ?></h2>
						<button type="button" class="cp-btn cp-btn--ghost cp-btn--sm" data-cp-refresh="orders">
							<?php esc_html_e( 'به‌روزرسانی', 'clickpop-core' ); ?>
						</button>
					</header>
					<div class="cp-tablewrap">
						<table class="cp-table">
							<caption class="cp-sr"><?php esc_html_e( 'فهرست سفارش‌ها با وضعیت و پیشرفت', 'clickpop-core' ); ?></caption>
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'شناسه', 'clickpop-core' ); ?></th>
									<th scope="col"><?php esc_html_e( 'سرویس', 'clickpop-core' ); ?></th>
									<th scope="col"><?php esc_html_e( 'تعداد', 'clickpop-core' ); ?></th>
									<th scope="col"><?php esc_html_e( 'مبلغ', 'clickpop-core' ); ?></th>
									<th scope="col"><?php esc_html_e( 'پیشرفت', 'clickpop-core' ); ?></th>
									<th scope="col"><?php esc_html_e( 'وضعیت', 'clickpop-core' ); ?></th>
								</tr>
							</thead>
							<tbody data-cp-orders>
								<tr><td colspan="6" class="cp-empty"><?php esc_html_e( 'در حال بارگذاری…', 'clickpop-core' ); ?></td></tr>
							</tbody>
						</table>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( 'wallet' === $active ) : ?>
				<section class="cp-panel" aria-labelledby="cp-wallet-h">
					<header class="cp-panel__h"><h2 id="cp-wallet-h"><?php esc_html_e( 'شارژ کیف پول', 'clickpop-core' ); ?></h2></header>
					<div class="cp-panel__b">
						<?php if ( ! $gateways ) : ?>
							<p class="cp-muted"><?php esc_html_e( 'هنوز درگاه پرداختی فعال نشده است.', 'clickpop-core' ); ?></p>
						<?php else : ?>
							<form class="cp-form" data-cp-topup>
								<div class="cp-field">
									<label for="cp-topup-amount"><?php esc_html_e( 'مبلغ (تومان)', 'clickpop-core' ); ?></label>
									<input type="number" id="cp-topup-amount" name="amount" min="1000" step="1000" value="50000" required>
								</div>
								<div class="cp-field">
									<label for="cp-topup-gateway"><?php esc_html_e( 'درگاه', 'clickpop-core' ); ?></label>
									<select id="cp-topup-gateway" name="gateway">
										<?php foreach ( $gateways as $slug => $gateway ) : ?>
											<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $gateway->label() ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<button type="submit" class="cp-btn cp-btn--primary"><?php esc_html_e( 'انتقال به درگاه', 'clickpop-core' ); ?></button>
								<p class="cp-formmsg" data-cp-topup-msg role="status" aria-live="polite"></p>
							</form>
						<?php endif; ?>
					</div>
				</section>

				<section class="cp-panel" aria-labelledby="cp-ledger-h">
					<header class="cp-panel__h"><h2 id="cp-ledger-h"><?php esc_html_e( 'دفتر تراکنش‌ها', 'clickpop-core' ); ?></h2></header>
					<div class="cp-ledger" data-cp-ledger>
						<p class="cp-empty"><?php esc_html_e( 'در حال بارگذاری…', 'clickpop-core' ); ?></p>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( 'tickets' === $active ) : ?>
				<section class="cp-panel" aria-labelledby="cp-ticket-h">
					<header class="cp-panel__h"><h2 id="cp-ticket-h"><?php esc_html_e( 'تیکت جدید', 'clickpop-core' ); ?></h2></header>
					<div class="cp-panel__b">
						<form class="cp-form" data-cp-ticket>
							<div class="cp-field">
								<label for="cp-ticket-dept"><?php esc_html_e( 'بخش', 'clickpop-core' ); ?></label>
								<select id="cp-ticket-dept" name="department">
									<?php foreach ( $depts as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="cp-field">
								<label for="cp-ticket-subject"><?php esc_html_e( 'موضوع', 'clickpop-core' ); ?></label>
								<input type="text" id="cp-ticket-subject" name="subject" maxlength="200" required>
							</div>
							<div class="cp-field cp-field--full">
								<label for="cp-ticket-body"><?php esc_html_e( 'متن پیام', 'clickpop-core' ); ?></label>
								<textarea id="cp-ticket-body" name="body" rows="5" maxlength="5000" required></textarea>
							</div>
							<button type="submit" class="cp-btn cp-btn--primary"><?php esc_html_e( 'ارسال تیکت', 'clickpop-core' ); ?></button>
							<p class="cp-formmsg" data-cp-ticket-msg role="status" aria-live="polite"></p>
						</form>
					</div>
				</section>

				<section class="cp-panel" aria-labelledby="cp-tickets-h">
					<header class="cp-panel__h"><h2 id="cp-tickets-h"><?php esc_html_e( 'تیکت‌های من', 'clickpop-core' ); ?></h2></header>
					<div class="cp-ledger" data-cp-tickets>
						<p class="cp-empty"><?php esc_html_e( 'در حال بارگذاری…', 'clickpop-core' ); ?></p>
					</div>
				</section>
			<?php endif; ?>

		</main>
	</div>
</div>
