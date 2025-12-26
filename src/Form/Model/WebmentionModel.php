<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class WebmentionModel
{
    #[Assert\NotBlank(message: 'Source must not be empty')]
    #[Assert\Url(message: 'Source must be a valid URL', protocols: [ 'http', 'https' ], requireTld: true)]
    #[Assert\NotEqualTo(propertyPath: 'target', message: 'Source must not be equal to target')]
    public string $source;
    #[Assert\NotBlank(message: 'Target must not be empty')]
    #[Assert\Url(message: 'Target must be a valid URL', protocols: [ 'http', 'https' ], requireTld: true)]
    #[Assert\NotEqualTo(propertyPath: 'source', message: 'Target must not be equal to source')]
    public string $target;
}
