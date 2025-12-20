export default function(container: HTMLElement) {
    const buttons = container.querySelectorAll<HTMLButtonElement>('button');

    if (buttons.length === 0) {
        return;
    }

    buttons.forEach(btn => btn.addEventListener('click', () => {
        const currentlyActive = btn.classList.contains('-active');
        const selector = btn.dataset.target;

        if (selector === undefined) {
            return;
        }

        const targets = document.querySelectorAll<HTMLElement>(selector);
        targets.forEach(t => t.style.display = (currentlyActive) ? 'none' : '');

        btn.classList.toggle('-active');
    }));
};
