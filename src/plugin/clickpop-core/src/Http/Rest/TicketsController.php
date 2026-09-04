<?php
declare( strict_types=1 );

namespace ClickPop\Core\Http\Rest;

use ClickPop\Core\Support\Jalali;
use ClickPop\Core\Tickets\TicketService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class TicketsController extends AbstractController {

	public function routes(): void {
		register_rest_route(
			$this->ns(),
			'/tickets',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'index' ],
					'permission_callback' => $this->can( 'clickpop_open_ticket' ),
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'create' ],
					'permission_callback' => $this->can( 'clickpop_open_ticket' ),
					'args'                => [
						'department' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						],
						'subject'    => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'body'       => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						],
						'order_id'   => [
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->ns(),
			'/tickets/(?P<id>\d+)/messages',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'messages' ],
					'permission_callback' => $this->can( 'clickpop_open_ticket' ),
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'reply' ],
					'permission_callback' => $this->can( 'clickpop_open_ticket' ),
					'args'                => [
						'body' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						],
					],
				],
			]
		);
	}

	public function index(): WP_REST_Response {
		$rows = ( new TicketService() )->forUser( get_current_user_id() );

		return $this->ok(
			[
				'departments' => TicketService::departments(),
				'items'       => array_map(
					static fn( object $t ): array => [
						'id'         => (int) $t->id,
						'subject'    => (string) $t->subject,
						'department' => (string) $t->department,
						'status'     => (string) $t->status,
						'updated_fa' => Jalali::format( (string) $t->last_reply_at ),
					],
					$rows
				),
			]
		);
	}

	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$limited = $this->guardRate( 'ticket', 20, HOUR_IN_SECONDS );
		if ( $limited ) {
			return $limited;
		}

		$id = ( new TicketService() )->create(
			get_current_user_id(),
			(string) $request['department'],
			(string) $request['subject'],
			(string) $request['body'],
			( (int) $request['order_id'] ) ?: null
		);

		if ( $id <= 0 ) {
			return new WP_Error( 'cp_db_error', __( 'ثبت تیکت ممکن نشد.', 'clickpop-core' ), [ 'status' => 500 ] );
		}

		return $this->ok( [ 'id' => $id ], 201 );
	}

	public function messages( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$service = new TicketService();
		$ticket  = $service->findOwned( (int) $request['id'], get_current_user_id() );

		if ( ! $ticket ) {
			return new WP_Error( 'cp_not_found', __( 'تیکت پیدا نشد.', 'clickpop-core' ), [ 'status' => 404 ] );
		}

		return $this->ok(
			[
				'ticket'   => [
					'id'      => (int) $ticket->id,
					'subject' => (string) $ticket->subject,
					'status'  => (string) $ticket->status,
				],
				'messages' => array_map(
					static fn( object $m ): array => [
						'id'         => (int) $m->id,
						'is_staff'   => (bool) $m->is_staff,
						'body'       => (string) $m->body,
						'created_fa' => Jalali::format( (string) $m->created_at ),
					],
					$service->messages( (int) $ticket->id )
				),
			]
		);
	}

	public function reply( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$limited = $this->guardRate( 'ticket', 20, HOUR_IN_SECONDS );
		if ( $limited ) {
			return $limited;
		}

		$service = new TicketService();
		$ticket  = $service->findOwned( (int) $request['id'], get_current_user_id() );

		if ( ! $ticket ) {
			return new WP_Error( 'cp_not_found', __( 'تیکت پیدا نشد.', 'clickpop-core' ), [ 'status' => 404 ] );
		}

		$ok = $service->reply( (int) $ticket->id, get_current_user_id(), (string) $request['body'], false );

		if ( ! $ok ) {
			return new WP_Error( 'cp_bad_request', __( 'متن پیام خالی است.', 'clickpop-core' ), [ 'status' => 422 ] );
		}

		return $this->ok( [ 'ok' => true ], 201 );
	}
}
