<?php

namespace Caydeesoft\CardSdk;

use Caydeesoft\CardSdk\Contracts\CardInterface;
use Illuminate\Support\ServiceProvider;

class CardServiceProvider extends ServiceProvider
    {
        public function register(): void
            {
                $this->mergeConfigFrom(__DIR__ . '/../config/card.php', 'card');

                $this->app->singleton(CardValidator::class, function () {
                    return new CardValidator();
                });

                $this->app->bind(CardInterface::class, function () {
                    $provider = config('card.payment_provider', 'visa');

                    return match ($provider) {
                        'visa' => new VisaClient(),
                        'mastercard' => new MastercardClient(),
                        'amex' => new AmexClient(),
                        'discover' => new DiscoverClient(),
                        default => throw new \InvalidArgumentException("Unsupported payment provider [{$provider}]."),
                        };
                });
            }

        public function boot(): void
            {
                if ($this->app->runningInConsole()) {
                    $this->publishes([
                        __DIR__ . '/../config/card.php' => $this->getConfigPath(),
                    ], 'card-config');

                    $this->publishes([
                        __DIR__ . '/../config/card.php' => $this->getConfigPath(),
                    ], 'config');
                }
            }

        private function getConfigPath(): string
            {
                if (function_exists('config_path')) {
                    return config_path('card.php');
                }

                return $this->app->basePath('config/card.php');
            }
    }