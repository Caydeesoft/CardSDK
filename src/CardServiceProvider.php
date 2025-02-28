<?php
	
	namespace Caydeesoft\CardSdk;
	
	use Illuminate\Support\ServiceProvider;
	
	class CardServiceProvider extends ServiceProvider
		{
			public function register()
				{
					$this->mergeConfigFrom(__DIR__ . '/../config/card.php', 'card');
					
					$this->app->singleton(CardValidator::class, function () {
						return new CardValidator();
					});
					$this->app->bind(CardInterface::class, function ($app)
						{
							$provider = config('card.payment_provider', 'visa');
							return match ($provider)
								{
								'visa'       => new VisaClient(),
								'mastercard' => new MastercardClient(),
								'amex'       => new AmexClient(),
								'discover'   => new DiscoverClient(),
								default      => throw new \Exception("Unsupported payment provider"),
								};
						});
				}
			
			public function boot()
				{
					// Publish configuration file to Laravel's config folder
					$this->publishes([
						                 __DIR__ . '/../config/card.php' => config_path('card.php'),
					                 ], 'config');
				}
		}
