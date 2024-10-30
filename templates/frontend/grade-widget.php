<?php
$host               = BTRN()->host;
$wrapper            = 'BoltronWidgetWrapper';
$floating_widget    = trim( BTRN()->get_option( 'floating_widget' ) );
$merchant_id        = BTRN()->get_option( 'merchant_id' );
$grade              = BTRN()->get_option( 'grade' );

if ( stripos( 'left right', $floating_widget ) === false ) return;
?>

<a class="boltron-widget-float widget-<?php echo $floating_widget; ?>" href="<?php echo $host . "/public-profile/$merchant_id" ?>" target="_blank">
    <div class="instant">
        <p class="grade-text"><?php esc_html_e( 'Grade', 'boltron' ); ?></p>
        <p class="grade grade-float"><?php echo $grade; ?></p>
    </div>
    <div class="verified-by">
        <p class="verified" style="margin-bottom: 5px; font-size:14px;"><?php esc_html_e( 'Verified By', 'boltron' ) ?></p>
        <img class="boltron-widget-logo" src="<?php echo $host . '/img/logos/logo.png' ?>" alt="Boltron" style="height: 28px !important;">
    </div>
</a>