import Glide, { Autoplay, Swipe } from '@glidejs/glide/dist/glide.modular.esm';
import '@glidejs/glide/dist/css/glide.core.css';

export default function(slideshow: HTMLElement) {
    const glide = new Glide(slideshow, {
        animationDuration: 1000,
        autoplay: 7000,
        hoverpause: true,
        rewindDuration: 3000
    });
    glide.mount({ Autoplay, Swipe });
}
