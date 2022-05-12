<?php
/**
 * PHP Billing Library
 *
 * @link      https://github.com/hiqdev/php-billing
 * @package   php-billing
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017-2020, HiQDev (http://hiqdev.com/)
 */

namespace hiqdev\php\billing\type;

/**
 * General Type.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class Type implements TypeInterface
{
    /**
     * @var int|string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    public function __construct($id, $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function hasName(): bool
    {
        return !empty($this->name) && $this->name !== self::ANY && $this->name !== self::NONE;
    }

    public function getUniqueId()
    {
        return $this->hasName() ? $this->name : $this->id;
    }

    public function equals(TypeInterface $other): bool
    {
        return $this->id === $other->getId() &&
            $this->name === $other->getName();
    }

    public function matches(TypeInterface $other): bool
    {
        return $this->id === self::ANY || $other->getId() === self::ANY
            ? $this->checkMatches($this->name, $other->getName())
            : $this->checkMatches($this->id, $other->getId());
    }

    protected function checkMatches($lhs, $rhs)
    {
        if ($lhs === self::NONE || $rhs === self::NONE) {
            return false;
        }

        return (string) $lhs === (string) $rhs;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}
