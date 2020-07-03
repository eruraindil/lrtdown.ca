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
                <p class="title is-3">
                    Last issue
                    @if (isset($trigger))
                        <span class="tag is-white is-medium">{{ $trigger }}</span>
                    @endif
                    reported {{ $lastUpdate }}
                </p>
                @if (isset($longestStreak))
                    <p class="subtitle is-5">
                        Longest streak of service without system failure: {{ $longestStreak }}.*
                    </p>
                @endif
            </div>
        </div>
        <div class="hero-foot">
            <div class="container has-text-centered">
                <div class="columns">
                    <div class="column is-three-fifths is-offset-one-fifth">
                        <article class="message">
                            <div class="message-header">
                                <p>*LRT was on reduced schedule due to COVID-19 between March and June*</p>
                            </div>
                            <div class="message-body">
                                Longest streak of <b>full service</b> without system failure: 5 days between Feb 20, 2020 and Feb 26, 2020.
                            </div>
                        </article>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </section>
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
                        <p>System status is determined automatically by parsing the <a href="https://twitter.com/OCTranspoLive" target="_blank">@@OCTranspoLive</a> Twitter account every 10 minutes and using <a href="https://raw.githubusercontent.com/eruraindil/lrtdown.ca/master/config/regex.php" target="_blank">keywords</a> to decide if tweets indicate an LRT specific system issue.</p>
                        <p>There may be false positives, false negatives, or completely missed outages if OC Transpo decides not to publish a service alert. This is by no means a complete or perfect system, so tweet at <a href="https://twitter.com/LRTdown" target="_blank">@@LRTdown</a> if something looks wrong or you just want to join the shenanigans.</p>
                        <p>If you'd like to help improve the logic or anything else, do so through the project's <a href="https://github.com/eruraindil/lrtdown.ca" target="_blank">Github</a>.</p>
                    </div>
                </div>
            </div>
        </div>
        <button class="modal-close is-large" aria-label="close"></button>
    </div>
</body>
</html>
