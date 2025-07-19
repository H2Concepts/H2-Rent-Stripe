initialize();

async function initialize() {
  const urlParams = new URLSearchParams(window.location.search);
  const sessionId = urlParams.get('session_id');
  if (!sessionId) return;
  const response = await fetch(produkt_return.ajax_url + '?action=get_checkout_session_status', {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    method: 'POST',
    body: JSON.stringify({ session_id: sessionId }),
  });
  const session = await response.json();

  if (session.status === 'open') {
    if (produkt_return.checkout_url) {
      window.location.replace(produkt_return.checkout_url);
    }
  } else if (session.status === 'complete') {
    document.getElementById('success').classList.remove('hidden');
    document.getElementById('customer-email').textContent = session.customer_email || '';
  }
}
