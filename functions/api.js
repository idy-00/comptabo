export async function onRequestPost(context) {
  const PAYTECH_API_KEY = '7126b7e274320ad63d3d17ecf95b8a66c6a1a6816e1fcae7ca34707fe577de36';
  const PAYTECH_API_SECRET = '666cbb401a9bd26cdd77ae597b76d50d1272313b68a4b4be9aa45098181ecb64';
  const BASE_URL = 'https://comptabo.pages.dev';

  try {
    const input = await context.request.json();
    const amount = parseInt(input.amount) || 0;
    const description = input.description || 'Paiement Comptabo';

    if (amount < 100) {
      return new Response(JSON.stringify({
        success: false,
        message: 'Montant minimum: 100 FCFA'
      }), {
        headers: { 'Content-Type': 'application/json' }
      });
    }

    const reference = 'CPT' + Date.now() + Math.floor(Math.random() * 1000);

    const formData = new URLSearchParams({
      item_name: 'COMPTABO - ' + description,
      item_price: amount.toString(),
      currency: 'XOF',
      ref_command: reference,
      command_name: description,
      env: 'prod',
      ipn_url: BASE_URL + '/ipn',
      success_url: BASE_URL + '/success.html',
      cancel_url: BASE_URL + '/cancel.html',
      custom_field: JSON.stringify({ reference, amount })
    });

    const response = await fetch('https://paytech.sn/api/payment/request-payment', {
      method: 'POST',
      headers: {
        'API_KEY': PAYTECH_API_KEY,
        'API_SECRET': PAYTECH_API_SECRET,
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: formData
    });

    const result = await response.json();

    if (result.success == 1 && result.redirect_url) {
      return new Response(JSON.stringify({
        success: true,
        reference: reference,
        redirect_url: result.redirect_url
      }), {
        headers: { 'Content-Type': 'application/json' }
      });
    } else {
      return new Response(JSON.stringify({
        success: false,
        message: 'Erreur lors de la création du paiement'
      }), {
        headers: { 'Content-Type': 'application/json' }
      });
    }
  } catch (error) {
    return new Response(JSON.stringify({
      success: false,
      message: 'Erreur serveur'
    }), {
      headers: { 'Content-Type': 'application/json' }
    });
  }
}
