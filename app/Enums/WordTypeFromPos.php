<?php

namespace App\Enums;

use App\Models\Word;

class WordTypeFromPos
{
    public const ADJ = 'ADJ';       // прилагательное
    public const ADV = 'ADV';       // наречие
    public const INTJ = 'INTJ';     // междометье
    public const NOUN = 'NOUN';     // существительное
    public const PROPN = 'PROPN';   // имя собственное
    public const VERB = 'VERB';     // глагол
    public const ADP = 'ADP';       // предлог
    public const CCONJ = 'CCONJ';   // союз
    public const NUM = 'NUM';       // числительное
    public const PART = 'PART';     // частица
    public const PRON = 'PRON';     // местоимение

    public const TYPES = [
        self::ADJ => Word::ADJECTIVE_TYPE,
        self::ADV => Word::ADVERB_TYPE,
        self::INTJ => Word::INTERJECTION_TYPE,
        self::NOUN => Word::NOUN_TYPE,
        self::PROPN => Word::PROPER_NOUN_TYPE,
        self::VERB => Word::VERB_TYPE,
        self::ADP => Word::PREPOSITION_TYPE,
        self::CCONJ => Word::UNION_TYPE,
        self::NUM => Word::NUMERAL_TYPE,
        self::PART => Word::PARTICLE_TYPE,
        self::PRON => Word::PRONOUN_TYPE,
    ];
}