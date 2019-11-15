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
    <section class="hero is-{{ $contextualClass }} is-fullheight">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title is-1">
                    Is the LRT Down? {{ $status }}
                </h1>
                <h2 class="subtitle is-3">
                    Last issue reported {{ $lastUpdate }}
                <h2>
            </div>
        </div>
    </section>
    {{--
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
    </section>--}}
    <footer class="footer">
        <div class="content has-text-centered">
            <p>
                <strong>LRTdown.ca</strong> by <a href="https://twitter.com/eruraindil" target="_blank">@eruraindil</a>. This is not an official website. Not even close.
            </p>
            <p>
                <button class="button is-info is-small modal-button" data-target="modal" aria-haspopup="true">Methodology</button>
            </p>
            <p>
                <a class="has-text-grey" href="https://twitter.com/LRTdown" target="_blank">
                    <span class="icon is-large"><i class="fab fa-twitter-square fa-3x"></i></span>
                </a>
                <a class="has-text-grey" href="https://github.com/eruraindil/lrtdown.ca" target="_blank">
                    <span class="icon is-large"><i class="fab fa-github-square fa-3x"></i></span>
                </a>
            </p>
        </div>
    </footer>
    <div id="modal" class="modal">
        <div class="modal-background"></div>
        <div class="modal-content">
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <p>System status is determined automatically by parsing the <a href="https://twitter.com/OCTranspoLive" target="_blank">@@OCTranspoLive</a> Twitter account and using keywords to decide if tweets indicate a system failure.</p>
                        <p>There may be false positives and false negatives, this is by no means a complete and perfect system.</p>
                        <p>If you'd like to help improve the logic, do so through Github issues or pull requests.</p>
                    </div>
                </div>
            </div>
        </div>
        <button class="modal-close is-large" aria-label="close"></button>
    </div>
</body>
</html>
