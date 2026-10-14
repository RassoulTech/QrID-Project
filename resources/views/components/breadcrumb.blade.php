{{--
  x-breadcrumb — le fil d'Ariane.

      <x-breadcrumb :items="[
          ['label' => 'Clients', 'url' => route('admin.clients.index')],
          ['label' => 'Awa Ndiaye'],
      ]" />

  Le dernier élément est la page courante (aria-current), sans lien. Les
  séparateurs sont des chevrons décoratifs, cachés au lecteur d'écran.
--}}
@props(['items' => []])

@if (count($items))
    <nav aria-label="{{ __('common.divers.fil_ariane') }}">
        <ol class="fil">
            @foreach ($items as $item)
                @php $isLast = $loop->last || empty($item['url']); @endphp
                <li @if ($isLast) aria-current="page" @endif>
                    @unless ($loop->first)
                        <x-icone name="chevron-right" />
                    @endunless
                    @if (! $isLast)
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @else
                        {{ $item['label'] }}
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
