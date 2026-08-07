@php $copy = \App\Support\PageContent::get('coming-soon', 'confirmed'); @endphp

<x-layouts.frith title="You’re on the Frith waiting list">

    <main class="site-main gutter slim" id="main">
        <div class="slim__inner">
            <h1 class="slim__heading">{{ $copy['heading'] }}</h1>
            <p class="slim__body">{{ $copy['body'] }}</p>
            <a class="btn btn--link" href="mailto:{{ config('frith.company.contact_email') }}">
                Write to {{ config('frith.company.contact_email') }}
            </a>
        </div>
    </main>

</x-layouts.frith>
