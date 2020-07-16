<section class="b-w-1/2 b-ml-auto b-px-8 b-mb-16">
    <h3 class="b-font-bold b-block b-mt-8 b-text-3xl b-text-center">
        {{ ucfirst(__('butik::web.total')) }}
    </h3>

    <hr class="b-border-gray-200 b-my-5">

    <div class="b-flex b-my-3 b-justify-between b-max-w-sm b-mx-auto">
        <span>{{ ucfirst(__('butik::web.shipping')) }}</span>
        <span>{{ currency() }} {{ $total_shipping }}</span>
    </div>

    <hr class="b-border-gray-200 b-my-5">

    <div class="b-flex b-mt-3 b-justify-between b-max-w-sm b-mx-auto">
        <span>{{ ucfirst(__('butik::web.total')) }}</span>
        <span class="b-font-black">{{ currency() }} {{ $total_price }}</span>
    </div>
    <div class="b-max-w-sm b-mx-auto b-pb-3 b-text-gray-500 b-text-right b-text-sm">
        {{ __('butik::web.including_taxes') }}
    </div>

    @if ($items->count() !== 0)
        <a href="{{ route('butik.checkout.delivery') }}" class="b-bg-gray-900 b-block b-mt-5 b-py-2 b-rounded b-text-center b-text-white b-text-xl hover:b-bg-gray-800">
            {{ ucfirst(__('butik::web.checkout')) }}
        </a>
    @endif
</section>
