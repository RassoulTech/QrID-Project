{{--
  SECTION CONTACT — placée AVANT l'appel à l'action final.

  L'ordre n'est pas indifférent. Le formulaire s'adresse à qui hésite encore :
  il doit rencontrer sa question ouverte AVANT qu'on lui redemande de créer un
  compte. Placé après, il ressemblerait à un lot de consolation.

  DEUX CANAUX CÔTE À CÔTE, et c'est délibéré. Au Sénégal, WhatsApp est le
  canal de support réellement utilisé ; un formulaire seul laisserait la
  moitié des demandes sans réponse. À l'inverse, un numéro seul écarte ceux
  qui écrivent depuis un ordinateur, ou en dehors des heures ouvrables.

  LE FORMULAIRE FONCTIONNE SANS JAVASCRIPT : POST classique, redirection,
  message en session. Aucune requête asynchrone, donc aucun écran qui reste
  figé si un script ne s'est pas chargé.

  ═══════════════════════════════════════════════════════════════════════
  LES CHAMPS SONT CEUX DU DESIGN SYSTEM, ET C'EST UNE CORRECTION
  ═══════════════════════════════════════════════════════════════════════
  Ils étaient écrits à la main, avec la classe « f__input ». Cette classe
  N'EXISTE DANS AUCUNE FEUILLE DE STYLE : elle avait été écrite de mémoire,
  à côté de « f__control » qui est le vrai nom. Les champs n'avaient donc
  AUCUN style — ni hauteur, ni rayon, ni bordure lisible, ni anneau de
  focus, ni traitement du thème sombre. Ce qu'on voyait était le rendu par
  défaut du navigateur, sur une page dont tout le reste est dessiné.

  Rien n'échouait, aucun test ne tombait : une classe absente ne casse pas
  une page, elle la laisse nue. C'est le genre de défaut qui survit aux
  relectures et se voit sur une capture d'écran.

  Corriger le nom de la classe aurait suffi à réparer l'apparence, et aurait
  laissé la cause : un formulaire écrit à part. Les composants x-input,
  x-select et x-textarea portent le style, l'étiquette, l'astérisque déduit
  de « required », le message d'erreur et les attributs d'accessibilité. Ce
  formulaire n'a plus de raison de les réécrire.
--}}
<section class="contact" id="contact">
  <div class="wrap">

    <div class="contact__grille">

      {{-- ================= COLONNE GAUCHE ================= --}}
      <div class="contact__intro">
        <h2 class="section-title">{!! __('landing.contact.titre') !!}</h2>

        <p class="section-sub">
          {!! __('landing.contact.sous_titre') !!}
        </p>

        @if (config('landing.support.whatsapp'))
          <a href="https://wa.me/{{ preg_replace('/\D+/', '', config('landing.support.whatsapp')) }}"
             class="contact__wa" target="_blank" rel="noopener noreferrer">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M12.04 2a9.9 9.9 0 0 0-8.5 15.02L2 22.5l5.62-1.47A9.9 9.9 0 1 0 12.04 2m0 1.67a8.23 8.23 0 1 1-4.19 15.31l-.3-.18-3.34.87.89-3.25-.2-.31A8.23 8.23 0 0 1 12.04 3.67"/>
              <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97s-.47-.15-.67.15-.77.96-.94 1.16-.35.22-.65.07a8.1 8.1 0 0 1-2.39-1.47 9 9 0 0 1-1.65-2.06c-.17-.3-.02-.46.13-.61s.3-.35.45-.52.2-.3.3-.5.05-.37-.02-.52-.67-1.61-.92-2.21c-.24-.58-.49-.5-.67-.51h-.57a1.1 1.1 0 0 0-.8.37 3.35 3.35 0 0 0-1.04 2.48 5.8 5.8 0 0 0 1.22 3.09 13.3 13.3 0 0 0 5.09 4.5c.71.3 1.27.49 1.7.63a4.1 4.1 0 0 0 1.88.12 3.07 3.07 0 0 0 2.01-1.42 2.5 2.5 0 0 0 .17-1.42c-.07-.12-.27-.2-.57-.35z"/>
            </svg>
            <span>
              <strong>{!! __('landing.contact.whatsapp_titre') !!}</strong>
              <small>{{ __('landing.contact.whatsapp_texte') }}</small>
            </span>
          </a>
        @endif
      </div>

      {{-- ================= COLONNE DROITE ================= --}}
      <div class="contact__carte">

        {{-- CONFIRMATION — annoncée aux lecteurs d'écran par role="status".
             Sans lui, quelqu'un qui n'a pas la page sous les yeux ne saurait
             pas que son message est parti, et l'enverrait à nouveau. --}}
        @if (session('contact.envoye'))
          <div class="contact__ok" role="status">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 6L9 17l-5-5"/>
            </svg>
            <span>
              <strong>{{ __('landing.contact.recu_titre') }}</strong>
              {!! __('landing.contact.recu_texte') !!}
            </span>
          </div>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="contact__form" novalidate>
          @csrf

          {{-- La légende de l'astérisque, comme sur tous les formulaires du
               produit : elle est POSÉE UNE FOIS en haut, et non répétée sous
               chaque champ. --}}
          <x-form-legende />

          {{-- PIÈGE À ROBOTS. Masqué par le CSS, retiré du parcours de
               tabulation et de l'arbre d'accessibilité : aucun humain ne peut
               le remplir, les robots remplissent tout. --}}
          <div class="contact__piege" aria-hidden="true">
            <label for="site_web">{{ __('common.champs.piege') }}</label>
            <input type="text" name="site_web" id="site_web" tabindex="-1" autocomplete="off">
          </div>

          <div class="contact__paire">
            <x-input name="name"
                     :label="__('landing.contact.votre_nom')"
                     :value="old('name', auth()->user()?->name)"
                     :placeholder="__('landing.contact.nom_exemple')"
                     autocomplete="name"
                     :required="true" />

            <x-input name="email" type="email"
                     :label="__('common.champs.email')"
                     :value="old('email', auth()->user()?->email)"
                     :placeholder="__('auth.champs.email_exemple')"
                     inputmode="email" autocomplete="email"
                     :required="true" />
          </div>

          <div class="contact__paire">
            {{-- Facultatif, et dit comme tel : exiger un numéro ferait
                 renoncer ceux qui ne veulent pas être appelés, pour une
                 information dont on n'a pas besoin pour répondre. --}}
            {{-- LE MÊME CHAMP QUE PARTOUT AILLEURS.
                 C'était un <x-input> libre, plafonné à trente caractères et
                 validé par rien : le seul formulaire du produit à ne pas
                 passer par le sélecteur de pays et la règle
                 TelephoneInternational. Un visiteur ivoirien y saisissait un
                 numéro que personne ne pouvait rappeler, et rien ne le lui
                 disait. Il reste facultatif — c'est le contrôle qui change,
                 pas l'exigence. --}}
            <x-phone-field name="phone"
                           :label="__('common.champs.telephone')"
                           :value="old('phone', auth()->user()?->phone)"
                           :required="false"
                           :optional="true" />

            {{-- LE MOTIF PEUT ÊTRE PRÉSÉLECTIONNÉ PAR L'URL : /#contact?motif=commande.
                 Un lien « Nous écrire » posé ailleurs amène ainsi la personne
                 avec le bon motif déjà choisi, au lieu d'une liste où il faut
                 deviner lequel s'applique.

                 La valeur est validée contre la liste fermée avant d'être
                 retenue : un motif inventé dans l'URL retombe sur le premier. --}}
            @php
              $motifDemande = request()->query('motif');
              $motifChoisi = old('subject')
                  ?? (in_array($motifDemande, \App\Http\Requests\ContactRequest::SUJETS, true)
                      ? $motifDemande
                      : \App\Http\Requests\ContactRequest::SUJETS[0]);

              /* SUJETS ne porte plus que des CLÉS. Il portait les libellés
                 français, que la vue passait à __() : la phrase française
                 servait donc de clé de traduction, et la reformuler aurait
                 fait disparaître l'anglais sans le moindre signal. */
              $motifs = collect(\App\Http\Requests\ContactRequest::SUJETS)
                  ->mapWithKeys(fn (string $cle) => [$cle => __('landing.contact.motifs.'.$cle)])
                  ->all();
            @endphp

            <x-select name="subject"
                      :label="__('common.champs.motif')"
                      :options="$motifs"
                      :selected="$motifChoisi"
                      :required="true" />
          </div>

          <x-textarea name="message"
                      :label="__('landing.contact.votre_message')"
                      :value="old('message')"
                      :rows="5"
                      :placeholder="__('landing.contact.message_exemple')"
                      :required="true" />

          <x-button :block="true">{{ __('landing.contact.envoyer') }}</x-button>

          <p class="contact__legal">
            {{ __('landing.contact.legal') }}
          </p>
        </form>
      </div>
    </div>
  </div>
</section>
