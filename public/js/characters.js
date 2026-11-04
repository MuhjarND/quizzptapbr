(() => {
    'use strict';
    const catalog = JSON.parse(document.querySelector('#character-catalog').textContent);
    const preview = document.querySelector('#character-preview');
    document.querySelectorAll('input[name=character]').forEach(input => input.addEventListener('change', () => {
        const character = catalog[input.value]; if (!character) return;
        const image = preview.querySelector('img'); image.src = character.image;
        image.alt = `Karakter ${character.name}`;
        image.style.setProperty('--character-color', character.color);
        preview.style.setProperty('--character-color', character.color);
        document.querySelector('#character-name').textContent = character.name;
        document.querySelector('#character-kind').textContent = character.kind;
        document.querySelector('#character-selection-status').textContent = `${character.name} siap menemanimu!`;
        window.ArenaSound?.play('pick');
    }));
})();
