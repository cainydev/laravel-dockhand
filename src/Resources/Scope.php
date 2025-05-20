<?php

namespace Cainy\Dockhand\Resources;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use function implode;

/**
 * Manages Docker registry access scopes for authentication.
 */
class Scope implements Arrayable, JsonSerializable
{
    /**
     * @var ScopeResourceType The type of resource.
     */
    protected ScopeResourceType $type;

    /**
     * @var string The name of the resource.
     */
    protected string $name;

    /**
     * @var bool Whether to allow pull action.
     */
    protected bool $allowPull = false;

    /**
     * @var bool Whether to allow push action.
     */
    protected bool $allowPush = false;

    /**
     * @var bool Whether to allow delete action.
     */
    protected bool $allowDelete = false;

    /**
     * @var array Allowed actions.
     */
    protected array $actions {
        get {
            $arr = [];
            if ($this->allowPull) {
                $arr[] = 'pull';
            }
            if ($this->allowPush) {
                $arr[] = 'push';
            }
            if ($this->allowDelete) {
                $arr[] = 'delete';
            }
            if ($this->allowAll()) {
                $arr[] = '*';
            }
            return $arr;
        }
    }

    /**
     * Create a new scope instance.
     */
    public function __construct()
    {
        $this->allowPull = false;
        $this->allowPush = false;
        $this->allowDelete = false;
    }

    /**
     * Create a new scope instance by parsing a scope string.
     *
     * @param string $scope
     * @return static
     */
    public static function fromString(string $scope): static
    {
        if (empty($scope)) {
            throw new \InvalidArgumentException('Scope cannot be empty');
        }

        if (!preg_match('/^([a-z0-9]+(?:\([a-z0-9]+\))?):([^:]+):([a-z,*]+)$/', $scope, $matches)) {
            throw new \InvalidArgumentException("Invalid scope format: $scope");
        }

        [, $resourceType, $resourceName, $actionString] = $matches;

        $type = ScopeResourceType::tryFrom($resourceType);

        if ($type === null) {
            throw new \InvalidArgumentException("Invalid resource type: $resourceType");
        }

        $actions = explode(',', $actionString);

        $instance = new static();

        $instance->type = $type;
        $instance->name = $resourceName;

        if (in_array('pull', $actions)) {
            $instance->allowPull();
        }

        if (in_array('push', $actions)) {
            $instance->allowPush();
        }

        if (in_array('delete', $actions)) {
            $instance->allowDelete();
        }

        if (in_array('*', $actions)) {
            $instance->allowAll();
        }

        return $instance;
    }

    /**
     * Add pull access.
     */
    public function allowPull(?bool $enabled = true): static
    {
        $this->allowPull = $enabled;
        return $this;
    }

    /**
     * Add push access.
     */
    public function allowPush(bool $enabled = true): static
    {
        $this->allowPush = $enabled;
        return $this;
    }

    /**
     * Add delete access.
     */
    public function allowDelete(bool $enabled = true): static
    {
        $this->allowDelete = $enabled;
        return $this;
    }

    /**
     * Allow all actions.
     */
    public function allowAll(): static
    {
        $this->allowPull = true;
        $this->allowPush = true;
        $this->allowDelete = true;
        return $this;
    }

    /**
     * Factory method to create a new catalog scope.
     */
    public function catalog(): static
    {
        $instance = new static();
        $instance->type = ScopeResourceType::Registry;
        $instance->name = 'catalog';
        $instance->allowAll();

        return $instance;
    }

    /**
     * Factory method to create a new scope for a repo.
     *
     * @param string $repo The name of the repository.
     * @return Scope
     */
    public function repository(string $repo): static
    {
        $instance = new static();
        $instance->type = ScopeResourceType::Repository;
        $instance->name = $repo;

        return $instance;
    }

    /**
     * Factory method to create a new scope for a repo write push access.
     *
     * @param string $repo The name of the repository.
     * @return Scope
     */
    public function writeRepository(string $repo): static
    {
        $instance = new static();
        $instance->type = ScopeResourceType::Repository;
        $instance->name = $repo;
        $instance->allowPush();

        return $instance;
    }

    /**
     * Factory method to create a new scope for a repo with pull access.
     *
     * @param string $repo The name of the repository.
     * @return Scope
     */
    public function readRepository(string $repo): static
    {
        $instance = new static();
        $instance->type = ScopeResourceType::Repository;
        $instance->name = $repo;
        $instance->allowPull();

        return $instance;
    }

    public function allowPushAndPull(): static
    {
        $this->allowPull = true;
        $this->allowPush = true;
        return $this;
    }

    /**
     * Allow no actions.
     */
    public function allowNone(): static
    {
        $this->allowPull = false;
        $this->allowPush = false;
        $this->allowDelete = false;
        return $this;
    }

    /**
     * Check if the scope has pull access.
     *
     * @return bool
     */
    public function hasPull(): bool
    {
        return $this->allowPull;
    }

    /**
     * Check if the scope has push access.
     *
     * @return bool
     */
    public function hasPush(): bool
    {
        return $this->allowPush;
    }

    /**
     * Check if the scope has delete access.
     *
     * @return bool
     */
    public function hasDelete(): bool
    {
        return $this->allowDelete;
    }

    /**
     * Convert the scope to a registry-compatible string.
     *
     * @return string
     * @see toString()
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Convert the scope to a registry-compatible string.
     *
     * @return string
     */
    public function toString(): string
    {
        return "{$this->type->value}:{$this->name}:" . implode(',', $this->actions);
    }

    /**
     * Convert the scope to JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the scope to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'name' => $this->name,
            'actions' => $this->allowAll() ? ['*'] : $this->actions,
        ];
    }

    /**
     * Define how the scope should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the resource type of the scope.
     *
     * @return ScopeResourceType
     */
    public function getResourceType(): ScopeResourceType
    {
        return $this->type;
    }

    /**
     * Get the resource name of the scope.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return $this->name;
    }

    /**
     * Get the actions of the scope.
     *
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Overwrite the actions with the given array.
     *
     * @param array $actions
     * @return $this
     */
    public function setActions(array $actions): static
    {
        $this->allowPull = in_array('pull', $actions);
        $this->allowPush = in_array('push', $actions);
        $this->allowDelete = in_array('delete', $actions);
        return $this;
    }
}
