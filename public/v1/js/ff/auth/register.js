/*
 * register.js
 * Copyright (c) 2026 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

$(function () {
    "use strict";
    const form        = document.querySelector('form[action="'+route+'"]');
    const errorBox    = document.getElementById('client-errors');
    const errorList   = document.getElementById('client-errors-list');
    const submitBtn   = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;

    function showErrors(errors) {
        errorList.innerHTML = errors.map(function(e) { return '<li>' + e + '</li>'; }).join('');
        errorBox.style.display = 'block';
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function sha1Hex(str) {
        const buf = await crypto.subtle.digest('SHA-1', new TextEncoder().encode(str));
        return Array.from(new Uint8Array(buf))
            .map(function(b) { return b.toString(16).padStart(2, '0'); })
            .join('')
            .toUpperCase();
    }

    async function isPwned(password) {
        const hash   = await sha1Hex(password);
        const prefix = hash.slice(0, 5);
        const suffix = hash.slice(5);
        const res    = await fetch('https://api.pwnedpasswords.com/range/' + prefix, {
            headers: { 'Add-Padding': 'true' }
        });
        if (!res.ok) { return false; }
        const text = await res.text();
        return text.toUpperCase().split('\n').some(function(line) {
            return line.split(':')[0] === suffix;
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errorBox.style.display = 'none';

        const password = form.querySelector('[name="password"]').value;
        const confirm  = form.querySelector('[name="password_confirmation"]').value;
        const verify   = form.querySelector('[name="verify_password"]');
        const errors   = [];

        if (password.length < 16) {
            errors.push(passwordLengthError);
        }
        if (password !== confirm) {
            errors.push(passwordMatchError);
        }

        if (errors.length > 0) {
            showErrors(errors);
            return;
        }

        if (verify && verify.checked) {
            submitBtn.disabled    = true;
            submitBtn.textContent = waitForVerify;
            try {
                if (await isPwned(password)) {
                    errors.push(needSecurePassword);
                }
            } catch (_) {
                // network failure — let server validate
            }
            submitBtn.disabled    = false;
            submitBtn.textContent = originalBtnText;
        }

        if (errors.length > 0) {
            showErrors(errors);
            return;
        }

        form.submit();
    });
})();
