<?php

class Affiliate_WP_PMP extends Affiliate_WP_Base {
	
	public function init() {

		$this->context = 'pmp';

		add_action( 'pmpro_add_order', array( $this, 'add_pending_referral' ), 10 );
		add_action( 'pmpro_added_order', array( $this, 'mark_referral_complete' ), 10 );
		add_action( 'admin_init', array( $this, 'revoke_referral_on_refund_and_cancel' ), 10);
		add_action( 'pmpro_delete_order', array( $this, 'revoke_referral_on_delete' ), 10, 2 );
		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );
	}

	public function add_pending_referral( $order ) {

		if( $this->was_referred() ) {

			$this->insert_pending_referral( $order->subtotal, $order->payment_transaction_id, $order->membership_name );
		}

	}

	public function mark_referral_complete( $order ) {

		// Now update the referral to have a nice reference. PMP doesn't make the order ID available early enough
		$referral = affiliate_wp()->referrals->get_by( 'reference', $order->payment_transaction_id );
		if( $referral ) {
			affiliate_wp()->referrals->update( $referral->referral_id, array( 'reference' => $order->id ) );
		}

		$this->complete_referral( $order->id );
	}

	public function revoke_referral_on_refund_and_cancel() {

		/*
		 * PMP does not have hooks for when an order is refunded or voided, so we detect the form submission manually
		 */

		if( ! isset( $_REQUEST['save'] ) ) {
			return;
		}

		if( ! isset( $_REQUEST['order'] ) ) {
			return;
		}

		if( ! isset( $_REQUEST['status'] ) ) {
			return;
		}

		if( ! isset( $_REQUEST['membership_id'] ) ) {
			return;
		}

		if( 'refunded' != $_REQUEST['status'] && 'cancelled' != $_REQUEST['status'] ) {
			return;
		}

		if( ! affiliate_wp()->settings->get( 'revoke_on_refund' ) ) {
			return;
		}

		$this->reject_referral( absint( $_REQUEST['order'] ) );

	}

	public function revoke_referral_on_delete( $order_id = 0, $order ) {

		if( ! affiliate_wp()->settings->get( 'revoke_on_refund' ) ) {
			return;
		}

		$this->reject_referral( $order_id );

	}

	public function reference_link( $reference = 0, $referral ) {

		if( empty( $referral->context ) || 'pmp' != $referral->context ) {

			return $reference;

		}

		$url = admin_url( 'admin.php?page=pmpro-orders&order=' . $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}
	
}
new Affiliate_WP_PMP;