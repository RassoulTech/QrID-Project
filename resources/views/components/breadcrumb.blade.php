{{--
  x-breadcrumb — fil d'Ariane.

  <x-breadcrumb :items="[
      ['label' => 'Tableau de bord', 'url' => route('dashboard')],
      ['label' => 'Mon profil'],
  ]" />

  Props : items (tableau de ['label' => ..., 'url' => ...]).
  Le dernier élément, sans url, est marqué comme page courante.
--}}
@props(['items' => []])

@if (count($items))
    <nav aria-label="{{ __('common.divers.fil_ariane') }}">
        <ol class="breadcrumb">
            @foreach ($items as $item)
                @php $isLast = $loop->last || empty($item['url']); @endphp

                <li class="breadcrumb-item {{ $isLast ? 'active' : '' }}"
                    @if ($isLast) aria-current="page" @endif>
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
