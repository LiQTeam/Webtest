<?php
/**
 * فرم ثبت سفارش. قیمت نمایشی فقط برای راهنمایی است؛
 * مبلغ نهایی همیشه روی سرور محاسبه می‌شود.
 *
 * @package ClickPop\Core
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="cp-panel" aria-labelledby="cp-neworder-h">
	<header class="cp-panel__h">
		<h2 id="cp-neworder-h"><?php esc_html_e( 'ثبت سفارش جدید', 'clickpop-core' ); ?></h2>
		<span class="cp-live"><?php esc_html_e( 'قیمت لحظه‌ای', 'clickpop-core' ); ?></span>
	</header>

	<div class="cp-panel__b">
		<form class="cp-form cp-form--order" data-cp-order novalidate>
			<div class="cp-field">
				<label for="cp-brand"><?php esc_html_e( 'پلتفرم', 'clickpop-core' ); ?></label>
				<select id="cp-brand" name="brand" data-cp-brand>
					<option value=""><?php esc_html_e( 'در حال بارگذاری…', 'clickpop-core' ); ?></option>
				</select>
			</div>

			<div class="cp-field">
				<label for="cp-category"><?php esc_html_e( 'دسته', 'clickpop-core' ); ?></label>
				<select id="cp-category" name="category" data-cp-category disabled></select>
			</div>

			<div class="cp-field cp-field--full">
				<label for="cp-service"><?php esc_html_e( 'سرویس', 'clickpop-core' ); ?></label>
				<select id="cp-service" name="service_id" data-cp-service disabled required></select>
				<p class="cp-hint" data-cp-service-hint></p>
			</div>

			<div class="cp-field cp-field--full">
				<label for="cp-link"><?php esc_html_e( 'لینک صفحه یا پست', 'clickpop-core' ); ?></label>
				<input type="url" id="cp-link" name="link" dir="ltr" inputmode="url" required
					placeholder="https://instagram.com/username"
					aria-describedby="cp-link-hint">
				<p class="cp-hint" id="cp-link-hint">
					<?php esc_html_e( 'لینک باید عمومی و متعلق به همان پلتفرم انتخابی باشد. رمز حساب هرگز لازم نیست.', 'clickpop-core' ); ?>
				</p>
			</div>

			<div class="cp-field">
				<label for="cp-qty"><?php esc_html_e( 'تعداد', 'clickpop-core' ); ?></label>
				<input type="number" id="cp-qty" name="quantity" min="1" step="1" required
					aria-describedby="cp-qty-hint">
				<p class="cp-hint" id="cp-qty-hint" data-cp-range></p>
			</div>

			<div class="cp-total" aria-live="polite">
				<span class="cp-total__l"><?php esc_html_e( 'مبلغ قابل پرداخت', 'clickpop-core' ); ?></span>
				<strong class="cp-total__v" data-cp-total>—</strong>
			</div>

			<button type="submit" class="cp-btn cp-btn--primary cp-btn--wide" data-cp-submit>
				<?php esc_html_e( 'ثبت و کسر از کیف پول', 'clickpop-core' ); ?>
			</button>

			<p class="cp-formmsg" data-cp-order-msg role="status" aria-live="polite"></p>
		</form>
	</div>
</section>
