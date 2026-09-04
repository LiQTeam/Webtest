<?php
/**
 * فهرست عمومی سرویس‌ها و قیمت‌ها.
 *
 * @var array<string,mixed> $cp
 * @package ClickPop\Core
 */

defined( 'ABSPATH' ) || exit;

$tree = (array) ( $cp['tree'] ?? [] );
?>
<div class="cp-app cp-app--services">
	<?php if ( ! $tree ) : ?>
		<p class="cp-empty"><?php esc_html_e( 'فهرست سرویس‌ها هنوز همگام نشده است.', 'clickpop-core' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $tree as $brand ) : ?>
		<section class="cp-brandblock" aria-labelledby="cp-b-<?php echo esc_attr( $brand['slug'] ); ?>">
			<h2 id="cp-b-<?php echo esc_attr( $brand['slug'] ); ?>" class="cp-brandblock__t">
				<?php echo esc_html( $brand['label'] ); ?>
			</h2>

			<?php foreach ( $brand['categories'] as $category ) : ?>
				<h3 class="cp-brandblock__c"><?php echo esc_html( $category['label'] ); ?></h3>
				<div class="cp-tablewrap">
					<table class="cp-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'سرویس', 'clickpop-core' ); ?></th>
								<th scope="col"><?php esc_html_e( 'قیمت هر ۱۰۰۰', 'clickpop-core' ); ?></th>
								<th scope="col"><?php esc_html_e( 'حداقل', 'clickpop-core' ); ?></th>
								<th scope="col"><?php esc_html_e( 'حداکثر', 'clickpop-core' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $category['services'] as $service ) : ?>
								<tr>
									<td><?php echo esc_html( $service['name'] ); ?></td>
									<td><strong><?php echo esc_html( \ClickPop\Core\Support\Money::fromRials( (int) $service['rate'] )->format() ); ?></strong></td>
									<td><?php echo esc_html( number_format_i18n( (int) $service['min'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( (int) $service['max'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endforeach; ?>
</div>
