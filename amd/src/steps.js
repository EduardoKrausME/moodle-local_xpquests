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
