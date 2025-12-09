<div id="produkt-cart-overlay" class="produkt-cart-overlay"></div>
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
        <h3>Willkommen zurück</h3>
        <p class="subline">Zum Einloggen bitte Ihre Email Adresse verwenden</p>
        <label for="checkout-login-email" class="checkout-label">Email Adresse</label>
        <input type="email" id="checkout-login-email" placeholder="name@mail.com" required>
        <div id="checkout-email-warning" class="checkout-email-warning" style="display:none;">
            Zu dieser E-Mail-Adresse besteht bereits ein Kundenkonto.<br>
            Sie können sich anmelden, damit wir Ihre Daten automatisch übernehmen.
        </div>
        <button id="checkout-login-btn">Code zum einloggen anfordern</button>
        <button id="checkout-back-shop" class="secondary">&#10229; Zurück zum Shop</button>
        <p class="guest-text"><span>Noch kein Konto bei uns?</span> <a href="#" id="checkout-guest-link">Als Gast bestellen</a></p>
    </div>
</div>

