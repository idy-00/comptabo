export async function onRequestPost(context) {
  const PAYTECH_API_KEY = 'a30dd0860b8fe70ed189f682ed7799718fb5315d2f5eb9ed1085155d224442f6';
  const PAYTECH_API_SECRET = 'dd95c7bfc0a10ccc0652328b8bd03bf56758b690f8413e8e7fde292e23087e65';
  const BASE_URL = 'https://comptabo.pages.dev';

  const corsHeaders = {
    'Content-Type': 'application/json',
    'Access-Control-Allow-Origin': '*'
  };

  try {
    const input = await context.request.json();
    const amount = parseInt(input.amount) || 0;
    const description = input.description || 'Paiement Comptabo';

    if (amount < 100) {
      return new Response(JSON.stringify({
        success: false,
        message: 'Montant minimum: 100 FCFA'
      }), { headers: corsHeaders });
    }

    const reference = 'CPT' + Date.now() + Math.floor(Math.random() * 1000);

    // Données pour PayTech
    const paymentData = {
      item_name: 'COMPTABO - ' + description,
      item_price: amount,
      currency: 'XOF',
      ref_command: reference,
      command_name: description,
      env: 'prod',
      ipn_url: BASE_URL + '/ipn',
      success_url: BASE_URL + '/success.html',
      cancel_url: BASE_URL + '/cancel.html',
      custom_field: JSON.stringify({ reference, amount })
    };

    // Convertir en form-urlencoded
    const formBody = Object.keys(paymentData)
      .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(paymentData[key]))
      .join('&');

    const response = await fetch('https://paytech.sn/api/payment/request-payment', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'API_KEY': PAYTECH_API_KEY,
        'API_SECRET': PAYTECH_API_SECRET
      },
      body: formBody
    });

    const responseText = await response.text();
    let result;

    try {
      result = JSON.parse(responseText);
    } catch (e) {
      return new Response(JSON.stringify({
        success: false,
        message: 'Réponse PayTech invalide',
        debug: responseText.substring(0, 200)
      }), { headers: corsHeaders });
    }

    if (result.success == 1 && result.redirect_url) {
      return new Response(JSON.stringify({
        success: true,
        reference: reference,
        redirect_url: result.redirect_url
      }), { headers: corsHeaders });
    } else {
      return new Response(JSON.stringify({
        success: false,
        message: result.message || 'Erreur PayTech',
        error: result.error || null
      }), { headers: corsHeaders });
    }
  } catch (error) {
    return new Response(JSON.stringify({
      success: false,
      message: 'Erreur: ' + error.message
    }), { headers: corsHeaders });
  }
}

export async function onRequestOptions() {
  return new Response(null, {
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type'
    }
  });
}
