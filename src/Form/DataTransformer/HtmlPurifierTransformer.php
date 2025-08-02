<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use HTMLPurifier;
use HTMLPurifier_Config;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<string|null,string|null>
 */
readonly final class HtmlPurifierTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        return $value;
    }

    public function reverseTransform(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'a[href|rel],p,b,strong,i,em,br,blockquote');
        $config->set('HTML.Nofollow', true);
        $config->set('Attr.AllowedRel', [ 'nofollow' ]);
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($value);
    }
}
