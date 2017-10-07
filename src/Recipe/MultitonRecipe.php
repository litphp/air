<?php namespace Lit\Air\Recipe;

use Lit\Air\Factory;
use Lit\Air\WritableContainerInterface;

class MultitonRecipe implements RecipeInterface
{
    /**
     * @var callable
     */
    protected $factory;

    /**
     * MultitonStub constructor.
     * @param callable $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        return Factory::of($container)->invoke($this->factory);
    }
}