import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';

export default function(slideshow: HTMLElement) {
    const track = slideshow.querySelector<HTMLElement>('.track');
    if (track === null) {
        return;
    }

    const autoplay = Autoplay({ playOnInit: false, delay: 7000 });
    // @ts-ignore "autoplay" is allowed to be added as a plugin
    const embla = EmblaCarousel(track, { loop: true }, [ autoplay ]);
    const left = slideshow.querySelector<HTMLButtonElement>('.left');
    const right = slideshow.querySelector<HTMLButtonElement>('.right');

    // ideally would do autoplay.reset() here however there doesn't seem to be a way to reset the animation on
    // toggleButton as the animation is running on a psuedo-element so don't seem to be able to do getAnimations()
    left?.addEventListener('click', () => embla.scrollPrev(), false);
    right?.addEventListener('click', () => embla.scrollNext(), false);

    const toggleButton = slideshow.querySelector<HTMLButtonElement>('.toggle');
    if (toggleButton !== null) {
        toggleButton.addEventListener('click', () => {
            (autoplay.isPlaying()) ? autoplay.stop() : autoplay.play();
        }, false);

        // @ts-ignore "autoplay:play" is a valid event type
        embla.on('autoplay:play', () => {
            toggleButton.classList.add('-play');
            toggleButton.classList.remove('-pause');
        });

        // @ts-ignore "autoplay:stop" is a valid event type
        embla.on('autoplay:stop', () => {
            toggleButton.classList.remove('-play');
            toggleButton.classList.add('-pause');
        });
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
