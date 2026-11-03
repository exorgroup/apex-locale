<?php

/**
 * Countries whose CASH totals round to a coarser step than the currency's smallest unit (LC1 D3,
 * P-D118). The step is in the minor units of the currency cashed there: 5 = 0.05.
 * Finland, the Netherlands, Ireland and Belgium round euro cash totals to 5 cents; Malta does not.
 * Card payments are never rounded. Seeded on insert only; never overwritten.
 *
 * @return array<string, int> iso2 => step in minor units
 */
return [
    'BE' => 5,
    'FI' => 5,
    'IE' => 5,
    'NL' => 5,
];
