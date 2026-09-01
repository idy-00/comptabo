export async function onRequestPost(context) {
  try {
    const formData = await context.request.formData();

    const typeEvent = formData.get('type_event') || '';
    const refCommand = formData.get('ref_command') || '';
    const itemPrice = formData.get('item_price') || '';
    const paymentMethod = formData.get('payment_method') || '';

    console.log('IPN received:', { typeEvent, refCommand, itemPrice, paymentMethod });

    if (typeEvent === 'sale_complete') {
      // Paiement réussi - tu peux ajouter ta logique ici
      // Par exemple: appeler ton API backend, envoyer un email, etc.

      return new Response(JSON.stringify({
        success: true,
        message: 'Notification reçue',
        reference: refCommand
      }), {
        headers: { 'Content-Type': 'application/json' }
      });
    }

    return new Response(JSON.stringify({
      success: true,
      message: 'Event reçu: ' + typeEvent
    }), {
      headers: { 'Content-Type': 'application/json' }
    });
  } catch (error) {
    return new Response(JSON.stringify({
      success: false,
      message: 'Erreur IPN'
    }), {
      headers: { 'Content-Type': 'application/json' }
    });
  }
}
