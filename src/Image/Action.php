<?php

declare(strict_types=1);

namespace App\Image;

use Exception;
use InvalidArgumentException;
use Stringable;

readonly final class Action implements Stringable
{
    public string $action;
    public string $parameters;

    public function __construct(string $action, string $parameters)
    {
        $this->action = trim($action);
        $this->parameters = trim($parameters);
    }

    public static function parse(string $str): self
    {
        if (!str_contains($str, ':')) {
            throw new InvalidArgumentException('Invalid action format');
        }

        [ $action, $parameters ] = explode(':', $str, 2);

        return new self($action, $parameters);
    }

    public function __toString(): string
    {
        return sprintf('%s:%s', $this->action, $this->parameters);
    }

    public function isSameAs(Action $action): bool
    {
        return $this->action === $action->action && $this->parameters === $action->parameters;
    }

    /**
     * @return array{w: int, h: int, x: int, y: int}
     */
    public function getCropParameters(): array
    {
        $regex = '/^(\d+)x(\d+)([+-]\d+)([+-]\d+)$/';
        $matches = [];

        if (preg_match($regex, $this->parameters, $matches) !== 1) {
            throw new Exception('Unable to parse crop parameters');
        }

        return [
            'w' => intval($matches[1]),
            'h' => intval($matches[2]),
            'x' => intval($matches[3]),
            'y' => intval($matches[4]),
        ];
    }

    /**
     * @return array{w?: int, h?: int}
     */
    public function getResizeParameters(): array
    {
        $wh = explode('x', $this->parameters, 2);

        if (
            (count($wh) === 1 && $wh[0] === $this->parameters) ||
            (count($wh) === 2 && $wh[0] === '' && $wh[1] === '')
        ) {
            throw new Exception('Unable to parse resize parameters');
        }

        if ($wh[0] === '') {
            return [ 'h' => intval($wh[1]) ];
        } elseif ($wh[1] === '') {
            return [ 'w' => intval($wh[0]) ];
        }

        return [
            'w' => intval($wh[0]),
            'h' => intval($wh[1]),
        ];
    }
}
