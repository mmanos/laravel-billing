<?php namespace Mmanos\Billing;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocalCreateCouponCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'laravel-billing:local:create-coupon';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a billing coupon in the local driver';
	
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
		
		$code = $this->ask('What is the coupon Code (eg. 20percentoff)?');
		$percent_off = $this->ask('What is the coupon Percent Off (enter for none)?');
		$amount_off = $this->ask('What is the coupon Amount Off (in cents) (enter for none)?');
		$duration_in_months = $this->ask('How many months should this coupon last (press enter for unlimited)?');
		
		$coupon = Gateways\Local\Models\Coupon::create(array(
			'code'               => $code,
			'percent_off'        => $percent_off ? $percent_off : null,
			'amount_off'         => $amount_off ? $amount_off : null,
			'duration_in_months' => $duration_in_months ? $duration_in_months : null,
		));
		
		$this->info('Coupon created successfully: ' . $coupon->id);
	}
}
