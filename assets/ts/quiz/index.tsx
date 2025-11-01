import { h, render } from 'preact';
import Quiz from './Quiz';

export default function(quizContainer: HTMLElement): void {
    const selector = quizContainer.dataset.questions;
    if (selector === undefined) {
        return;
    }

    const questionData = document.querySelector(selector);
    if (!questionData) {
        return;
    }

    const questions: Q[] = JSON.parse(questionData.textContent);
    const root = h(Quiz, { questions });
    render(root, quizContainer);
};

export interface Image {
    src: string;
    type: string;
    width?: number;
    height?: number;
}

export interface Q {
    question: string;
    images: Image[];
    answers: A[];
}

export interface A {
    answer: string;
    score: number;
}
