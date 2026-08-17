<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de Prêt</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f7; margin: 0; padding: 0;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
                    <!-- En-tête -->
                    <tr>
                        <td style="padding: 30px; text-align: center; border-bottom: 3px solid {{ $status === 'approved' ? '#10b981' : ($status === 'rejected' ? '#ef4444' : '#6366f1') }};">
                            <h1 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: -0.5px;">
                                {{ $status === 'approved' ? 'Prêt Accordé' : ($status === 'rejected' ? 'Demande Refusée' : 'Mise à Jour') }}
                            </h1>
                        </td>
                    </tr>

                    <!-- Contenu Principal -->
                    <tr>
                        <td style="padding: 40px 30px; color: #475569; font-size: 16px; line-height: 1.6;">
                            <p style="margin-bottom: 20px;">Bonjour <strong>{{ $loan->client->first_name }} {{ $loan->client->last_name }}</strong>,</p>

                            <p style="margin-bottom: 20px;">
                                {!! nl2br(e($message)) !!}
                            </p>

                            <!-- Détails de la Demande -->
                            <div style="background-color: #f8fafc; border-radius: 6px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                                <table width="100%" style="border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 5px 0; color: #64748b; font-size: 14px;">N° Dossier :</td>
                                        <td style="padding: 5px 0; color: #1e293b; font-weight: bold; text-align: right;">{{ $loan->loan_number }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; color: #64748b; font-size: 14px;">Montant Concerné :</td>
                                        <td style="padding: 5px 0; color: #1e293b; font-weight: bold; text-align: right;">{{ number_format($loan->approved_amount ?? $loan->requested_amount, 0, ',', ' ') }} XOF</td>
                                    </tr>
                                    @if($status === 'approved')
                                    <tr>
                                        <td style="padding: 5px 0; color: #64748b; font-size: 14px;">Mensualité Estimée :</td>
                                        <td style="padding: 5px 0; color: #10b981; font-weight: bold; text-align: right;">{{ number_format($loan->monthly_payment, 0, ',', ' ') }} XOF</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>

                            <p style="margin-bottom: 0;">
                                @if($status === 'approved')
                                    Veuillez vous rapprocher de votre gestionnaire pour finaliser les documents de décaissement.
                                @elseif($status === 'rejected')
                                    Si vous avez des questions concernant cette décision, n'hésitez pas à contacter notre service support.
                                @else
                                    Connectez-vous à votre espace membre pour plus de détails.
                                @endif
                            </p>
                        </td>
                    </tr>

                    <!-- Pied de page -->
                    <tr>
                        <td style="padding: 30px; background-color: #f1f5f9; text-align: center; color: #94a3b8; font-size: 12px;">
                            <p style="margin: 0 0 10px;">Ceci est un message automatique, merci de ne pas y répondre directement.</p>
                            <p style="margin: 0; font-weight: bold;">© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
