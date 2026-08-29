{{--
  Gabarit d'e-mail unique du produit. Tous les Mailables en héritent :

      @component('emails.layout', ['title' => 'Sujet'])
          ...contenu...
      @endcomponent

  Contraintes de délivrabilité, à ne jamais enfreindre :
  tableaux HTML, CSS strictement en ligne, aucune police ni image externe,
  aucun JavaScript. Les couleurs reprennent les tokens de _variables.scss
  (primaire #0B5D3B, gris #1E293B / #64748B / #E6EAF0).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1E293B;line-height:1.6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F8FAFC;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:480px;background:#FFFFFF;border-radius:12px;border:1px solid #E6EAF0;overflow:hidden;">
                    {{-- En-tête : marque --}}
                    <tr>
                        <td style="background:#0B5D3B;padding:20px 24px;">
                            <span style="color:#FFFFFF;font-size:19px;font-weight:800;letter-spacing:-.03em;">
                                {{ config('app.name') }}
                            </span>
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td style="padding:28px 24px;font-size:15px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Pied --}}
                    <tr>
                        <td style="padding:16px 24px;border-top:1px solid #E6EAF0;font-size:12px;color:#94A3B8;">
                            &copy; {{ date('Y') }} {{ config('app.name') }} — {{ __('emails.commun.pied_produit') }}<br>
                            {{ __('emails.commun.pied_raison') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
