import Glide, { Autoplay, Controls, Swipe } from '@glidejs/glide/dist/glide.modular.esm';
import '@glidejs/glide/dist/css/glide.core.css';
import scrollMonitor from 'scrollmonitor';

export default function(slideshow: HTMLElement) {
    const glide = new Glide(slideshow, {
        animationDuration: 1000,
        hoverpause: true,
        rewindDuration: 1500
    });

    glide.mount({ Autoplay, Controls, Swipe });

    const toggleButton = slideshow.querySelector<HTMLButtonElement>('.toggle');
    if (toggleButton === null) {
        return;
    }

    toggleButton.addEventListener('click', () => {
        glide.update({
            autoplay: toggleButton.classList.contains('-play') ? false : 7000,
        });

        toggleButton.classList.toggle('-play');
        toggleButton.classList.toggle('-pause');
    });

    const watcher = scrollMonitor.create(slideshow);
    watcher.enterViewport(() => {
        slideshow.querySelectorAll('img').forEach(img => {
            img.loading = 'eager';
        })
    }, false);
}
