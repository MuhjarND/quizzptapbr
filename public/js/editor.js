(() => {
    'use strict';
    const list = document.querySelector('#question-list'), nav = document.querySelector('#question-nav');
    const questionTemplate = document.querySelector('#question-template'), choiceTemplate = document.querySelector('#choice-template');
    const addButton = document.querySelector('#add-question');
    let key = 0;
    function reindex() {
        nav.replaceChildren(); const questions = [...list.children];
        // Detach radio group names before reordering. Renaming one group into another
        // group transiently unchecks the correct answer in native browser controls.
        const selected = questions.map(q => q.querySelector('input[type=radio]:checked'));
        list.querySelectorAll('input[type=radio]').forEach(radio => radio.removeAttribute('name'));
        document.querySelector('#question-total').textContent = questions.length;
        addButton.disabled = questions.length >= 50;
        document.querySelector('.add-question-bottom').disabled = questions.length >= 50;
        questions.forEach((q, i) => {
            q.querySelector('.question-number').textContent = i + 1;
            q.querySelector('.move-up').disabled = i === 0;
            q.querySelector('.move-down').disabled = i === questions.length - 1;
            q.querySelector('.remove-question').disabled = questions.length <= 1;
            q.querySelectorAll('[data-field]').forEach(el => {
                if (el.dataset.field === 'correct_index') return;
                el.name = `questions[${i}][${el.dataset.field}]`;
                el.id = `${q.id}-${el.dataset.field}`;
            });
            q.querySelectorAll('[data-for]').forEach(label => label.htmlFor = `${q.id}-${label.dataset.for}`);
            const choices = [...q.querySelector('.choices-list').children];
            choices.forEach((choice, j) => {
                const radio = choice.querySelector('input[type=radio]'), text = choice.querySelector('.choice-text');
                radio.name = `questions[${i}][correct_index]`; radio.value = j; radio.setAttribute('aria-label', `Jawaban ${String.fromCharCode(65+j)} benar untuk pertanyaan ${i+1}`);
                choice.querySelector('.choice-letter').textContent = String.fromCharCode(65 + j);
                text.name = `questions[${i}][choices][${j}]`; text.setAttribute('aria-label', `Pilihan ${String.fromCharCode(65+j)} untuk pertanyaan ${i+1}`);
                choice.querySelector('.remove-choice').disabled = choices.length <= 2;
            });
            q.querySelector('.add-choice').disabled = choices.length >= 4;
            if (selected[i]) selected[i].checked = true;
            const link = document.createElement('a'); link.href = `#${q.id}`;
            const number = document.createElement('span'); number.textContent = i + 1;
            link.append(number, document.createTextNode(`Pertanyaan ${i + 1}`)); nav.append(link);
        });
    }
    function addChoice(question, text = '', checked = false) {
        const choices = question.querySelector('.choices-list'); if (choices.children.length >= 4) return;
        const item = choiceTemplate.content.firstElementChild.cloneNode(true);
        item.querySelector('.choice-text').value = text; item.querySelector('input[type=radio]').checked = checked;
        item.querySelector('.remove-choice').addEventListener('click', () => {
            const wasChecked = item.querySelector('input[type=radio]').checked;
            item.remove(); if (wasChecked) choices.querySelector('input[type=radio]').checked = true;
            reindex();
        }); choices.append(item);
    }
    function addQuestion(data = {}, focus = false) {
        if (list.children.length >= 50) return;
        const q = questionTemplate.content.firstElementChild.cloneNode(true); q.id = `question-${++key}`;
        q.querySelector('[data-field=text]').value = data.text || '';
        q.querySelector('[data-field=duration]').value = data.duration || 20;
        q.querySelector('[data-field=existing_id]').value = data.existing_id || '';
        if (data.image_url) { const image = q.querySelector('.question-image-preview'); image.src = data.image_url; image.hidden = false; }
        (data.choices || ['', '', '', '']).forEach((text, i) => addChoice(q, text, i === Number(data.correct_index || 0)));
        q.querySelector('.add-choice').addEventListener('click', () => { addChoice(q); reindex(); });
        q.querySelector('.move-up').addEventListener('click', () => { if (q.previousElementSibling) list.insertBefore(q, q.previousElementSibling); reindex(); });
        q.querySelector('.move-down').addEventListener('click', () => { if (q.nextElementSibling) list.insertBefore(q.nextElementSibling, q); reindex(); });
        q.querySelector('.remove-question').addEventListener('click', () => window.arenaConfirm('Hapus pertanyaan?', 'Pertanyaan ini akan dihapus dari editor. Perubahan berlaku setelah kuis disimpan.', 'Hapus pertanyaan', () => { q.remove(); reindex(); }));
        list.append(q); reindex(); if (focus) { q.scrollIntoView({behavior:'smooth', block:'start'}); q.querySelector('[data-field=text]').focus({preventScroll:true}); }
    }
    JSON.parse(document.querySelector('#initial-questions').textContent).forEach(q => addQuestion(q));
    addButton.addEventListener('click', () => addQuestion({}, true));
    document.querySelector('.add-question-bottom').addEventListener('click', () => addQuestion({}, true));
})();
