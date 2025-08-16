<div id="produkt-cart-panel" class="produkt-cart-panel">
    <div class="cart-header">
        <span class="cart-title">Warenkorb</span>
        <button type="button" class="cart-close" aria-label="Warenkorb schließen">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="12" fill="#000"/>
                <path d="M6 6l12 12M18 6l-12 12" stroke="#fff" stroke-width="2"/>
            </svg>
        </button>
    </div>
    <div class="cart-items"></div>
    <div class="cart-summary"><span>Summe</span><span class="cart-total-amount">0€</span></div>
    <button id="produkt-cart-checkout">Jetzt bestellen</button>
</div>

<div id="checkout-login-modal" class="checkout-login-modal" style="display:none;">
    <div class="modal-content">
        <h3>Login</h3>
        <p>Zum Einloggen bitte Ihre Email Adresse verwenden</p>
        <input type="email" id="checkout-login-email" placeholder="Ihre E-Mail">
        <button id="checkout-login-btn">Code zum einloggen anfordern</button>
        <p class="guest-text"><a href="#" id="checkout-guest-link">Als Gast fortfahren</a></p>
    </div>
</div>

