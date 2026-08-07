@php $copy = \App\Support\PageContent::get('coming-soon', 'unsubscribed'); @endphp

<x-layouts.frith title="You’re off the Frith waiting list">

    <main class="site-main gutter slim" id="main">
        <div class="slim__inner">
            <h1 class="slim__heading">{{ $copy['heading'] }}</h1>
            <p class="slim__body">{{ $copy['body'] }}</p>

            {{-- Mail filters follow links, so this page can be reached without
                 anyone meaning to. Coming back is one tap, same as leaving. --}}
            <form method="POST" action="{{ $resubscribeUrl }}">
                @csrf
                <button class="btn" type="submit">Actually, keep me on the list</button>
            </form>
        </div>
    </main>

</x-layouts.frith>
