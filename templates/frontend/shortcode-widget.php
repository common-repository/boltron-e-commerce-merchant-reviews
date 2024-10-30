<?php
$host           = BTRN()->host;
$wrapper        = 'BoltronWidgetWrapper';
$merchant_id    = BTRN()->get_option( 'merchant_id' );
$grade          = BTRN()->get_option( 'grade' );

if ( empty( $grade ) || empty( $merchant_id ) ) {
    esc_html_e( 'No grade yet.', 'boltron' );

    return;
}
?>
<style>
<?php echo "#$wrapper" ?> {
    width: 100%;
    /* height: 85px; */
    background: #fff;
    border: 4px solid #FFA500;
    text-align: center;
    padding: 8px;
    margin: 0px;
    text-decoration:none;
}
</style>

<div id="<?php echo $wrapper ?>">
    <a href="<?php echo $host . "/public-profile/$merchant_id" ?>" target="_blank" class="merchant-profile-link">
        <div>
            <p class="main_tit"><?php esc_html_e( 'Store Rating', 'boltron' ) ?></p>
            <p class="grade"><?php echo $grade ?></p>
            <p class="verified"><?php esc_html_e( 'Verified By', 'boltron' ) ?></p>
            <img class="boltron-widget-logo" src="<?php echo $host . '/img/logos/logo.png' ?>" alt="Boltron">
        </div> 
    </a> 
</div>