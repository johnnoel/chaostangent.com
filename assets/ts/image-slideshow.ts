import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';

export default function(slideshow: HTMLElement) {
    const track = slideshow.querySelector<HTMLElement>('.track');
    if (track === null) {
        return;
    }

    const autoplay = Autoplay({ playOnInit: false, delay: 7000 });
    // @ts-ignore
    const embla = EmblaCarousel(track, { loop: true }, [ autoplay ]);

    slideshow.querySelector<HTMLButtonElement>('.left')?.addEventListener('click', () => embla.scrollPrev(), false);
    slideshow.querySelector<HTMLButtonElement>('.right')?.addEventListener('click', () => embla.scrollNext(), false);

    const toggleButton = slideshow.querySelector<HTMLButtonElement>('.toggle');
    if (toggleButton !== null) {
        toggleButton.addEventListener('click', () => {
            toggleButton.classList.toggle('-play');
            toggleButton.classList.toggle('-pause');
            (autoplay.isPlaying()) ? autoplay.stop() : autoplay.play();
        }, false);
    }

    slideshow.querySelectorAll<HTMLButtonElement>('.bullet').forEach(btn => {
        btn.addEventListener('click', () => embla.scrollTo(parseInt(btn.dataset.idx ?? '0', 10)));
    });

    const toggleActiveBullet = () => {
        const prev = embla.previousScrollSnap();
        const sel = embla.selectedScrollSnap();
        slideshow.querySelector('.bullet[data-idx="' + prev + '"]')?.classList.remove('-active');
        slideshow.querySelector('.bullet[data-idx="' + sel + '"]')?.classList.add('-active');
    };

    embla.on('init', toggleActiveBullet)
        .on('select', toggleActiveBullet)
    ;
}
