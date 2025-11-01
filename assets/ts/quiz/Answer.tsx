import { FunctionalComponent } from 'preact';

const Answer: FunctionalComponent<AnswerProps> = ({ answer, score, questionIndex, answerIndex, onSelect }) => {
    return <li>
        <button type="button" key={questionIndex + ' ' + answerIndex} onClick={() => onSelect(score)}>
            {answer}
        </button>
    </li>
};

export interface AnswerProps {
    answer: string;
    score: number;
    questionIndex: number,
    answerIndex: number,
    onSelect: (score: number) => void;
}

export default Answer;
