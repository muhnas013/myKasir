<?php

return [
    'pin_max_attempts' => (int) env('PIN_MAX_ATTEMPTS', 5),
    'pin_lock_minutes' => (int) env('PIN_LOCK_MINUTES', 15),
];
