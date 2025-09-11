/**
 * Password strength meter functionality.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/public/js
 */

(function($) {
    'use strict';

    /**
     * Initialize the password strength meter.
     */
    function initPasswordStrengthMeter() {
        // Check if we're on a page with a password field
        var $passwordField = $('input[name="pass1"]');

        if ($passwordField.length === 0) {
            return;
        }

        // Create the strength meter container if it doesn't exist
        var $strengthMeter = $('#kura-ai-password-strength');

        if ($strengthMeter.length === 0) {
            $passwordField.after('<div id="kura-ai-password-strength"></div>');
            $strengthMeter = $('#kura-ai-password-strength');
        }

        // Add the strength meter message container
        var $strengthMeterMessage = $('#kura-ai-password-strength-message');

        if ($strengthMeterMessage.length === 0) {
            $strengthMeter.after('<div id="kura-ai-password-strength-message"></div>');
            $strengthMeterMessage = $('#kura-ai-password-strength-message');
        }

        // Check password strength when the password field changes
        $passwordField.on('keyup', function() {
            var password = $(this).val();
            var strength = wp.passwordStrength.meter(password, wp.passwordStrength.userInputDisallowedList(), '');

            // Update the strength meter
            updateStrengthMeter(strength, $strengthMeter, $strengthMeterMessage);

            // Check if the password has been pwned
            if (password.length >= 8) {
                checkPasswordPwned(password, $strengthMeterMessage);
            }
        });
    }

    /**
     * Update the strength meter.
     *
     * @param {number} strength - The password strength (0-4).
     * @param {jQuery} $strengthMeter - The strength meter element.
     * @param {jQuery} $strengthMeterMessage - The strength meter message element.
     */
    function updateStrengthMeter(strength, $strengthMeter, $strengthMeterMessage) {
        // Remove all classes
        $strengthMeter.removeClass('short bad good strong');

        // Add the appropriate class based on the strength
        switch (strength) {
            case 0:
            case 1:
                $strengthMeter.addClass('short');
                $strengthMeterMessage.text(kuraAiPasswordStrength.short);
                break;
            case 2:
                $strengthMeter.addClass('bad');
                $strengthMeterMessage.text(kuraAiPasswordStrength.bad);
                break;
            case 3:
                $strengthMeter.addClass('good');
                $strengthMeterMessage.text(kuraAiPasswordStrength.good);
                break;
            case 4:
                $strengthMeter.addClass('strong');
                $strengthMeterMessage.text(kuraAiPasswordStrength.strong);
                break;
        }
    }

    /**
     * Check if a password has been pwned.
     *
     * @param {string} password - The password to check.
     * @param {jQuery} $strengthMeterMessage - The strength meter message element.
     */
    function checkPasswordPwned(password, $strengthMeterMessage) {
        // Generate the SHA-1 hash of the password
        var hash = sha1(password).toUpperCase();
        var prefix = hash.substring(0, 5);
        var suffix = hash.substring(5);

        // Make a request to the HaveIBeenPwned API
        $.ajax({
            url: 'https://api.pwnedpasswords.com/range/' + prefix,
            type: 'GET',
            success: function(data) {
                var lines = data.split('\n');
                var found = false;
                var count = 0;

                for (var i = 0; i < lines.length; i++) {
                    var parts = lines[i].split(':');

                    if (parts.length === 2 && parts[0] === suffix) {
                        found = true;
                        count = parseInt(parts[1], 10);
                        break;
                    }
                }

                if (found) {
                    $strengthMeterMessage.append('<p class="kura-ai-password-pwned">' + kuraAiPasswordStrength.pwned.replace('%d', count) + '</p>');
                }
            }
        });
    }

    /**
     * SHA-1 hash function.
     *
     * @param {string} str - The string to hash.
     * @return {string} The SHA-1 hash.
     */
    function sha1(str) {
        // This is a simple implementation of SHA-1 for client-side use
        // In a real implementation, you would use a proper crypto library
        var rotate_left = function(n, s) {
            return (n << s) | (n >>> (32 - s));
        };

        var cvt_hex = function(val) {
            var str = '';
            var i;
            var v;

            for (i = 7; i >= 0; i--) {
                v = (val >>> (i * 4)) & 0x0f;
                str += v.toString(16);
            }
            return str;
        };

        var blockstart;
        var i, j;
        var W = new Array(80);
        var H0 = 0x67452301;
        var H1 = 0xEFCDAB89;
        var H2 = 0x98BADCFE;
        var H3 = 0x10325476;
        var H4 = 0xC3D2E1F0;
        var A, B, C, D, E;
        var temp;

        // utf8_encode
        str = unescape(encodeURIComponent(str));
        var str_len = str.length;

        var word_array = [];
        for (i = 0; i < str_len - 3; i += 4) {
            j = str.charCodeAt(i) << 24 | str.charCodeAt(i + 1) << 16 | str.charCodeAt(i + 2) << 8 | str.charCodeAt(i + 3);
            word_array.push(j);
        }

        switch (str_len % 4) {
            case 0:
                i = 0x080000000;
                break;
            case 1:
                i = str.charCodeAt(str_len - 1) << 24 | 0x0800000;
                break;
            case 2:
                i = str.charCodeAt(str_len - 2) << 24 | str.charCodeAt(str_len - 1) << 16 | 0x08000;
                break;
            case 3:
                i = str.charCodeAt(str_len - 3) << 24 | str.charCodeAt(str_len - 2) << 16 | str.charCodeAt(str_len - 1) << 8 | 0x80;
                break;
        }

        word_array.push(i);

        while ((word_array.length % 16) != 14) {
            word_array.push(0);
        }

        word_array.push(str_len >>> 29);
        word_array.push((str_len << 3) & 0x0ffffffff);

        for (blockstart = 0; blockstart < word_array.length; blockstart += 16) {
            for (i = 0; i < 16; i++) {
                W[i] = word_array[blockstart + i];
            }
            for (i = 16; i < 80; i++) {
                W[i] = rotate_left(W[i - 3] ^ W[i - 8] ^ W[i - 14] ^ W[i - 16], 1);
            }

            A = H0;
            B = H1;
            C = H2;
            D = H3;
            E = H4;

            for (i = 0; i < 20; i++) {
                temp = (rotate_left(A, 5) + ((B & C) | (~B & D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (i = 20; i < 40; i++) {
                temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (i = 40; i < 60; i++) {
                temp = (rotate_left(A, 5) + ((B & C) | (B & D) | (C & D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (i = 60; i < 80; i++) {
                temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            H0 = (H0 + A) & 0x0ffffffff;
            H1 = (H1 + B) & 0x0ffffffff;
            H2 = (H2 + C) & 0x0ffffffff;
            H3 = (H3 + D) & 0x0ffffffff;
            H4 = (H4 + E) & 0x0ffffffff;
        }

        temp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);
        return temp.toLowerCase();
    }

    // Initialize when the document is ready
    $(document).ready(function() {
        initPasswordStrengthMeter();
    });

})(jQuery);