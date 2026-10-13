<?php
// RFC 6238 Appendix B test vectors for the TOTP code in src/Models/UserModel.php
//
// The SHA-1 rows of Appendix B use the ASCII secret "12345678901234567890", a
// 30 second time step and T0 = 0, and list 8-digit codes (per erratum 2866 the
// SHA-256 and SHA-512 rows use longer secrets). UserModel's TOTP uses HMAC-SHA1,
// 6 digits and a 30 second step, so this script replays the SHA-1 rows.
//
// verify2FACode turns time() into a step count and generateTOTP turns a step
// count into a code. The script repeats verify2FACode's division by 30 instead
// of calling it, so what is under test is generateTOTP, not the step size or
// the +/-1 window.
//
// Each 6-digit result is compared with the last six digits of the RFC's 8-digit
// value. HOTP's truncation (RFC 4226 section 5.3) reduces a 31-bit number mod
// 10^digits, so the 6-digit code is the 8-digit code mod 10^6.
//
// generateTOTP packs the counter as two 32-bit words with the high word set to
// zero (pack('N*', 0, $time)). That holds for every counter in the table, the
// largest being floor(20000000000 / 30) = 666666666. Counters at or above 2^32
// are outside both the implementation and this test.
//
// generateTOTP is private, so it is called through reflection on an instance
// built without the constructor, which means no database connection is needed.
//
// Usage: php tests/totp_rfc6238_vectors.php (exit code 1 if any vector fails)

require_once __DIR__ . '/../src/Models/UserModel.php';

// RFC 4648 base32 (a 20-byte secret is 160 bits, so no padding is needed)
function base32Encode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    $bits = '';
    foreach (str_split($data) as $char) {
        $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }

    $encoded = '';
    foreach (str_split($bits, 5) as $chunk) {
        $encoded .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
    }

    return $encoded;
}

$secret = base32Encode('12345678901234567890');

// Time (sec) and TOTP columns of the SHA1 rows in RFC 6238 Appendix B, Table 1
$vectors = [
    [59, '94287082'],
    [1111111109, '07081804'],
    [1111111111, '14050471'],
    [1234567890, '89005924'],
    [2000000000, '69279037'],
    [20000000000, '65353130'],
];

$model = (new ReflectionClass('UserModel'))->newInstanceWithoutConstructor();
$generateTOTP = new ReflectionMethod('UserModel', 'generateTOTP');

$failed = false;

foreach ($vectors as [$time, $rfc_code]) {
    // Same step count that verify2FACode computes from time()
    $counter = floor($time / 30);
    $expected = substr($rfc_code, -6);
    $actual = $generateTOTP->invoke($model, $secret, $counter);

    $ok = hash_equals($expected, $actual);
    if (!$ok) {
        $failed = true;
    }

    printf("%s  time %-11d  T %016X  expected %s  got %s\n", $ok ? 'PASS' : 'FAIL', $time, $counter, $expected, $actual);
}

if ($failed) {
    echo "FAIL: generateTOTP does not match RFC 6238 Appendix B\n";
    exit(1);
}

echo "PASS: generateTOTP matches the SHA-1 vectors of RFC 6238 Appendix B (last six digits)\n";
