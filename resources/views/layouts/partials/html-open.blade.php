{{--
  Ouverture de <html>, commune à TOUS les gabarits.

  Le thème est posé ICI, par le serveur, dès le premier octet de la réponse :
  aucun clignotement au chargement d'une page sombre, et la bascule fonctionne
  sans une ligne de JavaScript.

  Le thème vient du COMPTE quand quelqu'un est connecté, d'un COOKIE sinon :
  un visiteur de la landing ou du formulaire de connexion peut donc basculer
  lui aussi. Un seul endroit décide — voir App\Support\Theme.
--}}
<html lang="fr" @class(['theme-dark' => App\Support\Theme::estSombre()])>
