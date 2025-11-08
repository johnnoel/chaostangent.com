import '../css/main.css';

const slideshows = document.querySelectorAll<HTMLElement>('.image-slideshow');
if (slideshows.length > 0) {
    import('./image-slideshow' /* webpackChunkName: "image-slideshow" */).then(module => {
        slideshows.forEach(slideshow => module.default(slideshow));
    });
}

const quiz = document.getElementById('js-quiz');
if (quiz !== null) {
    import('./quiz' /* webpackChunkName: "quiz" */).then(module => {
        module.default(quiz);
    });
}

const maps = document.querySelectorAll<HTMLElement>('.js-map');
if (maps.length > 0) {
    import('./map' /* webpackChunkName: "map" */).then(module => {
        maps.forEach(map => module.default(map));
    });
}

const jeanneDarcMode = document.getElementById('js-jeannedarcmode');
if (jeanneDarcMode !== null) {
    import('./jeannedarcmode' /* webpackChunkName: "jeanne-darc-mode" */).then(module => {
        module.default(jeanneDarcMode);
    });
}
