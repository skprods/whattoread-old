<?php

return [
    'matches' => [
        'minTotalScore' => env('MATCHES_MIN_TOTAL_SCORE', 5),
        'chunkSize' => env('MATCHING_CHUNK_SIZE', 100),
        'weight' => [
            'genres' => env('MATCHING_WEIGHT_GENRES', 10),
        ],
    ],
    'vectors' => [
        'length' => (int) env("VECTORS_LENGTH", 300),
        'min' => (int) env("VECTORS_MIN", -5),
        'max' => (int) env("VECTORS_MAX", 5),
    ],
    'dictionaries' => [
        'rusvectors' => env("RUSVECTORS_DICTIONARY", 'geowac_lemmas_none_fasttextskipgram_300_5_2020'),
    ],
];