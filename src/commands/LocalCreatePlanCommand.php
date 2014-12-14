<?php namespace Mmanos\Billing;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocalCreatePlanCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'laravel-billing:local:create-plan';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a billing plan in the local driver';
	
	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if ('local' != Config::get('laravel-billing::default')) {
			return $this->error('Not configured to use the "local" driver.');
		}
		
		// Init gateway.
		Facades\Billing::customer();
		
		$key = $this->ask('What is the plan ID (eg. basic or pro)?');
		$amount = $this->ask('What is the plan Amount (in cents)?');
		$interval = $this->ask('What is the plan Interval (eg. monthly or yearly)?');
		$trial_period_days = $this->ask('How many days of trial do you want to give (press enter for none)?');
		
		$plan = Gateways\Local\Models\Plan::create(array(
			'key'               => $key,
			'name'              => ucwords($key),
			'amount'            => $amount,
			'interval'          => $interval,
			'trial_period_days' => $trial_period_days,
		));
		
		$this->info('Plan created successfully: ' . $plan->id);
	}
}
