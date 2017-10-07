<?php namespace Lit\Air\Psr;

use Lit\Air\Configurator;
use Lit\Air\Factory;
use Lit\Air\Recipe\AliasRecipe;
use Lit\Air\Recipe\AutowireRecipe;
use Lit\Air\Recipe\CachedRecipe;
use Lit\Air\Recipe\FixedValueRecipe;
use Lit\Air\Recipe\MultitonRecipe;
use Lit\Air\Recipe\RecipeInterface;
use Lit\Air\Recipe\SingletonRecipe;
use Lit\Air\WritableContainerInterface;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface, WritableContainerInterface
{
    /**
     * @var RecipeInterface[]
     */
    protected $recipe = [];
    protected $cache = [];
    /**
     * @var ContainerInterface
     */
    protected $delegateContainer;

    public function __construct(?array $config = null)
    {
        if ($config) {
            Configurator::config($this, $config);
        }
    }

    public static function alias(string $alias, array $extra = [])
    {
        return new AliasRecipe($alias, $extra);
    }

    public static function autowire(array $extra = [], ?string $className = null)
    {
        return new AutowireRecipe($extra, $className);
    }

    public static function cached(callable $factory)
    {
        return new CachedRecipe($factory);
    }

    public static function multiton(callable $factory)
    {
        return new MultitonRecipe($factory);
    }


    public static function singleton(callable $factory)
    {
        return new SingletonRecipe($factory);
    }

    public static function value($value)
    {
        return new FixedValueRecipe($value);
    }

    public static function wrap(ContainerInterface $container)
    {
        return (new static)->setDelegateContainer($container);
    }

    public function get($id)
    {
        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        if (array_key_exists($id, $this->recipe)) {
            return $this->recipe[$id]->resolve($this, $id);
        }

        if ($id === static::KEY_FACTORY) {
            return new Factory($this);
        }

        if ($this->delegateContainer && $this->delegateContainer->has($id)) {
            return $this->delegateContainer->get($id);
        }

        throw new NotFoundException();
    }

    public function has($id)
    {
        return array_key_exists($id, $this->cache)
            || array_key_exists($id, $this->recipe)
            || $id === static::KEY_FACTORY
            || ($this->delegateContainer && $this->delegateContainer->has($id));
    }

    public function define($id, RecipeInterface $recipe): self
    {
        $this->recipe[$id] = $recipe;
        return $this;
    }

    public function getRecipe($id): ?RecipeInterface
    {
        if (array_key_exists($id, $this->recipe)) {
            return $this->recipe[$id];
        }

        return null;
    }

    public function flush($id): self
    {
        unset($this->cache[$id]);
        return $this;
    }

    public function resolveRecipe($value)
    {
        if ($value instanceof RecipeInterface) {
            return $value->resolve($this);
        }

        return $value;
    }

    public function set($id, $value): self
    {
        $this->cache[$id] = $value;
        return $this;
    }

    /**
     * @param ContainerInterface $delegateContainer
     * @return $this
     */
    public function setDelegateContainer(ContainerInterface $delegateContainer): self
    {
        $this->delegateContainer = $delegateContainer;

        return $this;
    }
}