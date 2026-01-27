<?php
require __DIR__.'/vendor/autoload.php';

$classes = [
    'Filament\Pages\Auth\Login',
    'Filament\Auth\Pages\Login',
    'AbanoubNassem\FilamentGRecaptchaField\Forms\Components\GRecaptcha'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "$class exists.\n";
    } else {
        echo "$class DOES NOT exist.\n";
    }
}
