import { FunctionComponent } from 'preact/compat';

const Grats: FunctionComponent<GratsProps> = ({ score }) => {
    return <li class="congratulations">
        <h2>Congratulations</h2>
        <p>Your score is <span class="score">{score}</span></p>
        <p class="meaningless">A completely meaningless number!</p>
        <p class="twitter">
            <a href="http://twitter.com/share?url=http://n.adesi.co/NaP3Cj&text=I+scored+{score}+on+a+completely+meaningless+anime+quiz!+SO+CAN+YOU&amp;hashtags=animequiz">
                Tell your Twitter friends!
            </a>
        </p>
    </li>;
};

interface GratsProps {
    score: number;
}

export default Grats;
