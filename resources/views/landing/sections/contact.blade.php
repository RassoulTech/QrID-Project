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
--}}
<section class="contact" id="contact">
  <div class="wrap">

    <div class="contact__grille">

      {{-- ================= COLONNE GAUCHE ================= --}}
      <div class="contact__intro">
        <h2 class="section-title">Une question&nbsp;? Écrivez-nous.</h2>

        <p class="section-sub">
          Une demande sur le service, une commande de cartes imprimées, ou
          simplement un doute&nbsp;: nous répondons sous 24&nbsp;heures ouvrées.
        </p>

        @if (config('landing.support.whatsapp'))
          <a href="https://wa.me/{{ preg_replace('/\D+/', '', config('landing.support.whatsapp')) }}"
             class="contact__wa" target="_blank" rel="noopener noreferrer">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M12.04 2a9.9 9.9 0 0 0-8.5 15.02L2 22.5l5.62-1.47A9.9 9.9 0 1 0 12.04 2m0 1.67a8.23 8.23 0 1 1-4.19 15.31l-.3-.18-3.34.87.89-3.25-.2-.31A8.23 8.23 0 0 1 12.04 3.67"/>
              <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97s-.47-.15-.67.15-.77.96-.94 1.16-.35.22-.65.07a8.1 8.1 0 0 1-2.39-1.47 9 9 0 0 1-1.65-2.06c-.17-.3-.02-.46.13-.61s.3-.35.45-.52.2-.3.3-.5.05-.37-.02-.52-.67-1.61-.92-2.21c-.24-.58-.49-.5-.67-.51h-.57a1.1 1.1 0 0 0-.8.37 3.35 3.35 0 0 0-1.04 2.48 5.8 5.8 0 0 0 1.22 3.09 13.3 13.3 0 0 0 5.09 4.5c.71.3 1.27.49 1.7.63a4.1 4.1 0 0 0 1.88.12 3.07 3.07 0 0 0 2.01-1.42 2.5 2.5 0 0 0 .17-1.42c-.07-.12-.27-.2-.57-.35z"/>
            </svg>
            <span>
              <strong>Répondre plus vite&nbsp;: WhatsApp</strong>
              <small>Le canal le plus rapide, du lundi au samedi.</small>
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
              <strong>Message reçu.</strong>
              Nous vous répondons à l'adresse indiquée, sous 24&nbsp;heures ouvrées.
            </span>
          </div>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="contact__form" novalidate>
          @csrf

          {{-- PIÈGE À ROBOTS. Masqué par le CSS, retiré du parcours de
               tabulation et de l'arbre d'accessibilité : aucun humain ne peut
               le remplir, les robots remplissent tout. --}}
          <div class="contact__piege" aria-hidden="true">
            <label for="site_web">Ne remplissez pas ce champ</label>
            <input type="text" name="site_web" id="site_web" tabindex="-1" autocomplete="off">
          </div>

          <div class="contact__paire">
            <div class="f">
              <label class="f__label" for="contact_name">Votre nom</label>
              <input type="text" name="name" id="contact_name" class="f__input @error('name') is-invalid @enderror"
                     value="{{ old('name', auth()->user()?->name) }}"
                     autocomplete="name" required
                     @error('name') aria-invalid="true" aria-describedby="contact_name-err" @enderror>
              @error('name')<span class="f__error" id="contact_name-err">{{ $message }}</span>@enderror
            </div>

            <div class="f">
              <label class="f__label" for="contact_email">Adresse e-mail</label>
              <input type="email" name="email" id="contact_email" class="f__input @error('email') is-invalid @enderror"
                     value="{{ old('email', auth()->user()?->email) }}"
                     placeholder="vous@exemple.sn" inputmode="email" autocomplete="email" required
                     @error('email') aria-invalid="true" aria-describedby="contact_email-err" @enderror>
              @error('email')<span class="f__error" id="contact_email-err">{{ $message }}</span>@enderror
            </div>
          </div>

          <div class="contact__paire">
            <div class="f">
              {{-- Facultatif, et dit comme tel : exiger un numéro ferait
                   renoncer ceux qui ne veulent pas être appelés, pour une
                   information dont on n'a pas besoin pour répondre. --}}
              <label class="f__label" for="contact_phone">
                Téléphone <span class="f__hint">— facultatif</span>
              </label>
              <input type="tel" name="phone" id="contact_phone" class="f__input @error('phone') is-invalid @enderror"
                     value="{{ old('phone', auth()->user()?->phone) }}"
                     placeholder="77 000 00 00" inputmode="tel" autocomplete="tel">
              @error('phone')<span class="f__error">{{ $message }}</span>@enderror
            </div>

            <div class="f">
              <label class="f__label" for="contact_subject">Motif</label>
              <select name="subject" id="contact_subject" class="f__input @error('subject') is-invalid @enderror" required>
                @foreach (\App\Http\Requests\ContactRequest::SUJETS as $cle => $libelle)
                  <option value="{{ $cle }}" @selected(old('subject') === $cle)>{{ $libelle }}</option>
                @endforeach
              </select>
              @error('subject')<span class="f__error">{{ $message }}</span>@enderror
            </div>
          </div>

          <div class="f">
            <label class="f__label" for="contact_message">Votre message</label>
            <textarea name="message" id="contact_message" rows="5"
                      class="f__input @error('message') is-invalid @enderror"
                      placeholder="Dites-nous en quelques lignes ce dont vous avez besoin."
                      required
                      @error('message') aria-invalid="true" aria-describedby="contact_message-err" @enderror
            >{{ old('message') }}</textarea>
            @error('message')<span class="f__error" id="contact_message-err">{{ $message }}</span>@enderror
          </div>

          <x-button :block="true">Envoyer mon message</x-button>

          <p class="contact__legal">
            Vos coordonnées servent uniquement à vous répondre. Elles ne sont ni
            revendues, ni utilisées pour de la prospection.
          </p>
        </form>
      </div>
    </div>
  </div>
</section>
