document.addEventListener( 'DOMContentLoaded', initPaymentOption );

function initPaymentOption() {
    const bnplOption = document.querySelector( '.bnpl-options' );

    if( ! bnplOption || ! bnplOption.getAttribute( 'data-bnpl' ) ) {
        return;
    }

    jQuery( document.body ).on( 'change', '[name="bnpl_installment"]', onPlanChange );
    jQuery( document.body ).on( 'updated_checkout', initTippy );
}

/**
 * @typedef InstallmentPlan plan
 * @property {number} plan.id
 * @property {string} name
 * @property {number} installment_factor
 * @property {number} apr
 * @property {number} period
 * @property {number} total_due_factor
 * @property {number} nir
 * 
 * @returns {InstallmentPlan[]}
 */
function getInstallments() {
    const bnplOption = document.querySelector( '.bnpl-options' );

    return JSON.parse( atob( bnplOption.getAttribute( 'data-bnpl' ) ) );
}

/**
 * 
 * @param {Event} e 
 */
function onPlanChange(e) {
    const installments = getInstallments();

    const currentPlan = installments.find( x => x.id == e.target.value );

    const totalDueAmount = getTotal() * currentPlan.total_due_factor;
    const monthlyAmount = getTotal() * currentPlan.installment_factor;

    document.getElementById( 'tbi-monthly-amount' ).innerHTML = `${roundUp(monthlyAmount)} ${bnpl_data.currency}`;
    document.getElementById( 'tbi-due-amount' ).innerHTML = `${roundUp(totalDueAmount)} ${bnpl_data.currency}`;
    document.getElementById( 'tbi-apr' ).innerText = `${currentPlan.apr}%`;
    document.getElementById( 'tbi-interest-rate' ).innerText = `${currentPlan.nir}%`;
}

/**
 * @return {number|null}
 */
function getTotal() {
    const bnplOption = document.querySelector( '.bnpl-options' );

    return bnplOption.hasAttribute( 'data-bnpl-total' ) ? Number( bnplOption.getAttribute( 'data-bnpl-total' ) ) : null;
}

function roundUp( amount, precision = 2) {
    const fig = Math.pow(10, precision);

    return Math.ceil( amount * fig ) / fig;
}

function initTippy() {
    if( ! window.tippy ) {
        return;
    }

    window.tippy( '#apr-info', {
        content: 'С него можеш точно да изчислиш процента на разходите върху заетата сума, която дължиш на кредитора. За ползвателите на кредити е важно да са наясно предварително какво е пълното оскъпяване на тяхното задължение.',
        trigger: 'mouseenter click',
        delay: [null, 200]
    } );

    window.tippy( '#nir-info', {
        content: 'Това е лихвата, която потребителят плаща за използвания кредит за срок от една година. Това е цената на самият кредит, сметната средно при срок от 12 месеца.',
        trigger: 'mouseenter click',    
        delay: [null, 200]
    } );
}