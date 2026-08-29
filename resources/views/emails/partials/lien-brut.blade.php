{{--
  Repli du bouton : l'URL en toutes lettres.

  Indispensable, et pas seulement par principe. Beaucoup de clients lisent
  leur courrier dans l'application Gmail d'un téléphone d'entrée de gamme, où
  les images et parfois les styles sont bloqués : le bouton disparaît alors
  purement et simplement. Sans cette ligne, le message devient une impasse.

  word-break:break-all évite qu'une longue URL déborde de la carte sur mobile.
--}}
<p style="margin:0 0 16px;font-size:13px;color:#64748b;word-break:break-all;">
    {{ __('emails.commun.lien_brut') }}<br>
    <a href="{{ $url }}" style="color:#0B5D3B;">{{ $url }}</a>
</p>
