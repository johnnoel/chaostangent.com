import { FunctionalComponent } from 'preact';
import Answer from './Answer';
import { A, Image } from './index';

const Question: FunctionalComponent<QuestionProps> = ({ question, answers, images, index, total, onSelect }) => {
    return <li className="question">
        <h2 className="position">
            <span className="index">{index}</span> of <span className="total">{total}</span>
        </h2>

        <picture>
            {images.map(i => (i.type === 'image/jpeg') ?
                <img src={i.src} width={i.width} height={i.height} alt="" loading="lazy" /> :
                <source srcset={i.src} type={i.type} width={i.width} height={i.height} />
            )}
        </picture>

        <h3>{question}</h3>
        <ul className="answers">
            {answers.map((a, answerIndex) => <Answer
                onSelect={onSelect}
                questionIndex={index}
                answerIndex={answerIndex}
                {...a}
            />)}
        </ul>
    </li>;
};

export interface QuestionProps {
    question: string;
    answers: A[];
    images: Image[];
    index: number;
    total: number;
    onSelect: (score: number) => void;
}

export default Question;
