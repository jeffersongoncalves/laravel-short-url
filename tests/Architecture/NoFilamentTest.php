<?php

arch('does not depend on Filament')
    ->expect('JeffersonGoncalves\LaravelShortUrl')
    ->not->toUse('Filament');
