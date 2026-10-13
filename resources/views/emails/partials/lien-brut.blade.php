{{--
  Repli du bouton : l'URL en toutes lettres.

  Indispensable, et pas seulement par principe. Beaucoup de clients lisent
  leur courrier dans l'application Gmail d'un téléphone d'entrée de gamme, où
  les images et parfois les styles sont bloqués : le bouton disparaît alors
  purement et simplement. Sans cette ligne, le message devient une impasse.

  La coupure est posée sur le LIEN seulement : une longue URL ne déborde pas
  de la carte sur mobile, et elle se coupe d'abord aux tirets. L'ancien
  word-break:break-all, posé sur tout le paragraphe, coupait aussi la phrase
  d'introduction au milieu des mots (« dan s votre navigateur »).
  Le lien est SOULIGNÉ en plus d'être coloré : la couleur seule ne suffit pas
  à signaler un lien (#146A50 sur ivoire, 6,4:1).
--}}
<p class="q-texte2" style="margin:0 0 20px;font-size:14px;line-height:22px;color:#56605A;">
    {{ __('emails.commun.lien_brut') }}<br>
    <a href="{{ $url }}" class="q-lien" style="color:#146A50;text-decoration:underline;word-break:break-word;overflow-wrap:anywhere;">{{ $url }}</a>
</p>
