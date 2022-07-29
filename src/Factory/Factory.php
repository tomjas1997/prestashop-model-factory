<?php

namespace Invertus\Prestashop\Models\Factory;

use DusanKasan\Knapsack\Collection;

abstract class Factory
{
    /** @var \Faker\Generator */
    protected $faker;

    protected $model;

    /** @var int|null */
    protected $count;

    /** @var array */
    protected $state;

    /**
     * The "after making" callbacks that will be applied to the model.
     */
    protected $afterMaking;

    /**
     * The "after creating" callbacks that will be applied to the model.
     */
    protected $afterCreating;

    public function __construct(
        $count = null,
        array $state = null,
        \Closure $afterMaking = null,
        \Closure $afterCreating = null
    ) {
        $this->count = $count;
        $this->state = $state ?: [];
        $this->afterMaking = $afterMaking;
        $this->afterCreating = $afterCreating;
        $this->faker = $this->withFaker();
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    abstract public function definition();

    public static function initialize()
    {
        return (new static())->state([])->configure();
    }

    public function configure()
    {
        return $this;
    }

    /**
     * Create a collection of models.
     *
     * @param array $attributes
     * @return \ObjectModel|Collection
     */
    public function make($attributes = [])
    {
        if (!empty($attributes)) {
            return $this->state($attributes)->make([]);
        }

        if ($this->count === null) {
            $model = $this->makeInstance();

            $this->callAfterMaking(Collection::from([$model]));

            return $model;
        }

        if ($this->count < 1) {
            return new Collection([]);
        }

        $instances = Collection::range(1, $this->count)
            ->map(function () {
                return $this->makeInstance();
            })
            ->realize();

        $this->callAfterMaking($instances);

        return $instances;
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param array $attributes
     * @return \ObjectModel|Collection
     */
    public function create($attributes = [])
    {
        $results = $this->make($attributes);

        if ($results instanceof \ObjectModel) {
            $this->store(Collection::from([$results]));

            $this->callAfterCreating(Collection::from(([$results])));
        } else {
            $this->store($results);

            $this->callAfterCreating($results);
        }

        return $results;
    }

    /**
     * @param Collection $results
     * @return void
     */
    protected function store(Collection $results)
    {
        $results->each(function (\ObjectModel $model) {
            $model->save();
        })->realize();
    }

    /**
     * @return \ObjectModel
     */
    protected function makeInstance()
    {
        $model = $this->newModel();
        $model->hydrate(
            $this->getExpandAttributes($this->getRawAttributes()),
            (int) \Configuration::get('PS_LANG_DEFAULT')
        );

        return $model;
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param  array  $definition
     * @return array
     */
    protected function getExpandAttributes(array $definition)
    {
        return Collection::from($definition)->map(function ($attribute, $key) use (&$definition) {
            if (is_callable($attribute) && ! is_string($attribute) && ! is_array($attribute)) {
                $attribute = $attribute($definition);
            }

            if ($attribute instanceof \ObjectModel) {
                $attribute = $attribute->id;
            }

            $definition[$key] = $attribute;

            return $attribute;
        })->toArray();
    }

    protected function getRawAttributes()
    {
        return array_merge($this->definition(), $this->state);
    }

    /**
     * @return \ObjectModel
     */
    public function newModel()
    {
        return new $this->model();
    }

    /**
     * Create a new instance of the factory builder with the given mutated properties.
     *
     * @param  array  $arguments
     * @return static
     */
    protected function newInstance(array $arguments = [])
    {
        return new static(...array_values(array_merge([
            'count' => $this->count,
            'state' => $this->state,
            'afterMaking' => $this->afterMaking,
            'afterCreating' => $this->afterCreating,
        ], $arguments)));
    }

    public function count($count)
    {
        return $this->newInstance([
            'count' => $count,
        ]);
    }

    /**
     * Add a new state transformation to the model definition.
     *
     * @param  callable|array  $state
     * @return static
     */
    public function state($state)
    {
        return $this->newInstance([
            'state' => $state,
        ]);
    }

    /**
     * Add a new "after making" callback to the model definition.
     *
     * @param  \Closure $callback
     * @return static
     */
    public function afterMaking(\Closure $callback)
    {
        return $this->newInstance([
            'afterMaking' => $callback,
        ]);
    }

    /**
     * Add a new "after creating" callback to the model definition.
     *
     * @param \Closure $callback
     * @return static
     */
    public function afterCreating(\Closure $callback)
    {
        return $this->newInstance([
            'afterCreating' => $callback,
        ]);
    }

    protected function callAfterMaking(Collection $instances)
    {
        $instances->each(function ($model) {
            $callback = $this->afterMaking;

            if ($callback instanceof \Closure) {
                $callback($model);
            }
        })->realize();
    }

    protected function callAfterCreating(Collection $instances)
    {
        $instances->each(function ($model) {
            $callback = $this->afterCreating;

            if ($callback instanceof \Closure) {
                $callback($model);
            }
        })->realize();
    }

    protected function withFaker()
    {
        return \Faker\Factory::create();
    }
}