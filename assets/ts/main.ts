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
