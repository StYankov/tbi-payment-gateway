<?php
/**
 * @var array $selected
 * @var string $description
 * @var array $installments
 * @var float $loanMonthly
 * @var float $loanTotal
 * @var float $loanAPR
 * @var float $laonNIR
 * @var string $assetsUrl
 * @var string $infoIcon
 */

?>
<div class="bnpl-options" data-bnpl="<?php echo base64_encode( json_encode( $installments ) ); ?>" data-bnpl-total="<?php echo WC()->cart->get_total( 'edit' ); ?>">
    <?php if( ! empty( $description ) ) : ?>
        <div class="bnpl-options__description">
            <?php echo $description; ?>
        </div>
    <?php endif; ?>

    <div class="field-container bnpl-installment">
        <select name="bnpl_installment">
            <?php foreach( $installments as $item ) : ?>
                <option value="<?php echo $item['id']; ?>" <?php selected( $item['id'], $selected['id'] ); ?>><?php echo $item['name']; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="loan-params">
        <div class="loan-params__row">
            <span class="loan-params__label">Месечна вноска</span>
            <span class="loan-params__value" id="tbi-monthly-amount"><?php echo $loanMonthly . ' ' . get_woocommerce_currency_symbol(); ?></span>
        </div>

        <div class="loan-params__row">
            <span class="loan-params__label">Общо дължима сума</span>
            <span class="loan-params__value" id="tbi-due-amount"><?php echo $loanTotal . ' ' . get_woocommerce_currency_symbol(); ?></span>
        </div>

        <div class="loan-params__row">
            <span class="loan-params__label">
                ГПР 
                <span class="info-icon">
                    <?php echo $infoIcon; ?>
                </span>
            </span>
            <span class="loan-params__value" id="tbi-apr"><?php echo $loanAPR . '%'; ?></span>
        </div>


        <div class="loan-params__row">
            <span class="loan-params__label">
                ГЛП
                <span class="info-icon">
                    <?php echo $infoIcon; ?>
                </span>
            </span>
            <span class="loan-params__value" id="tbi-interest-rate"><?php echo $laonNIR . '%'; ?></span>
        </div>
    </div>
</div>