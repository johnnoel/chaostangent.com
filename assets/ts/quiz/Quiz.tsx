import { FunctionComponent } from 'preact/compat';
import { useState } from 'preact/hooks';
import Question from './Question';
import Grats from './Grats';
import { Q } from './index';

const Quiz: FunctionComponent<QuizProps> = ({ questions }) => {
    const [ currentIndex, setCurrentIndex ] = useState(0);
    const [ currentScore, setCurrentScore ] = useState(0);
    const question = questions[currentIndex];

    return <ul className="anime-quiz">
        {(currentIndex >= questions.length) ?
            <Grats score={currentScore} /> :
            <Question
                onSelect={score => {
                    setCurrentScore(currentScore + score);
                    setCurrentIndex(currentIndex + 1);
                }}
                index={currentIndex + 1}
                total={questions.length}
                {...question}
            />
        }
    </ul>
};

interface QuizProps {
    questions: Q[];
}

export default Quiz;
