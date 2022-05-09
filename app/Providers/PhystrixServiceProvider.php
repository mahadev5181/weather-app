<?php

namespace App\Providers;

use App\Libraries\Common\Container;
use App\Libraries\Common\ApcuCache;
use Illuminate\Support\ServiceProvider;
use Laminas\Config\Config;
use Odesk\Phystrix\ApcuStateStorage;
use Odesk\Phystrix\CircuitBreakerFactory;
use Odesk\Phystrix\CommandMetricsFactory;
use Odesk\Phystrix\CommandFactory;
use Odesk\Phystrix\RequestLog;

class PhystrixServiceProvider extends ServiceProvider {

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register() {

		$config = new Config(config("phystrix"));

		$stateStorage = new ApcuStateStorage();
		$circuitBreakerFactory = new CircuitBreakerFactory($stateStorage);
		$commandMetricsFactory = new CommandMetricsFactory($stateStorage);

		$phystrix = new CommandFactory(
			$config,
			$circuitBreakerFactory,
			$commandMetricsFactory,
			new ApcuCache(),
			new RequestLog(),
			new Container()
		);

		$this->app->singleton('phystrix', fn($app) => $phystrix);

	}

}
