/*
 * respond-to-repetition-end.js
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

function respondToRepetitionEnd() {
    var obj = document.getElementById('ffInput_repetition_end');
    var value = obj.value;
    switch (value) {
        case 'forever':
            document.getElementById('repeat_until_holder').style.display = 'none';
            document.getElementById('repetitions_holder').style.display = 'none';
            break;
        case 'until_date':
            document.getElementById('repeat_until_holder').style.display = 'block';
            document.getElementById('repetitions_holder').style.display = 'none';

            break;
        case 'times':
            document.getElementById('repeat_until_holder').style.display = 'none';
            document.getElementById('repetitions_holder').style.display = 'block';
            break;
    }
}

export {respondToRepetitionEnd}
