<?php

declare(strict_types=1);

namespace App\Repository\Query;

use App\Entity\Category;
use App\Entity\Tag;
use App\Repository\Criteria\FilterPostsCriteria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Parameter;
use Symfony\Bridge\Doctrine\Types\UlidType;

readonly final class FilterPostsQuery
{
    public function __construct(private FilterPostsCriteria $criteria)
    {
    }

    /**
     * @return array{0: string, 1: ArrayCollection<int,Parameter>}
     */
    public function posts(string $selectClause): array
    {
        $parameters = new ArrayCollection([
            new Parameter('published', true),
            new Parameter('approved', true),
            new Parameter('spam', false),
        ]);

        $limit = $this->criteria->perPage;
        $offset = ($this->criteria->page - 1) * $this->criteria->perPage;

        $sql = implode("\n", $this->getWith($parameters));
        $sql .= <<<SQL
            SELECT $selectClause, COUNT(co.id) AS comment_count
            FROM posts p
            LEFT JOIN comments co ON (co.post_id = p.id) AND (co.approved = :approved) AND (co.spam = :spam)
        SQL;

        $sql .= implode("\n", $this->getJoin());

        $sql .= <<<SQL
            WHERE (p.published = :published)
        SQL;

        $sql .= implode("\n", $this->getWhere($parameters));
        $sql .= <<<SQL
            GROUP BY p.id
            ORDER BY p.date DESC, p.id DESC
            LIMIT $limit OFFSET $offset
        SQL;

        return [ $sql, $parameters ];
    }

    /**
     * @return array{0: string, 1: ArrayCollection<int,Parameter>}
     */
    public function count(): array
    {
        $parameters = new ArrayCollection([
            new Parameter('published', true),
        ]);

        $sql = implode("\n", $this->getWith($parameters));
        $sql .= <<<SQL
            SELECT COUNT(DISTINCT p.id) AS post_count
            FROM posts p
        SQL;

        $sql .= implode("\n", $this->getJoin());

        $sql .= <<<SQL
            WHERE (p.published = :published)
        SQL;

        $sql .= implode("\n", $this->getWhere($parameters));

        return [ $sql, $parameters ];
    }

    /**
     * @param ArrayCollection<int,Parameter> $parameters
     * @return array<string>
     */
    private function getWith(ArrayCollection &$parameters): array
    {
        if (!($this->criteria->category instanceof Category)) {
            return [];
        }

        $parameters[] = new Parameter('category_id', $this->criteria->category->getId(), UlidType::NAME);

        return [
            <<<SQL
                WITH RECURSIVE subcategories AS (
                    SELECT id FROM categories WHERE id = :category_id
                    UNION
                    SELECT c.id FROM categories c INNER JOIN subcategories sc ON sc.id = c.parent_id
                )
            SQL,
        ];
    }

    /**
     * @return array<string>
     */
    private function getJoin(): array
    {
        $joins = [];

        if ($this->criteria->category instanceof Category) {
            $joins[] = <<<SQL
                JOIN posts2categories p2c ON p2c.post_id = p.id
                JOIN subcategories sc ON sc.id = p2c.category_id
            SQL;
        }

        if ($this->criteria->tag instanceof Tag) {
            $joins[] = <<<SQL
                JOIN posts2tags p2t ON p2t.post_id = p.id
                JOIN tags t ON t.id = p2t.tag_id
            SQL;
        }

        return $joins;
    }

    /**
     * @param ArrayCollection<int,Parameter> $parameters
     * @return array<string>
     */
    private function getWhere(ArrayCollection &$parameters): array
    {
        $where = [];

        if ($this->criteria->tag instanceof Tag) {
            $where[] = <<<SQL
                AND (t.id = :tag_id)
            SQL;

            $parameters[] = new Parameter('tag_id', $this->criteria->tag->getId(), UlidType::NAME);
        }

        if ($this->criteria->month !== null) {
            $where[] = <<<SQL
                AND (EXTRACT('MONTH' FROM p.date) = :month)
            SQL;

            $parameters[] = new Parameter('month', $this->criteria->month, Types::STRING);
        }

        if ($this->criteria->year !== null) {
            $where[] = <<<SQL
                AND (EXTRACT('YEAR' FROM p.date) = :year)
            SQL;

            $parameters[] = new Parameter('year', $this->criteria->year, Types::STRING);
        }

        return $where;
    }
}
