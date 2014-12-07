# Billing Package for Laravel 4

This package provides an expressive, fluent interface to a billing services gateway. It handles almost all of the boilerplate code for managing billing customers, subscriptions, and individual charges. The usage and feature set was heavily influenced by the popular [Laravel Cashier](http://laravel.com/docs/billing) package. However, it has these major differences:

* Support for more than one billing gateway (via [Facades](http://laravel.com/docs/facades))
* Support for multiple subscriptions per customer
* Support for multiple credit cards per customer
* Support for individual charges

It currently comes bundled with drivers for these billing services:

* [Stripe](https://stripe.com)
* [Braintree](https://www.braintreepayments.com)

> **Note:** Not all features are supported by every billing gateway. See the `Gateway Limitations` section below for more information.

## Installation

#### Composer

Add this to your composer.json file, in the require object:

```javascript
"mmanos/laravel-billing": "dev-master"
```

After that, run composer install to install the package.

#### Service Provider

Register the `Mmanos\Billing\BillingServiceProvider` in your `app` configuration file.

#### Dependencies

The following composer dependencies are needed for the listed billing gateways:

* Stripe: `stripe/stripe-php`
* Braintree: `braintree/braintree_php`

## Configuration

#### Config File

Publish the default config file to your application so you can make modifications.

```console
$ php artisan config:publish mmanos/laravel-billing
```

#### Customer Migration

Before using this package, we'll need to add several columns to the table that will represent your billing customer. You can use the `laravel-billing:customer-table` Artisan command to create a migration to add the necessary columns.

For example, to add the columns to the `users` table use:

```console
$ php artisan laravel-billing:customer-table users
```

#### Subscription Migrations

Next, we'll need to add several columns to the table that will represent your billing subscription plans. You can use the `laravel-billing:subscription-table` Artisan command to create a migration to add the necessary columns.

For example, if you have a hosting company and want to allow your customers add a subscription for a website, you might use:

```console
$ php artisan laravel-billing:subscription-table websites
```

> **Note:** If you only need one subscription per customer, you may use the same table for both the customer migration and the subscription migration (eg. `users` table).

> **Note:** You may also support multiple subscriptions per customer by adding these fields to more than one table.

#### Run Migrations

Once the migration has been created, simply run the `migrate` command.

#### Customer Model Setup

Next, add the CustomerBillableTrait to your customer model definition:

```php
use Mmanos\Billing\CustomerBillableTrait;

class User extends Eloquent
{
	use CustomerBillableTrait;

}
```

You should also define a `subscriptionmodels` method that returns all models that represent any billing subscriptions for a customer. This allows the customer model to propagate credit card changes and status changes (if the customer is deleted) to all of it's subscription models.

```php
public function subscriptionmodels()
{
	// Return an Eloquent relationship.
	return $this->hasMany('Website');
	
	// Or, return an array or collection of models.
	return Website::where('user_id', $this->id)->get();
	
	// Or, return an array of collections.
	return array(
		Website::where('user_id', $this->id)->get(),
		Domain::where('user_id', $this->id)->get(),
	);
}
```

#### Subscription Model Setup

Then, add the SubscriptionBillableTrait to your subscription model definition(s):

```php
use Mmanos\Billing\SubscriptionBillableTrait;

class Website extends Eloquent
{
	use SubscriptionBillableTrait;

}
```

You should also define a `customermodel` method that returns the model representing the customer who owns this subscription. This ensures a subscription has access to the necessary customer information when interacting with the different gateway APIs.

```php
public function customermodel()
{
	// Return an Eloquent relationship.
	return $this->belongsTo('User', 'user_id');
	
	// Or, Return an Eloquent model.
	return User::find($this->user_id);
}
```

#### Model Definitions

Finally, to add the ability to look up a model based on it's gateway ID (customer ID, or subscription ID), you need to define the array of Eloquent models acting as a Customer or Subscription.

Add your customer class to the `customer_models` property in this package's config file:

```php
'customer_models' => array('User')
```

And add the subscription class(es) to the `subscription_models` property in this package's config file:

```php
'subscription_models' => array('Website')
```

These values are primarily used by the WebhookControllers to help locate the corresponding Eloquent model from the gateway's ID when a new billing event is received.

## Customers

Billing customers can be created and managed separately from subscriptions or charges.

#### Creating A Customer

Once you have a customer model instance, you can create the customer in the billing gateway using a gateway-specific credit card token:

```php
$user = User::find(1);

$user->billing()->withCardToken('token')->create();
```

If you would like to apply a coupon when creating the customer, you may use the `withCoupon` method:

```php
$user->billing()->withCardToken('token')->withCoupon('code')->create();
```

The `billing` method will automatically update your database with the billing gateway's customer ID and other relevant billing information.

If you would like to specify additional customer details, you may do so by passing them in to the `create` method:

```php
$user->billing()->withCardToken('token')->create(array(
	'email' => $email,
));
```

#### Updating A Customer

To update an existing customers primary credit card information, or to add a coupon to their existing account, you may use the `update` method:

```php
$user->billing()->withCardToken('token')->withCoupon('code')->update();
```

#### Deleting A Customer

Deleting a customer will delete their account from the billing gateway and delete all existing subscriptions:

```php
$user->billing()->delete();
```

#### Multiple Credit Cards

You may add more than one credit card to a customer using the `creditcards` method:

```php
$card = $user->creditcards()->create('credit_card_token');
```

Updating an existing credit card's information (such as expiration date or billing address) is also possible:

```php
$card->update(array(
	'exp_month' => '01',
	'exp_year'  => '2017',
));
```

Or delete an existing credit card:

```php
$card->delete();
```

Retrieving and working with customer credit cards is pretty straight forward:

```php
// Get all customer credit cards.
$cards = $user->creditcards()->get();

// Get the first card for a customer.
$card = $user->creditcards()->first();

// Find a card by it's ID.
$card = $user->creditcards()->find('card_id');

echo $card->id;
echo "{$card->brand} xxxx-xxxx-xxxx-{$card->last4} Exp: {$card->exp_month}/{$card->exp_year}"
```

#### Invoices

You can easily retrieve an array of a customer's invoices using the `invoices` method:

```php
$invoices = $user->invoices()->get();
```

Or get the most recent invoice:

```php
$invoice = $user->invoices()->first();
```

Or find an invoice by it's ID:

```php
$invoice = $user->invoices()->find('invoice_id');
```

To display relevant invoice information, use the invoice properties:

```php
$invoice->id;
$invoice->date;
$invoice->amount;
$invoice->items();
//...
```

Use the `render` method to generate a pre-formatted HTML version of the invoice:

```php
$invoice->render();
```

#### Checking Customer Status

To verify that a customer has been created in the billing gateway, use the `readyForBilling` method:

```php
if ($user->readyForBilling()) {
	//
}
```

## Subscriptions

#### Subscribing To A Plan

Once you have a subscription model instance, you can subscribe a customer to a given plan:

```php
$user = User::find(1);
$website = Website::find(1);

$user->subscriptions('monthly')->create($website);
```

You may apply a coupon specifically to the subsciption using the `withCoupon` method:

```php
$user->subscriptions('monthly')->withCoupon('code')->create($website);
```

You may also specify a new credit card token to use with this subscription:

```php
$user->subscriptions('monthly')->withCardToken('token')->create($website);
```

Or specify an existing credit card ID:

```php
$user->subscriptions('monthly')->withCard('card_id')->create($website);
```

> **Note:** If no card or card_token is specified, the default (initial) card associated with the customer will be used.

There may be times when you haven't yet created the customer or you don't have the customer model available for use when you want to create a subscription. In these cases you may subscribe to a plan directly on the subscription model using the `subscription` method:

```php
$website->subscription('monthly')->create();
```

This method also supports the optional `withCoupon` and `withCardToken` methods:

```php
$website->subscription('monthly')->withCoupon('code')->withCardToken('token')->create();
```

> **Note:** The customer will automatically be retrieved using the `customermodel` method you defined in your subscription model above.

#### No Card Up Front

If your application offers a free-trial with no credit-card up front, set the `cardUpFront` property on your model to `false`:

```php
protected $cardUpFront = false;
```

On model creation, be sure to set the trial end date on the model:

```php
$website->billing_trial_ends_at = Carbon::now()->addDays(14);

$website->save();
```

#### Swapping Subscriptions

To swap a user to a new subscription, use the `swap` method:

```php
$website->subscription('premium')->swap();
```

If the user is on trial, the trial will be maintained as normal. Also, if a "quantity" exists for the subscription, that quantity will also be maintained.

#### Subscription Quantity

Sometimes subscriptions are affected by "quantity". For example, your application might charge $10 per month per user on an account. To easily increment or decrement your subscription quantity, use the `increment` and `decrement` methods:

```php
$website->subscription()->increment();

// Add five to the subscription's current quantity...
$website->subscription()->increment(5);

$website->subscription->decrement();

// Subtract five from the subscription's current quantity...
$website->subscription()->decrement(5);
```

#### Cancelling A Subscription

To cancel a subscription, use the `cancel` method:

```php
$website->subscription->cancel();
```

When a subscription is canceled, this package will automatically set the `billing_subscription_ends_at` column on your database. This column is used to know when the `subscribed` method should begin returning false. For example, if a customer cancels a subscription on March 1st, but the subscription was not scheduled to end until March 5th, the `subscribed` method will continue to return `true` until March 5th.

#### Resuming A Subscription

If a user has canceled their subscription and you wish to resume it, use the `resume` method:

```php
$website->subscription('monthly')->resume();
```

You may also specify a new credit card token:

```php
$website->subscription('monthly')->withCardToken('token')->resume();
```

If the user cancels a subscription and then resumes that subscription before the subscription has fully expired, they may not be billed immediately, depending on the billing gateway being used.

#### Working With Subscriptions

To verify that a model is subscribed to your application, use the `subscribed` command:

```php
if ($website->subscribed()) {
	//
}
```

To determine if the model has an active subscription in the billing gateway, use the `billingIsActive` method. This method would not return true if the model is currently on trial with `cardUpFront` set to `false` or if they have canceled and are on their grace period.

```php
if ($website->billingIsActive()) {
	//
}
```

You may also determine if the model is still within their trial period (if applicable) using the `onTrial` method:

```php
if ($website->onTrial()) {
	//
}
```

To determine if the model was once an active subscriber, but has canceled their subscription, you may use the `canceled` method:

```php
if ($website->canceled()) {
	//
}
```

You may also determine if a model has canceled their subscription, but are still on their "grace period" until the subscription fully expires. For example, if a model subscription is canceled on March 5th that was scheduled to end on March 10th, the model is on their "grace period" until March 10th. Note that the `subscribed` method still returns `true` during this time.

```php
if ($website->onGracePeriod()) {
	//
}
```

The `everSubscribed` method may be used to determine if the model has ever subscribed to a plan in your application:

```php
if ($website->everSubscribed()) {
	//
}
```

To retrieve the customer model associated with a subscription use the `customer` method:

```php
$customer = $website->customer();
```

You may also retrieve an array of subscriptions associated with a customer:

```php
$subscriptions = $user->subscriptions()->get();
```

## Charges

Creating individual charges on a customer, outside of subscriptions, is also possible.

#### Creating A Charge

Creating a new charge on a customer is easy:

```php
$charge = $user->charges()->create(499);
```

> **Note:** The amount of a charge is in cents.

To charge on a new credit card token, use the `withCardToken` method:

```php
$charge = $user->charges()->withCardToken('token')->create(499);
```

You may also specify an existing credit card to use for a charge:

```php
$charge = $user->charges()->withCard('card_id')->create(499);
```

> **Note:** If no card or card_token is specified, the default (initial) card associated with the customer will be used.

#### Capturing A Charge

Sometimes you may want to preauthorize a charge before you capture it:

```php
$charge = $user->charges()->create(499, array('capture' => false));

$charge->capture();
```

You may optionally specify the amount to capture as long as it is less than or equal to the amount preauthorized:

```php
$charge = $user->charges()->create(499, array('capture' => false));

$charge->capture(array('amount' => 399));
```

#### Refunding A Charge

Refunding a charge is also possible:

```php
$charge->refund();
```

Or optionally specify an amount to refund:

```php
$charge->refund(array('amount' => 399, 'reason' => '...'));
```

#### Working With Charges

You may retrieve an array of all charges for a customer:

```php
$charges = $user->charges()->get();
```

Or find the most recent charge for a customer:

```php
$charge = $user->charges()->first();
```

Finding a charge from it's ID is also easy:

```php
$charge = $user->charges()->find('charge_id');
```

Charge objects has several properties you might find useful, including: `id`, `created_at`, `amount`, `paid`, `refunded`, `card`, `invoice_id`, and `description`.

You may also access the associated invoice object (if available) from a charge:

```php
$invoice = $charge->invoice();
```

## Webhooks

This package comes bundled with a Webhook controller for each supported gateway, which can handle things such as failed or successful invoice payments, deleted subscriptions, and trial-will-end events.

#### Handling Webhook Events

To enable these events, just point a route to the appropriate gateway controller:

```php
// Stripe.
Route::post('stripe/webhook', 'Mmanos\Billing\Gateways\Stripe\WebhookController@handleWebhook');

// Braintree.
Route::post('braintree/webhook', 'Mmanos\Billing\Gateways\Braintree\WebhookController@handleWebhook');
```

By default, this package does not try to delete a subscription after a certain number of failed payment attempts. Most billing gateways can do this automatically which would trigger a deleted subscription webhook event. When that happens, we will update our local model to record that change in status.

#### Handling Other Events

If you have additional webhook events you would like to handle, simply extend the Webhook controller and point the route to your controller.

```php
class WebhookController extends Mmanos\Billing\Gateways\Stripe\WebhookController
{
	public function handleChargeDisputeCreated($payload)
	{
		// Handle The Event
	}
}
```

## Model Events

To make it even easier to work with billing-related events, this package will trigger several convenient events on Eloquent models that you can hook into, so you don't have to do everything in the Webhook controller.

For example, to be notified when a model's trail will end, subscribe to the `trialWillEnd` model method:

```php
Website::trialWillEnd(function ($website, $args = array()) {
	Log::info('Trial will end in ' . array_get($args, 'days') . ' day(s).');
});
```

#### Customer Events

There are several customer-related billing events you may subscribe to: `customerCreated`, `customerDeleted`, `creditcardAdded`, `creditcardRemoved`, `creditcardUpdated`, `creditcardChanged`, `discountAdded`, `discountRemoved`, `discountUpdated`, `discountChanged`, `invoiceCreated`, `invoicePaymentSucceeded`, and `invoicePaymentFailed`.

#### Subscription Events

There are several subscription-related billing events you may subscribe to: `billingActivated`, `billingCanceled`, `planSwapped`, `planChanged`, `subscriptionIncremented`, `subscriptionDecremented`, `billingResumed`, `trialExtended`, `trialWillEnd`, `subscriptionDiscountAdded`, `subscriptionDiscountRemoved`, `subscriptionDiscountUpdated`, and `subscriptionDiscountChanged`.

## Gateway Limitations

Each billing gateway provides a different API and set of functionality. That being said, not all features are supported by every billing gateway. Here is a high level breakdown of the features **NOT** supported by each gateway:

#### Stripe

* Does not support multiple subscriptions on separate credit cards (though this feature is coming). The `withCard` method will be ignored when creating a new subscription and the primary customer card will be used. However, multiple credit cards are supported on charges.

#### Braintree

* Does not support any action regarding a credit card token. So creating/updating a user with a card token is not supported. Also, adding new credit cards is not supported. You must use their transparent redirect flows to accomplish this. You would also need to manually update the model fields when finished.
* Does not support subscription quantities.
* Does not support customer-specific discounts.
* Does not support charge descriptions.
* Does not return starting/ending customer balance on invoices.
* Does not support resuming a canceled subscription. However, a new subscription will be created instead.
* Does not support modifying the trial end date for an existing subscription. However, the existing subscription will be canceled and a new one created.
