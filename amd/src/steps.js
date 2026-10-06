// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * steps.js
 *
 * @package   local_xpquests
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/notification'], function (Notification) {
    const init = (listId, sesskey) => {
        const list = document.getElementById(listId);
        if (!list) {
            return;
        }
        let dragged = null;
        list.querySelectorAll('[draggable="true"]').forEach((item) => {
            item.addEventListener('dragstart', () => {
                dragged = item;
                item.classList.add('is-dragging');
            });
            item.addEventListener('dragend', () => {
                item.classList.remove('is-dragging');
                dragged = null;
                save(list, sesskey);
            });
        });
        list.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (!dragged) {
                return;
            }
            const after = [...list.querySelectorAll('.xpq-sortable-item:not(.is-dragging)')].find((item) => {
                const rect = item.getBoundingClientRect();
                return event.clientY < rect.top + rect.height / 2;
            });
            if (after) {
                list.insertBefore(dragged, after);
            } else {
                list.appendChild(dragged);
            }
        });
    };

    const save = (list, sesskey) => {
        const body = new URLSearchParams();
        body.append('questid', list.dataset.questid);
        body.append('sesskey', sesskey);
        list.querySelectorAll('[data-stepid]').forEach((item) => body.append('order[]', item.dataset.stepid));
        fetch(M.cfg.wwwroot + '/local/xpquests/reorder.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: body.toString(),
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Unable to save quest step order.');
            }
            return response.json();
        }).catch(Notification.exception);
    };

    return {init: init};
});
