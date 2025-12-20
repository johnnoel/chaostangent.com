export default function(container: HTMLElement) {
    const buttons = Array.from(container.querySelectorAll<HTMLButtonElement>('button'));

    if (buttons.length === 0) {
        return;
    }

    buttons.forEach(btn => btn.addEventListener('click', () => {
        btn.classList.toggle('-active');

        const toHide = buttons
            .filter(btn => !btn.classList.contains('-active'))
            .map(btn => btn.dataset.target)
            .join(', ')
        ;

        const toShow = buttons
            .filter(btn => btn.classList.contains('-active'))
            .map(btn => btn.dataset.target)
            .join(', ')
        ;

        if (toHide !== '') {
            document.querySelectorAll<HTMLElement>(toHide).forEach(e => e.style.display = 'none');
        }

        if (toShow !== '') {
            document.querySelectorAll<HTMLElement>(toShow).forEach(e => e.style.display = '');
        }
    }));
};
