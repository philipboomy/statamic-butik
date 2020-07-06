@extends('butik::web.layouts.shop')

@section('content')

    <div class="b-block md:b-flex">
        {{-- Images --}}
        @if (! empty($product->images))
            <img class="b-w-full md:b-w-1/2" src="/assets/{{ $product->images[0] }}">
        @else
            <div class="b-w-full md:b-w-1/2">
                @include('butik::web.shop.partials.placeholder')
            </div>
        @endif

        <div class="b-px-5 md:b-w-1/2 b-w-full">
            @livewire('butik::product-variant-section', ['product' => $product])

            @if ($product->description )
                <p class="b-mt-8">{{ $product->description }}</p>
            @endif

            <a class="lg:b-hidden b--mb-3 b-block b-flex b-mt-8 b-text-gray-400 hover:b-text-gray-500" href="{{ route('butik.shop') }}">
                <svg class="b-fill-current b-mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path class="heroicon-ui" d="M5.41 11H21a1 1 0 0 1 0 2H5.41l5.3 5.3a1 1 0 0 1-1.42 1.4l-7-7a1 1 0 0 1 0-1.4l7-7a1 1 0 0 1 1.42 1.4L5.4 11z"/></svg>
                <span>{{ __('butik::general.back') }}</span>
            </a>
        </div>
    </div>

@endsection
