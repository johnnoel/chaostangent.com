import padStart from 'lodash/padStart';
import range from 'lodash/range';
import shuffle from 'lodash/shuffle';

export default function(container: HTMLElement) {
    container.style.display = 'block';
    container.querySelector('button')?.addEventListener('click', () => {
        let images = shuffle(range(27).map(i => i + 1));
        const regex = /bahamut-(jeanne-)?\d+/;

        document.querySelectorAll<HTMLPictureElement>('.image-slideshow picture').forEach(picture => {
            const i2 = getPaddedNumber(images);

            picture.querySelectorAll<HTMLSourceElement>('source').forEach(source => {
                replaceAttributeValue(source, 'srcset', regex, 'bahamut-jeanne-' + i2);
            });

            picture.querySelectorAll<HTMLImageElement>('img').forEach(img => {
                replaceAttributeValue(img, 'src', regex, 'bahamut-jeanne-' + i2);
            });
        });

        document.querySelectorAll<HTMLVideoElement>('video').forEach(video => {
            const i2 = getPaddedNumber(images);
            replaceAttributeValue(video, 'poster', regex, 'bahamut-jeanne-' + i2)
        });

        window.scroll(0, 0);
    });
}

function getPaddedNumber(numbers: number[]): string {
    const i = numbers.pop();
    if (i === undefined) {
        throw 'Unable to get number';
    }

    return padStart(i + '', 2, '0');
}

function replaceAttributeValue(element: HTMLElement, attribute: string, pattern: RegExp, replacement: string): void {
    const attributeValue = element.getAttribute(attribute) ?? '';
    element.setAttribute(attribute, attributeValue.replace(pattern, replacement));
}
