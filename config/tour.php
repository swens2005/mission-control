<?php

/*
|--------------------------------------------------------------------------
| Autopilot tours ("Watch it fly", ADR 0011)
|--------------------------------------------------------------------------
|
| The speed of every caption, cursor movement and typed character, in
| percent: 100 is normal, 0 makes everything instant. The browser tests
| run at 0 (phpunit.xml).
|
*/

return [

    'speed' => max(0, (int) env('TOUR_SPEED', 100)),

];
