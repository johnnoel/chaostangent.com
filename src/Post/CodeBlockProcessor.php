<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;

/**
 * Convert <pre><code></code></pre> and <pre class="brush: xxxx"></pre> blocks into Twig filter calls
 */
readonly final class CodeBlockProcessor implements Processor
{
    #[\Override]
    public function process(Post $post): void
    {
        $content = $post->getContent();
        $matches = [];
        $matched = preg_match_all(
            '#(<pre>.*<code>(?<code1>.*)</code>.*</pre>|<pre class="brush:(?<lang>.*)">(?<code2>.*)</pre>)#siU',
            $content,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        if ($matched === false || $matched === 0) {
            return;
        }

        foreach (array_reverse($matches) as $codeBlock) {
            $code = (array_key_exists('code1', $codeBlock) && $codeBlock['code1'][0] !== '') ?
                $codeBlock['code1'][0] :
                $codeBlock['code2'][0] ?? ''
            ;
            $code = trim(html_entity_decode(str_replace("\t", str_repeat(' ', 4), $code)));
            $lang = (array_key_exists('lang', $codeBlock) && $codeBlock['lang'][0] !== '') ?
                trim($codeBlock['lang'][0]) :
                'shellscript'
            ;
            $offset = $codeBlock[0][1];
            $length = strlen($codeBlock[0][0]);

            $replacement = "{% apply code('$lang') %}\n$code\n{% endapply %}";

            $content = substr_replace($content, $replacement, $offset, $length);
        }

        $post->setContent($content);
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'code';
    }
}
