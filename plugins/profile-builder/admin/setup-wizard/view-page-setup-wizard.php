<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$steps_completion = WPPB_Setup_Wizard::get_completed_progress_steps();
?>

<div class="wppb-setup-holder">

    <div class="wppb-setup-wrap">

        <img class="wppb-setup-logo" src="<?php echo esc_url( WPPB_PLUGIN_URL ); ?>assets/images/pb-banner.svg" alt="Paid Member Subscriptions" />

        <ul class="wppb-setup-steps">
            <?php foreach( $this->steps as $step => $label ) :
                //if current step index is greater than the loop step index, we know that the loop step is completed
                $completed = array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step, array_keys( $this->steps ), true );

                if( $this->step === $step ) : ?>
                    <li class="active"><?php echo esc_html( $label ); ?></li>
                <?php elseif( $completed ) : ?>
                    <li class="active <?php echo $steps_completion[$step] == 1 ? 'completed' : ''; ?>">
                        <a href="<?php echo esc_url( add_query_arg( 'step', $step ) ); ?>"><?php echo esc_html( $label ); ?></a>
                    </li>
                <?php else : ?>
                    <li><?php echo esc_html( $label ); ?></li>
                <?php endif;
            endforeach; ?>
        </ul>

        <div class="wppb-setup-content">
            <?php include_once 'view-tab-' . $this->step . '.php'; ?>
        </div>

        <div class="wppb-setup-background"></div>
    </div>

    <div class="wppb-setup-skip">
        <div class="wppb-setup-skip__action">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=profile-builder-dashboard' ) ); ?>"><?php esc_html_e( 'Skip Setup', 'profile-builder' ); ?></a>
        </div>
    </div>

</div>