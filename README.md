## Introduction

This package provides ability to test your database driven applications. Model factories make it easier to create test database records using Prestashop models. 

## Installation

Require this package with composer. It is recommended to only require the package for development.

```shell
composer require invertus/prestashop-models --dev
```

## Defining Model Factories

### Concept Overview

When testing, you may need to insert a few records into your database before executing your test. Instead of manually specifying the value of each column when you create this test data, this package allows you to define a set of default attributes for each of your Prestashop models using model factories.

To see an example of how to write a factory, take a look at the `tests/Factories/Models/UserFactory.php` file in your application.

    namespace Tests\Factories\Models;

    use Invertus\Prestashop\Models\Factory\Factory;
    use Customer;

    class CustomerFactory extends Factory
    {
        protected $model = Customer::class;

        /**
         * Define the model's default state.
         *
         * @return array
         */
        public function definition()
        {
            return [
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->safeEmail(),
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            ];
        }
    }
`

As you can see, in their most basic form, factories are classes that extend this package's base factory class and define `definition` method. The `definition` method returns the default set of attribute values that should be applied when creating a model using the factory.

Via the `faker` property, factories have access to the [Faker](https://github.com/FakerPHP/Faker) PHP library, which allows you to conveniently generate various kinds of random data for testing.

> {tip} You can override faker by overriding withFaker() function in Factory class.

### Generating Factories

To create a factory, execute the `make:factory`:

    php vendor/bin/prestashop-models make:factory ProductFactory

To create a factory with specific different name than {model}Factory execute this command:
    
    php vendor/bin/prestashop-models make:factory TestProductFactory --model="\Product" 

**--model should have full namespace to model.**

The new factory class will be placed in your `tests/Factories/Models` directory.

### Factory States

State manipulation methods allow you to define discrete modifications that can be applied to your model factories in any combination. For example, your `Tests\Factories\Models\CustomerFactory` factory might contain a `suspended` state method that modifies one of its default attribute values.

State transformation methods typically call the `state` method provided by package's base factory class. The `state` method accepts a closure which will receive the array of raw attributes defined for the factory and should return an array of attributes to modify:

    /**
     * Indicate that the customer is suspended.
     *
     * @return self
     */
    public function suspended()
    {
        return $this->state(function (array $attributes) {
            return [
                'account_status' => 'suspended',
            ];
        });
    }

### Factory Callbacks

Factory callbacks are registered using the `afterMaking` and `afterCreating` methods and allow you to perform additional tasks after making or creating a model. You should register these callbacks by defining a `configure` method on your factory class. This method will be automatically called by Laravel when the factory is instantiated:

    namespace Tests\Factories\Models;

    use Invertus\Prestashop\Models\Factory\Factory;
    use Customer;

    class CustomerFactory extends Factory
    {
        /**
         * Configure the model factory.
         *
         * @return $this
         */
        public function configure()
        {
            return $this->afterMaking(function (Customer $customer) {
                //
            })->afterCreating(function (Customer $customer) {
                //
            });
        }

        // ...
    }


## Creating Models Using Factories

### Instantiating Models

Once you have defined your factories, you may use the static `factory` method provided to your models by the `Illuminate\Database\Eloquent\Factories\HasFactory` trait in order to instantiate a factory instance for that model. Let's take a look at a few examples of creating models. First, we'll use the `make` method to create models without persisting them to the database:

    use Tests\Factories\Models\CustomerFactory;

    public function test_models_can_be_instantiated()
    {
        $customer = CustomerFactory::initialize()->make();

        // Use model in tests...
    }

You may create a collection of many models using the `count` method:

    $customers = CustomerFactory::initialize()->count(3)->make();

#### Applying States

You may also apply any of your states to the models. If you would like to apply multiple state transformations to the models, you may simply call the state transformation methods directly:

    $customers = CustomerFactory::initialize()->count(5)->suspended()->make();

#### Overriding Attributes

If you would like to override some of the default values of your models, you may pass an array of values to the `make` method. Only the specified attributes will be replaced while the rest of the attributes remain set to their default values as specified by the factory:

    $customer = CustomerFactory::initialize()->make([
        'name' => 'Abigail Otwell',
    ]);

### Persisting Models

The `create` method instantiates model instances and persists them to the database using model's `save` method:

    use Tests\Factories\Models\CustomerFactory;

    public function test_models_can_be_persisted()
    {
        // Create a single App\Models\User instance...
        $customer = CustomerFactory::initialize()->create();

        // Create three App\Models\User instances...
        $customers = CustomerFactory::initialize()->count(3)->create();

        // Use model in tests...
    }

You may override the factory's default model attributes by passing an array of attributes to the `create` method:

    $customer = CustomerFactory::initialize()->create([
        'name' => 'Abigail',
    ]);