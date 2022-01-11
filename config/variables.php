<?php

return [
    'matches' => [
        'minTotalScore' => env('MATCHES_MIN_TOTAL_SCORE', 5),
        'chunkSize' => env('MATCHING_CHUNK_SIZE', 100),
    ],
];