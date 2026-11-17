<?php
// nyw_validate_tckn: validate Turkish Republic ID number
function nyw_validate_tckn($nyw_tckn) {

    // Step 1: Check format — must be exactly 11 digits.
    // A valid TCKN consists of 11 numeric characters only. :contentReference[oaicite:1]{index=1}
    if (!preg_match('/^\d{11}$/', $nyw_tckn)) {
        return false;
    }

    // Step 2: Reject if the first digit is '0'.
    // Official TCKN rules specify that the first digit cannot be zero. :contentReference[oaicite:2]{index=2}
    if ($nyw_tckn[0] == '0') {
        return false;
    }

    // Step 3: Reject if all digits are identical.
    // Sequences like '11111111111' are considered invalid patterns. :contentReference[oaicite:3]{index=3}
    if (count(array_unique(str_split($nyw_tckn))) === 1) {
        return false;
    }

    // Step 4: Convert the string to an array of integer digits.
    $nyw_digits = array_map('intval', str_split($nyw_tckn));

    // Step 5: Calculate sum of digits in odd positions (1st, 3rd, ..., 9th).
    // These correspond to indices 0,2,4,6,8 in zero‑based arrays. :contentReference[oaicite:4]{index=4}
    $nyw_sumOdd  = $nyw_digits[0] + $nyw_digits[2] + $nyw_digits[4] + $nyw_digits[6] + $nyw_digits[8];

    // Step 6: Calculate sum of digits in even positions (2nd, 4th, ..., 8th).
    // These correspond to indices 1,3,5,7 in zero‑based arrays. :contentReference[oaicite:5]{index=5}
    $nyw_sumEven = $nyw_digits[1] + $nyw_digits[3] + $nyw_digits[5] + $nyw_digits[7];

    // Step 7: Compute the expected 10th digit using the TCKN control formula:
    // ((sum of odd position digits × 7) − sum of even position digits) mod 10. :contentReference[oaicite:6]{index=6}
    $nyw_calc10  = (($nyw_sumOdd * 7) - $nyw_sumEven) % 10;

    // If the intermediate result is negative, adjust by adding 10 (to ensure a non‑negative remainder).
    if ($nyw_calc10 < 0) {
        $nyw_calc10 += 10;
    }

    // Step 8: Check if the actual 10th digit matches the calculated one.
    if ($nyw_digits[9] !== $nyw_calc10) {
        return false;
    }

    // Step 9: Sum the first 10 digits.
    $nyw_sumAll10 = array_sum(array_slice($nyw_digits, 0, 10));

    // Step 10: Compute the expected 11th digit:
    // the sum of the first 10 digits modulo 10. :contentReference[oaicite:7]{index=7}
    $nyw_calc11   = $nyw_sumAll10 % 10;

    // Step 11: Return true only if the actual 11th digit matches the calculated value.
    return $nyw_digits[10] === $nyw_calc11;
}
?>