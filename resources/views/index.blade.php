<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ottawa's LRT Down?</title>

    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
    <script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script>
    <script defer src="{{ mix('/js/app.js') }}"></script>
</head>
<body>
    @if ($lrtDown)
    <section class="hero is-danger is-fullheight">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title is-1">
                    Is the LRT Down? Yes
                </h1>
                <h2 class="subtitle is-3">
                    0️⃣ 0️⃣ days issue free
                <h2>
            </div>
        </div>
    </section>
    @else
    <section class="hero is-success is-fullheight">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title is-1">
                    Is the LRT Down? No <small><em><a class="has-text-white" href="#">but maybe yes</a> ¯\_(ツ)_/¯</em></small>
                </h1>
                <h2 class="subtitle is-3">
                    0️⃣ 1️⃣ days issue free
                <h2>
            </div>
        </div>
    </section>
    @endif
    <footer class="footer">
        <div class="content has-text-centered">
            <p>
                <strong>LRTdown.ca</strong> by <a href="https://twitter.com/eruraindil" target="_blank">@eruraindil</a>. This is not an official website. Not even close.
            </p>
        </div>
    </footer>
</body>
</html>
